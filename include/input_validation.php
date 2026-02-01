<?php
/*
 * Input Validation Helper
 * Functions untuk sanitize dan validate user input
 */

/**
 * Sanitize input based on type
 * @param mixed $input - Input to sanitize
 * @param string $type - Type of input (string, int, email, url, alphanumeric)
 * @return mixed - Sanitized input
 */
function sanitizeInput($input, $type = 'string') {
    if ($input === null || $input === '') {
        return '';
    }
    
    switch($type) {
        case 'string':
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        case 'int':
            return intval($input);
        case 'float':
            return floatval($input);
        case 'email':
            $email = filter_var(trim($input), FILTER_SANITIZE_EMAIL);
            return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
        case 'url':
            return filter_var(trim($input), FILTER_SANITIZE_URL);
        case 'alphanumeric':
            return preg_replace('/[^a-zA-Z0-9_]/', '', $input);
        case 'session_name':
            // Only allow alphanumeric, underscore, and dash
            return preg_replace('/[^a-zA-Z0-9_-]/', '', $input);
        default:
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Validate session name format
 * @param string $session - Session name to validate
 * @return bool - True if valid
 */
function validateSessionName($session) {
    // Only allow alphanumeric, underscore, and dash
    return preg_match('/^[a-zA-Z0-9_-]+$/', $session) && strlen($session) <= 100;
}

/**
 * Validate username format
 * @param string $username - Username to validate
 * @return bool - True if valid
 */
function validateUsername($username) {
    // Only allow alphanumeric, underscore, and dash
    // Length: 3-50 characters
    return preg_match('/^[a-zA-Z0-9_-]{3,50}$/', $username);
}

/**
 * Validate password strength
 * @param string $password - Password to validate
 * @param int $minLength - Minimum length (default: 4)
 * @return array - ['valid' => bool, 'message' => string]
 */
function validatePasswordStrength($password, $minLength = 4) {
    if (strlen($password) < $minLength) {
        return ['valid' => false, 'message' => "Password minimal {$minLength} karakter"];
    }
    
    return ['valid' => true, 'message' => ''];
}

/**
 * Sanitize GET parameter
 * @param string $key - Parameter key
 * @param string $type - Type of input
 * @param mixed $default - Default value if not set
 * @return mixed - Sanitized value
 */
function getSanitized($key, $type = 'string', $default = '') {
    if (!isset($_GET[$key])) {
        return $default;
    }
    return sanitizeInput($_GET[$key], $type);
}

/**
 * Sanitize POST parameter
 * @param string $key - Parameter key
 * @param string $type - Type of input
 * @param mixed $default - Default value if not set
 * @return mixed - Sanitized value
 */
function postSanitized($key, $type = 'string', $default = '') {
    if (!isset($_POST[$key])) {
        return $default;
    }
    return sanitizeInput($_POST[$key], $type);
}
