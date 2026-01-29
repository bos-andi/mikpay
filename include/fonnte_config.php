<?php
/**
 * Fonnte WhatsApp API Configuration
 * Mengelola konfigurasi dan fungsi Fonnte API
 * Dengan fitur Anti-Spam Protection
 */

if (!defined('FONNTE_CONFIG_FILE')) {
    define('FONNTE_CONFIG_FILE', __DIR__ . '/fonnte_settings.json');
}
if (!defined('FONNTE_LOG_FILE')) {
    define('FONNTE_LOG_FILE', __DIR__ . '/fonnte_logs.json');
}
if (!defined('FONNTE_RATE_FILE')) {
    define('FONNTE_RATE_FILE', __DIR__ . '/fonnte_rate.json');
}

// Anti-Spam Default Settings
if (!defined('ANTISPAM_DELAY_MIN')) {
    define('ANTISPAM_DELAY_MIN', 15); // Minimum 15 detik antar pesan
}
if (!defined('ANTISPAM_DELAY_MAX')) {
    define('ANTISPAM_DELAY_MAX', 30); // Maximum 30 detik antar pesan
}
if (!defined('ANTISPAM_HOURLY_LIMIT')) {
    define('ANTISPAM_HOURLY_LIMIT', 10); // Max 10 pesan per jam
}
if (!defined('ANTISPAM_DAILY_LIMIT')) {
    define('ANTISPAM_DAILY_LIMIT', 50); // Max 50 pesan per hari
}

/**
 * Get default Fonnte settings with anti-spam
 */
function getDefaultFonnteSettings() {
    return array(
        'enabled' => false,
        'api_token' => '',
        'sender_name' => 'MIKPAY WiFi',
        'auto_send_h3' => false,
        'auto_send_h0' => false,
        'auto_send_overdue' => false,
        // Anti-Spam Settings
        'antispam_enabled' => true,
        'antispam_delay_min' => ANTISPAM_DELAY_MIN,
        'antispam_delay_max' => ANTISPAM_DELAY_MAX,
        'antispam_hourly_limit' => ANTISPAM_HOURLY_LIMIT,
        'antispam_daily_limit' => ANTISPAM_DAILY_LIMIT,
        'antispam_randomize_message' => true,
        // Message Templates with variations
        'template_greetings' => array(
            "Halo {nama},",
            "Hai {nama},",
            "Yth. {nama},",
            "Assalamualaikum {nama},",
            "Selamat siang {nama},"
        ),
        'template_reminder' => "Ini adalah pengingat bahwa tagihan WiFi Anda sebesar *Rp {nominal}* akan jatuh tempo pada tanggal {tanggal} ({hari} hari lagi).\n\nMohon segera lakukan pembayaran.\n\nTerima kasih,\n{pengirim}",
        'template_due_today' => "Tagihan WiFi Anda sebesar *Rp {nominal}* JATUH TEMPO HARI INI.\n\nMohon segera lakukan pembayaran untuk menghindari pemutusan layanan.\n\nTerima kasih,\n{pengirim}",
        'template_overdue' => "Tagihan WiFi Anda sebesar *Rp {nominal}* sudah MELEWATI jatuh tempo.\n\nMohon segera lakukan pembayaran untuk menghindari pemutusan layanan.\n\nTerima kasih,\n{pengirim}",
        'template_closings' => array(
            "\n\nTerima kasih atas perhatiannya.",
            "\n\nTerima kasih.",
            "\n\nSalam hangat.",
            "\n\nHormat kami.",
            ""
        ),
        'last_updated' => null
    );
}

/**
 * Get Fonnte settings
 */
function getFonnteSettings() {
    $defaults = getDefaultFonnteSettings();
    
    if (!file_exists(FONNTE_CONFIG_FILE)) {
        return $defaults;
    }
    
    $data = file_get_contents(FONNTE_CONFIG_FILE);
    $result = json_decode($data, true);
    
    if ($result === null || empty($result)) {
        return $defaults;
    }
    
    // Merge with defaults to ensure all keys exist
    return array_merge($defaults, $result);
}

