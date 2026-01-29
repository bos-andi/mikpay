<?php
/*
 * AJAX Handler for PPP Billing
 */
session_start();
error_reporting(0);
header('Content-Type: application/json');

if (!isset($_SESSION["mikpay"])) {
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit;
}

include('billing-data.php');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add_payment':
        $customerName = trim($_POST['customer_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        $period = trim($_POST['period'] ?? '');
        $paymentMethod = trim($_POST['payment_method'] ?? 'Transfer');
        $notes = trim($_POST['notes'] ?? '');
        
        if (empty($customerName) || empty($period) || $amount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
            exit;
        }
        
        $payment = addBillingPayment($customerName, $phone, $amount, $period, $paymentMethod, $notes);
        echo json_encode(['success' => true, 'message' => 'Pembayaran berhasil dicatat', 'payment' => $payment]);
        break;
        
    case 'get_monthly_report':
        $year = intval($_GET['year'] ?? date('Y'));
        $month = intval($_GET['month'] ?? date('m'));
        
        $report = getMonthlyReport($year, $month);
        echo json_encode(['success' => true, 'data' => $report]);
        break;
        
    case 'get_yearly_summary':
        $year = intval($_GET['year'] ?? date('Y'));
        
        $summary = getYearlySummary($year);
        echo json_encode(['success' => true, 'data' => $summary]);
        break;
        
    case 'get_invoice':
        $invoiceId = $_GET['id'] ?? '';
        
        $invoice = getInvoiceById($invoiceId);
        if ($invoice) {
            echo json_encode(['success' => true, 'data' => $invoice]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invoice tidak ditemukan']);
        }
        break;
        
    case 'delete_payment':
        $invoiceId = $_POST['id'] ?? '';
        
        if (deletePayment($invoiceId)) {
            echo json_encode(['success' => true, 'message' => 'Pembayaran dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus']);
        }
        break;
        
    case 'get_customer_payments':
        $customerName = $_GET['customer'] ?? '';
        
        $payments = getPaymentsByCustomer($customerName);
        echo json_encode(['success' => true, 'data' => array_values($payments)]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
