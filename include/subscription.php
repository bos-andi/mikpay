<?php
/*
 * MIKPAY Subscription System
 * Mengelola status langganan aplikasi
 */

// File untuk menyimpan data langganan
if (!defined('SUBSCRIPTION_FILE')) {
    define('SUBSCRIPTION_FILE', __DIR__ . '/subscription.json');
}
if (!defined('USERS_FILE')) {
    define('USERS_FILE', __DIR__ . '/users.json');
}

// Paket langganan dengan fitur detail
$subscriptionPackages = array(
    'monthly' => array(
        'name' => 'Paket 1 Bulan',
        'price' => 50000,
        'duration' => 30,
        'max_customers' => -1, // unlimited
        'max_routers' => -1,
        'max_users' => -1,
        'features' => array(
            'Dashboard',
            'PPP Secrets',
            'PPP Profiles',
            'PPP Active',
            'Quick Print',
            'Tagihan WiFi (Billing)',
            'Laporan Keuangan',
            'Hotspot Management',
            'Voucher System',
            'DHCP Leases',
            'WhatsApp API (Fonnte)',
            'Reminder Otomatis',
            'Traffic Monitor',
            'Log Activity',
            'Multi-User Admin',
            'Unlimited Router',
            'Unlimited User Admin',
            'API Access',
            'White Label',
            'Custom Domain',
            'Backup Otomatis'
        ),
        'disabled_features' => array(),
        'support' => 'Email + WhatsApp (24 jam)',
        'color' => '#4D44B5',
        'discount' => null,
        'original_price' => null
    ),
    'monthly5' => array(
        'name' => 'Paket 5 Bulan',
        'price' => 200000,
        'duration' => 150, // 5 bulan = 150 hari
        'max_customers' => -1, // unlimited
        'max_routers' => -1,
        'max_users' => -1,
        'features' => array(
            'Dashboard',
            'PPP Secrets',
            'PPP Profiles',
            'PPP Active',
            'Quick Print',
            'Tagihan WiFi (Billing)',
            'Laporan Keuangan',
            'Hotspot Management',
            'Voucher System',
            'DHCP Leases',
            'WhatsApp API (Fonnte)',
            'Reminder Otomatis',
            'Traffic Monitor',
            'Log Activity',
            'Multi-User Admin',
            'Unlimited Router',
            'Unlimited User Admin',
            'API Access',
            'White Label',
            'Custom Domain',
            'Backup Otomatis'
        ),
        'disabled_features' => array(),
        'support' => 'Email + WhatsApp (24 jam)',
        'color' => '#f97316',
        'discount' => 50000, // Diskon dari 250.000 (5x50.000) menjadi 200.000
        'original_price' => 250000
    )
);

/**
 * Get subscription data
 */
function getSubscription() {
    if (!file_exists(SUBSCRIPTION_FILE)) {
        // Default: trial 7 hari dari sekarang
        $defaultSub = array(
            'status' => 'trial',
            'package' => 'trial',
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+7 days')),
            'payment_history' => array()
        );
        saveSubscription($defaultSub);
        return $defaultSub;
    }
    
    $data = file_get_contents(SUBSCRIPTION_FILE);
    return json_decode($data, true);
}

/**
 * Save subscription data
 */
function saveSubscription($data) {
    file_put_contents(SUBSCRIPTION_FILE, json_encode($data, JSON_PRETTY_PRINT));
}

/**
 * Check if subscription is active
 */
function isSubscriptionActive() {
    $sub = getSubscription();
    $today = date('Y-m-d');
    $endDate = $sub['end_date'] ?? '2000-01-01';
    
    return ($today <= $endDate);
}

/**
 * Get remaining days
 */
function getRemainingDays() {
    $sub = getSubscription();
    $today = new DateTime();
    $endDate = new DateTime($sub['end_date'] ?? date('Y-m-d'));
    $diff = $today->diff($endDate);
    
    if ($diff->invert) {
        return -$diff->days;
    }
    return $diff->days;
}

/**
 * Activate subscription after payment
 */
function activateSubscription($package, $transactionId = '') {
    global $subscriptionPackages;
    
    if (!isset($subscriptionPackages[$package])) {
        return false;
    }
    
    $pkg = $subscriptionPackages[$package];
    $sub = getSubscription();
    
    // Jika masih aktif, tambahkan ke end_date yang ada
    $startDate = date('Y-m-d');
    if (isSubscriptionActive()) {
        $baseDate = $sub['end_date'];
    } else {
        $baseDate = $startDate;
    }
    
    $endDate = date('Y-m-d', strtotime($baseDate . ' + ' . $pkg['duration'] . ' days'));
    
    $sub['status'] = 'active';
    $sub['package'] = $package;
    $sub['start_date'] = $startDate;
    $sub['end_date'] = $endDate;
    $sub['payment_history'][] = array(
        'date' => date('Y-m-d H:i:s'),
        'package' => $package,
        'amount' => $pkg['price'],
        'transaction_id' => $transactionId
    );
    
    saveSubscription($sub);
    return true;
}

