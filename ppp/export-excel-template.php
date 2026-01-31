<?php
/*
 * Export Template CSV untuk Tagihan WiFi (PPP Billing)
 * Format RESMI: CSV saja (dibuka & diedit pakai Excel)
 */

session_start();
error_reporting(0);
ini_set('display_errors', 0);

if (!isset($_SESSION["mikpay"])) {
    header('HTTP/1.1 401 Unauthorized');
    die('Session expired');
}

$session = $_GET['session'] ?? '';
if (empty($session)) {
    header('HTTP/1.1 400 Bad Request');
    die('Session tidak ditemukan');
}

// Include koneksi ke router
include(dirname(__FILE__) . '/../include/config.php');
include(dirname(__FILE__) . '/../include/readcfg.php');
include_once(dirname(__FILE__) . '/../lib/routeros_api.class.php');

$API = new RouterosAPI();
$API->debug = false;

// Try to connect to router
try {
    if (!$API->connect($iphost, $userhost, decrypt($passwdhost))) {
        header('HTTP/1.1 500 Internal Server Error');
        die('Tidak bisa terhubung ke router. Pastikan router online dan API enabled.');
    }
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    die('Error koneksi router: ' . $e->getMessage());
}

// Ambil semua PPP Secret (username PPPoE)
try {
    $getpppsecrets = $API->comm("/ppp/secret/print");
    if (!is_array($getpppsecrets)) {
        $getpppsecrets = array();
    }
} catch (Exception $e) {
    $getpppsecrets = array();
}

// Ambil data billing yang sudah tersimpan (billing_customers.json)
include_once(dirname(__FILE__) . '/billing-data.php');
$billingSettings = function_exists('getCustomerBillingSettings') ? getCustomerBillingSettings() : array();

// Siapkan data baris
$rows = [];
foreach ($getpppsecrets as $secret) {
    if (!isset($secret['name'])) continue;
    $username = $secret['name'];

    $custBilling  = isset($billingSettings[$username]) ? $billingSettings[$username] : array();
    $displayName  = isset($custBilling['display_name']) ? $custBilling['display_name'] : '';
    $phone        = isset($custBilling['phone']) ? $custBilling['phone'] : '';
    $dueDay       = isset($custBilling['due_day']) ? intval($custBilling['due_day']) : 0;
    $monthlyFee   = isset($custBilling['monthly_fee']) ? floatval($custBilling['monthly_fee']) : 0;
    $notes        = isset($custBilling['notes']) ? $custBilling['notes'] : '';

    $rows[] = [
        $username,
        $displayName,
        $phone,
        $dueDay > 0 ? $dueDay : '',
        $monthlyFee > 0 ? intval($monthlyFee) : '',
        $notes,
    ];
}

// Disconnect API
try {
    $API->disconnect();
} catch (Exception $e) {
    // Ignore disconnect errors
}

// === OUTPUT CSV (format resmi) ===
$filename = 'Template_Pelanggan_WiFi_' . date('Y-m-d_His') . '.csv';

// Clear any previous output
if (ob_get_level()) {
    ob_clean();
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

// BOM UTF‑8 supaya Excel Windows baca karakter dengan benar
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');
if ($output === false) {
    die('Tidak bisa membuat output stream');
}

// Header kolom (JANGAN diubah urutannya)
fputcsv($output, [
    'Username PPPoE',
    'Nama Pelanggan',
    'No HP / WhatsApp',
    'Tanggal Jatuh Tempo',
    'Tarif Bulanan (Rp)',
    'Catatan'
], ',');

// Data baris
foreach ($rows as $row) {
    fputcsv($output, $row, ',');
}

fclose($output);
exit;

