<?php
/*
 * MIKPAY Super Admin System
 * Mengelola akun super admin dan approval pembayaran
 */

// Super Admin Credentials (encrypted)
define('SUPERADMIN_EMAIL', 'ndiandie@gmail.com');
define('SUPERADMIN_PASSWORD_HASH', password_hash('MikPayandidev.id', PASSWORD_DEFAULT));

// File untuk menyimpan pending payments
define('PENDING_PAYMENTS_FILE', __DIR__ . '/pending_payments.json');

/**
 * Verify super admin login
 */
function verifySuperAdmin($email, $password) {
    if ($email === SUPERADMIN_EMAIL && $password === 'MikPayandidev.id') {
        return true;
    }
    return false;
}

/**
 * Check if current session is super admin
 */
function isSuperAdmin() {
    return isset($_SESSION['superadmin']) && $_SESSION['superadmin'] === true;
}

/**
 * Get all pending payments
 */
function getPendingPayments() {
    if (!file_exists(PENDING_PAYMENTS_FILE)) {
        return array();
    }
    $data = file_get_contents(PENDING_PAYMENTS_FILE);
    return json_decode($data, true) ?: array();
}

/**
 * Save pending payments
 */
function savePendingPayments($payments) {
    // Ensure directory exists before writing
    $fileDir = dirname(PENDING_PAYMENTS_FILE);
    if (!is_dir($fileDir)) {
        if (!@mkdir($fileDir, 0755, true)) {
            error_log("savePendingPayments: Failed to create directory: " . $fileDir);
            return false;
        }
    }
    
    $result = file_put_contents(PENDING_PAYMENTS_FILE, json_encode($payments, JSON_PRETTY_PRINT));
    
    if ($result === false) {
        error_log("savePendingPayments: Failed to write to file: " . PENDING_PAYMENTS_FILE);
        return false;
    }
    
    return true;
}

/**
 * Add pending payment request
 */
function addPendingPayment($userId, $package, $amount, $proofFile = '') {
    if (empty($userId) || empty($package) || empty($amount)) {
        error_log("addPendingPayment: Missing required parameters");
        return false;
    }
    
    $payments = getPendingPayments();
    
    $payment = array(
        'id' => 'PAY' . date('YmdHis') . rand(100, 999),
        'user_id' => $userId,
        'package' => $package,
        'amount' => $amount,
        'proof_file' => $proofFile,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
        'approved_at' => null,
        'approved_by' => null
    );
    
    $payments[] = $payment;
    $result = savePendingPayments($payments);
    
    if ($result === false) {
        error_log("addPendingPayment: Failed to save payment data");
        return false;
    }
    
    return $payment['id'];
}

/**
 * Approve payment
 */
function approvePayment($paymentId) {
    include_once(__DIR__ . '/subscription.php');
    
    $payments = getPendingPayments();
    $found = false;
    
    foreach ($payments as &$payment) {
        if ($payment['id'] === $paymentId && $payment['status'] === 'pending') {
            $found = true;
            
            // Validate package exists
            if (!isset($payment['package']) || empty($payment['package'])) {
                error_log("Superadmin approvePayment: Package not found in payment data");
                return false;
            }
            
            // Normalize package name to lowercase for consistency
            $packageKey = strtolower(trim($payment['package']));
            
            // Activate subscription for user
            $activationResult = activateSubscription($packageKey, $paymentId);
            
            if ($activationResult === false) {
                error_log("Superadmin approvePayment: Failed to activate subscription for package: " . $packageKey . " (original: " . $payment['package'] . ")");
                return false;
            }
            
            // Update payment status only if activation successful
            $payment['status'] = 'approved';
            $payment['approved_at'] = date('Y-m-d H:i:s');
            $payment['approved_by'] = SUPERADMIN_EMAIL;
            
            $saveResult = savePendingPayments($payments);
            
            if ($saveResult === false) {
                error_log("Superadmin approvePayment: Failed to save payment data");
                return false;
            }
            
            error_log("Superadmin approvePayment: Successfully approved payment " . $paymentId . " for package " . $packageKey);
            return true;
        }
    }
    
    if (!$found) {
        error_log("Superadmin approvePayment: Payment not found or already processed: " . $paymentId);
    }
    
    return false;
}

/**
 * Reject payment
 */
function rejectPayment($paymentId, $reason = '') {
    $payments = getPendingPayments();
    $found = false;
    
    foreach ($payments as &$payment) {
        if ($payment['id'] === $paymentId && $payment['status'] === 'pending') {
            $found = true;
            
            $payment['status'] = 'rejected';
            $payment['rejected_at'] = date('Y-m-d H:i:s');
            $payment['rejected_by'] = SUPERADMIN_EMAIL;
            $payment['reject_reason'] = $reason;
            
            $saveResult = savePendingPayments($payments);
            
            if ($saveResult === false) {
                error_log("Superadmin rejectPayment: Failed to save payment data");
                return false;
            }
            
            error_log("Superadmin rejectPayment: Successfully rejected payment " . $paymentId);
            return true;
        }
    }
    
    if (!$found) {
        error_log("Superadmin rejectPayment: Payment not found or already processed: " . $paymentId);
    }
    
    return false;
}

/**
 * Get payment statistics
 */
function getPaymentStats() {
    $payments = getPendingPayments();
    
    $stats = array(
        'total' => count($payments),
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0,
        'total_revenue' => 0
    );
    
    foreach ($payments as $payment) {
        if ($payment['status'] === 'pending') $stats['pending']++;
        if ($payment['status'] === 'approved') {
            $stats['approved']++;
            $stats['total_revenue'] += $payment['amount'];
        }
        if ($payment['status'] === 'rejected') $stats['rejected']++;
    }
    
    return $stats;
}
