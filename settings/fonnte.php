<?php
/*
 * Fonnte WhatsApp API Settings Page
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(0);

if (!isset($_SESSION["mikpay"])) {
    header("Location:../admin.php?id=login");
    exit;
}

include_once(__DIR__ . '/../include/fonnte_config.php');

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_settings'])) {
        $currentSettings = getFonnteSettings();
        $settings = array(
            'enabled' => isset($_POST['enabled']),
            'api_token' => trim(isset($_POST['api_token']) ? $_POST['api_token'] : ''),
            'sender_name' => trim(isset($_POST['sender_name']) ? $_POST['sender_name'] : 'MIKPAY WiFi'),
            'auto_send_h3' => isset($_POST['auto_send_h3']),
            'auto_send_h0' => isset($_POST['auto_send_h0']),
            'auto_send_overdue' => isset($_POST['auto_send_overdue']),
            // Anti-Spam Settings
            'antispam_enabled' => isset($_POST['antispam_enabled']),
            'antispam_delay_min' => intval(isset($_POST['antispam_delay_min']) ? $_POST['antispam_delay_min'] : 15),
            'antispam_delay_max' => intval(isset($_POST['antispam_delay_max']) ? $_POST['antispam_delay_max'] : 30),
            'antispam_hourly_limit' => intval(isset($_POST['antispam_hourly_limit']) ? $_POST['antispam_hourly_limit'] : 10),
            'antispam_daily_limit' => intval(isset($_POST['antispam_daily_limit']) ? $_POST['antispam_daily_limit'] : 50),
            'antispam_randomize_message' => isset($_POST['antispam_randomize_message']),
            // Templates
            'template_greetings' => isset($currentSettings['template_greetings']) ? $currentSettings['template_greetings'] : array("Halo {nama},"),
            'template_reminder' => isset($_POST['template_reminder']) ? $_POST['template_reminder'] : '',
            'template_due_today' => isset($_POST['template_due_today']) ? $_POST['template_due_today'] : '',
            'template_overdue' => isset($_POST['template_overdue']) ? $_POST['template_overdue'] : '',
            'template_closings' => isset($currentSettings['template_closings']) ? $currentSettings['template_closings'] : array("")
        );
        
        saveFonnteSettings($settings);
        $message = 'Pengaturan berhasil disimpan!';
        $messageType = 'success';
    }
    
    if (isset($_POST['test_connection'])) {
        $token = trim($_POST['api_token'] ?? '');
        if (empty($token)) {
            $message = 'API Token tidak boleh kosong!';
            $messageType = 'error';
        } else {
            $result = testFonnteConnection($token);
            if ($result['success']) {
                $message = 'Koneksi berhasil! Device: ' . ($result['device'] ?? 'Connected');
                $messageType = 'success';
            } else {
                $message = 'Koneksi gagal: ' . $result['message'];
                $messageType = 'error';
            }
        }
    }
    
    if (isset($_POST['test_send'])) {
        $phone = trim(isset($_POST['test_phone']) ? $_POST['test_phone'] : '');
        if (empty($phone)) {
            $message = 'Nomor tujuan tidak boleh kosong!';
            $messageType = 'error';
        } else {
            // Test send skips rate limit
            $result = sendFonnteMessage($phone, 'Ini adalah pesan test dari MIKPAY. Jika Anda menerima pesan ini, berarti konfigurasi Fonnte sudah benar. [Test #' . rand(1000,9999) . ']', true);
            if ($result['success']) {
                $message = 'Pesan test berhasil dikirim ke ' . $phone;
                $messageType = 'success';
            } else {
                $message = 'Gagal mengirim: ' . $result['message'];
                $messageType = 'error';
            }
        }
    }
    
    // Reset daily quota (admin action)
    if (isset($_POST['reset_quota'])) {
        file_put_contents(FONNTE_RATE_FILE, json_encode(array(
            'today' => date('Y-m-d'),
            'hour' => date('Y-m-d H'),
            'daily_count' => 0,
            'hourly_count' => 0,
            'last_sent' => null
        ), JSON_PRETTY_PRINT));
        $message = 'Kuota berhasil direset!';
        $messageType = 'success';
    }
}

$settings = getFonnteSettings();
$logs = getFonnteLogs(20);
$quota = getRemainingQuota();
?>

<style>
.fonnte-container {
    max-width: 1200px;
    margin: 0 auto;
}

.fonnte-header {
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
    border-radius: 16px;
    padding: 30px;
    color: #FFF;
    margin-bottom: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.fonnte-header h2 {
    margin: 0;
    font-size: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.fonnte-header .status-badge {
    padding: 8px 16px;
    border-radius: 25px;
    font-size: 13px;
    font-weight: 600;
}

.fonnte-header .status-badge.active {
    background: rgba(255,255,255,0.2);
    color: #FFF;
}

.fonnte-header .status-badge.inactive {
    background: rgba(0,0,0,0.2);
    color: rgba(255,255,255,0.8);
}

.fonnte-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
}

@media (max-width: 900px) {
    .fonnte-grid {
        grid-template-columns: 1fr;
    }
}

.fonnte-card {
    background: #FFFFFF;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    overflow: hidden;
}

.fonnte-card-header {
    background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%);
    color: #FFF;
    padding: 18px 25px;
    font-size: 16px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.fonnte-card-header.green {
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
}

.fonnte-card-header.orange {
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
}

.fonnte-card-body {
    padding: 25px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 8px;
    font-size: 14px;
}

.form-group label .hint {
    font-weight: 400;
    color: #64748b;
    font-size: 12px;
}

.form-input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.3s ease;
    box-sizing: border-box;
}

.form-input:focus {
    outline: none;
    border-color: #4D44B5;
    box-shadow: 0 0 0 3px rgba(77, 68, 181, 0.1);
}

.form-input.token-input {
    font-family: monospace;
    letter-spacing: 1px;
}

.form-textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 13px;
    min-height: 120px;
    resize: vertical;
    font-family: inherit;
    box-sizing: border-box;
}

.form-textarea:focus {
    outline: none;
    border-color: #4D44B5;
}

.form-switch {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 15px;
    background: #f8fafc;
    border-radius: 10px;
    cursor: pointer;
}

.form-switch input[type="checkbox"] {
    width: 50px;
    height: 26px;
    appearance: none;
    background: #cbd5e1;
    border-radius: 13px;
    position: relative;
    cursor: pointer;
    transition: all 0.3s ease;
}

.form-switch input[type="checkbox"]:checked {
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
}

.form-switch input[type="checkbox"]::before {
    content: '';
    position: absolute;
    width: 22px;
    height: 22px;
    background: #FFF;
    border-radius: 50%;
    top: 2px;
    left: 2px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.form-switch input[type="checkbox"]:checked::before {
    left: 26px;
}

.form-switch .switch-label {
    flex: 1;
}

.form-switch .switch-label strong {
    display: block;
    color: #1e293b;
    font-size: 14px;
}

.form-switch .switch-label small {
    color: #64748b;
    font-size: 12px;
}

.btn-row {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 20px;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%);
    color: #FFF;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(77, 68, 181, 0.4);
}

.btn-success {
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
    color: #FFF;
}

.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(37, 211, 102, 0.4);
}

.btn-secondary {
    background: #e2e8f0;
    color: #64748b;
}

.alert {
    padding: 15px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-success {
    background: #f0fdf4;
    color: #16a34a;
    border: 2px solid #22c55e;
}

.alert-error {
    background: #fef2f2;
    color: #dc2626;
    border: 2px solid #ef4444;
}

.template-vars {
    background: #f8fafc;
    padding: 12px 15px;
    border-radius: 8px;
    margin-bottom: 15px;
    font-size: 12px;
    color: #64748b;
}

.template-vars code {
    background: #e2e8f0;
    padding: 2px 6px;
    border-radius: 4px;
    color: #4D44B5;
    font-family: monospace;
}

.log-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.log-table th {
    background: #f8fafc;
    padding: 12px 15px;
    text-align: left;
    font-weight: 600;
    color: #64748b;
    border-bottom: 2px solid #e2e8f0;
}

.log-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #f1f5f9;
}

.log-table tr:hover {
    background: #fafafa;
}

.log-status {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: 600;
}

.log-status.sent {
    background: #dcfce7;
    color: #16a34a;
}

.log-status.failed {
    background: #fee2e2;
    color: #dc2626;
}

.log-status.rate_limited, .log-status.skipped {
    background: #fef3c7;
    color: #d97706;
}

.test-section {
    background: #f0fdf4;
    border: 2px solid #22c55e;
    border-radius: 12px;
    padding: 20px;
    margin-top: 20px;
}

.test-section h4 {
    margin: 0 0 15px;
    color: #16a34a;
    font-size: 15px;
}

.test-row {
    display: flex;
    gap: 10px;
}

.test-row input {
    flex: 1;
}
</style>

<div class="fonnte-container">
    <form method="POST">
        <!-- Header -->
        <div class="fonnte-header">
            <h2>
                <i class="fa fa-whatsapp"></i> Pengaturan WhatsApp API
            </h2>
            <span class="status-badge <?= $settings['enabled'] && !empty($settings['api_token']) ? 'active' : 'inactive' ?>">
                <?= $settings['enabled'] && !empty($settings['api_token']) ? '🟢 Aktif' : '⚪ Tidak Aktif' ?>
            </span>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <i class="fa fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>
        
        <!-- Quota Display -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">
            <div style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); border-radius: 12px; padding: 20px; color: #FFF;">
                <div style="font-size: 12px; opacity: 0.9; margin-bottom: 5px;">Kuota Jam Ini</div>
                <div style="font-size: 28px; font-weight: 700;"><?= $quota['hourly_remaining'] ?> <small style="font-size: 14px;">/ <?= $quota['hourly_limit'] ?></small></div>
            </div>
            <div style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border-radius: 12px; padding: 20px; color: #FFF;">
                <div style="font-size: 12px; opacity: 0.9; margin-bottom: 5px;">Kuota Hari Ini</div>
                <div style="font-size: 28px; font-weight: 700;"><?= $quota['daily_remaining'] ?> <small style="font-size: 14px;">/ <?= $quota['daily_limit'] ?></small></div>
            </div>
            <div style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); border-radius: 12px; padding: 20px; color: #FFF;">
                <div style="font-size: 12px; opacity: 0.9; margin-bottom: 5px;">Terakhir Kirim</div>
                <div style="font-size: 16px; font-weight: 600;"><?= $quota['last_sent'] ? date('H:i', strtotime($quota['last_sent'])) : '-' ?></div>
            </div>
            <div style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); border-radius: 12px; padding: 20px; color: #FFF;">
                <div style="font-size: 12px; opacity: 0.9; margin-bottom: 5px;">Status Anti-Spam</div>
                <div style="font-size: 16px; font-weight: 600;">
                    <?= (isset($settings['antispam_enabled']) && $settings['antispam_enabled']) ? '🛡️ Aktif' : '⚠️ Nonaktif' ?>
                </div>
            </div>
        </div>
        
        <!-- Reset Quota Button (small) -->
        <div style="text-align: right; margin-bottom: 15px;">
            <button type="submit" name="reset_quota" class="btn btn-secondary" style="font-size: 12px; padding: 8px 15px;" onclick="return confirm('Reset kuota pengiriman hari ini?')">
                <i class="fa fa-refresh"></i> Reset Kuota
            </button>
        </div>
        
        <div class="fonnte-grid">
            <!-- Left Column: Configuration -->
            <div>
                <div class="fonnte-card">
                    <div class="fonnte-card-header green">
                        <i class="fa fa-cog"></i> Konfigurasi API
                    </div>
                    <div class="fonnte-card-body">
                        <div class="form-group">
                            <label class="form-switch">
                                <input type="checkbox" name="enabled" <?= $settings['enabled'] ? 'checked' : '' ?>>
                                <div class="switch-label">
                                    <strong>Aktifkan Fonnte</strong>
                                    <small>Aktifkan pengiriman WhatsApp via Fonnte API</small>
                                </div>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label>API Token <span class="hint">(dari dashboard Fonnte)</span></label>
                            <input type="text" name="api_token" class="form-input token-input" 
                                   value="<?= htmlspecialchars($settings['api_token']) ?>" 
                                   placeholder="Masukkan API Token dari Fonnte">
                        </div>
                        
                        <div class="form-group">
                            <label>Nama Pengirim</label>
                            <input type="text" name="sender_name" class="form-input" 
                                   value="<?= htmlspecialchars($settings['sender_name']) ?>" 
                                   placeholder="Contoh: MIKPAY WiFi">
                        </div>
                        
                        <div class="btn-row">
                            <button type="submit" name="test_connection" class="btn btn-secondary">
                                <i class="fa fa-plug"></i> Test Koneksi
                            </button>
                            <button type="submit" name="save_settings" class="btn btn-primary">
                                <i class="fa fa-save"></i> Simpan
                            </button>
                        </div>
                        
                        <!-- Test Send Section -->
                        <div class="test-section">
                            <h4><i class="fa fa-paper-plane"></i> Kirim Pesan Test</h4>
                            <div class="test-row">
                                <input type="text" name="test_phone" class="form-input" placeholder="Nomor tujuan (08xxx)">
                                <button type="submit" name="test_send" class="btn btn-success">
                                    <i class="fa fa-send"></i> Kirim
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Auto Send Settings -->
                <div class="fonnte-card" style="margin-top: 25px;">
                    <div class="fonnte-card-header orange">
                        <i class="fa fa-clock-o"></i> Pengiriman Otomatis
                    </div>
                    <div class="fonnte-card-body">
                        <div class="form-group">
                            <label class="form-switch">
                                <input type="checkbox" name="auto_send_h3" <?= $settings['auto_send_h3'] ? 'checked' : '' ?>>
                                <div class="switch-label">
                                    <strong>Kirim H-3</strong>
                                    <small>Kirim pengingat 3 hari sebelum jatuh tempo</small>
                                </div>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-switch">
                                <input type="checkbox" name="auto_send_h0" <?= $settings['auto_send_h0'] ? 'checked' : '' ?>>
                                <div class="switch-label">
                                    <strong>Kirim Hari H</strong>
                                    <small>Kirim pengingat pada hari jatuh tempo</small>
                                </div>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-switch">
                                <input type="checkbox" name="auto_send_overdue" <?= $settings['auto_send_overdue'] ? 'checked' : '' ?>>
                                <div class="switch-label">
                                    <strong>Kirim Jika Terlambat</strong>
                                    <small>Kirim pengingat jika sudah melewati jatuh tempo</small>
                                </div>
                            </label>
                        </div>
                        
                        <small style="color:#94a3b8; display:block; margin-top:10px;">
                            <i class="fa fa-info-circle"></i> Pengiriman otomatis memerlukan cron job yang berjalan setiap hari
                        </small>
                    </div>
                </div>
                
                <!-- Anti-Spam Settings -->
                <div class="fonnte-card" style="margin-top: 25px;">
                    <div class="fonnte-card-header" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                        <i class="fa fa-shield"></i> Proteksi Anti-Spam
                    </div>
                    <div class="fonnte-card-body">
                        <div style="background: #fef2f2; border: 2px solid #fecaca; border-radius: 10px; padding: 15px; margin-bottom: 20px;">
                            <strong style="color: #dc2626;"><i class="fa fa-exclamation-triangle"></i> Penting!</strong>
                            <p style="color: #991b1b; font-size: 13px; margin: 8px 0 0;">
                                Fitur ini melindungi akun WhatsApp Anda dari pembatasan/banned karena spam. 
                                Sangat disarankan untuk tetap mengaktifkan fitur ini.
                            </p>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-switch">
                                <input type="checkbox" name="antispam_enabled" <?= (isset($settings['antispam_enabled']) && $settings['antispam_enabled']) ? 'checked' : '' ?>>
                                <div class="switch-label">
                                    <strong>Aktifkan Anti-Spam</strong>
                                    <small>Batasi kecepatan pengiriman untuk menghindari ban</small>
                                </div>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-switch">
                                <input type="checkbox" name="antispam_randomize_message" <?= (isset($settings['antispam_randomize_message']) && $settings['antispam_randomize_message']) ? 'checked' : '' ?>>
                                <div class="switch-label">
                                    <strong>Variasi Pesan Otomatis</strong>
                                    <small>Buat setiap pesan sedikit berbeda agar tidak terdeteksi spam</small>
                                </div>
                            </label>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label>Jeda Minimum (detik)</label>
                                <input type="number" name="antispam_delay_min" class="form-input" 
                                       value="<?= isset($settings['antispam_delay_min']) ? $settings['antispam_delay_min'] : 15 ?>" 
                                       min="5" max="120">
                            </div>
                            <div class="form-group">
                                <label>Jeda Maximum (detik)</label>
                                <input type="number" name="antispam_delay_max" class="form-input" 
                                       value="<?= isset($settings['antispam_delay_max']) ? $settings['antispam_delay_max'] : 30 ?>" 
                                       min="10" max="180">
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label>Batas per Jam</label>
                                <input type="number" name="antispam_hourly_limit" class="form-input" 
                                       value="<?= isset($settings['antispam_hourly_limit']) ? $settings['antispam_hourly_limit'] : 10 ?>" 
                                       min="1" max="30">
                            </div>
                            <div class="form-group">
                                <label>Batas per Hari</label>
                                <input type="number" name="antispam_daily_limit" class="form-input" 
                                       value="<?= isset($settings['antispam_daily_limit']) ? $settings['antispam_daily_limit'] : 50 ?>" 
                                       min="5" max="200">
                            </div>
                        </div>
                        
                        <div style="background: #f0fdf4; border-radius: 10px; padding: 15px; margin-top: 10px;">
                            <strong style="color: #16a34a;"><i class="fa fa-lightbulb-o"></i> Rekomendasi Aman:</strong>
                            <ul style="color: #166534; font-size: 13px; margin: 8px 0 0; padding-left: 20px;">
                                <li>Jeda: 15-30 detik antar pesan</li>
                                <li>Maksimal 10 pesan per jam</li>
                                <li>Maksimal 50 pesan per hari</li>
                                <li>Aktifkan variasi pesan</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Column: Templates -->
            <div>
                <div class="fonnte-card">
                    <div class="fonnte-card-header">
                        <i class="fa fa-file-text"></i> Template Pesan
                    </div>
                    <div class="fonnte-card-body">
                        <div class="template-vars">
                            <strong>Variabel tersedia:</strong><br>
                            <code>{nama}</code> Nama pelanggan &nbsp;
                            <code>{nominal}</code> Tarif &nbsp;
                            <code>{tanggal}</code> Tgl jatuh tempo &nbsp;
                            <code>{hari}</code> Sisa hari &nbsp;
                            <code>{pengirim}</code> Nama pengirim
                        </div>
                        
                        <div class="form-group">
                            <label>Template H-3 (Pengingat)</label>
                            <textarea name="template_reminder" class="form-textarea"><?= htmlspecialchars($settings['template_reminder']) ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Template Hari H (Jatuh Tempo)</label>
                            <textarea name="template_due_today" class="form-textarea"><?= htmlspecialchars($settings['template_due_today']) ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Template Terlambat</label>
                            <textarea name="template_overdue" class="form-textarea"><?= htmlspecialchars($settings['template_overdue']) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    
    <!-- Logs Section -->
    <div class="fonnte-card" style="margin-top: 25px;">
        <div class="fonnte-card-header">
            <i class="fa fa-history"></i> Riwayat Pengiriman (20 Terakhir)
        </div>
        <div class="fonnte-card-body" style="padding: 0;">
            <?php if (empty($logs)): ?>
            <div style="padding: 40px; text-align: center; color: #94a3b8;">
                <i class="fa fa-inbox" style="font-size: 40px; margin-bottom: 10px;"></i>
                <p>Belum ada riwayat pengiriman</p>
            </div>
            <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="log-table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Nomor</th>
                            <th>Pesan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
                            <td><?= htmlspecialchars($log['phone']) ?></td>
                            <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?= htmlspecialchars($log['message']) ?>
                            </td>
                            <td>
                                <span class="log-status <?= $log['status'] ?>">
                                    <?= $log['status'] === 'sent' ? 'Terkirim' : 'Gagal' ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