/**
 * Save Fonnte settings
 */
function saveFonnteSettings($settings) {
    $settings['last_updated'] = date('Y-m-d H:i:s');
    file_put_contents(FONNTE_CONFIG_FILE, json_encode($settings, JSON_PRETTY_PRINT));
    return $settings;
}

/**
 * Check if Fonnte is configured and enabled
 */
function isFonnteEnabled() {
    $settings = getFonnteSettings();
    return $settings['enabled'] && !empty($settings['api_token']);
}

// =============================================
// ANTI-SPAM RATE LIMITING FUNCTIONS
// =============================================

/**
 * Get rate limiting data
 */
function getRateLimitData() {
    if (!file_exists(FONNTE_RATE_FILE)) {
        return array(
            'today' => date('Y-m-d'),
            'hour' => date('Y-m-d H'),
            'daily_count' => 0,
            'hourly_count' => 0,
            'last_sent' => null
        );
    }
    
    $data = json_decode(file_get_contents(FONNTE_RATE_FILE), true);
    if (!$data) {
        return array(
            'today' => date('Y-m-d'),
            'hour' => date('Y-m-d H'),
            'daily_count' => 0,
            'hourly_count' => 0,
            'last_sent' => null
        );
    }
    
    // Reset counters if day/hour changed
    $today = date('Y-m-d');
    $currentHour = date('Y-m-d H');
    
    if ($data['today'] !== $today) {
        $data['today'] = $today;
        $data['daily_count'] = 0;
    }
    
    if ($data['hour'] !== $currentHour) {
        $data['hour'] = $currentHour;
        $data['hourly_count'] = 0;
    }
    
    return $data;
}

/**
 * Save rate limiting data
 */
function saveRateLimitData($data) {
    file_put_contents(FONNTE_RATE_FILE, json_encode($data, JSON_PRETTY_PRINT));
}

/**
 * Increment rate limit counters
 */
function incrementRateLimit() {
    $data = getRateLimitData();
    $data['daily_count']++;
    $data['hourly_count']++;
    $data['last_sent'] = date('Y-m-d H:i:s');
    saveRateLimitData($data);
}

/**
 * Check if can send message (rate limit check)
 */
function canSendMessage() {
    $settings = getFonnteSettings();
    $rateData = getRateLimitData();
    
    // If anti-spam disabled, always allow
    if (!isset($settings['antispam_enabled']) || !$settings['antispam_enabled']) {
        return array('allowed' => true, 'reason' => '');
    }
    
    $hourlyLimit = isset($settings['antispam_hourly_limit']) ? $settings['antispam_hourly_limit'] : ANTISPAM_HOURLY_LIMIT;
    $dailyLimit = isset($settings['antispam_daily_limit']) ? $settings['antispam_daily_limit'] : ANTISPAM_DAILY_LIMIT;
    
    // Check hourly limit
    if ($rateData['hourly_count'] >= $hourlyLimit) {
        return array(
            'allowed' => false,
            'reason' => 'Batas pengiriman per jam tercapai (' . $hourlyLimit . ' pesan). Tunggu jam berikutnya.',
            'type' => 'hourly_limit'
        );
    }
    
    // Check daily limit
    if ($rateData['daily_count'] >= $dailyLimit) {
        return array(
            'allowed' => false,
            'reason' => 'Batas pengiriman harian tercapai (' . $dailyLimit . ' pesan). Coba lagi besok.',
            'type' => 'daily_limit'
        );
    }
    
    return array('allowed' => true, 'reason' => '');
}

/**
 * Get remaining quota
 */
