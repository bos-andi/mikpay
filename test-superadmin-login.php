<?php
/*
 * Test Superadmin Login
 * Script untuk test dan setup superadmin
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>MIKPAY Superadmin Login Test</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} table{border-collapse:collapse;margin:20px 0;} th,td{padding:10px;border:1px solid #ddd;} th{background:#f0f0f0;}</style>";

// Test 1: Database Connection
echo "<h2>Test 1: Database Connection</h2>";
try {
    include_once('./include/database.php');
    $conn = getDBConnection();
    if ($conn) {
        echo "<p class='success'>✓ Database connection OK</p>";
    } else {
        echo "<p class='error'>✗ Database connection failed</p>";
        echo "<p class='info'>Pastikan database 'mikpay' sudah dibuat dan konfigurasi di include/database.php sudah benar.</p>";
        exit;
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Test 2: Check Superadmin in Database
echo "<h2>Test 2: Check Superadmin in Database</h2>";
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE role = 'superadmin'");
    $stmt->execute();
    $superadmin = $stmt->fetch();
    
    if ($superadmin) {
        echo "<p class='success'>✓ Superadmin ditemukan di database</p>";
        echo "<table>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>ID</td><td>" . htmlspecialchars($superadmin['id']) . "</td></tr>";
        echo "<tr><td>Username</td><td><strong>" . htmlspecialchars($superadmin['username']) . "</strong></td></tr>";
        echo "<tr><td>Email</td><td>" . htmlspecialchars($superadmin['email'] ?: '-') . "</td></tr>";
        echo "<tr><td>Role</td><td>" . htmlspecialchars($superadmin['role']) . "</td></tr>";
        echo "<tr><td>Status</td><td>" . htmlspecialchars($superadmin['status']) . "</td></tr>";
        echo "</table>";
        
        // Test password
        echo "<h2>Test 3: Test Password</h2>";
        $testPasswords = array(
            'MikPayandidev.id',
            'superadmin',
            'admin123'
        );
        
        echo "<p>Testing passwords:</p>";
        echo "<ul>";
        foreach ($testPasswords as $testPass) {
            if (password_verify($testPass, $superadmin['password'])) {
                echo "<li class='success'>✓ Password '<strong>$testPass</strong>' BENAR</li>";
            } else {
                echo "<li class='error'>✗ Password '$testPass' salah</li>";
            }
        }
        echo "</ul>";
        
    } else {
        echo "<p class='error'>✗ Superadmin tidak ditemukan di database</p>";
        echo "<p class='info'>Mencoba migrate superadmin...</p>";
        
        // Try to migrate
        if (function_exists('migrateSuperAdmin')) {
            if (migrateSuperAdmin()) {
                echo "<p class='success'>✓ Superadmin berhasil di-migrate</p>";
                echo "<p class='info'>Silakan refresh halaman ini untuk melihat detail superadmin.</p>";
            } else {
                echo "<p class='error'>✗ Gagal migrate superadmin</p>";
            }
        } else {
            echo "<p class='error'>✗ Function migrateSuperAdmin tidak ditemukan</p>";
        }
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}

// Test 4: Login Test
echo "<h2>Test 4: Login Test</h2>";
if ($superadmin) {
    echo "<p>Test login dengan username dan password:</p>";
    
    if (function_exists('verifyUser')) {
        $testLogin = verifyUser($superadmin['username'], 'MikPayandidev.id');
        if ($testLogin) {
            echo "<p class='success'>✓ Login berhasil dengan username: <strong>" . htmlspecialchars($superadmin['username']) . "</strong> dan password: <strong>MikPayandidev.id</strong></p>";
        } else {
            echo "<p class='error'>✗ Login gagal dengan password 'MikPayandidev.id'</p>";
        }
    }
}

// Summary
echo "<h2>📋 Summary - Cara Login Superadmin</h2>";
echo "<div style='background:#f0f0f0;padding:20px;border-radius:8px;'>";
echo "<h3>Login di admin.php (admin.php?id=login):</h3>";
echo "<ul>";
echo "<li><strong>Username:</strong> superadmin</li>";
echo "<li><strong>Password:</strong> MikPayandidev.id</li>";
echo "</ul>";

echo "<h3>Login di superadmin/index.php:</h3>";
echo "<ul>";
echo "<li><strong>Email/Username:</strong> superadmin atau " . ($superadmin ? htmlspecialchars($superadmin['email'] ?: 'ndiandie@gmail.com') : 'ndiandie@gmail.com') . "</li>";
echo "<li><strong>Password:</strong> MikPayandidev.id</li>";
echo "</ul>";
echo "</div>";

// Create/Update Superadmin Button
echo "<h2>🔧 Tools</h2>";
echo "<form method='POST' style='background:#f0f0f0;padding:20px;border-radius:8px;'>";
echo "<h3>Create/Reset Superadmin</h3>";
echo "<p>Username: <input type='text' name='username' value='superadmin' required></p>";
echo "<p>Password: <input type='password' name='password' value='MikPayandidev.id' required></p>";
echo "<p>Email: <input type='email' name='email' value='ndiandie@gmail.com' required></p>";
echo "<button type='submit' name='create_superadmin' style='padding:10px 20px;background:#4D44B5;color:white;border:none;border-radius:5px;cursor:pointer;'>Create/Update Superadmin</button>";
echo "</form>";

if (isset($_POST['create_superadmin'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $email = trim($_POST['email']);
    
    try {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Check if exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR role = 'superadmin'");
        $stmt->execute([$username]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update
            $stmt = $conn->prepare("UPDATE users SET password = ?, email = ?, role = 'superadmin', status = 'active' WHERE id = ?");
            $stmt->execute([$hashedPassword, $email, $existing['id']]);
            echo "<p class='success'>✓ Superadmin berhasil di-update</p>";
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO users (username, password, email, full_name, role, status, subscription_package, subscription_start, subscription_end) VALUES (?, ?, ?, ?, 'superadmin', 'active', 'enterprise', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 365 DAY))");
            $stmt->execute([$username, $hashedPassword, $email, 'Super Administrator']);
            echo "<p class='success'>✓ Superadmin berhasil dibuat</p>";
        }
        
        echo "<p class='info'>Silakan refresh halaman untuk melihat hasil.</p>";
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
    }
}

?>
