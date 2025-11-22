<?php
/**
 * Get CSRF Token - Generate and return a CSRF token
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

header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Credentials: true');

// Secure session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);

session_start();

// Include configuration
require_once('../config/config.php');

// Generate CSRF token
$token = bin2hex(random_bytes(32));
$ipAddress = $_SERVER['REMOTE_ADDR'];
$expiresAt = date('Y-m-d H:i:s', time() + CSRF_TOKEN_EXPIRY);

try {
    // Connect to database
    $pdo = getDBConnection();
    
    // Insert token
    $stmt = $pdo->prepare("
        INSERT INTO csrf_tokens (token, ip_address, expires_at)
        VALUES (:token, :ip_address, :expires_at)
    ");
    
    $stmt->execute([
        ':token' => $token,
        ':ip_address' => $ipAddress,
        ':expires_at' => $expiresAt
    ]);
    
    // Return token
    echo json_encode([
        'success' => true,
        'token' => $token,
        'expires_in' => CSRF_TOKEN_EXPIRY
    ]);
    
} catch (PDOException $e) {
    error_log("CSRF Token Generation Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la génération du token'
    ]);
}

?>
