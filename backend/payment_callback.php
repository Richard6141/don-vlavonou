<?php
/**
 * Payment Callback - Handle callbacks from payment gateways
 */

// Include configuration
require_once('../config/config.php');

// Log callback
error_log("Payment callback received: " . json_encode($_REQUEST));

// Get payment method
$paymentMethod = $_GET['method'] ?? $_POST['method'] ?? '';

if ($paymentMethod === 'fedapay') {
    handleFedaPayCallback();
} elseif ($paymentMethod === 'kkiapay') {
    handleKkiaPayCallback();
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Mode de paiement non spécifié']);
    exit;
}

/**
 * Handle FedaPay callback
 */
function handleFedaPayCallback() {
    try {
        // Get transaction ID from callback
        $transactionId = $_POST['transaction_id'] ?? $_GET['transaction_id'] ?? '';
        $status = $_POST['status'] ?? $_GET['status'] ?? '';
        
        if (empty($transactionId)) {
            throw new Exception("Transaction ID manquant");
        }
        
        // Verify payment with FedaPay API
        $verified = verifyFedaPayPayment($transactionId);
        
        if ($verified && $status === 'approved') {
            updateDonationStatus($transactionId, 'completed', 'FEDAPAY-' . $transactionId);
            
            // Redirect to success page
            header('Location: ../public/index.html?status=success&tx=' . urlencode($transactionId));
            exit;
        } else {
            updateDonationStatus($transactionId, 'failed');
            
            // Redirect to error page
            header('Location: ../public/index.html?status=error&tx=' . urlencode($transactionId));
            exit;
        }
        
    } catch (Exception $e) {
        error_log("FedaPay Callback Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Handle KkiaPay callback
 */
function handleKkiaPayCallback() {
    try {
        // Get transaction ID from callback
        $transactionId = $_POST['transaction_id'] ?? $_GET['transaction_id'] ?? '';
        $status = $_POST['status'] ?? $_GET['status'] ?? '';
        
        if (empty($transactionId)) {
            throw new Exception("Transaction ID manquant");
        }
        
        // Verify payment with KkiaPay API
        $verified = verifyKkiaPayPayment($transactionId);
        
        if ($verified && $status === 'SUCCESS') {
            updateDonationStatus($transactionId, 'completed', 'KKIAPAY-' . $transactionId);
            
            // Redirect to success page
            header('Location: ../public/index.html?status=success&tx=' . urlencode($transactionId));
            exit;
        } else {
            updateDonationStatus($transactionId, 'failed');
            
            // Redirect to error page
            header('Location: ../public/index.html?status=error&tx=' . urlencode($transactionId));
            exit;
        }
        
    } catch (Exception $e) {
        error_log("KkiaPay Callback Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Verify FedaPay payment
 */
function verifyFedaPayPayment($transactionId) {
    try {
        // Call FedaPay API to verify payment
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.fedapay.com/v1/transactions/' . $transactionId,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . FEDAPAY_SECRET_KEY,
                'Content-Type: application/json'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return isset($data['status']) && $data['status'] === 'approved';
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("FedaPay Verification Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Verify KkiaPay payment
 */
function verifyKkiaPayPayment($transactionId) {
    try {
        // Call KkiaPay API to verify payment
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.kkiapay.me/api/v1/transactions/' . $transactionId,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'X-API-KEY: ' . KKIAPAY_PRIVATE_KEY,
                'Content-Type: application/json'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return isset($data['status']) && $data['status'] === 'SUCCESS';
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("KkiaPay Verification Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update donation status
 */
function updateDonationStatus($transactionId, $status, $paymentReference = null) {
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            UPDATE donations 
            SET status = :status, 
                payment_reference = :payment_reference,
                updated_at = NOW()
            WHERE transaction_id = :transaction_id
        ");
        
        $stmt->execute([
            ':status' => $status,
            ':payment_reference' => $paymentReference,
            ':transaction_id' => $transactionId
        ]);
        
        // If completed, send receipt
        if ($status === 'completed') {
            $stmt = $pdo->prepare("SELECT * FROM donations WHERE transaction_id = :transaction_id");
            $stmt->execute([':transaction_id' => $transactionId]);
            $donation = $stmt->fetch();
            
            if ($donation) {
                sendReceiptEmail($donation);
            }
        }
        
    } catch (PDOException $e) {
        error_log("Update Status Error: " . $e->getMessage());
    }
}

/**
 * Send receipt email (same as in confirm_payment.php)
 */
function sendReceiptEmail($donation) {
    // Implementation same as in confirm_payment.php
    // ... (code omitted for brevity)
}

?>
