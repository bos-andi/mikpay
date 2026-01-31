<?php
/**
 * MIKPAY Fonnte Auto-Send Reminder
 * Script untuk mengirim reminder WhatsApp otomatis via cron job
 * 
 * Cara setup cron:
 * # Jalankan setiap jam (cek reminder H-3, H-0, dan overdue)
 * 0 * * * * /usr/bin/php /var/www/mikpay/cron/fonnte-auto-send.php >> /var/log/mikpay-fonnte-cron.log 2>&1
 * 
 * # Atau jalankan setiap 30 menit
 * */30 * * * * /usr/bin/php /var/www/mikpay/cron/fonnte-auto-send.php >> /var/log/mikpay-fonnte-cron.log 2>&1
 */

// Set working directory
chdir(__DIR__ . '/..');

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/fonnte-auto-send-error.log');

// Log function
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logFile = __DIR__ . '/fonnte-auto-send.log';
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    echo $logMessage;
}

logMessage("=== Fonnte Auto-Send Reminder Started ===");

// Include required files
require_once(__DIR__ . '/../include/fonnte_config.php');
require_once(__DIR__ . '/../ppp/billing-data.php');

// Check if Fonnte is enabled
if (!isFonnteEnabled()) {
    logMessage("Fonnte tidak aktif. Script dihentikan.");
    exit(0);
}

logMessage("Fonnte aktif. Memulai proses auto-send...");

// Get Fonnte settings
$settings = getFonnteSettings();
$sentCount = 0;
$skippedCount = 0;
$errorCount = 0;

// Check schedule (jika schedule enabled)
$scheduleEnabled = isset($settings['schedule_enabled']) ? $settings['schedule_enabled'] : true;
$currentDay = (int)date('N'); // 1=Monday, 7=Sunday
$currentTime = date('H:i');
$scheduleDays = isset($settings['schedule_days']) ? $settings['schedule_days'] : array(1,2,3,4,5,6,7);

// Check if today is in schedule days
$canRunToday = in_array($currentDay, $scheduleDays);