function getRemainingQuota() {
    $settings = getFonnteSettings();
    $rateData = getRateLimitData();
    
    $hourlyLimit = isset($settings['antispam_hourly_limit']) ? $settings['antispam_hourly_limit'] : ANTISPAM_HOURLY_LIMIT;
    $dailyLimit = isset($settings['antispam_daily_limit']) ? $settings['antispam_daily_limit'] : ANTISPAM_DAILY_LIMIT;
    
    return array(
        'hourly_remaining' => max(0, $hourlyLimit - $rateData['hourly_count']),
        'hourly_limit' => $hourlyLimit,
        'hourly_used' => $rateData['hourly_count'],
        'daily_remaining' => max(0, $dailyLimit - $rateData['daily_count']),
        'daily_limit' => $dailyLimit,
        'daily_used' => $rateData['daily_count'],
        'last_sent' => $rateData['last_sent']
    );
}

/**
 * Get random delay between messages
 */
function getRandomDelay() {
    $settings = getFonnteSettings();
    $min = isset($settings['antispam_delay_min']) ? $settings['antispam_delay_min'] : ANTISPAM_DELAY_MIN;
    $max = isset($settings['antispam_delay_max']) ? $settings['antispam_delay_max'] : ANTISPAM_DELAY_MAX;
    return rand($min, $max);
}

/**
 * Randomize message to avoid spam detection
 */
function randomizeMessage($message, $data = array()) {
    $settings = getFonnteSettings();
    
    // If randomization disabled, return original
    if (!isset($settings['antispam_randomize_message']) || !$settings['antispam_randomize_message']) {
        return $message;
    }
    
    // Get random greeting
    $greetings = isset($settings['template_greetings']) ? $settings['template_greetings'] : array("Halo {nama},");
    $randomGreeting = $greetings[array_rand($greetings)];
    
    // Get random closing (sometimes)
    $closings = isset($settings['template_closings']) ? $settings['template_closings'] : array("");
    $randomClosing = (rand(0, 1) == 1) ? $closings[array_rand($closings)] : "";
    
    // Add unique identifier (invisible to user but makes message unique)
    $uniqueId = "\n\n#" . substr(md5(time() . rand()), 0, 6);
    
    // Build final message
    $finalMessage = $randomGreeting . "\n\n" . $message . $randomClosing . $uniqueId;
    
    // Replace template variables
    if (!empty($data)) {
        $finalMessage = replaceTemplateVariables($finalMessage, $data);
    }
    
    return $finalMessage;
}

/**
 * Send WhatsApp message via Fonnte API (with Anti-Spam Protection)
 * @param string $phone - Nomor tujuan (format: 08xxx atau 628xxx)
 * @param string $message - Pesan yang akan dikirim
 * @param bool $skipRateLimit - Skip rate limit check (for testing)
 * @return array Response dari Fonnte
 */
function sendFonnteMessage($phone, $message, $skipRateLimit = false) {
    $settings = getFonnteSettings();
    
    if (!$settings['enabled'] || empty($settings['api_token'])) {
        return array(
            'success' => false,
            'message' => 'Fonnte belum dikonfigurasi',
            'status' => 'not_configured'
        );
    }
    
    // Check rate limit (unless skipped)
    if (!$skipRateLimit) {
        $canSend = canSendMessage();
        if (!$canSend['allowed']) {
            return array(
                'success' => false,
                'message' => $canSend['reason'],
                'status' => 'rate_limited',
                'type' => isset($canSend['type']) ? $canSend['type'] : 'limit_reached'
            );
        }
    }
    
    // Format phone number
    $phone = formatPhoneNumber($phone);
    
    // Prepare request
    $curl = curl_init();
    
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.fonnte.com/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array(
            'target' => $phone,
            'message' => $message,
            'countryCode' => '62'
        ),
        CURLOPT_HTTPHEADER => array(
            'Authorization: ' . $settings['api_token']
        ),
    ));
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    
    // Parse response
    $result = json_decode($response, true);
    
    // Log the message
    logFonnteMessage($phone, $message, $result, $httpCode);
    
    // Increment rate limit counter on success
    if (isset($result['status']) && $result['status'] === true) {
        incrementRateLimit();
    }
    
    if ($error) {
        return array(
            'success' => false,
            'message' => 'Curl error: ' . $error,
            'status' => 'error'
        );
    }
    
    if (isset($result['status']) && $result['status'] === true) {
        return array(
            'success' => true,
            'message' => 'Pesan berhasil dikirim',
            'status' => 'sent',
            'detail' => $result,
            'quota' => getRemainingQuota()
        );
    }
    
    return array(
        'success' => false,
        'message' => isset($result['reason']) ? $result['reason'] : (isset($result['message']) ? $result['message'] : 'Gagal mengirim pesan'),
        'status' => 'failed',
        'detail' => $result
    );
}

