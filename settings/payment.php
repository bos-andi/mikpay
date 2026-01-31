<?php
/*
 * MIKPAY Payment Page
 * Halaman untuk pembayaran langganan
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
$packageKey = $_GET['package'] ?? '';

if (empty($packageKey)) {
    header("Location:../admin.php?id=subscription&session=" . $session);
    exit;
}

$subscriptionPackages = getSubscriptionPackages();
if (!isset($subscriptionPackages[$packageKey])) {
    header("Location:../admin.php?id=subscription&session=" . $session);
    exit;
}

$package = $subscriptionPackages[$packageKey];
$subscription = getSubscription();

// Get pending payments for this user
$allPayments = getPendingPayments();
$userPendingPayments = array_filter($allPayments, function($p) {
    return $p['status'] === 'pending';
});
$hasPending = count($userPendingPayments) > 0;

// Handle payment request submission
$successMsg = '';
$errorMsg = '';
if (isset($_POST['submit_payment'])) {
    if ($hasPending) {
        $errorMsg = "Anda masih memiliki pembayaran yang menunggu konfirmasi.";
    } else {
        $amount = $package['price'];
        $paymentId = addPendingPayment('user', $packageKey, $amount);
        $successMsg = "Permintaan pembayaran berhasil dikirim! ID: " . $paymentId . ". Silakan transfer dan tunggu konfirmasi admin.";
        $hasPending = true;
    }
}
?>

<style>
.payment-page {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
}

.payment-header {
    text-align: center;
    margin-bottom: 30px;
}
.payment-header h2 {
    font-size: 28px;
    color: #1e293b;
    margin: 0 0 10px;
}
.payment-header p {
    color: #64748b;
    font-size: 16px;
}

.payment-card {
    background: #fff;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
}

.package-summary {
    background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%);
    color: #fff;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 25px;
}
.package-summary h3 {
    margin: 0 0 15px;
    font-size: 24px;
}
.package-summary .price {
    font-size: 36px;
    font-weight: 700;
    margin: 10px 0;
}
.package-summary .duration {
    font-size: 16px;
    opacity: 0.9;
}

.payment-info-section {
    margin-bottom: 25px;
}
.payment-info-section h4 {
    color: #1e293b;
    margin-bottom: 15px;
    font-size: 18px;
}
.payment-info-section ol {
    color: #475569;
    line-height: 2;
    margin: 0 0 20px 20px;
}

.payment-methods {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}
.payment-method {
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s;
}
.payment-method:hover {
    border-color: #4D44B5;
    transform: translateY(-2px);
}
.payment-method strong {
    display: block;
    color: #1e293b;
    margin-bottom: 10px;
    font-size: 16px;
}
.payment-method p {
    color: #64748b;
    font-size: 14px;
    margin: 5px 0;
}

.btn-submit-payment {
    width: 100%;
    padding: 15px;
    background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 18px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}
.btn-submit-payment:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(77, 68, 181, 0.3);
}
.btn-submit-payment:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-back {
    display: inline-block;
    padding: 12px 24px;
    background: #f1f5f9;
    color: #475569;
    border-radius: 8px;
    text-decoration: none;
    margin-bottom: 20px;
    transition: all 0.3s;
}
.btn-back:hover {
    background: #e2e8f0;
}

.success-alert, .error-alert {
    padding: 15px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.success-alert {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border: 2px solid #16a34a;
    color: #166534;
}
.error-alert {
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    border: 2px solid #ef4444;
    color: #dc2626;
}
</style>

<div class="payment-page">
    <a href="./admin.php?id=subscription&session=<?= $session ?>" class="btn-back">
        <i class="fa fa-arrow-left"></i> Kembali ke Paket
    </a>
    
    <div class="payment-header">
        <h2><i class="fa fa-credit-card" style="color: #4D44B5;"></i> Pembayaran Langganan</h2>
        <p>Lengkapi informasi pembayaran untuk melanjutkan</p>
    </div>
    
    <?php if ($successMsg): ?>
    <div class="success-alert">
        <i class="fa fa-check-circle"></i>
        <p><?= $successMsg ?></p>
    </div>
    <?php endif; ?>
    
    <?php if ($errorMsg): ?>
    <div class="error-alert">
        <i class="fa fa-exclamation-circle"></i>
        <p><?= $errorMsg ?></p>
    </div>
    <?php endif; ?>
    
    <?php if ($hasPending): ?>
    <div class="error-alert">
        <i class="fa fa-clock-o"></i>
        <p>Pembayaran Anda sedang menunggu konfirmasi admin. Silakan tunggu atau hubungi admin.</p>
    </div>
    <?php endif; ?>
    
    <div class="payment-card">
        <!-- Package Summary -->
        <div class="package-summary">
            <h3><?= $package['name'] ?></h3>
            <div class="price">Rp <?= number_format($package['price'], 0, ',', '.') ?></div>
            <div class="duration">
                <?php if ($package['duration'] >= 365): ?>
                    Akses selamanya
                <?php else: ?>
                    <?= $package['duration'] ?> hari akses penuh
                <?php endif; ?>
            </div>
            <?php if (isset($package['discount']) && $package['discount'] > 0): ?>
            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.3);">
                <div style="font-size: 14px; opacity: 0.9;">Harga Normal: <span style="text-decoration: line-through;">Rp <?= number_format($package['original_price'], 0, ',', '.') ?></span></div>
                <div style="font-size: 16px; font-weight: 600; margin-top: 5px;">Hemat: Rp <?= number_format($package['discount'], 0, ',', '.') ?></div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Payment Instructions -->
        <div class="payment-info-section">
            <h4><i class="fa fa-info-circle"></i> Cara Pembayaran</h4>
            <ol>
                <li>Transfer ke rekening di bawah ini sesuai nominal paket</li>
                <li>Setelah transfer, klik tombol "Kirim Permintaan Pembayaran" di bawah</li>
                <li>Konfirmasi pembayaran via WhatsApp dengan menyertakan bukti transfer</li>
                <li>Admin akan mengaktifkan langganan Anda dalam 1x24 jam</li>
            </ol>
        </div>
        
        <!-- Payment Methods -->
        <div class="payment-info-section">
            <h4><i class="fa fa-university"></i> Rekening Pembayaran</h4>
            <div class="payment-methods">
                <div class="payment-method">
                    <strong style="color: #0066b3;"><i class="fa fa-university"></i> Bank BCA</strong>
                    <div style="display: flex; align-items: center; gap: 10px; margin: 10px 0 5px;">
                        <p style="font-size: 20px; font-weight: 700; color: #1e293b; margin: 0;">1841343455</p>
                        <button type="button" onclick="copyToClipboard('1841343455', this)" class="btn-copy" title="Salin" style="background: #4D44B5; color: #fff; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer;">
                            <i class="fa fa-copy"></i>
                        </button>
                    </div>
                    <p style="color: #64748b;">a.n. MUHAMMAD ANDI</p>
                </div>
                <div class="payment-method">
                    <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px;">
                        <span style="background: #00aae7; color: #fff; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;"><i class="fa fa-wallet"></i> DANA</span>
                        <span style="background: #ee4d2d; color: #fff; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;"><i class="fa fa-wallet"></i> ShopeePay</span>
                        <span style="background: #00aed6; color: #fff; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;"><i class="fa fa-wallet"></i> GoPay</span>
                        <span style="background: #e82127; color: #fff; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;"><i class="fa fa-wallet"></i> LinkAja</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px; margin: 10px 0 5px;">
                        <p style="font-size: 20px; font-weight: 700; color: #1e293b; margin: 0;">0857-0700-4054</p>
                        <button type="button" onclick="copyToClipboard('085707004054', this)" class="btn-copy" title="Salin" style="background: #4D44B5; color: #fff; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer;">
                            <i class="fa fa-copy"></i>
                        </button>
                    </div>
                    <p style="color: #64748b;">a.n. Muhammad Andi</p>
                </div>
            </div>
        </div>
        
        <!-- Contact Info -->
        <div class="payment-info-section" style="background: #f8fafc; padding: 20px; border-radius: 10px; margin-top: 20px;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <i class="fa fa-whatsapp" style="font-size: 32px; color: #25D366;"></i>
                <div>
                    <h4 style="margin: 0 0 5px; color: #1e293b;">Konfirmasi Pembayaran</h4>
                    <p style="margin: 0; color: #475569;">Kirim bukti transfer ke WhatsApp: <a href="https://wa.me/6285707004054?text=Halo%20Admin%2C%20saya%20sudah%20transfer%20untuk%20langganan%20MIKPAY" target="_blank" style="color: #4D44B5; font-weight: 600;">0857-0700-4054</a></p>
                </div>
            </div>
        </div>
        
        <!-- Submit Payment Form -->
        <form method="POST">
            <input type="hidden" name="package" value="<?= $packageKey ?>">
            <button type="submit" name="submit_payment" class="btn-submit-payment" <?= $hasPending ? 'disabled' : '' ?>>
                <i class="fa fa-paper-plane"></i> <?= $hasPending ? 'Menunggu Konfirmasi' : 'Kirim Permintaan Pembayaran' ?>
            </button>
        </form>
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
    var textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.position = "fixed";
    textArea.style.left = "-999999px";
    document.body.appendChild(textArea);
    textArea.select();
    try {
        document.execCommand('copy');
        showCopiedFeedback(btn);
    } catch (err) {
        console.error('Fallback: Oops, unable to copy', err);
    }
    document.body.removeChild(textArea);
}

function showCopiedFeedback(btn) {
    var originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fa fa-check"></i>';
    btn.style.background = '#16a34a';
    setTimeout(function() {
        btn.innerHTML = originalHTML;
        btn.style.background = '#4D44B5';
    }, 2000);
}
</script>