/**
 * Get subscription status text
 */
function getSubscriptionStatusText() {
    $sub = getSubscription();
    $remaining = getRemainingDays();
    
    if ($remaining < 0) {
        return '<span class="sub-expired">Expired ' . abs($remaining) . ' hari lalu</span>';
    } elseif ($remaining <= 7) {
        return '<span class="sub-warning">Tersisa ' . $remaining . ' hari</span>';
    } else {
        return '<span class="sub-active">Aktif - ' . $remaining . ' hari tersisa</span>';
    }
}

/**
 * Check if a feature is available for current subscription
 */
function isFeatureAvailable($feature) {
    global $subscriptionPackages;
    $sub = getSubscription();
    
    if (!isSubscriptionActive()) {
        return false;
    }
    
    $package = isset($sub['package']) ? $sub['package'] : 'starter';
    if (!isset($subscriptionPackages[$package])) {
        return false;
    }
    
    $disabled = $subscriptionPackages[$package]['disabled_features'];
    return !in_array($feature, $disabled);
}

/**
 * Get current package info
 */
function getCurrentPackageInfo() {
    global $subscriptionPackages;
    $sub = getSubscription();
    $package = isset($sub['package']) ? $sub['package'] : 'starter';
    
    if (isset($subscriptionPackages[$package])) {
        return $subscriptionPackages[$package];
    }
    return $subscriptionPackages['starter'];
}

/**
 * Get all subscription packages
 */
function getSubscriptionPackages() {
    global $subscriptionPackages;
    return $subscriptionPackages;
}

// =============================================
// USER MANAGEMENT FUNCTIONS
// =============================================

/**
 * Get sessions from config.php
 */
function getSessionsFromConfig() {
    $configFile = __DIR__ . '/config.php';
    $sessions = array();
    
    if (!file_exists($configFile)) {
        return $sessions;
    }
    
    $lines = file($configFile);
    foreach ($lines as $line) {
        // Match pattern: $data['SESSION_NAME']
        if (preg_match("/\\\$data\['([^']+)'\]/", $line, $matches)) {
            $sessionName = $matches[1];
            if ($sessionName !== 'mikpay') { // Skip admin config
                // Extract session details
                $sessionData = array(
                    'id' => $sessionName,
                    'name' => $sessionName,
                    'source' => 'config'
                );
                
                // Try to extract router name from the line
                // Format: SESSION%Router Name
                if (preg_match("/'[^']+%([^']+)'/", $line, $nameMatch)) {
                    $sessionData['router_name'] = $nameMatch[1];
                    $sessionData['name'] = $nameMatch[1];
                }
                
                // Try to extract IP/host
                // Format: SESSION!ip:port
                if (preg_match("/'[^']+!([^']+)'/", $line, $ipMatch)) {
                    $sessionData['host'] = $ipMatch[1];
                }
                
                $sessions[$sessionName] = $sessionData;
            }
        }
    }
    
    return $sessions;
}

/**
 * Get all users (merged from config.php sessions and users.json)
 */
function getAllUsers() {
    // Get sessions from config.php
    $configSessions = getSessionsFromConfig();
    
    // Get users from JSON file
    $jsonUsers = array();
    if (file_exists(USERS_FILE)) {
        $data = file_get_contents(USERS_FILE);
        $result = json_decode($data, true);
        if (is_array($result)) {
            foreach ($result as $user) {
                $jsonUsers[$user['id']] = $user;
            }
        }
    }
    
    // Merge: config sessions + json user data
    $allUsers = array();
    foreach ($configSessions as $sessionId => $sessionData) {
        if (isset($jsonUsers[$sessionId])) {
            // Merge with existing JSON data
            $allUsers[] = array_merge($sessionData, $jsonUsers[$sessionId]);
        } else {
            // New session from config, add default data
            $allUsers[] = array(
                'id' => $sessionId,
                'name' => isset($sessionData['name']) ? $sessionData['name'] : $sessionId,
                'router_name' => isset($sessionData['router_name']) ? $sessionData['router_name'] : $sessionId,
                'host' => isset($sessionData['host']) ? $sessionData['host'] : '',
                'email' => '',
                'phone' => '',
                'package' => 'starter',
                'status' => 'active',
                'source' => 'config',
                'created_at' => date('Y-m-d H:i:s')
            );
        }
    }
    
    // Add users from JSON that are not in config (manually added)
    foreach ($jsonUsers as $userId => $userData) {
        if (!isset($configSessions[$userId])) {
            $allUsers[] = $userData;
        }
    }
    
    return $allUsers;
}

