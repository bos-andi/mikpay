<?php
/*
 * Business Configuration
 * Menyimpan data bisnis/usaha
 */

define('BUSINESS_CONFIG_FILE', __DIR__ . '/business_settings.json');

/**
 * Get business settings
 */
function getBusinessSettings() {
    if (!file_exists(BUSINESS_CONFIG_FILE)) {
        $default = array(
            'business_name' => 'MIKPAY',
            'business_address' => '',
            'business_phone' => '',
            'business_email' => '',
            'business_logo' => 'logo-mikdev.png',
            'bank_account' => '',
            'bank_name' => '',
            'bank_account_name' => ''
        );
        saveBusinessSettings($default);
        return $default;
    }
    $data = file_get_contents(BUSINESS_CONFIG_FILE);
    return json_decode($data, true) ?: array();
}

/**
 * Save business settings
 */
function saveBusinessSettings($settings) {
    // Ensure directory exists before writing
    $configDir = dirname(BUSINESS_CONFIG_FILE);
    if (!is_dir($configDir)) {
        @mkdir($configDir, 0755, true);
    }
    file_put_contents(BUSINESS_CONFIG_FILE, json_encode($settings, JSON_PRETTY_PRINT));
}

/**
 * Get business name
 */
function getBusinessName() {
    $settings = getBusinessSettings();
    return $settings['business_name'] ?? 'MIKPAY';
}

/**
 * Get logo path for a session
 * @param string $session - Session/router name
 * @param string $basePath - Base path prefix (e.g., './', '../', or absolute)
 * @param bool $useSessionLogo - If true, check for session-specific logo (for voucher/reports). If false, always use logo.png (for dashboard/navbar)
 * @return array - ['exists' => bool, 'path' => string, 'url' => string]
 */
function getLogoPath($session = '', $basePath = './', $useSessionLogo = true) {
    $imgDir = __DIR__ . '/../img/';
    
    // For dashboard/navbar: always use logo.png
    if (!$useSessionLogo) {
        $defaultLogo = 'logo.png';
        if (file_exists($imgDir . $defaultLogo)) {
            return array(
                'exists' => true,
                'file' => $defaultLogo,
                'path' => $basePath . 'img/' . $defaultLogo
            );
        }
        return array(
            'exists' => false,
            'file' => '',
            'path' => ''
        );
    }
    
    // For voucher/reports: Priority: 1. Session-specific logo, 2. Default logo.png
    $sessionLogo = 'logo-' . $session . '.png';
    $defaultLogo = 'logo.png';
    
    if (!empty($session) && file_exists($imgDir . $sessionLogo)) {
        return array(
            'exists' => true,
            'file' => $sessionLogo,
            'path' => $basePath . 'img/' . $sessionLogo
        );
    } elseif (file_exists($imgDir . $defaultLogo)) {
        return array(
            'exists' => true,
            'file' => $defaultLogo,
            'path' => $basePath . 'img/' . $defaultLogo
        );
    }
    
    return array(
        'exists' => false,
        'file' => '',
        'path' => ''
    );
}

/**
 * Get logo URL (absolute URL for use in popups/print pages)
 * @param string $session - Session/router name
 * @param bool $useSessionLogo - If true, check for session-specific logo (for voucher/reports). If false, always use logo.png
 * @return array - ['exists' => bool, 'url' => string]
 */
function getLogoUrl($session = '', $useSessionLogo = true) {
    $imgDir = __DIR__ . '/../img/';
    
    // Build base URL
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Get script path and go up to root
    $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
    $basePath = dirname(dirname($scriptPath)); // Go up from current location
    if ($basePath === '\\' || $basePath === '/') $basePath = '';
    
    $baseUrl = $protocol . "://" . $host . $basePath;
    
    // For reports/voucher: Priority: 1. Session-specific logo, 2. Default logo.png
    if ($useSessionLogo) {
        $sessionLogo = 'logo-' . $session . '.png';
        $defaultLogo = 'logo.png';
        
        if (!empty($session) && file_exists($imgDir . $sessionLogo)) {
            return array(
                'exists' => true,
                'url' => $baseUrl . '/img/' . $sessionLogo
            );
        } elseif (file_exists($imgDir . $defaultLogo)) {
            return array(
                'exists' => true,
                'url' => $baseUrl . '/img/' . $defaultLogo
            );
        }
    } else {
        // Always use logo.png
        $defaultLogo = 'logo.png';
        if (file_exists($imgDir . $defaultLogo)) {
            return array(
                'exists' => true,
                'url' => $baseUrl . '/img/' . $defaultLogo
            );
        }
    }
    
    return array(
        'exists' => false,
        'url' => ''
    );
}
