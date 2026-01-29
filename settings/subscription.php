<?php
/*
 * MIKPAY Subscription Page
 * Halaman untuk mengelola langganan
 */
// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(0);

if (!isset($_SESSION["mikpay"])) {
    header("Location:../admin.php?id=login");
    exit;
}

// Include files with correct path handling
$basePath = dirname(__DIR__) . '/';
if (!function_exists('getSubscription')) {
    include($basePath . 'include/subscription.php');
}
if (!function_exists('getPendingPayments')) {
    include($basePath . 'include/superadmin.php');
}

$session = $_GET['session'] ?? '';
$subscription = getSubscription();
$isActive = isSubscriptionActive();
$remainingDays = getRemainingDays();

// Get pending payments for this user
$allPayments = getPendingPayments();
$userPendingPayments = array_filter($allPayments, function($p) {
    return $p['status'] === 'pending';
});
$hasPending = count($userPendingPayments) > 0;

// Handle payment request submission
$successMsg = '';
$errorMsg = '';
if (isset($_POST['request_payment'])) {
    $package = $_POST['package'];
    
    if ($hasPending) {
        $errorMsg = "Anda masih memiliki pembayaran yang menunggu konfirmasi.";
    } else {
        global $subscriptionPackages;
        if (isset($subscriptionPackages[$package])) {
            $amount = $subscriptionPackages[$package]['price'];
            $paymentId = addPendingPayment('user', $package, $amount);
            $successMsg = "Permintaan pembayaran berhasil dikirim! ID: " . $paymentId . ". Silakan transfer dan tunggu konfirmasi admin.";
            $hasPending = true;
        }
    }
}
?>

<style>
.subscription-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.sub-header {
    text-align: center;
    margin-bottom: 40px;
}
.sub-header h2 {
    font-size: 32px;
    color: #1e293b;
    margin: 0 0 10px;
}
.sub-header p {
    color: #64748b;
    font-size: 16px;
}

.sub-status-card {
    background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%);
    border-radius: 20px;
    padding: 30px;
    color: #FFF;
    margin-bottom: 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}
.sub-status-info h3 {
    margin: 0 0 10px;
    font-size: 24px;
}
.sub-status-info p {
    margin: 0;
    opacity: 0.9;
}
.sub-status-badge {
    padding: 12px 24px;
    border-radius: 30px;
    font-weight: 600;
    font-size: 16px;
}
.sub-status-badge.active {
    background: rgba(34, 197, 94, 0.2);
    color: #4ade80;
    border: 2px solid #4ade80;
}
.sub-status-badge.expired {
    background: rgba(239, 68, 68, 0.2);
    color: #f87171;
    border: 2px solid #f87171;
}
.sub-status-badge.warning {
    background: rgba(251, 191, 36, 0.2);
    color: #fbbf24;
    border: 2px solid #fbbf24;
}

.packages-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 25px;
    margin-bottom: 40px;
}

.package-card {
    background: #FFF;
    border-radius: 20px;
    padding: 30px;
    text-align: center;
    box-shadow: 0 0 40px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}
.package-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 50px rgba(77, 68, 181, 0.15);
}
.package-card.popular {
    border: 3px solid #4D44B5;
}
.package-card.popular::before {
    content: 'POPULER';
    position: absolute;
    top: 15px;
    right: -30px;
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    color: #FFF;
    padding: 5px 40px;
    font-size: 11px;
    font-weight: 700;
    transform: rotate(45deg);
}

.package-name {
    font-size: 22px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 15px;
}
.package-price {
    font-size: 42px;
    font-weight: 800;
    color: #4D44B5;
    margin: 0 0 5px;
}
.package-price span {
    font-size: 16px;
    font-weight: 400;
    color: #94a3b8;
}
.package-duration {
    color: #64748b;
    font-size: 14px;
    margin-bottom: 25px;
}

.package-features {
    list-style: none;
    padding: 0;
    margin: 0 0 25px;
    text-align: left;
}
.package-features li {
    padding: 10px 0;
    border-bottom: 1px solid #f1f5f9;
    color: #475569;
    font-size: 14px;
}
.package-features li:last-child {
    border-bottom: none;
}
.package-features li::before {
    content: '✓';
    color: #22c55e;
    font-weight: bold;
    margin-right: 10px;
}

.btn-subscribe {
    width: 100%;
    padding: 15px 30px;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}
