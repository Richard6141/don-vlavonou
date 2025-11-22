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
        // Get our local transaction ID from ref parameter
        $localTransactionId = $_GET['ref'] ?? '';
        // Get FedaPay transaction ID
        $fedapayId = $_GET['id'] ?? $_POST['id'] ?? '';
        $status = $_GET['status'] ?? $_POST['status'] ?? '';

        error_log("FedaPay callback - ref: $localTransactionId, id: $fedapayId, status: $status");

        if (empty($localTransactionId)) {
            throw new Exception("Transaction ID manquant");
        }

        // Check if payment was approved
        if ($status === 'approved') {
            // Verify payment with FedaPay API if we have the FedaPay ID
            $verified = true;
            if (!empty($fedapayId)) {
                $verified = verifyFedaPayPayment($fedapayId);
            }

            if ($verified) {
                updateDonationStatus($localTransactionId, 'completed', 'FEDAPAY-' . $fedapayId);

                // Redirect to success page
                header('Location: ../public/index.php?status=success&tx=' . urlencode($localTransactionId));
                exit;
            }
        }

        // Payment failed or not approved
        updateDonationStatus($localTransactionId, 'failed');

        // Redirect to error page
        header('Location: ../public/index.php?status=error&tx=' . urlencode($localTransactionId));
        exit;

    } catch (Exception $e) {
        error_log("FedaPay Callback Error: " . $e->getMessage());
        header('Location: ../public/index.php?status=error');
        exit;
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
        // Determine API URL based on mode
        $apiUrl = FEDAPAY_MODE === 'sandbox'
            ? 'https://sandbox-api.fedapay.com/v1/transactions/'
            : 'https://api.fedapay.com/v1/transactions/';

        // Call FedaPay API to verify payment
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $apiUrl . $transactionId,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . FEDAPAY_SECRET_KEY,
                'Content-Type: application/json'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        error_log("FedaPay verification response: " . $response);

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            $txData = $data['v1/transaction'] ?? $data;
            return isset($txData['status']) && $txData['status'] === 'approved';
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
