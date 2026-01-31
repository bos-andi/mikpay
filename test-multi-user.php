<?php
/*
 * Test Multi-User System
 * File untuk test semua fungsi multi-user
 */
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>MIKPAY Multi-User System Test</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

// Test 1: Database Connection
echo "<h2>Test 1: Database Connection</h2>";
try {
    include_once('./include/database.php');
    $conn = getDBConnection();
    echo "<p class='success'>✓ Database connection OK</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    echo "<p class='info'>Pastikan database 'mikpay' sudah dibuat dan konfigurasi di include/database.php sudah benar.</p>";
    exit;
}

// Test 2: Database Tables
echo "<h2>Test 2: Database Tables</h2>";
try {
    $conn = getDBConnection();
    $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('users', $tables)) {
        echo "<p class='success'>✓ Table 'users' exists</p>";
    } else {
        echo "<p class='error'>✗ Table 'users' tidak ditemukan</p>";
        echo "<p class='info'>Mencoba membuat tabel...</p>";
        initDatabase();
    }
    
    if (in_array('user_sessions', $tables)) {
        echo "<p class='success'>✓ Table 'user_sessions' exists</p>";
    } else {
        echo "<p class='error'>✗ Table 'user_sessions' tidak ditemukan</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error checking tables: " . $e->getMessage() . "</p>";
}

// Test 3: Register User Function
echo "<h2>Test 3: Register User Function</h2>";
if (function_exists('registerUser')) {
    echo "<p class='success'>✓ Function registerUser() exists</p>";
} else {
    echo "<p class='error'>✗ Function registerUser() tidak ditemukan</p>";
}

// Test 4: Verify User Function
echo "<h2>Test 4: Verify User Function</h2>";
if (function_exists('verifyUser')) {
    echo "<p class='success'>✓ Function verifyUser() exists</p>";
} else {
    echo "<p class='error'>✗ Function verifyUser() tidak ditemukan</p>";
}

// Test 5: Test Register (if test user doesn't exist)
echo "<h2>Test 5: Test Register User</h2>";
$testUsername = 'testuser_' . time();
$testPassword = 'test123456';
$result = registerUser($testUsername, $testPassword, 'test@example.com', 'Test User', '081234567890');
if ($result && isset($result['success']) && $result['success']) {
    echo "<p class='success'>✓ Test user berhasil dibuat: " . $testUsername . "</p>";
    echo "<p class='info'>Password: " . $testPassword . "</p>";
    
    // Test 6: Test Login
    echo "<h2>Test 6: Test Login</h2>";
    $user = verifyUser($testUsername, $testPassword);
    if ($user) {
        echo "<p class='success'>✓ Login berhasil!</p>";
        echo "<p class='info'>User ID: " . $user['id'] . "</p>";
        echo "<p class='info'>Username: " . $user['username'] . "</p>";
        echo "<p class='info'>Role: " . $user['role'] . "</p>";
        echo "<p class='info'>Trial Ends: " . ($user['trial_ends'] ?? 'N/A') . "</p>";
    } else {
        echo "<p class='error'>✗ Login gagal</p>";
    }
    
    // Test 7: Test Subscription Check
    echo "<h2>Test 7: Test Subscription Check</h2>";
    if (function_exists('isUserSubscriptionActive')) {
        $isActive = isUserSubscriptionActive($user['id']);
        if ($isActive) {
            echo "<p class='success'>✓ Subscription aktif</p>";
        } else {
            echo "<p class='error'>✗ Subscription tidak aktif</p>";
        }
    } else {
        echo "<p class='error'>✗ Function isUserSubscriptionActive() tidak ditemukan</p>";
    }
    
    // Cleanup test user
    echo "<h2>Cleanup</h2>";
    if (deleteUser($user['id'])) {
        echo "<p class='success'>✓ Test user dihapus</p>";
    }
} else {
    echo "<p class='error'>✗ Gagal membuat test user: " . (isset($result['message']) ? $result['message'] : 'Unknown error') . "</p>";
}

// Test 8: Check Existing Users
echo "<h2>Test 8: Check Existing Users</h2>";
try {
    $users = getAllUsers(10, 0);
    echo "<p class='info'>Total users: " . count($users) . "</p>";
    if (count($users) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Status</th><th>Trial Ends</th></tr>";
        foreach ($users as $u) {
            echo "<tr>";
            echo "<td>" . $u['id'] . "</td>";
            echo "<td>" . htmlspecialchars($u['username']) . "</td>";
            echo "<td>" . htmlspecialchars($u['email'] ?? '-') . "</td>";
            echo "<td>" . $u['status'] . "</td>";
            echo "<td>" . ($u['trial_ends'] ?? '-') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p>Jika semua test menunjukkan ✓ (hijau), sistem multi-user siap digunakan.</p>";
echo "<p>Jika ada ✗ (merah), perbaiki masalah tersebut terlebih dahulu.</p>";
echo "<p><a href='register.php'>Test Halaman Registrasi</a> | <a href='admin.php?id=login'>Test Login</a></p>";
?>