/**
 * Save all users
 */
function saveAllUsers($users) {
    file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT));
}

/**
 * Add or update user
 */
function saveUser($userId, $userData) {
    $users = getAllUsers();
    
    $userData['id'] = $userId;
    $userData['updated_at'] = date('Y-m-d H:i:s');
    
    // Find existing user
    $found = false;
    foreach ($users as &$user) {
        if ($user['id'] === $userId) {
            $user = array_merge($user, $userData);
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        $userData['created_at'] = date('Y-m-d H:i:s');
        $users[] = $userData;
    }
    
    saveAllUsers($users);
    return true;
}

/**
 * Get user by ID
 */
function getUser($userId) {
    $users = getAllUsers();
    foreach ($users as $user) {
        if ($user['id'] === $userId) {
            return $user;
        }
    }
    return null;
}

/**
 * Get JSON users only (for saving)
 */
function getJsonUsers() {
    if (!file_exists(USERS_FILE)) {
        return array();
    }
    $data = file_get_contents(USERS_FILE);
    $result = json_decode($data, true);
    return is_array($result) ? $result : array();
}

/**
 * Activate user
 */
function activateUser($userId) {
    $jsonUsers = getJsonUsers();
    $found = false;
    
    foreach ($jsonUsers as &$user) {
        if ($user['id'] === $userId) {
            $user['status'] = 'active';
            $user['deactivated_at'] = null;
            $user['deactivated_reason'] = null;
            $user['updated_at'] = date('Y-m-d H:i:s');
            $found = true;
            break;
        }
    }
    
    // If not found in JSON, add it
    if (!$found) {
        $jsonUsers[] = array(
            'id' => $userId,
            'status' => 'active',
            'updated_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s')
        );
    }
    
    saveAllUsers($jsonUsers);
    return true;
}

/**
 * Deactivate user
 */
function deactivateUser($userId, $reason = '') {
    $jsonUsers = getJsonUsers();
    $found = false;
    
    foreach ($jsonUsers as &$user) {
        if ($user['id'] === $userId) {
            $user['status'] = 'inactive';
            $user['deactivated_at'] = date('Y-m-d H:i:s');
            $user['deactivated_reason'] = $reason;
            $user['updated_at'] = date('Y-m-d H:i:s');
            $found = true;
            break;
        }
    }
    
    // If not found in JSON (from config.php), add it with inactive status
    if (!$found) {
        $jsonUsers[] = array(
            'id' => $userId,
            'status' => 'inactive',
            'deactivated_at' => date('Y-m-d H:i:s'),
            'deactivated_reason' => $reason,
            'updated_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s')
        );
    }
    
    saveAllUsers($jsonUsers);
    return true;
}

/**
 * Delete user
 */
function deleteUser($userId) {
    $users = getAllUsers();
    $newUsers = array();
    foreach ($users as $user) {
        if ($user['id'] !== $userId) {
            $newUsers[] = $user;
        }
    }
    saveAllUsers($newUsers);
    return true;
}

/**
 * Check if user is active
 */
function isUserActive($userId) {
    $user = getUser($userId);
    if ($user === null) {
        return true; // Default active if not in list
    }
    return (isset($user['status']) ? $user['status'] : 'active') === 'active';
}

/**
 * Get user statistics
 */
function getUserStats() {
    $users = getAllUsers();
    $stats = array(
        'total' => count($users),
        'active' => 0,
        'inactive' => 0
    );
    
    foreach ($users as $user) {
        $status = isset($user['status']) ? $user['status'] : 'active';
        if ($status === 'active') {
            $stats['active']++;
        } else {
            $stats['inactive']++;
        }
    }
    
    return $stats;
}

/**
 * Extend subscription for user
 */
function extendUserSubscription($userId, $days) {
    $sub = getSubscription();
    
    if (isSubscriptionActive()) {
        $baseDate = $sub['end_date'];
    } else {
        $baseDate = date('Y-m-d');
    }
    
    $sub['end_date'] = date('Y-m-d', strtotime($baseDate . ' + ' . $days . ' days'));
    $sub['status'] = 'active';
    $sub['updated_at'] = date('Y-m-d H:i:s');
    
    saveSubscription($sub);
    return true;
}

/**
 * Set user package manually
 */
function setUserPackage($package, $days = 30) {
    global $subscriptionPackages;
    
    if (!isset($subscriptionPackages[$package])) {
        return false;
    }
    
    $sub = getSubscription();
    $sub['status'] = 'active';
    $sub['package'] = $package;
    $sub['start_date'] = date('Y-m-d');
    $sub['end_date'] = date('Y-m-d', strtotime('+' . $days . ' days'));
    $sub['updated_at'] = date('Y-m-d H:i:s');
    
    saveSubscription($sub);
    return true;
}
