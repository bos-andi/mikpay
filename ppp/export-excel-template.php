<?php
/*
 * Export Template CSV untuk Tagihan WiFi (PPP Billing)
 * Format RESMI: CSV saja (dibuka & diedit pakai Excel)
 */

session_start();
error_reporting(0);

if (!isset($_SESSION["mikpay"])) {
    die('Session expired');
}

$session = $_GET['session'] ?? '';
if (empty($session)) {
    die('Session tidak ditemukan');
}

// Include koneksi ke router
include(dirname(__FILE__) . '/../include/config.php');
include(dirname(__FILE__) . '/../include/readcfg.php');
include_once(dirname(__FILE__) . '/../lib/routeros_api.class.php');

$API = new RouterosAPI();
$API->debug = false;
$API->connect($iphost, $userhost, decrypt($passwdhost));

// Ambil semua PPP Secret (username PPPoE)
$getpppsecrets = $API->comm("/ppp/secret/print");
if (!is_array($getpppsecrets)) {
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

$API->disconnect();

// === OUTPUT CSV (format resmi) ===
$filename = 'Template_Pelanggan_WiFi_' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// BOM UTF‑8 supaya Excel Windows baca karakter dengan benar
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

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

