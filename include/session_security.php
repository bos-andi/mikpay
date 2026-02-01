<?php
/*
 * Session Security Helper
 * Functions untuk secure session management
 */

/**
 * Initialize secure session
 */
function initSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Set secure session parameters
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');
        
        session_start();
    }
}

/**
 * Regenerate session ID (call after login)
 */
function regenerateSessionID() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
}

/**
 * Check session validity
 * @param int $timeout - Session timeout in seconds (default: 3600 = 1 hour)
 * @return bool - True if session is valid
 */
function checkSessionValidity($timeout = 3600) {
    if (session_status() === PHP_SESSION_NONE) {
        return false;
    }
    
    // Check timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        return false;
    }
    
    // Check IP address (optional, can be disabled for users behind proxy)
    if (isset($_SESSION['ip_address']) && !empty($_SESSION['ip_address'])) {
        $currentIP = $_SERVER['REMOTE_ADDR'] ?? '';
        // Allow IP change for users behind proxy (check first 2 octets)
        $sessionIPParts = explode('.', $_SESSION['ip_address']);
        $currentIPParts = explode('.', $currentIP);
        if (count($sessionIPParts) >= 2 && count($currentIPParts) >= 2) {
            // If first 2 octets are different, might be different network
            if ($sessionIPParts[0] !== $currentIPParts[0] || $sessionIPParts[1] !== $currentIPParts[1]) {
                // Log but don't block (for proxy users)
                error_log("Session IP changed: " . $_SESSION['ip_address'] . " -> " . $currentIP);
            }
        }
    }
    
    // Check user agent
    if (isset($_SESSION['user_agent'])) {
        $currentUA = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if ($_SESSION['user_agent'] !== $currentUA) {
            // User agent changed, might be session hijacking
            return false;
        }
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
    
    return true;
}

/**
 * Destroy session securely
 */
function destroySecureSession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = array();
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
    }
}
