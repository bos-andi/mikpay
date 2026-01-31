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
        return null; // Return null instead of throwing to allow fallback
    }
    
    if ($conn === null) {
        try {
            // Check if constants are defined
            if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER')) {
                $error = 'Database configuration tidak lengkap';
                return null;
            }
            
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . (defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4');
            $options = array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 5,
            );
            
            $dbPass = defined('DB_PASS') ? DB_PASS : '';
            $conn = new PDO($dsn, DB_USER, $dbPass, $options);
            initDatabase(); // Ensure tables exist on first connection
        } catch (PDOException $e) {
            $error = $e->getMessage();
            return null; // Return null to allow fallback to old system
        } catch (Exception $e) {
            $error = $e->getMessage();
            return null;
        }
    }
    
    return $conn;
}

/**
 * Initialize database tables
 */
function initDatabase() {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    try {
        // Create users table
        $sqlUsers = "CREATE TABLE IF NOT EXISTS `users` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `username` VARCHAR(100) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `email` VARCHAR(255) NULL,
            `full_name` VARCHAR(255) NULL,
            `phone` VARCHAR(50) NULL,
            `role` ENUM('superadmin','admin','user') NOT NULL DEFAULT 'user',
            `status` ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
            `trial_started` DATE NULL,
            `trial_ends` DATE NULL,
            `subscription_package` VARCHAR(50) NULL,
            `subscription_start` DATE NULL,
            `subscription_end` DATE NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `last_login` DATETIME NULL,
            PRIMARY KEY (`id`),
            INDEX `idx_username` (`username`),
            INDEX `idx_status` (`status`),
            INDEX `idx_role` (`role`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $conn->exec($sqlUsers);

        // Create user_sessions table (MikroTik router sessions per user)
        $sqlSessions = "CREATE TABLE IF NOT EXISTS `user_sessions` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `user_id` INT(11) NOT NULL,
            `session_name` VARCHAR(100) NOT NULL,
            `router_name` VARCHAR(255) NULL,
            `host` VARCHAR(255) NOT NULL,
            `username` VARCHAR(100) NOT NULL,
            `password` VARCHAR(255) NOT NULL,
            `currency` VARCHAR(10) NULL,
            `currency_position` INT(11) NULL,
            `expiry_mode` INT(11) NULL,
            `expiry_days` INT(11) NULL,
            `domain` VARCHAR(255) NULL,
            `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_user_session` (`user_id`, `session_name`),
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            INDEX `idx_user_id` (`user_id`),
            INDEX `idx_session_name` (`session_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $conn->exec($sqlSessions);

        // Migrate superadmin from superadmin.php if exists
        migrateSuperAdmin();
        
        return true;
    } catch (PDOException $e) {
        error_log("Database init error: " . $e->getMessage());
        return false;
    }
}

/**
 * Migrate superadmin from superadmin.php to database
 */
function migrateSuperAdmin() {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    try {
        // Check if superadmin already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE role = 'superadmin' LIMIT 1");
        $stmt->execute();
        if ($stmt->fetch()) {
            return true; // Superadmin already exists
        }
        
        // Load superadmin from superadmin.php
        if (file_exists(__DIR__ . '/superadmin.php')) {
            // Read superadmin credentials
            $superadminFile = file_get_contents(__DIR__ . '/superadmin.php');
            
            // Extract email and password
            $email = 'ndiandie@gmail.com'; // Default from superadmin.php
            $password = 'MikPayandidev.id'; // Default from superadmin.php
            
            if (preg_match("/define\('SUPERADMIN_EMAIL',\s*'([^']+)'/", $superadminFile, $emailMatch)) {
                $email = $emailMatch[1];
            }
            if (preg_match("/password\s*===\s*'([^']+)'/", $superadminFile, $passMatch)) {
                $password = $passMatch[1];
            }
            
            // Create superadmin user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, email, full_name, role, status, subscription_package, subscription_start, subscription_end) VALUES (?, ?, ?, ?, 'superadmin', 'active', 'enterprise', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 365 DAY))");
            $stmt->execute(['superadmin', $hashedPassword, $email, 'Super Administrator']);
            
            return true;
        }
    } catch (PDOException $e) {
        error_log("Superadmin migration error: " . $e->getMessage());
        return false;
    }
    
    return false;
}

/**
 * Verify user login
 */
function verifyUser($username, $password) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    try {
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
    } catch (PDOException $e) {
        error_log("User verification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user by ID
 */
function getUserById($userId) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get user error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user by username
 */
function getUserByUsername($username) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get user error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all users (for admin)
 */
function getAllUsers($limit = 100, $offset = 0) {
    $conn = getDBConnection();
    if (!$conn) return array();
    
    try {
        $stmt = $conn->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Get all users error: " . $e->getMessage());
        return array();
    }
}

/**
 * Create new user
 */
function createUser($username, $password, $email = '', $fullName = '', $phone = '', $role = 'user') {
    $conn = getDBConnection();
    if (!$conn) return array('success' => false, 'message' => 'Database connection failed');
    
    try {
        // Check if username exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            return array('success' => false, 'message' => 'Username sudah digunakan');
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Set trial period (5 days)
        $trialStart = date('Y-m-d');
        $trialEnd = date('Y-m-d', strtotime('+5 days'));
        
        // Insert user
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, full_name, phone, role, trial_started, trial_ends, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt->execute([$username, $hashedPassword, $email, $fullName, $phone, $role, $trialStart, $trialEnd]);
        $userId = $conn->lastInsertId();
        
        return array('success' => true, 'message' => 'User berhasil dibuat', 'user_id' => $userId);
    } catch (PDOException $e) {
        error_log("Create user error: " . $e->getMessage());
        return array('success' => false, 'message' => 'Gagal membuat user: ' . $e->getMessage());
    }
}

/**
 * Update user
 */
function updateUser($userId, $data) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    try {
        $updates = array();
        $values = array();
        
        $allowedFields = array('email', 'full_name', 'phone', 'role', 'status', 'subscription_package', 'subscription_start', 'subscription_end', 'trial_ends');
        
        foreach ($data as $key => $value) {
            if ($key === 'password') {
                $updates[] = "`password` = ?";
                $values[] = password_hash($value, PASSWORD_DEFAULT);
            } elseif (in_array($key, $allowedFields)) {
                $updates[] = "`{$key}` = ?";
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
    } catch (PDOException $e) {
        error_log("Update user error: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete user
 */
function deleteUser($userId) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    try {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'superadmin'");
        return $stmt->execute([$userId]);
    } catch (PDOException $e) {
        error_log("Delete user error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user routers (sessions)
 */
function getUserRouters($userId) {
    $conn = getDBConnection();
    if (!$conn) return array();
    
    try {
        $stmt = $conn->prepare("SELECT * FROM user_sessions WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Get user routers error: " . $e->getMessage());
        return array();
    }
}

/**
 * Save user router (session)
 */
function saveUserRouter($userId, $sessionName, $routerName, $host, $username, $password, $currency = 'Rp', $currencyPosition = 10, $expiryMode = 1, $expiryDays = 10, $domain = '', $status = 'active') {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    try {
        // Check if session already exists
        $stmt = $conn->prepare("SELECT id FROM user_sessions WHERE user_id = ? AND session_name = ?");
        $stmt->execute([$userId, $sessionName]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing
            $stmt = $conn->prepare("UPDATE user_sessions SET router_name = ?, host = ?, username = ?, password = ?, currency = ?, currency_position = ?, expiry_mode = ?, expiry_days = ?, domain = ?, status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$routerName, $host, $username, $password, $currency, $currencyPosition, $expiryMode, $expiryDays, $domain, $status, $existing['id']]);
        } else {
            // Insert new
            $stmt = $conn->prepare("INSERT INTO user_sessions (user_id, session_name, router_name, host, username, password, currency, currency_position, expiry_mode, expiry_days, domain, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $sessionName, $routerName, $host, $username, $password, $currency, $currencyPosition, $expiryMode, $expiryDays, $domain, $status]);
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Save user router error: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete user router
 */
function deleteUserRouter($userId, $sessionName) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    try {
        $stmt = $conn->prepare("DELETE FROM user_sessions WHERE user_id = ? AND session_name = ?");
        return $stmt->execute([$userId, $sessionName]);
    } catch (PDOException $e) {
        error_log("Delete user router error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user router by session name
 */
function getUserRouterBySession($userId, $sessionName) {
    $conn = getDBConnection();
    if (!$conn) return false;
    
    try {
        $stmt = $conn->prepare("SELECT * FROM user_sessions WHERE user_id = ? AND session_name = ?");
        $stmt->execute([$userId, $sessionName]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get user router error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user subscription is active
 */
function isUserSubscriptionActive($userId) {
    $user = getUserById($userId);
    if (!$user) return false;
    
    // Check trial
    if ($user['trial_ends'] && date('Y-m-d') <= $user['trial_ends']) {
        return true;
    }
    
    // Check subscription
    if ($user['subscription_end'] && date('Y-m-d') <= $user['subscription_end']) {
        return true;
    }
    
    return false;
}

/**
 * Check if user is superadmin or admin
 */
function isAdmin($userId) {
    $user = getUserById($userId);
    if (!$user) return false;
    return in_array($user['role'], array('superadmin', 'admin'));
}

/**
 * Check if user is superadmin
 */
function isSuperAdmin($userId) {
    $user = getUserById($userId);
    if (!$user) return false;
    return $user['role'] === 'superadmin';
}

?>
