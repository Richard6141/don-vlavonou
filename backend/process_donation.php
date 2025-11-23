<?php
/**
 * Process Donation - Handle donation form submission
 */

header('Content-Type: application/json');

// Restrict CORS to allowed origins
$allowedOrigins = [
    'http://localhost',
    'https://localhost',
    'http://127.0.0.1',
    'https://127.0.0.1',
    'http://vlavonou.salanon.info',
    'https://vlavonou.salanon.info'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: ' . $allowedOrigins[0]);
}

header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Secure session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);

session_start();

// Regenerate session ID periodically to prevent fixation
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// Include configuration
require_once('../config/config.php');

// Include Composer autoloader for FedaPay (only if needed)
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once($composerAutoload);
}

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

// Validate CSRF token (temporarily disabled for debugging)
// if (!isset($data['csrf_token']) || !validateCSRFToken($data['csrf_token'])) {
//     http_response_code(403);
//     logSecurityEvent('csrf_validation_failed', $_SERVER['REMOTE_ADDR']);
//     echo json_encode(['success' => false, 'message' => 'Token de sécurité invalide']);
//     exit;
// }

// Validate and sanitize input
$errors = [];

// Validate first name - letters, spaces, hyphens, apostrophes only
$firstName = sanitizeInput($data['first_name'] ?? '');
if (empty($firstName) || strlen($firstName) < 2 || strlen($firstName) > 50) {
    $errors[] = 'Prénom invalide (2-50 caractères)';
} elseif (!preg_match("/^[a-zA-ZÀ-ÿ\s\-']+$/u", $firstName)) {
    $errors[] = 'Prénom contient des caractères non autorisés';
}

// Validate last name - letters, spaces, hyphens, apostrophes only
$lastName = sanitizeInput($data['last_name'] ?? '');
if (empty($lastName) || strlen($lastName) < 2 || strlen($lastName) > 50) {
    $errors[] = 'Nom invalide (2-50 caractères)';
} elseif (!preg_match("/^[a-zA-ZÀ-ÿ\s\-']+$/u", $lastName)) {
    $errors[] = 'Nom contient des caractères non autorisés';
}

// Validate email with stricter pattern
$email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email invalide';
} elseif (strlen($email) > 254) {
    $errors[] = 'Email trop long';
} elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
    $errors[] = 'Format email invalide';
}

// Validate phone - must be digits with optional + and spaces
$phone = $data['phone'] ?? '';
$phoneClean = preg_replace('/[\s\-\.]/', '', $phone);
if (empty($phoneClean) || strlen($phoneClean) < 8 || strlen($phoneClean) > 15) {
    $errors[] = 'Numéro de téléphone invalide (8-15 chiffres)';
} elseif (!preg_match('/^\+?[0-9]{8,15}$/', $phoneClean)) {
    $errors[] = 'Format téléphone invalide';
}
$phone = sanitizeInput($phone);

// Validate amount - must be positive integer within bounds
$amount = filter_var($data['amount'] ?? 0, FILTER_VALIDATE_INT);
if ($amount === false || $amount < MIN_DONATION_AMOUNT || $amount > MAX_DONATION_AMOUNT) {
    $errors[] = 'Montant invalide (' . MIN_DONATION_AMOUNT . ' - ' . number_format(MAX_DONATION_AMOUNT) . ' FCFA)';
}

// Validate payment method - whitelist only
$paymentMethod = $data['payment_method'] ?? '';
if (!in_array($paymentMethod, ['fedapay', 'kkiapay'], true)) {
    $errors[] = 'Mode de paiement invalide';
}

// Validate message - optional but limit length and sanitize
$message = sanitizeInput($data['message'] ?? '');
if (strlen($message) > 500) {
    $errors[] = 'Message trop long (max 500 caractères)';
}

// Return errors if any
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Rate limiting - check if IP has made too many requests (disabled for testing)
// if (!checkRateLimit($_SERVER['REMOTE_ADDR'])) {
//     http_response_code(429);
//     logSecurityEvent('rate_limit_exceeded', $_SERVER['REMOTE_ADDR']);
//     echo json_encode(['success' => false, 'message' => 'Trop de tentatives. Veuillez réessayer dans quelques minutes.']);
//     exit;
// }

try {
    // Generate unique transaction ID
    $transactionId = generateTransactionId();
    $paymentUrl = null;

    // Create payment transaction FIRST (before saving to database)
    if ($paymentMethod === 'fedapay') {
        // Initialize FedaPay
        \FedaPay\FedaPay::setApiKey(FEDAPAY_SECRET_KEY);
        \FedaPay\FedaPay::setEnvironment(FEDAPAY_MODE);

        // Create FedaPay transaction
        $fedapayTransaction = \FedaPay\Transaction::create([
            "description" => "Don pour Vlavonou - {$firstName} {$lastName}",
            "amount" => (int)$amount,
            "currency" => ["iso" => "XOF"],
            "callback_url" => SITE_URL . "/?payment=complete&ref=" . $transactionId,
            "customer" => [
                "firstname" => $firstName,
                "lastname" => $lastName,
                "email" => $email,
                "phone_number" => [
                    "number" => preg_replace('/[^0-9]/', '', $phone),
                    "country" => "bj"
                ]
            ]
        ]);

        // Generate payment token and get URL
        $token = $fedapayTransaction->generateToken();
        $paymentUrl = $token->url;
    }

    // For FedaPay: save to database immediately (will be updated on callback)
    // For Kkiapay: store in session, save only after successful payment
    if ($paymentUrl) {
        // FedaPay - save to database
        $pdo = getDBConnection();

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
    } elseif ($paymentMethod === 'kkiapay') {
        // Kkiapay - store in session for later
        $_SESSION['pending_donation'] = [
            'transaction_id' => $transactionId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'message' => $message,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
    }

    // Return success with payment URL
    echo json_encode([
        'success' => true,
        'transaction_id' => $transactionId,
        'payment_url' => $paymentUrl,
        'payment_method' => $paymentMethod,
        'message' => 'Redirection vers le paiement...'
    ]);
    
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur base de données. Veuillez réessayer.']);
} catch (\FedaPay\Error\Base $e) {
    error_log("FedaPay Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur FedaPay: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
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
        // Simplified validation for local development (remove IP check)
        $stmt = $pdo->prepare("
            SELECT id FROM csrf_tokens
            WHERE token = :token
            AND expires_at > NOW()
            AND used = 0
        ");
        $stmt->execute([
            ':token' => $token
        ]);

        $result = $stmt->fetch();
        return $result !== false;
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
