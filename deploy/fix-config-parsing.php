<?php
/**
 * MIKPAY Config Parser Fix
 * Script untuk memperbaiki parsing config.php yang bermasalah
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$configPath = __DIR__ . '/../include/config.php';

if (!file_exists($configPath)) {
    die("ERROR: config.php tidak ditemukan di: $configPath\n");
}

echo "🔧 MIKPAY Config Parser Fix\n";
echo "==========================\n\n";

// Read config file
$configContent = file_get_contents($configPath);
$configLines = file($configPath);

// Parse config manually
$data = array();
$currentSession = null;
$currentArray = array();
$inArray = false;
$arrayDepth = 0;

foreach ($configLines as $lineNum => $line) {
    $trimmed = trim($line);
    
    // Skip empty lines and comments
    if (empty($trimmed) || strpos($trimmed, '//') === 0) {
        continue;
    }
    
    // Match: $data['SESSION_NAME'] = array(
    if (preg_match("/\\\$data\['([^']+)'\]\s*=\s*array\s*\(/", $line, $matches)) {
        // Save previous session if exists
        if ($currentSession !== null && !empty($currentArray)) {
            $data[$currentSession] = $currentArray;
        }
        
        $currentSession = $matches[1];
        $currentArray = array();
        $inArray = true;
        $arrayDepth = 1;
        
        echo "Found session: $currentSession\n";
        continue;
    }
    
    // If we're in an array definition
    if ($inArray && $currentSession !== null) {
        // Check for array closing
        if (strpos($trimmed, ')') !== false && strpos($trimmed, ';') !== false) {
            $inArray = false;
            if (!empty($currentArray)) {
                $data[$currentSession] = $currentArray;
            }
            $currentSession = null;
            $currentArray = array();
            continue;
        }
        
        // Match array elements: 'INDEX'=>'VALUE',
        if (preg_match("/'(\d+)'\s*=>\s*'([^']+)'/", $line, $matches)) {
            $index = (int)$matches[1];
            $value = $matches[2];
            $currentArray[$index] = $value;
            echo "  [$index] = $value\n";
        }
    }
}

// Save last session
if ($currentSession !== null && !empty($currentArray)) {
    $data[$currentSession] = $currentArray;
}

echo "\n✅ Parsing complete!\n\n";

// Validate routers
echo "📋 Validating routers:\n";
$validRouters = 0;
$invalidRouters = 0;

foreach ($data as $session => $config) {
    if ($session == 'mikpay') {
        continue; // Skip admin
    }
    
    $errors = array();
    
    // Check required fields
    if (!isset($config[1]) || strpos($config[1], '!') === false) {
        $errors[] = "Missing or invalid IP:PORT (field 1)";
    }
    if (!isset($config[2]) || strpos($config[2], '@|@') === false) {
        $errors[] = "Missing or invalid Username (field 2)";
    }
    if (!isset($config[3]) || strpos($config[3], '#|#') === false) {
        $errors[] = "Missing or invalid Password (field 3)";
    }
    if (!isset($config[4]) || strpos($config[4], '%') === false) {
        $errors[] = "Missing or invalid Router Name (field 4)";
    }
    
    if (empty($errors)) {
        $routerName = explode('%', $config[4])[1] ?? 'Unknown';
        echo "  ✅ $session: $routerName\n";
        $validRouters++;
    } else {
        echo "  ❌ $session:\n";
        foreach ($errors as $error) {
            echo "      - $error\n";
        }
        $invalidRouters++;
    }
}

echo "\n📊 Summary:\n";
echo "  Valid routers: $validRouters\n";
echo "  Invalid routers: $invalidRouters\n";
echo "  Total sessions: " . count($data) . "\n";

if ($invalidRouters > 0) {
    echo "\n⚠️  Beberapa router memiliki konfigurasi yang tidak valid.\n";
    echo "   Silakan perbaiki file config.php sesuai format yang benar.\n";
    echo "   Lihat DEPLOY_VPS.md untuk contoh format yang benar.\n";
}

?>
