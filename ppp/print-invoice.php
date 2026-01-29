<?php
/*
 * Print Invoice Page
 */
session_start();
error_reporting(0);

if (!isset($_SESSION["mikpay"])) {
    die('Session expired');
}

include('billing-data.php');
include('../include/business_config.php');

$invoiceId = $_GET['id'] ?? '';
$session = $_GET['session'] ?? '';
$invoice = getInvoiceById($invoiceId);
$businessSettings = getBusinessSettings();
$businessName = $businessSettings['business_name'] ?? 'MIKPAY';
$businessAddress = $businessSettings['business_address'] ?? '';
$businessPhone = $businessSettings['business_phone'] ?? '';

// Get logo using centralized function
$logoInfo = getLogoUrl($session);
$hasLogo = $logoInfo['exists'];
$logoUrl = $logoInfo['url'] . '?v=' . time(); // Add cache buster

if (!$invoice) {
    die('Invoice tidak ditemukan');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?= $invoice['id'] ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            padding: 20px;
        }
        .invoice-container {
            max-width: 400px;
            margin: 0 auto;
            background: #FFF;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        .invoice-header {
            background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%);
            color: #FFF;
            padding: 30px;
            text-align: center;
        }
        .invoice-logo {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.2);
            border-radius: 15px;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
        }
        .invoice-logo-img {
            width: 80px;
            height: 80px;
            object-fit: contain;
            margin: 0 auto 15px;
            border-radius: 12px;
            background: rgba(255,255,255,0.9);
            padding: 8px;
        }
        .invoice-header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        .invoice-header p {
            opacity: 0.9;
            font-size: 14px;
        }
        .invoice-id {
            background: rgba(255,255,255,0.2);
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        .invoice-body {
            padding: 30px;
        }
        .invoice-status {
            text-align: center;
            margin-bottom: 25px;
        }
        .status-badge {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: #FFF;
            padding: 10px 30px;
            border-radius: 25px;
            font-weight: 600;
            display: inline-block;
        }
        .invoice-details {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px dashed #e2e8f0;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: #64748b;
            font-size: 14px;
        }
        .detail-value {
            color: #1e293b;
            font-weight: 600;
            font-size: 14px;
        }
        .invoice-total {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: #FFF;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
        }
        .invoice-total h3 {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        .invoice-total .amount {
            font-size: 36px;
            font-weight: 700;
        }
        .invoice-footer {
            text-align: center;
            padding: 25px;
            color: #94a3b8;
            font-size: 12px;
            border-top: 1px solid #f1f5f9;
        }
        .btn-print {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%);
            color: #FFF;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
        }
        @media print {
            body {
                padding: 0;
                background: #FFF;
            }
            .invoice-container {
                box-shadow: none;
            }
            .btn-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <?php if ($hasLogo): ?>
            <img src="<?= $logoUrl ?>" alt="Logo" class="invoice-logo-img">
            <?php else: ?>
            <div class="invoice-logo"><?= strtoupper(substr($businessName, 0, 1)) ?></div>
            <?php endif; ?>
            <h1><?= htmlspecialchars($businessName) ?></h1>
            <p>WiFi Billing Invoice</p>
            <?php if ($businessAddress): ?>
            <p style="font-size:12px; opacity:0.8;"><?= htmlspecialchars($businessAddress) ?></p>
            <?php endif; ?>
            <?php if ($businessPhone): ?>
            <p style="font-size:12px; opacity:0.8;">Tel: <?= htmlspecialchars($businessPhone) ?></p>
            <?php endif; ?>
            <div class="invoice-id"><?= $invoice['id'] ?></div>
        </div>
        
        <div class="invoice-body">
            <div class="invoice-status">
                <span class="status-badge">✓ LUNAS</span>
            </div>
            
            <div class="invoice-details">
                <div class="detail-row">
                    <span class="detail-label">Pelanggan</span>
                    <span class="detail-value"><?= htmlspecialchars($invoice['display_name'] ?? $invoice['customer_name']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">ID Pelanggan</span>
                    <span class="detail-value" style="font-size:12px; color:#64748b;"><?= htmlspecialchars($invoice['customer_name']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">No. HP</span>
                    <span class="detail-value"><?= htmlspecialchars($invoice['phone'] ?: '-') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Periode</span>
                    <span class="detail-value"><?= date('F Y', strtotime($invoice['period'] . '-01')) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Tanggal Bayar</span>
                    <span class="detail-value"><?= date('d M Y, H:i', strtotime($invoice['payment_date'])) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Metode</span>
                    <span class="detail-value"><?= htmlspecialchars($invoice['payment_method']) ?></span>
                </div>
                <?php if (!empty($invoice['notes'])): ?>
                <div class="detail-row">
                    <span class="detail-label">Catatan</span>
                    <span class="detail-value"><?= htmlspecialchars($invoice['notes']) ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="invoice-total">
                <h3>Total Pembayaran</h3>
                <div class="amount">Rp <?= number_format($invoice['amount'], 0, ',', '.') ?></div>
            </div>
            
            <button class="btn-print" onclick="window.print()">
                <i class="fa fa-print"></i> Cetak Invoice
            </button>
        </div>
        
        <div class="invoice-footer">
            <p>Terima kasih atas pembayaran Anda</p>
            <p><?= htmlspecialchars($businessName) ?> - WiFi Management System</p>
        </div>
    </div>
</body>
</html>
