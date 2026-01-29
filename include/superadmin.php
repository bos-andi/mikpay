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
    file_put_contents(PENDING_PAYMENTS_FILE, json_encode($payments, JSON_PRETTY_PRINT));
}

/**
 * Add pending payment request
 */
function addPendingPayment($userId, $package, $amount, $proofFile = '') {
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
    savePendingPayments($payments);
    
    return $payment['id'];
}

/**
 * Approve payment
 */
function approvePayment($paymentId) {
    include_once(__DIR__ . '/subscription.php');
    
    $payments = getPendingPayments();
    
    foreach ($payments as &$payment) {
        if ($payment['id'] === $paymentId && $payment['status'] === 'pending') {
            $payment['status'] = 'approved';
            $payment['approved_at'] = date('Y-m-d H:i:s');
            $payment['approved_by'] = SUPERADMIN_EMAIL;
            
            // Activate subscription for user
            activateSubscription($payment['package'], $paymentId);
            
            savePendingPayments($payments);
            return true;
        }
    }
    
    return false;
}

/**
 * Reject payment
 */
function rejectPayment($paymentId, $reason = '') {
    $payments = getPendingPayments();
    
    foreach ($payments as &$payment) {
        if ($payment['id'] === $paymentId && $payment['status'] === 'pending') {
            $payment['status'] = 'rejected';
            $payment['rejected_at'] = date('Y-m-d H:i:s');
            $payment['rejected_by'] = SUPERADMIN_EMAIL;
            $payment['reject_reason'] = $reason;
            
            savePendingPayments($payments);
            return true;
        }
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
