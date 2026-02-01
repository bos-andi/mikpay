<?php
/*
 * Router Operation Logger
 * Helper functions for logging router-related operations
 */

/**
 * Log router operation with timestamp
 */
function logRouterOperation($operation, $session, $message, $status = 'info') {
    $logDir = __DIR__ . '/../logs';
    
    // Ensure logs directory exists
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/router_operations.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    $user = isset($_SESSION['mikpay']) ? $_SESSION['mikpay'] : 'unknown';
    
    $logEntry = sprintf(
        "[%s] [%s] [%s] [%s] [%s] %s: %s\n",
        $timestamp,
        strtoupper($status),
        $ip,
        $user,
        $session,
        $operation,
        $message
    );
    
    @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Log router deletion attempt
 */
function logRouterDelete($session, $success, $error = '') {
    if ($success) {
        logRouterOperation('DELETE_ROUTER', $session, 'Router session deleted successfully', 'success');
    } else {
        logRouterOperation('DELETE_ROUTER', $session, 'Failed to delete router: ' . $error, 'error');
    }
}

/**
 * Log router creation attempt
 */
function logRouterCreate($session, $success, $error = '') {
    if ($success) {
        logRouterOperation('CREATE_ROUTER', $session, 'Router session created successfully', 'success');
    } else {
        logRouterOperation('CREATE_ROUTER', $session, 'Failed to create router: ' . $error, 'error');
    }
}

/**
 * Log file permission errors
 */
function logFilePermissionError($file, $operation) {
    $logDir = __DIR__ . '/../logs';
    
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/file_permissions.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    $user = isset($_SESSION['mikpay']) ? $_SESSION['mikpay'] : 'unknown';
    
    $perms = @fileperms($file);
    $permsStr = $perms !== false ? decoct($perms & 0777) : 'unknown';
    $writable = is_writable($file) ? 'yes' : 'no';
    $readable = is_readable($file) ? 'yes' : 'no';
    
    $logEntry = sprintf(
        "[%s] [ERROR] [%s] [%s] File permission issue: %s\n" .
        "  Operation: %s\n" .
        "  File: %s\n" .
        "  Permissions: %s\n" .
        "  Writable: %s\n" .
        "  Readable: %s\n" .
        "  Owner: %s\n" .
        "  Group: %s\n\n",
        $timestamp,
        $ip,
        $user,
        $operation,
        $operation,
        $file,
        $permsStr,
        $writable,
        $readable,
        function_exists('posix_getpwuid') && $perms !== false ? posix_getpwuid(fileowner($file))['name'] : 'unknown',
        function_exists('posix_getgrgid') && $perms !== false ? posix_getgrgid(filegroup($file))['name'] : 'unknown'
    );
    
    @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}
