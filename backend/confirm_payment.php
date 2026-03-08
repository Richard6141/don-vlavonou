<?php
/**
 * Confirm Payment - Update donation status after successful payment
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include configuration
require_once('../config/config.php');

// Get POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Validate transaction ID
$transactionId = $data['transaction_id'] ?? '';
if (empty($transactionId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de transaction manquant']);
    exit;
}

try {
    // Connect to database
    $pdo = getDBConnection();
    
    // Get donation details
    $stmt = $pdo->prepare("SELECT * FROM donations WHERE transaction_id = :transaction_id");
    $stmt->execute([':transaction_id' => $transactionId]);
    $donation = $stmt->fetch();
    
    if (!$donation) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Donation non trouvée']);
        exit;
    }
    
    // Update donation status to completed
    $stmt = $pdo->prepare("
        UPDATE donations 
        SET status = 'completed', 
            payment_reference = :payment_reference,
            updated_at = NOW()
        WHERE transaction_id = :transaction_id
    ");
    
    $paymentReference = $data['payment_reference'] ?? 'PAY-' . time();
    $stmt->execute([
        ':payment_reference' => $paymentReference,
        ':transaction_id' => $transactionId
    ]);
    
    // Send receipt email
    $emailSent = sendReceiptEmail($donation);
    
    // Log receipt sending
    $stmt = $pdo->prepare("
        INSERT INTO receipts (donation_id, email_sent, sent_at, email_status)
        VALUES (:donation_id, :email_sent, NOW(), :email_status)
    ");
    $stmt->execute([
        ':donation_id' => $donation['id'],
        ':email_sent' => $emailSent ? 1 : 0,
        ':email_status' => $emailSent ? 'sent' : 'failed'
    ]);
    
    // Return success
    echo json_encode([
        'success' => true,
        'message' => 'Paiement confirmé avec succès',
        'receipt_sent' => $emailSent
    ]);
    
} catch (PDOException $e) {
    error_log("Payment Confirmation Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}

/**
 * Send receipt email to donor
 */
function sendReceiptEmail($donation) {
    try {
        $to = $donation['email'];
        $subject = 'Reçu de don - Merci pour votre soutien à Vlavonou';
        
        // Email body
        $message = generateReceiptHTML($donation);
        
        // Email headers
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">" . "\r\n";
        
        // Send email
        $sent = mail($to, $subject, $message, $headers);
        
        if (!$sent) {
            error_log("Email sending failed for: " . $to);
        }
        
        return $sent;
        
    } catch (Exception $e) {
        error_log("Email Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate receipt HTML
 */
function generateReceiptHTML($donation) {
    $html = '
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #16a34a 0%, #15803d 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9fafb; padding: 30px; border-radius: 0 0 10px 10px; }
            .receipt-box { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #16a34a; }
            .amount { font-size: 32px; font-weight: bold; color: #16a34a; text-align: center; margin: 20px 0; }
            .details { margin: 15px 0; }
            .details strong { color: #16a34a; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🌳 Union Progressiste le Renouveau</h1>
                <p>Reçu de Don</p>
            </div>
            <div class="content">
                <h2>Merci ' . htmlspecialchars($donation['first_name']) . ' !</h2>
                <p>Nous avons bien reçu votre don et vous en remercions chaleureusement. Votre soutien est précieux pour notre mission.</p>
                
                <div class="receipt-box">
                    <div class="amount">' . number_format($donation['amount'], 0, ',', ' ') . ' FCFA</div>
                    
                    <div class="details">
                        <p><strong>N° de transaction :</strong> ' . htmlspecialchars($donation['transaction_id']) . '</p>
                        <p><strong>Donateur :</strong> ' . htmlspecialchars($donation['first_name'] . ' ' . $donation['last_name']) . '</p>
                        <p><strong>Email :</strong> ' . htmlspecialchars($donation['email']) . '</p>
                        <p><strong>Téléphone :</strong> ' . htmlspecialchars($donation['phone']) . '</p>
                        <p><strong>Date :</strong> ' . date('d/m/Y à H:i', strtotime($donation['created_at'])) . '</p>
                        <p><strong>Mode de paiement :</strong> ' . ucfirst($donation['payment_method']) . '</p>
                    </div>
                    
                    ' . (!empty($donation['message']) ? '<p><strong>Votre message :</strong><br><em>"' . htmlspecialchars($donation['message']) . '"</em></p>' : '') . '
                </div>
                
                <p>Ce reçu confirme votre don et peut être utilisé à des fins comptables.</p>
                
                <p style="margin-top: 30px;">
                    <strong>Ensemble, construisons un avenir meilleur !</strong><br>
                    L\'équipe de Vlavonou
                </p>
            </div>
            <div class="footer">
                <p>Union Progressiste le Renouveau<br>
                Email: contact@unionprogressiste.org<br>
                © 2024 Tous droits réservés</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    return $html;
}

?>