/**
 * Send bulk WhatsApp messages (with Anti-Spam Protection)
 * @param array $recipients - Array of ['phone' => '08xxx', 'message' => 'text', 'data' => [...]]
 * @param bool $randomize - Whether to randomize messages
 * @return array Results
 */
function sendFonnteBulk($recipients, $randomize = true) {
    $settings = getFonnteSettings();
    $quota = getRemainingQuota();
    
    $results = array(
        'total' => count($recipients),
        'success' => 0,
        'failed' => 0,
        'skipped' => 0,
        'details' => array(),
        'quota_before' => $quota
    );
    
    // Check if we have enough quota
    if ($quota['hourly_remaining'] < count($recipients)) {
        $results['warning'] = 'Kuota tidak cukup. Tersedia: ' . $quota['hourly_remaining'] . ' dari ' . count($recipients) . ' yang diminta.';
    }
    
    foreach ($recipients as $index => $recipient) {
        // Check rate limit before each message
        $canSend = canSendMessage();
        if (!$canSend['allowed']) {
            $results['skipped']++;
            $results['details'][] = array(
                'phone' => $recipient['phone'],
                'result' => array(
                    'success' => false,
                    'message' => $canSend['reason'],
                    'status' => 'skipped'
                )
            );
            continue;
        }
        
        // Randomize message if enabled
        $message = $recipient['message'];
        if ($randomize && isset($settings['antispam_randomize_message']) && $settings['antispam_randomize_message']) {
            $data = isset($recipient['data']) ? $recipient['data'] : array();
            $message = randomizeMessage($message, $data);
        }
        
        // Send message
        $result = sendFonnteMessage($recipient['phone'], $message);
        
        if ($result['success']) {
            $results['success']++;
        } else {
            $results['failed']++;
        }
        
        $results['details'][] = array(
            'phone' => $recipient['phone'],
            'result' => $result
        );
        
        // Random delay between messages (Anti-Spam)
        if ($index < count($recipients) - 1) { // Don't delay after last message
            $delay = getRandomDelay();
            sleep($delay);
        }
    }
    
    $results['quota_after'] = getRemainingQuota();
    
    return $results;
}

/**
 * Prepare bulk send - returns estimated time and checks quota
 */
function prepareBulkSend($count) {
    $settings = getFonnteSettings();
    $quota = getRemainingQuota();
    
    $delayMin = isset($settings['antispam_delay_min']) ? $settings['antispam_delay_min'] : ANTISPAM_DELAY_MIN;
    $delayMax = isset($settings['antispam_delay_max']) ? $settings['antispam_delay_max'] : ANTISPAM_DELAY_MAX;
    $avgDelay = ($delayMin + $delayMax) / 2;
    
    $estimatedTime = $count * $avgDelay; // seconds
    $estimatedMinutes = ceil($estimatedTime / 60);
    
    $canSendNow = min($count, $quota['hourly_remaining'], $quota['daily_remaining']);
    
    return array(
        'requested' => $count,
        'can_send_now' => $canSendNow,
        'hourly_remaining' => $quota['hourly_remaining'],
        'daily_remaining' => $quota['daily_remaining'],
        'estimated_seconds' => $estimatedTime,
        'estimated_minutes' => $estimatedMinutes,
        'delay_range' => $delayMin . '-' . $delayMax . ' detik',
        'warning' => ($canSendNow < $count) ? 'Kuota tidak mencukupi. Hanya ' . $canSendNow . ' pesan yang dapat dikirim.' : null,
        'safe_to_send' => ($canSendNow >= $count)
    );
}

