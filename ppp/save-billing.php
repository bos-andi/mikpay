<?php
/*
 *  AJAX Handler for saving PPP Billing data
 *  Now saves to billing_customers.json instead of Mikrotik comment
 */
session_start();
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

// Include billing data functions
include_once('billing-data.php');

$action = $_POST['action'] ?? '';

// Handle save customer billing settings
if ($action === 'save_customer_billing') {
    $username = trim($_POST['username'] ?? '');
    $dueDay = intval($_POST['due_day'] ?? 0);
    $displayName = trim($_POST['display_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $monthlyFee = floatval($_POST['monthly_fee'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    
    if (empty($username)) {
        echo json_encode(['success' => false, 'message' => 'Username tidak valid']);
        exit;
    }
    
    if ($dueDay < 1 || $dueDay > 31) {
        echo json_encode(['success' => false, 'message' => 'Tanggal jatuh tempo harus antara 1-31']);
        exit;
    }
    
    if (empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'No. HP wajib diisi']);
        exit;
    }
    
    if ($monthlyFee <= 0) {
        echo json_encode(['success' => false, 'message' => 'Tarif bulanan wajib diisi']);
        exit;
    }
    
    try {
        $result = saveCustomerBilling($username, $dueDay, $displayName, $phone, $monthlyFee, $notes);
        echo json_encode([
            'success' => true, 
            'message' => 'Data berhasil disimpan',
            'data' => $result
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// Legacy: Handle old save_billing action (save to Mikrotik comment)
$session = $_POST['session'] ?? $_GET['session'] ?? '';

if (empty($session)) {
    echo json_encode(['success' => false, 'message' => 'Session tidak ditemukan']);
    exit;
}

include('../include/config.php');
include('../include/readcfg.php');
include_once('../lib/routeros_api.class.php');

$API = new RouterosAPI();
$API->debug = false;

if (!$API->connect($iphost, $userhost, decrypt($passwdhost))) {
    echo json_encode(['success' => false, 'message' => 'Gagal koneksi ke Mikrotik']);
    exit;
}

$secretId = trim($_POST['secret_id'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$dueDate = trim($_POST['due_date'] ?? '');
$notes = trim($_POST['notes'] ?? '');

if (empty($secretId)) {
    echo json_encode(['success' => false, 'message' => 'ID pelanggan tidak valid']);
    exit;
}

// Format comment: phone|due_date|notes
$comment = $phone . '|' . $dueDate;
if (!empty($notes)) {
    $comment .= '|' . $notes;
}

try {
    $result = $API->comm("/ppp/secret/set", array(
        ".id" => $secretId,
        "comment" => $comment
    ));
    
    if (isset($result['!trap'])) {
        echo json_encode(['success' => false, 'message' => $result['!trap'][0]['message'] ?? 'Error tidak diketahui']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Data berhasil disimpan']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$API->disconnect();
