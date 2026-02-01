<?php
/*
 * Change Password Page
 * User can change their own username and password
 */

if (!isset($_SESSION["mikpay"])) {
  header("Location:./admin.php?id=login");
  exit;
}

include_once('./include/subscription.php');
include_once('./include/password_security.php');
include_once('./include/csrf.php');
include_once('./include/input_validation.php');
include_once('./lib/routeros_api.class.php');

$currentUser = $_SESSION["mikpay"];
$msg = '';
$error = '';

// Check if user is from JSON or config.php
$isJsonUser = isset($_SESSION["user_from_json"]) && $_SESSION["user_from_json"] === true;
$userData = null;

if ($isJsonUser) {
    $userData = getUser($currentUser);
    if (!$userData) {
        $error = 'User tidak ditemukan!';
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    // Validate CSRF token
    if (!validateCSRFPost()) {
        $error = 'Invalid security token. Please refresh the page.';
    } else {
        $oldPass = $_POST['old_password'];
        $newPass = $_POST['new_password'];
        $confirmPass = $_POST['confirm_password'];
        $newUsername = sanitizeInput($_POST['new_username'] ?? '', 'alphanumeric');
        
        // Validate password strength
        $passwordValidation = validatePasswordStrength($newPass, 4);
        if (!$passwordValidation['valid']) {
            $error = $passwordValidation['message'];
        } elseif (empty($newPass) || empty($confirmPass)) {
            $error = 'Password baru dan konfirmasi password harus diisi!';
        } elseif ($newPass !== $confirmPass) {
            $error = 'Password baru dan konfirmasi password tidak cocok!';
        } else {
            if ($isJsonUser && $userData) {
                // Change password for JSON user
                $storedPassword = $userData['password'] ?? '';
                $oldPasswordValid = false;
                
                // Check if password is hashed or plain text (backward compatibility)
                if (isPasswordHashed($storedPassword)) {
                    $oldPasswordValid = verifySecurePassword($oldPass, $storedPassword);
                } else {
                    $oldPasswordValid = ($storedPassword === $oldPass);
                }
                
                if (!$oldPasswordValid) {
                    $error = 'Password lama tidak sesuai!';
                } else {
                    // Update username if changed
                    if (!empty($newUsername) && $newUsername !== $currentUser) {
                        // Validate username
                        if (!validateUsername($newUsername)) {
                            $error = 'Username tidak valid! Hanya boleh huruf, angka, underscore, dan dash (3-50 karakter).';
                        } else {
                            // Check if new username already exists
                            $existingUser = getUser($newUsername);
                            if ($existingUser) {
                                $error = 'Username sudah digunakan!';
                            } else {
                                // Update user ID
                                $userData['id'] = $newUsername;
                                $userData['password'] = secureHashPassword($newPass); // Hash new password
                                saveUser($newUsername, $userData);
                                deleteUser($currentUser);
                                $_SESSION["mikpay"] = $newUsername;
                                $_SESSION["user_id"] = $newUsername;
                                $msg = 'Username dan password berhasil diubah!';
                                $currentUser = $newUsername;
                                $userData = getUser($currentUser);
                            }
                        }
                    } else {
                        // Only change password - hash the new password
                        $userData['password'] = secureHashPassword($newPass);
                        saveUser($currentUser, $userData);
                        $msg = 'Password berhasil diubah!';
                    }
                }
            } else {
                // Change password for admin (config.php)
                include('./include/config.php');
                include('./include/readcfg.php');
                
                if ($oldPass !== decrypt($passadm)) {
                    $error = 'Password lama tidak sesuai!';
                } else {
                    // Update config.php (keep Base64 for router password compatibility)
                    $newPassEncrypted = encrypt($newPass);
                    $cari = array('1' => "mikpay<|<$useradm", "mikpay>|>$passadm");
                    $ganti = array('1' => "mikpay<|<$useradm", "mikpay>|>$newPassEncrypted");
                    
                    for ($i = 1; $i < 3; $i++) {
                        $file = file("./include/config.php");
                        $content = file_get_contents("./include/config.php");
                        $newcontent = str_replace((string)$cari[$i], (string)$ganti[$i], "$content");
                        file_put_contents("./include/config.php", "$newcontent");
                    }
                    
                    // Update username if changed
                    if (!empty($newUsername) && $newUsername !== $useradm) {
                        if (!validateUsername($newUsername)) {
                            $error = 'Username tidak valid! Hanya boleh huruf, angka, underscore, dan dash (3-50 karakter).';
                        } else {
                            $cariUser = "mikpay<|<$useradm";
                            $gantiUser = "mikpay<|<$newUsername";
                            $file = file("./include/config.php");
                            $content = file_get_contents("./include/config.php");
                            $newcontent = str_replace($cariUser, $gantiUser, "$content");
                            file_put_contents("./include/config.php", "$newcontent");
                            $_SESSION["mikpay"] = $newUsername;
                            $msg = 'Username dan password berhasil diubah!';
                        }
                    } else {
                        $msg = 'Password berhasil diubah!';
                    }
                }
            }
        }
    }
}
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3><i class="fa fa-key"></i> Ubah Username & Password</h3>
            </div>
            <div class="card-body">
                <?php if ($msg): ?>
                <div class="alert alert-success" style="background: #d1fae5; border: 1px solid #10b981; color: #065f46; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
                    <i class="fa fa-check-circle"></i> <?= $msg ?>
                </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="alert alert-danger" style="background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
                    <i class="fa fa-exclamation-circle"></i> <?= $error ?>
                </div>
                <?php endif; ?>
                
                <form method="POST">
                    <?php echo getCSRFTokenField(); ?>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Username Saat Ini</label>
                        <input type="text" value="<?= htmlspecialchars($currentUser) ?>" disabled style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; background: #f9fafb;">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Username Baru (Opsional)</label>
                        <input type="text" name="new_username" placeholder="Kosongkan jika tidak ingin mengubah username" pattern="[a-zA-Z0-9_-]{3,50}" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;">
                        <small style="color: #64748b; font-size: 12px; display: block; margin-top: 5px;">Hanya huruf, angka, underscore, dan dash (3-50 karakter)</small>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Password Lama *</label>
                        <input type="password" name="old_password" required style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Password Baru *</label>
                        <input type="password" name="new_password" required minlength="4" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;">
                        <small style="color: #64748b; font-size: 12px; display: block; margin-top: 5px;">Minimal 4 karakter</small>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Konfirmasi Password Baru *</label>
                        <input type="password" name="confirm_password" required minlength="4" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;">
                    </div>
                    
                    <button type="submit" name="change_password" class="btn btn-primary" style="background: #4D44B5; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600;">
                        <i class="fa fa-save"></i> Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
