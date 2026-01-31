<?php
/*
 * MIKPAY User Registration
 * Halaman registrasi untuk user baru
 */
session_start();
// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$error = '';
$success = '';

// Include database with error handling
$dbLoaded = false;
try {
    if (!file_exists('./include/database.php')) {
        throw new Exception('File database.php tidak ditemukan');
    }
    include_once('./include/database.php');
    
    if (!function_exists('registerUser')) {
        throw new Exception('Function registerUser tidak ditemukan');
    }
    
    // Try to initialize database
    if (function_exists('initDatabase')) {
        try {
            initDatabase();
        } catch (Exception $e) {
            // Database might not exist yet, that's okay
        }
    }
    
    $dbLoaded = true;
} catch (Exception $e) {
    $error = 'Error loading database: ' . $e->getMessage();
    $dbLoaded = false;
}

// Include business config with error handling
try {
    if (file_exists('./include/business_config.php')) {
        include_once('./include/business_config.php');
    }
} catch (Exception $e) {
    // Business config is optional, continue
}

// Get logo path safely
$loginLogo = array('exists' => false, 'path' => '');
if (function_exists('getLogoPath')) {
    try {
        $loginLogo = getLogoPath('', './');
    } catch (Exception $e) {
        // Use default
    }
}

// Handle registration
if (isset($_POST['register'])) {
    try {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];
        $email = trim($_POST['email']);
        $fullName = trim($_POST['full_name']);
        $phone = trim($_POST['phone']);
        
        // Validation
        if (empty($username) || empty($password)) {
            $error = 'Username dan password harus diisi';
        } elseif (strlen($username) < 3) {
            $error = 'Username minimal 3 karakter';
        } elseif (strlen($password) < 6) {
            $error = 'Password minimal 6 karakter';
        } elseif ($password !== $confirmPassword) {
            $error = 'Password dan konfirmasi password tidak sama';
        } else {
            // Check if database is loaded
            if (!$dbLoaded || !function_exists('registerUser')) {
                $error = 'Sistem registrasi belum siap. Pastikan database sudah dikonfigurasi dengan benar.';
            } else {
                // Register user
                try {
                    $result = registerUser($username, $password, $email, $fullName, $phone);
                    
                    if ($result && isset($result['success']) && $result['success']) {
                        $success = 'Registrasi berhasil! Anda mendapat trial 5 hari. Silakan login.';
                        // Clear form data
                        $_POST = array();
                    } else {
                        $error = isset($result['message']) ? $result['message'] : 'Gagal melakukan registrasi. Silakan coba lagi.';
                    }
                } catch (Exception $e) {
                    $error = 'Error saat registrasi: ' . $e->getMessage();
                }
            }
        }
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    } catch (Error $e) {
        $error = 'Fatal Error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Daftar - MIKPAY</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="css/font-awesome/css/font-awesome.min.css" />
    <link rel="stylesheet" href="css/mikpay-ui.dark.min.css">
    <link rel="icon" href="./img/favicon.png?v=<?= time() ?>" />
</head>
<body>
<?php include_once('./include/login.php'); ?>

<style>
.register-link {
    text-align: center;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.register-link a {
    color: #fb923c;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.3s ease;
}

.register-link a:hover {
    color: #fbbf24;
}

.login-form-container form {
    margin-top: 0;
}
</style>

<script>
// Modify login form to registration form
document.addEventListener('DOMContentLoaded', function() {
    var form = document.querySelector('.login-form-container form');
    if (form) {
        form.innerHTML = `
            <h2 style="color: #FFF; text-align: center; margin-bottom: 25px; font-size: 24px;">Daftar Akun MIKPAY</h2>
            <?php if ($success): ?>
            <div class="login-error-message" style="background: rgba(34, 197, 94, 0.2); border-color: rgba(34, 197, 94, 0.4); color: #4ade80;">
                <i class="fa fa-check-circle"></i> <?= $success ?>
            </div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="login-error-message">
                <i class="fa fa-exclamation-circle"></i> <?= $error ?>
            </div>
            <?php endif; ?>
            <div class="login-input-group">
                <input type="text" name="username" class="login-input" placeholder="Username" required autofocus>
                <i class="fa fa-user login-input-icon"></i>
            </div>
            <div class="login-input-group">
                <input type="text" name="full_name" class="login-input" placeholder="Nama Lengkap" required>
                <i class="fa fa-id-card login-input-icon"></i>
            </div>
            <div class="login-input-group">
                <input type="email" name="email" class="login-input" placeholder="Email (Opsional)">
                <i class="fa fa-envelope login-input-icon"></i>
            </div>
            <div class="login-input-group">
                <input type="text" name="phone" class="login-input" placeholder="No. HP (Opsional)">
                <i class="fa fa-phone login-input-icon"></i>
            </div>
            <div class="login-input-group">
                <input type="password" name="password" class="login-input" placeholder="Password (Min. 6 karakter)" required>
                <i class="fa fa-lock login-input-icon"></i>
            </div>
            <div class="login-input-group">
                <input type="password" name="confirm_password" class="login-input" placeholder="Konfirmasi Password" required>
                <i class="fa fa-lock login-input-icon"></i>
            </div>
            <button type="submit" name="register" class="login-btn-modern">
                <i class="fa fa-user-plus" style="margin-right: 10px;"></i> Daftar
            </button>
            <div class="register-link">
                <p style="color: rgba(255, 255, 255, 0.7); margin: 0;">Sudah punya akun? <a href="./admin.php?id=login">Login di sini</a></p>
            </div>
        `;
    }
});
</script>
</body>
</html>
