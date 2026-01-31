<?php
/*
 * MIKPAY Database Connection
 * Koneksi ke database MySQL untuk sistem multi-user
 */

// Database configuration
// Edit sesuai dengan konfigurasi database Anda
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'mikpay');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_CHARSET', 'utf8mb4');
}

/**
 * Get database connection
 */
function getDBConnection() {
    static $conn = null;
    static $error = null;
    
    // Return cached error if connection already failed
    if ($error !== null) {
        throw new Exception($error);
    }
    
    if ($conn === null) {
        try {
            // Check if constants are defined
            if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER')) {
                $error = 'Database configuration tidak lengkap. Edit include/database.php dan set DB_HOST, DB_NAME, DB_USER, DB_PASS.';
                throw new Exception($error);
            }
            
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . (defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4');
            $options = array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 5, // 5 second timeout
            );
            
            $dbPass = defined('DB_PASS') ? DB_PASS : '';
            $conn = new PDO($dsn, DB_USER, $dbPass, $options);
        } catch (PDOException $e) {
            $errorMsg = "Database connection failed: " . $e->getMessage();
            $error = $errorMsg;
            throw new Exception($errorMsg);
        } catch (Exception $e) {
            $error = $e->getMessage();
            throw $e;
        }
    }
    
    return $conn;
}

/**
 * Initialize database tables
 */
