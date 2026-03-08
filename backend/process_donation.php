<?php
/**
 * Process Donation - Handle donation form submission
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Start session
session_start();

// Include configuration
require_once('../config/config.php');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Get POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Validate CSRF token
if (!isset($data['csrf_token']) || !validateCSRFToken($data['csrf_token'])) {
    http_response_code(403);
    logSecurityEvent('csrf_validation_failed', $_SERVER['REMOTE_ADDR']);
    echo json_encode(['success' => false, 'message' => 'Token de sécurité invalide']);
    exit;
}

// Validate and sanitize input
$errors = [];

$firstName = sanitizeInput($data['first_name'] ?? '');
if (empty($firstName) || strlen($firstName) < 2) {
    $errors[] = 'Prénom invalide';
}

$lastName = sanitizeInput($data['last_name'] ?? '');
if (empty($lastName) || strlen($lastName) < 2) {
    $errors[] = 'Nom invalide';
}

$email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email invalide';
}

$phone = sanitizeInput($data['phone'] ?? '');
if (empty($phone) || strlen($phone) < 8) {
    $errors[] = 'Numéro de téléphone invalide';
}

$amount = floatval($data['amount'] ?? 0);
if ($amount < MIN_DONATION_AMOUNT || $amount > MAX_DONATION_AMOUNT) {
    $errors[] = 'Montant invalide';
}

$paymentMethod = $data['payment_method'] ?? '';
if (!in_array($paymentMethod, ['fedapay', 'kkiapay'])) {
    $errors[] = 'Mode de paiement invalide';
}

$message = sanitizeInput($data['message'] ?? '');

// Return errors if any
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Rate limiting - check if IP has made too many requests
if (!checkRateLimit($_SERVER['REMOTE_ADDR'])) {
    http_response_code(429);
    logSecurityEvent('rate_limit_exceeded', $_SERVER['REMOTE_ADDR']);
    echo json_encode(['success' => false, 'message' => 'Trop de tentatives. Veuillez réessayer dans quelques minutes.']);
    exit;
}

try {
    // Connect to database
    $pdo = getDBConnection();
    
    // Generate unique transaction ID
    $transactionId = generateTransactionId();
    
    // Insert donation record
    $stmt = $pdo->prepare("
        INSERT INTO donations (
            transaction_id, first_name, last_name, email, phone, 
            amount, payment_method, message, status, ip_address, user_agent
        ) VALUES (
            :transaction_id, :first_name, :last_name, :email, :phone,
            :amount, :payment_method, :message, 'pending', :ip_address, :user_agent
        )
    ");
    
    $stmt->execute([
        ':transaction_id' => $transactionId,
        ':first_name' => $firstName,
        ':last_name' => $lastName,
        ':email' => $email,
        ':phone' => $phone,
        ':amount' => $amount,
        ':payment_method' => $paymentMethod,
        ':message' => $message,
        ':ip_address' => $_SERVER['REMOTE_ADDR'],
        ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    // Mark CSRF token as used
    markCSRFTokenUsed($data['csrf_token']);
    
    // Log successful submission
    logSecurityEvent('donation_initiated', $_SERVER['REMOTE_ADDR'], [
        'transaction_id' => $transactionId,
        'amount' => $amount,
        'payment_method' => $paymentMethod
    ]);
    
    // Return success with transaction ID
    echo json_encode([
        'success' => true,
        'transaction_id' => $transactionId,
        'message' => 'Redirection vers le paiement...'
    ]);
    
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur. Veuillez réessayer.']);
}

// Helper Functions

function sanitizeInput($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

function validateCSRFToken($token) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT id FROM csrf_tokens 
            WHERE token = :token 
            AND expires_at > NOW() 
            AND used = FALSE
            AND ip_address = :ip_address
        ");
        $stmt->execute([
            ':token' => $token,
            ':ip_address' => $_SERVER['REMOTE_ADDR']
        ]);
        
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("CSRF Validation Error: " . $e->getMessage());
        return false;
    }
}

function markCSRFTokenUsed($token) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE csrf_tokens SET used = TRUE WHERE token = :token");
        $stmt->execute([':token' => $token]);
    } catch (PDOException $e) {
        error_log("CSRF Token Update Error: " . $e->getMessage());
    }
}

function generateTransactionId() {
    return 'DON-' . strtoupper(uniqid()) . '-' . time();
}

function checkRateLimit($ipAddress, $maxAttempts = 5, $timeWindow = 300) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as attempts 
            FROM donations 
            WHERE ip_address = :ip_address 
            AND created_at > DATE_SUB(NOW(), INTERVAL :time_window SECOND)
        ");
        $stmt->execute([
            ':ip_address' => $ipAddress,
            ':time_window' => $timeWindow
        ]);
        
        $result = $stmt->fetch();
        return $result['attempts'] < $maxAttempts;
    } catch (PDOException $e) {
        error_log("Rate Limit Check Error: " . $e->getMessage());
        return true; // Allow on error
    }
}

function logSecurityEvent($eventType, $ipAddress, $details = null) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            INSERT INTO security_logs (event_type, ip_address, user_agent, details)
            VALUES (:event_type, :ip_address, :user_agent, :details)
        ");
        $stmt->execute([
            ':event_type' => $eventType,
            ':ip_address' => $ipAddress,
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            ':details' => $details ? json_encode($details) : null
        ]);
    } catch (PDOException $e) {
        error_log("Security Log Error: " . $e->getMessage());
    }
}

?>
