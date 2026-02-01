<?php
/*
 * CSRF Protection Helper
 * Functions untuk generate dan validate CSRF tokens
 */

/**
 * Generate CSRF token
 * @return string - CSRF token
 */
function generateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 * @param string $token - Token to validate
 * @return bool - True if token is valid
 */
function validateCSRFToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF token for form
 * @return string - HTML input hidden field with token
 */
function getCSRFTokenField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Validate CSRF token from POST request
 * @return bool - True if valid, false otherwise
 */
function validateCSRFPost() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return true; // GET requests don't need CSRF
    }
    
    if (!isset($_POST['csrf_token'])) {
        return false;
    }
    
    return validateCSRFToken($_POST['csrf_token']);
}
