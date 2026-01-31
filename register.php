<?php
/*
 * MIKPAY User Registration
 * Halaman registrasi untuk user baru
 */
session_start();
error_reporting(0);

// Include database
include_once('./include/database.php');
include_once('./include/business_config.php');

$error = '';
$success = '';

// Handle registration
if (isset($_POST['register'])) {
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
        // Register user
        $result = registerUser($username, $password, $email, $fullName, $phone);
        
        if ($result['success']) {
            $success = 'Registrasi berhasil! Anda mendapat trial 5 hari. Silakan login.';
        } else {
            $error = $result['message'];
        }
    }
}

$loginLogo = getLogoPath('', './');
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
