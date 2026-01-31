<?php
/**
 * Debug Script untuk Import CSV
 * Script ini membantu menemukan penyebab error 500
 * 
 * Cara pakai:
 * php deploy/debug-import.php
 * atau akses via browser: http://your-domain.com/deploy/debug-import.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Debug Import CSV</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #4D44B5; }
        .success { color: #1EBA62; padding: 10px; background: #e8f5e9; border-left: 4px solid #1EBA62; margin: 10px 0; }
        .error { color: #fd5353; padding: 10px; background: #ffebee; border-left: 4px solid #fd5353; margin: 10px 0; }
        .warning { color: #ff9800; padding: 10px; background: #fff3e0; border-left: 4px solid #ff9800; margin: 10px 0; }
        .info { color: #2196F3; padding: 10px; background: #e3f2fd; border-left: 4px solid #2196F3; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #4D44B5; color: white; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🔍 Debug Import CSV</h1>";

// 1. Check PHP version
echo "<h2>1. PHP Version</h2>";
$phpVersion = phpversion();
echo "<div class='info'>PHP Version: <strong>$phpVersion</strong></div>";

// 2. Check file existence
echo "<h2>2. File Existence</h2>";
$files = [
    'import-excel.php' => __DIR__ . '/../ppp/import-excel.php',
    'billing-data.php' => __DIR__ . '/../ppp/billing-data.php',
    'config.php' => __DIR__ . '/../include/config.php',
];

echo "<table>";
echo "<tr><th>File</th><th>Path</th><th>Status</th><th>Readable</th></tr>";
foreach ($files as $name => $path) {
    $exists = file_exists($path);
    $readable = $exists ? (is_readable($path) ? 'Yes' : 'No') : 'N/A';
    $status = $exists ? "<span style='color:green'>✓ Exists</span>" : "<span style='color:red'>✗ Not Found</span>";
    echo "<tr>";
    echo "<td><strong>$name</strong></td>";
    echo "<td><code>$path</code></td>";
    echo "<td>$status</td>";
    echo "<td>$readable</td>";
    echo "</tr>";
}
echo "</table>";

// 3. Check PHP settings
echo "<h2>3. PHP Settings</h2>";
$settings = [
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_file_uploads' => ini_get('max_file_uploads'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'session.save_path' => ini_get('session.save_path'),
    'error_log' => ini_get('error_log'),
];

echo "<table>";
echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>";
foreach ($settings as $key => $value) {
    $status = 'OK';
    if ($key === 'upload_max_filesize') {
        $bytes = return_bytes($value);
        $status = $bytes >= (5 * 1024 * 1024) ? '<span style="color:green">OK</span>' : '<span style="color:red">Too Small (min 5M)</span>';
    }
    if ($key === 'post_max_size') {
        $bytes = return_bytes($value);
        $status = $bytes >= (6 * 1024 * 1024) ? '<span style="color:green">OK</span>' : '<span style="color:red">Too Small (min 6M)</span>';
    }
    echo "<tr><td><strong>$key</strong></td><td><code>$value</code></td><td>$status</td></tr>";
}
echo "</table>";

function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    return $val;
}

// 4. Test include billing-data.php
echo "<h2>4. Test Include billing-data.php</h2>";
$billingDataFile = __DIR__ . '/../ppp/billing-data.php';
if (file_exists($billingDataFile)) {
    try {
        ob_start();
        include_once($billingDataFile);
        $output = ob_get_clean();
        
        if (!empty($output)) {
            echo "<div class='warning'><strong>⚠️ Warning:</strong> File mengeluarkan output saat di-include:</div>";
            echo "<pre>" . htmlspecialchars($output) . "</pre>";
        }
        
        // Check functions
        $functions = [
            'getCustomerBilling',
            'saveCustomerBilling',
            'getCustomerBillingSettings',
            'saveCustomerBillingSettings'
        ];
        
        echo "<table>";
        echo "<tr><th>Function</th><th>Status</th></tr>";
        foreach ($functions as $func) {
            $exists = function_exists($func);
            $status = $exists ? "<span style='color:green'>✓ Exists</span>" : "<span style='color:red'>✗ Not Found</span>";
            echo "<tr><td><strong>$func</strong></td><td>$status</td></tr>";
        }
        echo "</table>";
        
        if (empty($output) && count(array_filter($functions, 'function_exists')) === count($functions)) {
            echo "<div class='success'><strong>✅ File billing-data.php bisa di-include dengan benar</strong></div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'><strong>❌ Error:</strong> " . $e->getMessage() . "</div>";
    } catch (Error $e) {
        echo "<div class='error'><strong>❌ Fatal Error:</strong> " . $e->getMessage() . "</div>";
        echo "<div class='info'>File: " . $e->getFile() . " Line: " . $e->getLine() . "</div>";
    }
} else {
    echo "<div class='error'><strong>❌ File tidak ditemukan:</strong> $billingDataFile</div>";
}

// 5. Check permissions
echo "<h2>5. File Permissions</h2>";
$dirs = [
    'include' => __DIR__ . '/../include',
    'ppp' => __DIR__ . '/../ppp',
];

echo "<table>";
echo "<tr><th>Directory</th><th>Path</th><th>Writable</th><th>Permission</th></tr>";
foreach ($dirs as $name => $path) {
    if (file_exists($path)) {
        $writable = is_writable($path) ? '<span style="color:green">Yes</span>' : '<span style="color:red">No</span>';
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        echo "<tr>";
        echo "<td><strong>$name</strong></td>";
        echo "<td><code>$path</code></td>";
        echo "<td>$writable</td>";
        echo "<td>$perms</td>";
        echo "</tr>";
    }
}
echo "</table>";

// 6. Check error log
echo "<h2>6. Recent Error Log</h2>";
$errorLog = __DIR__ . '/../include/import-error.log';
if (file_exists($errorLog)) {
    $lines = file($errorLog);
    $recent = array_slice($lines, -20);
    echo "<div class='info'><strong>Last 20 lines from import-error.log:</strong></div>";
    echo "<pre>" . htmlspecialchars(implode('', $recent)) . "</pre>";
} else {
    echo "<div class='warning'>Error log file tidak ditemukan: $errorLog</div>";
}

// 7. Test syntax
echo "<h2>7. Syntax Check</h2>";
$importFile = __DIR__ . '/../ppp/import-excel.php';
if (file_exists($importFile)) {
    $output = [];
    $return = 0;
    exec("php -l $importFile 2>&1", $output, $return);
    
    if ($return === 0) {
        echo "<div class='success'><strong>✅ Syntax OK</strong></div>";
    } else {
        echo "<div class='error'><strong>❌ Syntax Error:</strong></div>";
        echo "<pre>" . htmlspecialchars(implode("\n", $output)) . "</pre>";
    }
}

echo "</div></body></html>";
?>