/**
 * Format phone number to international format
 */
function formatPhoneNumber($phone) {
    // Remove spaces, dashes, parentheses
    $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
    
    // If starts with 0, replace with 62
    if (substr($phone, 0, 1) === '0') {
        $phone = '62' . substr($phone, 1);
    }
    
    // If starts with +, remove it
    if (substr($phone, 0, 1) === '+') {
        $phone = substr($phone, 1);
    }
    
    // If doesn't start with 62, add it
    if (substr($phone, 0, 2) !== '62') {
        $phone = '62' . $phone;
    }
    
    return $phone;
}

/**
 * Log Fonnte message
 */
function logFonnteMessage($phone, $message, $response, $httpCode) {
    $logs = getFonnteLogs();
    
    $log = array(
        'id' => 'LOG-' . date('YmdHis') . '-' . rand(1000, 9999),
        'phone' => $phone,
        'message' => substr($message, 0, 100) . (strlen($message) > 100 ? '...' : ''),
        'full_message' => $message,
        'response' => $response,
        'http_code' => $httpCode,
        'status' => (isset($response['status']) && $response['status'] === true) ? 'sent' : 'failed',
        'created_at' => date('Y-m-d H:i:s')
    );
    
    // Keep only last 500 logs
    array_unshift($logs, $log);
    $logs = array_slice($logs, 0, 500);
    
    file_put_contents(FONNTE_LOG_FILE, json_encode($logs, JSON_PRETTY_PRINT));
    
    return $log;
}

/**
 * Get Fonnte logs
 */
function getFonnteLogs($limit = 50) {
    if (!file_exists(FONNTE_LOG_FILE)) {
        return array();
    }
    $data = file_get_contents(FONNTE_LOG_FILE);
    $logs = json_decode($data, true) ?: array();
    return array_slice($logs, 0, $limit);
}

/**
 * Test Fonnte connection
 */
function testFonnteConnection($apiToken) {
    $curl = curl_init();
    
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.fonnte.com/device',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_HTTPHEADER => array(
            'Authorization: ' . $apiToken
        ),
    ));
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    
    if ($error) {
        return array(
            'success' => false,
            'message' => 'Connection error: ' . $error,
            'status' => 'error'
        );
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['status']) && $result['status'] === true) {
        return array(
            'success' => true,
            'message' => 'Terhubung ke Fonnte',
            'status' => 'connected',
            'device' => $result['device'] ?? null,
            'detail' => $result
        );
    }
    
    return array(
        'success' => false,
        'message' => $result['reason'] ?? $result['message'] ?? 'Token tidak valid',
        'status' => 'invalid',
        'detail' => $result
    );
}

/**
 * Replace template variables
 */
function replaceTemplateVariables($template, $data) {
    $replacements = array(
        '{nama}' => $data['nama'] ?? '',
        '{nominal}' => $data['nominal'] ?? '',
        '{tanggal}' => $data['tanggal'] ?? '',
        '{hari}' => $data['hari'] ?? '',
        '{periode}' => $data['periode'] ?? '',
        '{username}' => $data['username'] ?? '',
        '{pengirim}' => $data['pengirim'] ?? 'MIKPAY WiFi'
    );
    
    return str_replace(array_keys($replacements), array_values($replacements), $template);
}

/**
 * Get appropriate template based on status
 */
function getFonnteTemplate($status) {
    $settings = getFonnteSettings();
    
    switch ($status) {
        case 'due_soon':
        case 'due_h3':
            return $settings['template_reminder'];
        case 'due_today':
            return $settings['template_due_today'];
        case 'overdue':
            return $settings['template_overdue'];
        default:
            return $settings['template_reminder'];
    }
}
