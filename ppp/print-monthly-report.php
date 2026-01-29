<?php
/*
 * Print Monthly Report Page
 * Generates a printable monthly report with F4 paper size
 */
session_start();
error_reporting(0);

if (!isset($_SESSION["mikpay"])) {
    die('Session expired');
}

include('billing-data.php');
include('../include/business_config.php');

$year = intval(isset($_GET['year']) ? $_GET['year'] : date('Y'));
$month = intval(isset($_GET['month']) ? $_GET['month'] : date('m'));
$session = isset($_GET['session']) ? $_GET['session'] : '';
$businessSettings = getBusinessSettings();
$businessName = isset($businessSettings['business_name']) ? $businessSettings['business_name'] : 'MIKPAY';
$businessAddress = isset($businessSettings['business_address']) ? $businessSettings['business_address'] : '';
$businessPhone = isset($businessSettings['business_phone']) ? $businessSettings['business_phone'] : '';
$businessEmail = isset($businessSettings['email']) ? $businessSettings['email'] : '';

// Get logo using centralized function
$logoInfo = getLogoUrl($session);
$hasLogo = $logoInfo['exists'];
$logoUrl = $logoInfo['url'] . '?v=' . time();

// Get monthly data
$monthlyData = getMonthlyReport($year, $month);
$payments = $monthlyData['payments'];
$totalAmount = $monthlyData['total_amount'];
$totalTransactions = $monthlyData['total_transactions'];

$monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$monthName = $monthNames[$month];

