<?php
/**
 * AJAX Handler for sending WhatsApp via Fonnte
 * With Anti-Spam Protection
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

include_once(__DIR__ . '/../include/fonnte_config.php');

$action = isset($_POST['action']) ? $_POST['action'] : '';

// Get quota action (no auth check needed for Fonnte)
if ($action === 'get_quota') {
    if (!isFonnteEnabled()) {
        echo json_encode([
            'success' => false,
            'message' => 'Fonnte tidak aktif',
            'quota' => null
        ]);
        exit;
    }
    
    $quota = getRemainingQuota();
    $settings = getFonnteSettings();
    echo json_encode([
        'success' => true,
        'quota' => $quota,
        'antispam_enabled' => isset($settings['antispam_enabled']) ? $settings['antispam_enabled'] : false
    ]);
    exit;
}

// Check bulk preparation
if ($action === 'prepare_bulk') {
    $count = intval(isset($_POST['count']) ? $_POST['count'] : 0);
    if (!isFonnteEnabled()) {
        echo json_encode([
            'success' => false,
            'message' => 'Fonnte tidak aktif'
        ]);
        exit;
    }
    
    $preparation = prepareBulkSend($count);
    echo json_encode([
        'success' => true,
        'preparation' => $preparation
    ]);
    exit;
}

// Check if Fonnte is enabled
if (!isFonnteEnabled()) {
    echo json_encode([
        'success' => false, 
        'message' => 'Fonnte belum dikonfigurasi. Silakan konfigurasi di menu Settings > WhatsApp API',
        'redirect' => true
    ]);
    exit;
}

// Send single message
if ($action === 'send_single') {
    $phone = trim(isset($_POST['phone']) ? $_POST['phone'] : '');
    $message = trim(isset($_POST['message']) ? $_POST['message'] : '');
    
    if (empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'Nomor telepon tidak boleh kosong']);
        exit;
    }
    
    if (empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Pesan tidak boleh kosong']);
        exit;
    }
    
    $result = sendFonnteMessage($phone, $message);
    $result['quota'] = getRemainingQuota();
    echo json_encode($result);
    exit;
}

// Send bulk messages (with Anti-Spam)
if ($action === 'send_bulk') {
    $recipients = json_decode(isset($_POST['recipients']) ? $_POST['recipients'] : '[]', true);
    $randomize = isset($_POST['randomize']) ? $_POST['randomize'] === 'true' : true;
    
    if (empty($recipients)) {
        echo json_encode(['success' => false, 'message' => 'Tidak ada penerima']);
        exit;
    }
    
    // Check quota first
    $quota = getRemainingQuota();
    if ($quota['hourly_remaining'] < 1) {
        echo json_encode([
            'success' => false,
            'message' => 'Kuota per jam habis. Tersisa: ' . $quota['hourly_remaining'] . ' pesan. Tunggu jam berikutnya.',
            'quota' => $quota,
            'rate_limited' => true
        ]);
        exit;
    }
    
    $results = sendFonnteBulk($recipients, $randomize);
    
    $status = ($results['failed'] == 0 && $results['skipped'] == 0) ? 'success' : 
              (($results['success'] > 0) ? 'partial' : 'failed');
    
    echo json_encode([
        'success' => $results['success'] > 0,
        'status' => $status,
        'message' => "Berhasil: {$results['success']}, Gagal: {$results['failed']}, Dilewati: {$results['skipped']}",
        'results' => $results,
        'quota' => $results['quota_after']
    ]);
    exit;
}

// Send with template
if ($action === 'send_template') {
    $phone = trim(isset($_POST['phone']) ? $_POST['phone'] : '');
    $status = isset($_POST['status']) ? $_POST['status'] : 'reminder';
    $settings = getFonnteSettings();
    $data = array(
        'nama' => isset($_POST['nama']) ? $_POST['nama'] : '',
        'nominal' => isset($_POST['nominal']) ? $_POST['nominal'] : '',
        'tanggal' => isset($_POST['tanggal']) ? $_POST['tanggal'] : '',
        'hari' => isset($_POST['hari']) ? $_POST['hari'] : '',
        'periode' => isset($_POST['periode']) ? $_POST['periode'] : '',
        'username' => isset($_POST['username']) ? $_POST['username'] : '',
        'pengirim' => isset($settings['sender_name']) ? $settings['sender_name'] : 'MIKPAY WiFi'
    );
    
    if (empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'Nomor telepon tidak boleh kosong']);
        exit;
    }
    
    // Get template based on status
    $template = getFonnteTemplate($status);
    
    // Apply randomization if enabled
    if (isset($settings['antispam_randomize_message']) && $settings['antispam_randomize_message']) {
        $message = randomizeMessage($template, $data);
    } else {
        $message = replaceTemplateVariables($template, $data);
    }
    
    $result = sendFonnteMessage($phone, $message);
    $result['message_sent'] = $message;
    $result['quota'] = getRemainingQuota();
    echo json_encode($result);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action tidak valid']);