.btn-subscribe.primary {
    background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%);
    color: #FFF;
}
.btn-subscribe.primary:hover {
    transform: scale(1.02);
    box-shadow: 0 10px 30px rgba(77, 68, 181, 0.3);
}
.btn-subscribe.outline {
    background: transparent;
    color: #4D44B5;
    border: 2px solid #4D44B5;
}
.btn-subscribe.outline:hover {
    background: #4D44B5;
    color: #FFF;
}

.payment-info {
    background: #FFF;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 0 40px rgba(0,0,0,0.05);
}
.payment-info h3 {
    margin: 0 0 20px;
    color: #1e293b;
    font-size: 20px;
}
.payment-methods {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}
.payment-method {
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s ease;
}
.payment-method:hover {
    border-color: #4D44B5;
    background: #faf5ff;
}
.payment-method img {
    height: 40px;
    margin-bottom: 10px;
}
.payment-method p {
    margin: 0;
    color: #64748b;
    font-size: 13px;
}
.payment-method strong {
    display: block;
    color: #1e293b;
    margin-bottom: 5px;
}

.btn-copy {
    background: #e2e8f0;
    border: none;
    border-radius: 6px;
    padding: 6px 10px;
    cursor: pointer;
    color: #64748b;
    transition: all 0.2s ease;
    font-size: 14px;
}
.btn-copy:hover {
    background: #4D44B5;
    color: #FFF;
}
.btn-copy.copied {
    background: #22c55e;
    color: #FFF;
}

.contact-info {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}
.contact-info i {
    font-size: 40px;
    color: #22c55e;
}
.contact-info div h4 {
    margin: 0 0 5px;
    color: #166534;
}
.contact-info div p {
    margin: 0;
    color: #15803d;
}
.contact-info a {
    color: #15803d;
    font-weight: 600;
}

.success-alert {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border: 2px solid #22c55e;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    gap: 15px;
}
.success-alert i {
    font-size: 30px;
    color: #22c55e;
}
.success-alert p {
    margin: 0;
    color: #166534;
    font-weight: 500;
}

@media (max-width: 768px) {
    .sub-status-card {
        flex-direction: column;
        text-align: center;
    }
    .package-price {
        font-size: 32px;
    }
}
</style>

