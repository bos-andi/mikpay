<?php
/**
 * Blocked User Page
 * Shown when user/session is deactivated by Super Admin
 */

// Get user info if available
$blockedUser = null;
$blockedReason = '';
if (function_exists('getUser') && isset($session)) {
    $blockedUser = getUser($session);
    if ($blockedUser && isset($blockedUser['deactivated_reason'])) {
        $blockedReason = $blockedUser['deactivated_reason'];
    }
}

// Get business settings for contact info
$businessSettings = array();
if (file_exists(__DIR__ . '/include/business_settings.json')) {
    $data = file_get_contents(__DIR__ . '/include/business_settings.json');
    $businessSettings = json_decode($data, true);
}

$contactPhone = isset($businessSettings['phone']) ? $businessSettings['phone'] : '';
$contactEmail = isset($businessSettings['email']) ? $businessSettings['email'] : '';
$businessName = isset($businessSettings['business_name']) ? $businessSettings['business_name'] : 'MIKPAY';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Diblokir - MIKPAY</title>
    <link rel="stylesheet" href="./css/font-awesome.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .blocked-container {
            background: #FFF;
            border-radius: 24px;
            padding: 50px 40px;
            width: 100%;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 25px 80px rgba(0,0,0,0.3);
        }
        .blocked-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
        }
        .blocked-icon i {
            font-size: 50px;
            color: #FFF;
        }
        .blocked-title {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 15px;
        }
        .blocked-message {
            font-size: 16px;
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        .blocked-session {
            background: #fef2f2;
            border: 1px solid #fecaca;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
        }
        .blocked-session label {
            font-size: 12px;
            color: #dc2626;
            font-weight: 600;
            display: block;
            margin-bottom: 5px;
        }
        .blocked-session span {
            font-size: 18px;
            font-weight: 700;
            color: #dc2626;
        }
        .blocked-reason {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
        }
        .blocked-reason label {
            font-size: 12px;
            color: #ea580c;
            font-weight: 600;
            display: block;
            margin-bottom: 5px;
        }
        .blocked-reason span {
            font-size: 14px;
            color: #c2410c;
        }
        .contact-info {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
        }
        .contact-info h4 {
            font-size: 14px;
            color: #1e293b;
            margin-bottom: 12px;
        }
        .contact-info a {
            display: inline-block;
            margin: 5px 10px;
            color: #4D44B5;
            text-decoration: none;
            font-size: 14px;
        }
        .contact-info a:hover {
            text-decoration: underline;
        }
        .contact-info i {
            margin-right: 5px;
        }
        .btn-back {
            display: inline-block;
            padding: 14px 30px;
            background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%);
            color: #FFF;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(77, 68, 181, 0.4);
        }
        .footer-note {
            margin-top: 30px;
            font-size: 12px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="blocked-container">
        <div class="blocked-icon">
            <i class="fa fa-ban"></i>
        </div>
        
        <h1 class="blocked-title">Akses Diblokir</h1>
        
        <p class="blocked-message">
            Maaf, akses Anda ke aplikasi ini telah dinonaktifkan oleh administrator. 
            Silakan hubungi admin untuk informasi lebih lanjut.
        </p>
        
        <div class="blocked-session">
            <label>Session ID</label>
            <span><?= htmlspecialchars(isset($session) ? $session : 'N/A') ?></span>
        </div>
        
        <?php if ($blockedReason): ?>
        <div class="blocked-reason">
            <label>Alasan Pemblokiran</label>
            <span><?= htmlspecialchars($blockedReason) ?></span>
        </div>
        <?php endif; ?>
        
        <div class="contact-info">
            <h4>Hubungi Administrator</h4>
            <?php if ($contactPhone): ?>
            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $contactPhone) ?>">
                <i class="fa fa-whatsapp"></i> <?= htmlspecialchars($contactPhone) ?>
            </a>
            <?php endif; ?>
            <?php if ($contactEmail): ?>
            <a href="mailto:<?= htmlspecialchars($contactEmail) ?>">
                <i class="fa fa-envelope"></i> <?= htmlspecialchars($contactEmail) ?>
            </a>
            <?php endif; ?>
            <?php if (!$contactPhone && !$contactEmail): ?>
            <span style="color:#64748b; font-size:13px;">Silakan hubungi administrator sistem</span>
            <?php endif; ?>
        </div>
        
        <a href="./admin.php?id=sessions" class="btn-back">
            <i class="fa fa-arrow-left"></i> Kembali
        </a>
        
        <p class="footer-note">
            &copy; <?= date('Y') ?> <?= htmlspecialchars($businessName) ?>
        </p>
    </div>
</body>
</html>
