<?php
/**
 * Delete Invoice Handler
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(0);
header('Content-Type: application/json');

if (!isset($_SESSION["mikpay"])) {
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$invoiceId = isset($_POST['invoice_id']) ? trim($_POST['invoice_id']) : '';

if (empty($invoiceId)) {
    echo json_encode(['success' => false, 'message' => 'Invoice ID tidak boleh kosong']);
    exit;
}

// Include billing data functions
include_once(dirname(__FILE__) . '/billing-data.php');

// Get all payments
$payments = getBillingPayments();

// Find and remove the invoice
$found = false;
$newPayments = array();
foreach ($payments as $payment) {
    if ($payment['id'] === $invoiceId) {
        $found = true;
        continue; // Skip this payment (delete it)
    }
    $newPayments[] = $payment;
}

if (!$found) {
    echo json_encode(['success' => false, 'message' => 'Invoice tidak ditemukan']);
    exit;
}

// Save updated payments
saveBillingPayments($newPayments);

echo json_encode(['success' => true, 'message' => 'Invoice berhasil dihapus']);