// Current date for report
$reportDate = date('d F Y');
$reportCity = 'Surabaya'; // Default city, can be extracted from address
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Bulanan <?= $monthName ?> <?= $year ?> - <?= htmlspecialchars($businessName) ?></title>
    <style>
        /* F4 Paper Size: 215.9mm x 330.2mm */
        @page {
            size: 215.9mm 330.2mm;
            margin: 15mm 20mm 20mm 20mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #000;
            background: #f5f5f5;
        }
        
        .page-container {
            width: 215.9mm;
            min-height: 330.2mm;
            margin: 0 auto;
            background: #FFF;
            padding: 15mm 20mm 20mm 20mm;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        /* ======= LETTERHEAD / KOP SURAT ======= */
        .letterhead {
            display: flex;
            align-items: center;
            padding-bottom: 15px;
            border-bottom: 3px double #000;
            margin-bottom: 20px;
        }
        
        .letterhead-logo {
            width: 80px;
            height: 80px;
            margin-right: 20px;
            flex-shrink: 0;
        }
        
        .letterhead-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .letterhead-logo-placeholder {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FFF;
            font-size: 36px;
            font-weight: bold;
            font-family: Arial, sans-serif;
        }
        
        .letterhead-info {
            flex: 1;
            text-align: center;
        }
        
        .letterhead-name {
            font-size: 22pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 5px;
        }
        
        .letterhead-tagline {
            font-size: 11pt;
            font-style: italic;
            margin-bottom: 8px;
            color: #333;
        }
        
        .letterhead-contact {
            font-size: 10pt;
            color: #444;
        }
        
        .letterhead-contact span {
            margin: 0 10px;
        }
        
        /* ======= REPORT TITLE ======= */
        .report-title {
            text-align: center;
            margin: 30px 0 25px;
        }
        
        .report-title h1 {
            font-size: 16pt;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        
        .report-title p {
            font-size: 12pt;
            color: #333;
        }
        
        /* ======= SUMMARY SECTION ======= */
        .summary-section {
            margin-bottom: 25px;
        }
        
        .summary-section p {
            text-indent: 40px;
            text-align: justify;
            margin-bottom: 10px;
        }
        
        .summary-box {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }
        
        .summary-item {
            text-align: center;
        }
        
        .summary-item .label {
            font-size: 10pt;
            color: #666;
            margin-bottom: 5px;
        }
        
        .summary-item .value {
            font-size: 18pt;
            font-weight: bold;
            color: #4D44B5;
        }
        
        .summary-item .value.green {
            color: #16a34a;
        }
        
        /* ======= TABLE ======= */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 11pt;
        }
        
        .data-table th {
            background: #4D44B5;
            color: #FFF;
            padding: 12px 10px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #4D44B5;
        }
        
        .data-table th:nth-child(1) { width: 8%; text-align: center; }
        .data-table th:nth-child(2) { width: 25%; }
        .data-table th:nth-child(3) { width: 20%; }
        .data-table th:nth-child(4) { width: 15%; text-align: center; }
        .data-table th:nth-child(5) { width: 17%; text-align: right; }
        .data-table th:nth-child(6) { width: 15%; text-align: right; }
        
        .data-table td {
            padding: 10px;
            border: 1px solid #dee2e6;
        }
        
        .data-table td:nth-child(1) { text-align: center; }
        .data-table td:nth-child(4) { text-align: center; }
        .data-table td:nth-child(5) { text-align: right; font-weight: 600; }
        .data-table td:nth-child(6) { text-align: right; font-weight: 600; }
        
        .data-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .data-table tbody tr.no-data td {
            color: #999;
            font-style: italic;
        }
        
        .data-table tfoot td {
            background: #e9ecef;
            font-weight: bold;
            font-size: 12pt;
            border: 2px solid #4D44B5;
        }
        
        /* ======= SIGNATURE SECTION ======= */
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-box {
            width: 45%;
            text-align: center;
        }
        
        .signature-box.right {
            text-align: center;
        }
        
        .signature-date {
            margin-bottom: 60px;
        }
        
        .signature-name {
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 5px;
            display: inline-block;
            min-width: 150px;
        }
        
        .signature-title {
            font-size: 10pt;
            color: #666;
        }
        
        /* ======= FOOTER ======= */
        .report-footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            font-size: 9pt;
            color: #666;
        }
        
        /* ======= PRINT BUTTON ======= */
        .btn-print-container {
            text-align: center;
            margin: 20px 0;
        }
        
        .btn-print {
            background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%);
            color: #FFF;
            border: none;
            padding: 15px 40px;
            font-size: 14pt;
            font-weight: bold;
            cursor: pointer;
            border-radius: 8px;
            font-family: Arial, sans-serif;
        }
        
        .btn-print:hover {
            opacity: 0.9;
        }
        
        /* ======= PRINT STYLES ======= */
        @media print {
            body {
                background: #FFF;
            }
            
            .page-container {
                width: 100%;
                min-height: auto;
                padding: 0;
                box-shadow: none;
                margin: 0;
            }
            
            .btn-print-container {
                display: none;
            }
            
            .summary-box {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .data-table th {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .letterhead-logo-placeholder {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        
        /* Screen preview adjustments */
        @media screen {
            body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <!-- KOP SURAT / LETTERHEAD -->
        <div class="letterhead">
            <div class="letterhead-logo">
                <?php if ($hasLogo): ?>
                <img src="<?= $logoUrl ?>" alt="Logo">
                <?php else: ?>
                <div class="letterhead-logo-placeholder"><?= strtoupper(substr($businessName, 0, 1)) ?></div>
                <?php endif; ?>
            </div>
            <div class="letterhead-info">
                <div class="letterhead-name"><?= htmlspecialchars($businessName) ?></div>
                <div class="letterhead-tagline">Layanan Internet WiFi Terpercaya</div>
                <div class="letterhead-contact">
                    <?php if ($businessAddress): ?>
                    <span><?= htmlspecialchars($businessAddress) ?></span>
                    <?php endif; ?>
                    <?php if ($businessPhone): ?>
                    <br><span>Telp: <?= htmlspecialchars($businessPhone) ?></span>
                    <?php endif; ?>
                    <?php if ($businessEmail): ?>
                    <span>Email: <?= htmlspecialchars($businessEmail) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- REPORT TITLE -->
        <div class="report-title">
            <h1>Laporan Pendapatan Bulanan</h1>
            <p>Periode: <?= $monthName ?> <?= $year ?></p>
        </div>
        
        <!-- SUMMARY SECTION -->
        <div class="summary-section">
            <p>
                Berikut ini adalah laporan rekapitulasi pendapatan dari layanan internet WiFi 
                <?= htmlspecialchars($businessName) ?> selama periode bulan <?= $monthName ?> tahun <?= $year ?>.
            </p>
            
            <div class="summary-box">
                <div class="summary-item">
                    <div class="label">Total Pendapatan</div>
                    <div class="value">Rp <?= number_format($totalAmount, 0, ',', '.') ?></div>
                </div>
                <div class="summary-item">
                    <div class="label">Total Transaksi</div>
                    <div class="value green"><?= $totalTransactions ?></div>
                </div>
            </div>
        </div>
        
        <!-- DATA TABLE -->
        <?php if ($totalTransactions > 0): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Pelanggan</th>
                    <th>Periode</th>
                    <th>Tanggal</th>
                    <th>Tagihan (Rp)</th>
                    <th>Dibayar (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                foreach ($payments as $payment): 
                    $customerName = isset($payment['customer_name']) ? $payment['customer_name'] : '-';
                    $period = isset($payment['period']) ? $payment['period'] : '-';
                    $date = isset($payment['date']) ? date('d/m/Y', strtotime($payment['date'])) : '-';
                    $totalBill = isset($payment['total_bill']) ? $payment['total_bill'] : $payment['amount'];
                    $amount = isset($payment['amount']) ? $payment['amount'] : 0;
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($customerName) ?></td>
                    <td><?= date('F Y', strtotime($period . '-01')) ?></td>
                    <td><?= $date ?></td>
                    <td>Rp <?= number_format($totalBill, 0, ',', '.') ?></td>
                    <td>Rp <?= number_format($amount, 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align: center;"><strong>TOTAL <?= strtoupper($monthName) ?> <?= $year ?></strong></td>
                    <td style="text-align: right;"><strong>Rp <?= number_format(array_reduce($payments, function($sum, $p) { return $sum + (isset($p['total_bill']) ? $p['total_bill'] : $p['amount']); }, 0), 0, ',', '.') ?></strong></td>
                    <td style="text-align: right;"><strong>Rp <?= number_format($totalAmount, 0, ',', '.') ?></strong></td>
                </tr>
            </tfoot>
        </table>
        <?php else: ?>
        <div style="text-align: center; padding: 40px; color: #999; font-style: italic;">
            <p>Tidak ada transaksi pada periode <?= $monthName ?> <?= $year ?></p>
        </div>
        <?php endif; ?>
        
        <!-- SIGNATURE SECTION -->
        <div class="signature-section">
            <div class="signature-box">
                <p>Mengetahui,</p>
                <div class="signature-date"></div>
                <div class="signature-name">____________________</div>
                <div class="signature-title">Pemilik Usaha</div>
            </div>
            <div class="signature-box right">
                <p><?= $reportCity ?>, <?= $reportDate ?></p>
                <div class="signature-date"></div>
                <div class="signature-name">____________________</div>
                <div class="signature-title">Penanggung Jawab</div>
            </div>
        </div>
        
        <!-- FOOTER -->
        <div class="report-footer">
            <p>Dokumen ini dibuat secara otomatis oleh sistem <?= htmlspecialchars($businessName) ?> pada <?= date('d F Y, H:i') ?> WIB</p>
            <p>Laporan ini sah tanpa tanda tangan basah</p>
        </div>
        
        <!-- PRINT BUTTON -->
        <div class="btn-print-container">
            <button class="btn-print" onclick="window.print()">
                🖨️ Cetak Laporan (F4)
            </button>
        </div>
    </div>
</body>
</html>