<div class="subscription-page">
    <div class="sub-header">
        <h2><i class="fa fa-crown" style="color: #f97316;"></i> Langganan MIKPAY</h2>
        <p>Pilih paket yang sesuai dengan kebutuhan Anda</p>
    </div>
    
    <?php if ($successMsg): ?>
    <div class="success-alert">
        <i class="fa fa-check-circle"></i>
        <p><?= $successMsg ?></p>
    </div>
    <?php endif; ?>
    
    <?php if ($errorMsg): ?>
    <div class="success-alert" style="background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); border-color: #ef4444;">
        <i class="fa fa-exclamation-circle" style="color: #ef4444;"></i>
        <p style="color: #dc2626;"><?= $errorMsg ?></p>
    </div>
    <?php endif; ?>
    
    <?php if ($hasPending): ?>
    <div class="success-alert" style="background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); border-color: #f97316;">
        <i class="fa fa-clock-o" style="color: #f97316;"></i>
        <p style="color: #ea580c;">Pembayaran Anda sedang menunggu konfirmasi admin. Silakan tunggu atau hubungi admin.</p>
    </div>
    <?php endif; ?>
    
    <!-- Status Card -->
    <div class="sub-status-card">
        <div class="sub-status-info">
            <h3>Status Langganan Anda</h3>
            <p>Paket: <strong><?= ucfirst($subscription['package'] ?? 'Trial') ?></strong> | 
               Berlaku hingga: <strong><?= date('d F Y', strtotime($subscription['end_date'])) ?></strong></p>
        </div>
        <?php
        $badgeClass = 'expired';
        $badgeText = 'Expired';
        if ($isActive) {
            if ($remainingDays <= 7) {
                $badgeClass = 'warning';
                $badgeText = $remainingDays . ' Hari Lagi';
            } else {
                $badgeClass = 'active';
                $badgeText = 'Aktif';
            }
        }
        ?>
        <div class="sub-status-badge <?= $badgeClass ?>">
            <?= $badgeText ?>
        </div>
    </div>
    
    <!-- Packages -->
    <div class="packages-grid">
        <?php foreach ($subscriptionPackages as $key => $pkg): ?>
        <div class="package-card <?= $key == 'pro' ? 'popular' : '' ?>">
            <h3 class="package-name"><?= $pkg['name'] ?></h3>
            <div class="package-price">
                Rp <?= number_format($pkg['price'], 0, ',', '.') ?>
                <span>/<?= $pkg['duration'] >= 365 ? 'selamanya' : $pkg['duration'] . ' hari' ?></span>
            </div>
            <p class="package-duration">
                <?php if ($pkg['duration'] >= 365): ?>
                    Akses selamanya
                <?php else: ?>
                    <?= $pkg['duration'] ?> hari akses penuh
                <?php endif; ?>
            </p>
            <ul class="package-features">
                <?php foreach ($pkg['features'] as $feature): ?>
                <li><?= $feature ?></li>
                <?php endforeach; ?>
            </ul>
            <form method="POST" style="margin:0;">
                <input type="hidden" name="package" value="<?= $key ?>">
                <button type="submit" name="request_payment" class="btn-subscribe <?= $key == 'pro' ? 'primary' : 'outline' ?>" <?= $hasPending ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : '' ?>>
                    <?= $hasPending ? 'Menunggu Konfirmasi' : 'Pilih Paket' ?>
                </button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Payment Info -->
    <div class="payment-info">
        <h3><i class="fa fa-credit-card"></i> Cara Pembayaran</h3>
        <ol style="color: #475569; line-height: 2; margin: 0 0 20px 20px;">
            <li>Pilih paket yang diinginkan dan klik "Pilih Paket"</li>
            <li>Transfer ke rekening di bawah ini sesuai nominal paket</li>
            <li>Konfirmasi pembayaran via WhatsApp dengan menyertakan bukti transfer</li>
            <li>Admin akan mengaktifkan langganan Anda dalam 1x24 jam</li>
        </ol>
        
        <div class="payment-methods" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
            <div class="payment-method" style="text-align: left;">
                <strong style="color: #0066b3;"><i class="fa fa-university"></i> Bank BCA</strong>
                <div style="display: flex; align-items: center; gap: 10px; margin: 10px 0 5px;">
                    <p style="font-size: 20px; font-weight: 700; color: #1e293b; margin: 0;">1841343455</p>
                    <button type="button" onclick="copyToClipboard('1841343455', this)" class="btn-copy" title="Salin">
                        <i class="fa fa-copy"></i>
                    </button>
                </div>
                <p style="color: #64748b;">a.n. MUHAMMAD ANDI</p>
            </div>
            <div class="payment-method" style="text-align: left;">
                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px;">
                    <span style="background: #00aae7; color: #fff; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;"><i class="fa fa-wallet"></i> DANA</span>
                    <span style="background: #ee4d2d; color: #fff; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;"><i class="fa fa-wallet"></i> ShopeePay</span>
                    <span style="background: #00aed6; color: #fff; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;"><i class="fa fa-wallet"></i> GoPay</span>
                    <span style="background: #e82127; color: #fff; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;"><i class="fa fa-wallet"></i> LinkAja</span>
                </div>
                <div style="display: flex; align-items: center; gap: 10px; margin: 10px 0 5px;">
                    <p style="font-size: 20px; font-weight: 700; color: #1e293b; margin: 0;">0857-0700-4054</p>
                    <button type="button" onclick="copyToClipboard('085707004054', this)" class="btn-copy" title="Salin">
                        <i class="fa fa-copy"></i>
                    </button>
                </div>
                <p style="color: #64748b;">a.n. Muhammad Andi</p>
            </div>
        </div>
        
        <div class="contact-info">
            <i class="fa fa-whatsapp"></i>
            <div>
                <h4>Konfirmasi Pembayaran</h4>
                <p>Kirim bukti transfer ke WhatsApp: <a href="https://wa.me/6285707004054?text=Halo%20Admin%2C%20saya%20sudah%20transfer%20untuk%20langganan%20MIKPAY" target="_blank">0857-0700-4054</a></p>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text, btn) {
    // Try modern clipboard API first
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function() {
            showCopiedFeedback(btn);
        }).catch(function() {
            fallbackCopy(text, btn);
        });
    } else {
        fallbackCopy(text, btn);
    }
}

function fallbackCopy(text, btn) {
    // Fallback for older browsers
    var textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        document.execCommand('copy');
        showCopiedFeedback(btn);
    } catch (err) {
        alert('Gagal menyalin. Silakan salin manual: ' + text);
    }
    
    document.body.removeChild(textArea);
}

function showCopiedFeedback(btn) {
    var originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fa fa-check"></i>';
    btn.classList.add('copied');
    
    setTimeout(function() {
        btn.innerHTML = originalHTML;
        btn.classList.remove('copied');
    }, 2000);
}
</script>
