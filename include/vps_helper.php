<?php
/*
 * VPS Compatibility Helper
 * Helper functions untuk memastikan aplikasi berjalan dengan baik di VPS
 */

/**
 * Ensure directory exists and is writable
 * @param string $dir - Directory path
 * @param int $mode - Directory permissions (default: 0755)
 * @return bool - True if directory exists and is writable, false otherwise
 */
function ensureDirectory($dir, $mode = 0755) {
    if (is_dir($dir)) {
        return is_writable($dir);
    }
    
    // Try to create directory
    if (@mkdir($dir, $mode, true)) {
        return true;
    }
    
    // If creation failed, try to fix permissions on parent
    $parent = dirname($dir);
    if (is_dir($parent) && !is_writable($parent)) {
        @chmod($parent, 0755);
    }
    
    // Try again
    return @mkdir($dir, $mode, true);
}

/**
 * Ensure file directory exists before writing
 * @param string $filePath - Full file path
 * @param int $mode - Directory permissions (default: 0755)
 * @return bool - True if directory exists, false otherwise
 */
function ensureFileDirectory($filePath, $mode = 0755) {
    $dir = dirname($filePath);
    return ensureDirectory($dir, $mode);
}

/**
 * Safe file write with directory creation
 * @param string $filePath - Full file path
 * @param string $content - File content
 * @param int $flags - file_put_contents flags (default: 0)
 * @return int|false - Bytes written or false on failure
 */
function safeFileWrite($filePath, $content, $flags = 0) {
    if (!ensureFileDirectory($filePath)) {
        return false;
    }
    
    return @file_put_contents($filePath, $content, $flags);
}

/**
 * Get absolute path from relative path
 * @param string $relativePath - Relative path (e.g., './voucher/temp.php')
 * @return string - Absolute path
 */
function getAbsolutePath($relativePath) {
    // Remove leading ./
    $path = ltrim($relativePath, './');
    
    // If already absolute, return as is
    if (strpos($path, '/') === 0 || (PHP_OS_FAMILY === 'Windows' && preg_match('/^[A-Z]:\\\\/i', $path))) {
        return $path;
    }
    
    // Get base directory (parent of include directory)
    $baseDir = dirname(__DIR__);
    
    return $baseDir . '/' . $path;
}

/**
 * Normalize path separators for cross-platform compatibility
 * @param string $path - File path
 * @return string - Normalized path
 */
function normalizePath($path) {
    // Replace backslashes with forward slashes
    $path = str_replace('\\', '/', $path);
    
    // Remove duplicate slashes
    $path = preg_replace('#/+#', '/', $path);
    
    return $path;
}