function initDatabase() {
    try {
        $conn = getDBConnection();
    } catch (Exception $e) {
        // Database connection failed, can't initialize
        return false;
    }
    
    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS `users` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `username` VARCHAR(100) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `email` VARCHAR(100) DEFAULT NULL,
        `full_name` VARCHAR(200) DEFAULT NULL,
        `phone` VARCHAR(20) DEFAULT NULL,
        `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
        `role` ENUM('admin', 'user') DEFAULT 'user',
        `trial_started` DATE DEFAULT NULL,
        `trial_ends` DATE DEFAULT NULL,
        `subscription_package` VARCHAR(50) DEFAULT NULL,
        `subscription_start` DATE DEFAULT NULL,
        `subscription_end` DATE DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `last_login` DATETIME DEFAULT NULL,
        PRIMARY KEY (`id`),
        INDEX `idx_username` (`username`),
        INDEX `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    try {
        $conn->exec($sql);
    } catch (PDOException $e) {
        error_log("Database init error (users table): " . $e->getMessage());
        return false;
    }
    
    // Create user_sessions table (untuk menyimpan session MikroTik per user)
    $sql2 = "CREATE TABLE IF NOT EXISTS `user_sessions` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `user_id` INT(11) NOT NULL,
        `session_name` VARCHAR(100) NOT NULL,
        `router_name` VARCHAR(200) DEFAULT NULL,
        `router_ip` VARCHAR(50) DEFAULT NULL,
        `router_port` INT(5) DEFAULT 8728,
        `router_username` VARCHAR(100) DEFAULT NULL,
        `router_password` TEXT DEFAULT NULL,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        INDEX `idx_user_id` (`user_id`),
        INDEX `idx_session_name` (`session_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    try {
        $conn->exec($sql2);
        return true;
    } catch (PDOException $e) {
        error_log("Database init error (user_sessions table): " . $e->getMessage());
        return false;
    }
}

/**
 * Register new user
 */
function registerUser($username, $password, $email = '', $fullName = '', $phone = '') {
    try {
        $conn = getDBConnection();
        
        // Check if username exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            return array('success' => false, 'message' => 'Username sudah digunakan');
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        if ($hashedPassword === false) {
            return array('success' => false, 'message' => 'Gagal mengenkripsi password');
        }
        
        // Set trial period (5 days)
        $trialStart = date('Y-m-d');
        $trialEnd = date('Y-m-d', strtotime('+5 days'));
        
        // Insert user
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, full_name, phone, trial_started, trial_ends, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
        
        $stmt->execute([$username, $hashedPassword, $email, $fullName, $phone, $trialStart, $trialEnd]);
        $userId = $conn->lastInsertId();
        
        if (!$userId) {
            return array('success' => false, 'message' => 'Gagal membuat user');
        }
        
        // Create subscription for trial
        if (function_exists('createUserSubscription')) {
            createUserSubscription($userId, 'trial', 5);
        }
        
        return array('success' => true, 'message' => 'Registrasi berhasil', 'user_id' => $userId);
    } catch (PDOException $e) {
        // Return user-friendly error message
        $errorMsg = 'Gagal registrasi';
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            $errorMsg = 'Username sudah digunakan';
        } elseif (strpos($e->getMessage(), 'Connection') !== false) {
            $errorMsg = 'Koneksi database gagal. Silakan hubungi administrator.';
        }
        return array('success' => false, 'message' => $errorMsg);
    } catch (Exception $e) {
        return array('success' => false, 'message' => 'Error: ' . $e->getMessage());
    }
}

/**
 * Verify user login
 */
function verifyUser($username, $password) {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // Update last login
        $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $updateStmt->execute([$user['id']]);
        
        return $user;
    }
    
    return false;
}

/**
 * Get user by ID
 */
function getUserById($userId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

/**
 * Get user by username
 */
function getUserByUsername($username) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch();
}

/**
 * Get all users
 */
function getAllUsers($limit = 100, $offset = 0) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$limit, $offset]);
    return $stmt->fetchAll();
}

/**
 * Update user
 */
function updateUser($userId, $data) {
    $conn = getDBConnection();
    
    $allowedFields = ['email', 'full_name', 'phone', 'status', 'role', 'subscription_package', 'subscription_start', 'subscription_end'];
    $updates = array();
    $values = array();
    
    foreach ($data as $key => $value) {
        if (in_array($key, $allowedFields)) {
            $updates[] = "$key = ?";
            $values[] = $value;
        }
    }
    
    if (empty($updates)) {
        return false;
    }
    
    $values[] = $userId;
    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    return $stmt->execute($values);
}

/**
 * Delete user
 */
function deleteUser($userId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    return $stmt->execute([$userId]);
}

/**
 * Create user subscription (trial or paid)
 */
function createUserSubscription($userId, $package = 'trial', $days = 5) {
    global $subscriptionPackages;
    
    if (!isset($subscriptionPackages)) {
        include_once(__DIR__ . '/subscription.php');
    }
    
    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d', strtotime("+$days days"));
    
    $data = array(
        'subscription_package' => $package,
        'subscription_start' => $startDate,
        'subscription_end' => $endDate
    );
    
    // If trial, also set trial dates
    if ($package == 'trial') {
        $data['trial_started'] = $startDate;
        $data['trial_ends'] = $endDate;
    }
    
    return updateUser($userId, $data);
}

/**
 * Check if user subscription is active
 */
function isUserSubscriptionActive($userId) {
    try {
        $user = getUserById($userId);
        if (!$user) {
            return false;
        }
        
        // Check trial first
        if (!empty($user['trial_ends'])) {
            $today = date('Y-m-d');
            if ($today <= $user['trial_ends']) {
                return true;
            }
        }
        
        // Check subscription
        if (!empty($user['subscription_end'])) {
            $today = date('Y-m-d');
            return ($today <= $user['subscription_end']);
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Subscription check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user's MikroTik sessions
 */
function getUserSessions($userId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM user_sessions WHERE user_id = ? AND is_active = 1 ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/**
 * Add MikroTik session for user
 */
function addUserSession($userId, $sessionName, $routerName, $routerIp, $routerPort, $routerUsername, $routerPassword) {
    $conn = getDBConnection();
    
    // Check if session name already exists for this user
    $stmt = $conn->prepare("SELECT id FROM user_sessions WHERE user_id = ? AND session_name = ?");
    $stmt->execute([$userId, $sessionName]);
    if ($stmt->fetch()) {
        return array('success' => false, 'message' => 'Session name sudah digunakan');
    }
    
    $stmt = $conn->prepare("INSERT INTO user_sessions (user_id, session_name, router_name, router_ip, router_port, router_username, router_password) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    try {
        $stmt->execute([$userId, $sessionName, $routerName, $routerIp, $routerPort, $routerUsername, $routerPassword]);
        return array('success' => true, 'message' => 'Session berhasil ditambahkan', 'id' => $conn->lastInsertId());
    } catch (PDOException $e) {
        return array('success' => false, 'message' => 'Gagal menambahkan session: ' . $e->getMessage());
    }
}

/**
 * Delete user session
 */
function deleteUserSession($sessionId, $userId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("DELETE FROM user_sessions WHERE id = ? AND user_id = ?");
    return $stmt->execute([$sessionId, $userId]);
}

// Initialize database on first load
if (!function_exists('initDatabase')) {
    initDatabase();
}