if ($scheduleEnabled && !$canRunToday) {
    $dayNames = array(1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu');
    logMessage("Hari ini (" . $dayNames[$currentDay] . ") tidak dalam jadwal pengiriman. Script dihentikan.");
    exit(0);
}

// Function to check if current time matches schedule
function shouldSendAtTime($scheduleTime, $currentTime, $toleranceMinutes = 5) {
    if (empty($scheduleTime)) return true;
    
    list($scheduleHour, $scheduleMin) = explode(':', $scheduleTime);
    list($currentHour, $currentMin) = explode(':', $currentTime);
    
    $scheduleTotal = (int)$scheduleHour * 60 + (int)$scheduleMin;
    $currentTotal = (int)$currentHour * 60 + (int)$currentMin;
    
    // Check if within tolerance (default 5 menit sebelum/sesudah)
    return abs($currentTotal - $scheduleTotal) <= $toleranceMinutes;
}

// ============================================
// 1. AUTO SEND H-3 (3 hari sebelum jatuh tempo)
// ============================================
$scheduleH3Time = isset($settings['schedule_h3_time']) ? $settings['schedule_h3_time'] : '09:00';
$shouldSendH3 = !$scheduleEnabled || shouldSendAtTime($scheduleH3Time, $currentTime);

if (isset($settings['auto_send_h3']) && $settings['auto_send_h3'] && $shouldSendH3) {
    logMessage("Mengecek reminder H-3...");
    
    $customers = getCustomersNeedingReminder();
    foreach ($customers as $customer) {
        // Hanya kirim jika H-3 (3 hari lagi)
        if ($customer['days_to_due'] === 3 && $customer['status'] === 'due_soon') {
            if (empty($customer['phone'])) {
                logMessage("SKIP: {$customer['customer_name']} - No phone number");
                $skippedCount++;
                continue;
            }
            
            // Get customer billing data
            $customerData = getCustomerBilling($customer['customer_name']);
            $monthlyFee = isset($customerData['monthly_fee']) ? $customerData['monthly_fee'] : 0;
            
            // Prepare message data
            $data = array(
                'nama' => $customer['display_name'] ?: $customer['customer_name'],
                'nominal' => number_format($monthlyFee, 0, ',', '.'),
                'tanggal' => date('d M Y', strtotime($customer['due_date'])),
                'hari' => $customer['days_to_due'],
                'periode' => $customer['period'],
                'username' => $customer['customer_name'],
                'pengirim' => $settings['sender_name'] ?? 'MIKPAY WiFi'
            );
            
            // Get template
            $template = getFonnteTemplate('due_soon');
            
            // Randomize message if enabled
            if (isset($settings['antispam_randomize_message']) && $settings['antispam_randomize_message']) {
                $message = randomizeMessage($template, $data);
            } else {
                $message = replaceTemplateVariables($template, $data);
            }
            
            // Send message
            $result = sendFonnteMessage($customer['phone'], $message);
            
            if ($result['success']) {
                logMessage("SUCCESS H-3: {$customer['customer_name']} ({$customer['phone']})");
                $sentCount++;
            } else {
                logMessage("ERROR H-3: {$customer['customer_name']} - {$result['message']}");
                $errorCount++;
            }
            
            // Delay between messages (anti-spam)
            if (isset($settings['antispam_enabled']) && $settings['antispam_enabled']) {
                $delay = getRandomDelay();
                sleep($delay);
            }
        }
    }
}

// ============================================
// 2. AUTO SEND H-0 (Hari ini jatuh tempo)
// ============================================
$scheduleH0Time = isset($settings['schedule_h0_time']) ? $settings['schedule_h0_time'] : '08:00';
$shouldSendH0 = !$scheduleEnabled || shouldSendAtTime($scheduleH0Time, $currentTime);

if (isset($settings['auto_send_h0']) && $settings['auto_send_h0'] && $shouldSendH0) {
    logMessage("Mengecek reminder H-0 (Due Today)...");
    
    $customers = getCustomersNeedingReminder();
    foreach ($customers as $customer) {
        // Hanya kirim jika due today
        if ($customer['status'] === 'due_today') {
            if (empty($customer['phone'])) {
                logMessage("SKIP: {$customer['customer_name']} - No phone number");
                $skippedCount++;
                continue;
            }
            
            // Get customer billing data
            $customerData = getCustomerBilling($customer['customer_name']);
            $monthlyFee = isset($customerData['monthly_fee']) ? $customerData['monthly_fee'] : 0;
            
            // Prepare message data
            $data = array(
                'nama' => $customer['display_name'] ?: $customer['customer_name'],
                'nominal' => number_format($monthlyFee, 0, ',', '.'),
                'tanggal' => date('d M Y', strtotime($customer['due_date'])),
                'hari' => 0,
                'periode' => $customer['period'],
                'username' => $customer['customer_name'],
                'pengirim' => $settings['sender_name'] ?? 'MIKPAY WiFi'
            );
            
            // Get template
            $template = getFonnteTemplate('due_today');
            
            // Randomize message if enabled
            if (isset($settings['antispam_randomize_message']) && $settings['antispam_randomize_message']) {
                $message = randomizeMessage($template, $data);
            } else {
                $message = replaceTemplateVariables($template, $data);
            }
            
            // Send message
            $result = sendFonnteMessage($customer['phone'], $message);
            
            if ($result['success']) {
                logMessage("SUCCESS H-0: {$customer['customer_name']} ({$customer['phone']})");
                $sentCount++;
            } else {
                logMessage("ERROR H-0: {$customer['customer_name']} - {$result['message']}");
                $errorCount++;
            }
            
            // Delay between messages (anti-spam)
            if (isset($settings['antispam_enabled']) && $settings['antispam_enabled']) {
                $delay = getRandomDelay();
                sleep($delay);
            }
        }
    }
}

// ============================================
// 3. AUTO SEND OVERDUE (Sudah lewat jatuh tempo)
// ============================================
$scheduleOverdueTime = isset($settings['schedule_overdue_time']) ? $settings['schedule_overdue_time'] : '10:00';
$shouldSendOverdue = !$scheduleEnabled || shouldSendAtTime($scheduleOverdueTime, $currentTime);

if (isset($settings['auto_send_overdue']) && $settings['auto_send_overdue'] && $shouldSendOverdue) {
    logMessage("Mengecek reminder Overdue...");
    
    $customers = getOverdueCustomers();
    foreach ($customers as $customer) {
        if (empty($customer['phone'])) {
            logMessage("SKIP: {$customer['customer_name']} - No phone number");
            $skippedCount++;
            continue;
        }
        
        // Get customer billing data
        $customerData = getCustomerBilling($customer['customer_name']);
        $monthlyFee = isset($customerData['monthly_fee']) ? $customerData['monthly_fee'] : 0;
        
        // Prepare message data
        $data = array(
            'nama' => $customer['display_name'] ?: $customer['customer_name'],
            'nominal' => number_format($monthlyFee, 0, ',', '.'),
            'tanggal' => date('d M Y', strtotime($customer['due_date'])),
            'hari' => abs($customer['days_overdue']),
            'periode' => $customer['period'],
            'username' => $customer['customer_name'],
            'pengirim' => $settings['sender_name'] ?? 'MIKPAY WiFi'
        );
        
        // Get template
        $template = getFonnteTemplate('overdue');
        
        // Randomize message if enabled
        if (isset($settings['antispam_randomize_message']) && $settings['antispam_randomize_message']) {
            $message = randomizeMessage($template, $data);
        } else {
            $message = replaceTemplateVariables($template, $data);
        }
        
        // Send message
        $result = sendFonnteMessage($customer['phone'], $message);
        
        if ($result['success']) {
            logMessage("SUCCESS Overdue: {$customer['customer_name']} ({$customer['phone']})");
            $sentCount++;
        } else {
            logMessage("ERROR Overdue: {$customer['customer_name']} - {$result['message']}");
            $errorCount++;
        }
        
        // Delay between messages (anti-spam)
        if (isset($settings['antispam_enabled']) && $settings['antispam_enabled']) {
            $delay = getRandomDelay();
            sleep($delay);
        }
    }
}

// Summary
logMessage("=== Summary ===");
logMessage("Total sent: $sentCount");
logMessage("Total skipped: $skippedCount");
logMessage("Total errors: $errorCount");
logMessage("=== Fonnte Auto-Send Reminder Finished ===");

exit(0);
