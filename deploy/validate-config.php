<?php
/**
 * MIKPAY Config Validator
 * Script untuk validasi dan debug config.php di VPS
 * 
 * Cara pakai:
 * php validate-config.php
 * atau akses via browser: http://your-domain.com/deploy/validate-config.php
 */

// Set error reporting untuk debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>MIKPAY Config Validator</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #4D44B5; }
        .success { color: #1EBA62; padding: 10px; background: #e8f5e9; border-left: 4px solid #1EBA62; margin: 10px 0; }
        .error { color: #fd5353; padding: 10px; background: #ffebee; border-left: 4px solid #fd5353; margin: 10px 0; }
        .warning { color: #ff9800; padding: 10px; background: #fff3e0; border-left: 4px solid #ff9800; margin: 10px 0; }
        .info { color: #2196F3; padding: 10px; background: #e3f2fd; border-left: 4px solid #2196F3; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #4D44B5; color: white; }
        tr:hover { background: #f5f5f5; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-success { background: #1EBA62; color: white; }
        .badge-error { background: #fd5353; color: white; }
        .badge-warning { background: #ff9800; color: white; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🔍 MIKPAY Config Validator</h1>";

$configPath = __DIR__ . '/../include/config.php';
$configExamplePath = __DIR__ . '/../include/config.php.example';

// Check if config.php exists
if (!file_exists($configPath)) {
    echo "<div class='error'><strong>❌ ERROR:</strong> File config.php tidak ditemukan di: <code>$configPath</code></div>";
    echo "<div class='info'><strong>💡 SOLUSI:</strong> Copy dari template:<br><code>cp include/config.php.example include/config.php</code></div>";
    echo "</div></body></html>";
    exit;
}

echo "<div class='success'><strong>✅ File config.php ditemukan</strong></div>";

// Check file permissions
$perms = substr(sprintf('%o', fileperms($configPath)), -4);
echo "<div class='info'><strong>📋 File Permissions:</strong> $perms</div>";

// Read config file
$configContent = file_get_contents($configPath);
$configLines = file($configPath);

// Check if file is readable
if ($configContent === false) {
    echo "<div class='error'><strong>❌ ERROR:</strong> Tidak bisa membaca file config.php</div>";
    echo "</div></body></html>";
    exit;
}

echo "<div class='success'><strong>✅ File config.php bisa dibaca</strong></div>";

// Try to include config
echo "<h2>📊 Parsing Config.php</h2>";

// Suppress errors untuk include
$data = array();
$oldErrorReporting = error_reporting(0);

// Include config
include($configPath);

error_reporting($oldErrorReporting);

// Check if $data array exists
if (!isset($data) || !is_array($data)) {
    echo "<div class='error'><strong>❌ ERROR:</strong> Variable \$data tidak terdefinisi atau bukan array</div>";
    echo "<div class='info'><strong>💡 SOLUSI:</strong> Pastikan file config.php berisi definisi array \$data</div>";
    echo "</div></body></html>";
    exit;
}

echo "<div class='success'><strong>✅ Variable \$data terdefinisi</strong></div>";

// Check admin config
if (!isset($data['mikpay'])) {
    echo "<div class='warning'><strong>⚠️ WARNING:</strong> Admin config (\$data['mikpay']) tidak ditemukan</strong></div>";
} else {
    echo "<div class='success'><strong>✅ Admin config ditemukan</strong></div>";
}

// Find router sessions
$routers = array();
$errors = array();

foreach ($configLines as $lineNum => $line) {
    // Skip empty lines and comments
    if (trim($line) == '' || strpos(trim($line), '//') === 0 || strpos(trim($line), '/*') === 0) {
        continue;
    }
    
    // Match pattern: $data['SESSION_NAME'] = array(
    if (preg_match("/\\\$data\['([^']+)'\]\s*=\s*array\s*\(/", $line, $matches)) {
        $sessionName = $matches[1];
        
        if ($sessionName == 'mikpay') {
            continue; // Skip admin config
        }
        
        // Try to extract router info
        $routerInfo = array(
            'session' => $sessionName,
            'line' => $lineNum + 1,
            'valid' => false,
            'errors' => array()
        );
        
        // Check if session exists in $data array
        if (!isset($data[$sessionName])) {
            $routerInfo['errors'][] = "Session tidak ada di array \$data";
            $routers[$sessionName] = $routerInfo;
            continue;
        }
        
        // Validate required fields
        $requiredFields = array(
            1 => 'IP:PORT (format: SESSION!IP:PORT)',
            2 => 'Username (format: SESSION@|@USERNAME)',
            3 => 'Password (format: SESSION#|#PASSWORD_BASE64)',
            4 => 'Router Name (format: SESSION%ROUTER_NAME)'
        );
        
        foreach ($requiredFields as $index => $desc) {
            if (!isset($data[$sessionName][$index])) {
                $routerInfo['errors'][] = "Field $index ($desc) tidak ditemukan";
            } else {
                // Try to parse
                switch($index) {
                    case 1: // IP:PORT
                        if (strpos($data[$sessionName][$index], '!') === false) {
                            $routerInfo['errors'][] = "Format IP:PORT salah (harus mengandung '!')";
                        } else {
                            $ipPort = explode('!', $data[$sessionName][$index])[1] ?? '';
                            if (empty($ipPort)) {
                                $routerInfo['errors'][] = "IP:PORT kosong";
                            }
                        }
                        break;
                    case 2: // Username
                        if (strpos($data[$sessionName][$index], '@|@') === false) {
                            $routerInfo['errors'][] = "Format Username salah (harus mengandung '@|@')";
                        }
                        break;
                    case 3: // Password
                        if (strpos($data[$sessionName][$index], '#|#') === false) {
                            $routerInfo['errors'][] = "Format Password salah (harus mengandung '#|#')";
                        }
                        break;
                    case 4: // Router Name
                        if (strpos($data[$sessionName][$index], '%') === false) {
                            $routerInfo['errors'][] = "Format Router Name salah (harus mengandung '%')";
                        } else {
                            $routerName = explode('%', $data[$sessionName][$index])[1] ?? '';
                            if (empty($routerName)) {
                                $routerInfo['errors'][] = "Router Name kosong";
                            } else {
                                $routerInfo['router_name'] = $routerName;
                            }
                        }
                        break;
                }
            }
        }
        
        if (empty($routerInfo['errors'])) {
            $routerInfo['valid'] = true;
        }
        
        $routers[$sessionName] = $routerInfo;
    }
}

// Display results
echo "<h2>📋 Router Sessions Found</h2>";

if (empty($routers)) {
    echo "<div class='warning'><strong>⚠️ Tidak ada router yang dikonfigurasi</strong></div>";
    echo "<div class='info'><strong>💡 SOLUSI:</strong> Tambahkan router di file config.php dengan format:<br>";
    echo "<pre>\$data['ROUTER1'] = array (
  '1'=>'ROUTER1!192.168.1.1:8728',
  'ROUTER1@|@admin',
  'ROUTER1#|#YOUR_PASSWORD_BASE64',
  'ROUTER1%My Router Name',
  ...
);</pre></div>";
} else {
    echo "<table>";
    echo "<tr><th>Session Name</th><th>Router Name</th><th>Status</th><th>Line</th><th>Errors</th></tr>";
    
    foreach ($routers as $session => $info) {
        $status = $info['valid'] ? 
            "<span class='badge badge-success'>Valid</span>" : 
            "<span class='badge badge-error'>Invalid</span>";
        
        $routerName = $info['router_name'] ?? 'N/A';
        $errors = empty($info['errors']) ? 
            "<span style='color: #1EBA62;'>✓ OK</span>" : 
            "<ul style='margin: 0; padding-left: 20px;'><li>" . implode('</li><li>', $info['errors']) . "</li></ul>";
        
        echo "<tr>";
        echo "<td><strong>$session</strong></td>";
        echo "<td>$routerName</td>";
        echo "<td>$status</td>";
        echo "<td>{$info['line']}</td>";
        echo "<td>$errors</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

// Show sample config
echo "<h2>📝 Sample Config Format</h2>";
echo "<div class='info'>";
echo "<strong>Format yang benar:</strong>";
echo "<pre>\$data['ROUTER1'] = array (
  '1'=>'ROUTER1!192.168.1.1:8728',           // IP:PORT
  'ROUTER1@|@admin',                         // Username
  'ROUTER1#|#YOUR_PASSWORD_BASE64',          // Password (Base64)
  'ROUTER1%My Router Name',                   // Router Name
  'ROUTER1^mydomain.com',                    // Domain
  'ROUTER1&Rp',                              // Currency
  'ROUTER1*10',                              // Currency position
  'ROUTER1(1',                               // Expiry mode
  'ROUTER1)',                                // 
  'ROUTER1=10',                              // Expiry days
  'ROUTER1@!@enable'                         // Status
);</pre>";
echo "</div>";

// Show current config content (first 50 lines)
echo "<h2>📄 Config.php Content (Preview)</h2>";
echo "<pre>" . htmlspecialchars(implode('', array_slice($configLines, 0, 50))) . "</pre>";

echo "</div></body></html>";
?>
