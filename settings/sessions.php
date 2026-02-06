<?php
/*
 *  Copyright (C) 2018 Muhammad Andi.
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// Enable error reporting for debugging (disable in production)
// Set error_reporting(0) in production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include security helpers
include_once('./include/csrf.php');
include_once('./include/input_validation.php');

if (!isset($_SESSION["mikpay"])) {
  header("Location:./admin.php?id=login");
  exit;
} else {
  
  // Include required files with error handling
  try {
    include('./include/config.php');
    include('./include/readcfg.php');
    // Include quickbt.php if exists to get $qrbt value
    if (file_exists('./include/quickbt.php')) {
      include('./include/quickbt.php');
    }
    // Initialize $qrbt if not set
    if (!isset($qrbt)) {
      $qrbt = 'disable';
    }
  } catch (Exception $e) {
    die("Error loading configuration: " . $e->getMessage());
  }

// array color
  $color = array('1' => 'bg-blue', 'bg-indigo', 'bg-purple', 'bg-pink', 'bg-red', 'bg-yellow', 'bg-green', 'bg-teal', 'bg-cyan', 'bg-grey', 'bg-light-blue');

  $errorMsg = '';
  $successMsg = '';

  if (isset($_POST['save'])) {
    // Validate CSRF token
    if (!validateCSRFPost()) {
      $errorMsg = 'Invalid security token. Please refresh the page.';
    } else {
      // Sanitize and validate username
      $suseradm = sanitizeInput($_POST['useradm'] ?? '', 'alphanumeric');
      
      if (empty($suseradm)) {
        $errorMsg = 'Username tidak boleh kosong!';
      } elseif (strlen($suseradm) < 3 || strlen($suseradm) > 50) {
        $errorMsg = 'Username harus antara 3-50 karakter!';
      } else {
        // Validate password
        $newPassword = $_POST['passadm'] ?? '';
        if (empty($newPassword)) {
          $errorMsg = 'Password tidak boleh kosong!';
        } else {
          $spassadm = encrypt($newPassword);
          $logobt = isset($_POST['logobt']) ? $_POST['logobt'] : '';
          $qrbt = isset($_POST['qrbt']) ? $_POST['qrbt'] : 'disable';

          // Read config file once
          $configFile = './include/config.php';
          if (!file_exists($configFile)) {
            $errorMsg = 'File konfigurasi tidak ditemukan!';
          } else {
            $content = file_get_contents($configFile);
            
            if ($content === false) {
              $errorMsg = 'Gagal membaca file konfigurasi!';
            } else {
              // Replace username and password in one operation
              $search = array(
                "mikpay<|<$useradm",
                "mikpay>|>$passadm"
              );
              $replace = array(
                "mikpay<|<$suseradm",
                "mikpay>|>$spassadm"
              );
              
              $newcontent = str_replace($search, $replace, $content);
              
              // Write updated content
              $result = file_put_contents($configFile, $newcontent);
              
              if ($result === false) {
                $errorMsg = 'Gagal menyimpan perubahan ke file konfigurasi!';
              } else {
                // Update session if username changed
                if ($suseradm !== $useradm) {
                  $_SESSION["mikpay"] = $suseradm;
                }
                
                // Save QR button setting
                $gen = '<?php $qrbt="' . htmlspecialchars($qrbt, ENT_QUOTES, 'UTF-8') . '";?>';
                $key = './include/quickbt.php';
                $handle = fopen($key, 'w');
                if ($handle) {
                  fwrite($handle, $gen);
                  fclose($handle);
                }
                
                $successMsg = 'Username dan password berhasil diubah!';
                // Redirect after successful update
                echo "<script>setTimeout(function(){ window.location='./admin.php?id=sessions'; }, 1000);</script>";
              }
            }
          }
        }
      }
    }
  }

}
?>
<script>
  function Pass(id){
    var x = document.getElementById(id);
    if (x.type === 'password') {
    x.type = 'text';
    } else {
    x.type = 'password';
    }}
</script>

<style>
/* ========================================
   ADMIN SETTINGS - Ultra Modern Glass UI
   ======================================== */

/* Animated Background */
.settings-page-bg {
    position: fixed;
    top: 60px;
    left: 260px;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
    z-index: -1;
    overflow: hidden;
}

.settings-page-bg::before {
    content: '';
    position: absolute;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(99, 102, 241, 0.3) 0%, transparent 70%);
    top: -100px;
    right: -100px;
    animation: pulse-bg 8s ease-in-out infinite;
}

.settings-page-bg::after {
    content: '';
    position: absolute;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(249, 115, 22, 0.25) 0%, transparent 70%);
    bottom: -100px;
    left: -100px;
    animation: pulse-bg 10s ease-in-out infinite reverse;
}

@keyframes pulse-bg {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.2); opacity: 0.8; }
}

/* Main Container */
.settings-container {
    display: grid;
    grid-template-columns: 1.3fr 1fr;
    gap: 30px;
    padding: 20px;
    position: relative;
    z-index: 1;
    max-width: 1400px;
    margin: 0 auto;
}

@media (max-width: 1200px) {
    .settings-container { grid-template-columns: 1fr; }
    .settings-page-bg { left: 0; }
}

/* Page Header */
.settings-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 30px;
    padding: 25px 30px;
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.settings-header-icon {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #f97316 0%, #fb923c 100%);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: #fff;
    box-shadow: 
        0 10px 40px rgba(249, 115, 22, 0.4),
        0 0 60px rgba(249, 115, 22, 0.2);
    animation: float-icon 3s ease-in-out infinite;
}

@keyframes float-icon {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}

.settings-header-text h1 {
    margin: 0;
    font-size: 28px;
    font-weight: 800;
    color: #fff;
    text-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
}

.settings-header-text p {
    margin: 6px 0 0;
    font-size: 14px;
    color: rgba(255, 255, 255, 0.7);
}

.settings-header-actions {
    margin-left: auto;
}

.btn-reload {
    width: 50px;
    height: 50px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 14px;
    color: #fff;
    font-size: 18px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-reload:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: rotate(180deg);
    box-shadow: 0 0 30px rgba(255, 255, 255, 0.2);
}

/* Section Cards */
.glass-card {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.glass-card:hover {
    border-color: rgba(255, 255, 255, 0.2);
    box-shadow: 0 25px 80px rgba(0, 0, 0, 0.3);
}

.glass-card-header {
    padding: 25px 28px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    display: flex;
    align-items: center;
    gap: 16px;
}

.glass-card-header-icon {
    width: 52px;
    height: 52px;
    background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: #fff;
    box-shadow: 0 8px 30px rgba(77, 68, 181, 0.4);
}

.glass-card-header-text h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
    color: #fff;
}

.glass-card-header-text p {
    margin: 4px 0 0;
    font-size: 13px;
    color: rgba(255, 255, 255, 0.6);
}

.glass-card-body {
    padding: 25px 28px;
}

/* Router Cards - Neon Style */
.router-list {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.router-card {
    background: rgba(255, 255, 255, 0.06);
    border-radius: 18px;
    padding: 24px;
    display: flex;
    align-items: center;
    gap: 20px;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(255, 255, 255, 0.08);
    position: relative;
    overflow: hidden;
}

.router-card::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 5px;
    background: linear-gradient(180deg, #f97316 0%, #fb923c 50%, #fbbf24 100%);
    box-shadow: 0 0 20px rgba(249, 115, 22, 0.5);
}

.router-card::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(249, 115, 22, 0.1) 0%, transparent 50%);
    opacity: 0;
    transition: opacity 0.4s ease;
    pointer-events: none;
}

.router-card:hover {
    transform: translateY(-6px) scale(1.01);
    border-color: rgba(249, 115, 22, 0.3);
    box-shadow: 
        0 20px 60px rgba(0, 0, 0, 0.3),
        0 0 40px rgba(249, 115, 22, 0.15);
}

.router-card:hover::after {
    opacity: 1;
}

.router-icon {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    color: #fff;
    cursor: pointer;
    transition: all 0.4s ease;
    box-shadow: 
        0 8px 30px rgba(30, 27, 75, 0.5),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
    flex-shrink: 0;
    position: relative;
    z-index: 2;
}

.router-icon::before {
    content: '';
    position: absolute;
    inset: -3px;
    background: linear-gradient(135deg, #f97316 0%, #6366f1 100%);
    border-radius: 20px;
    z-index: -1;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.router-icon:hover {
    transform: scale(1.1) rotate(5deg);
}

.router-icon:hover::before {
    opacity: 1;
}

.router-info {
    flex: 1;
    min-width: 0;
    position: relative;
    z-index: 2;
}

.router-info h4 {
    margin: 0 0 8px;
    font-size: 18px;
    font-weight: 700;
    color: #fff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.router-info .router-session {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 20px;
    font-size: 12px;
    color: rgba(255, 255, 255, 0.8);
}

.router-info .router-session i {
    color: #f97316;
}

.router-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    position: relative;
    z-index: 2;
}

.router-btn {
    padding: 12px 18px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: none;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.router-btn.btn-open {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: #fff;
    box-shadow: 0 4px 20px rgba(34, 197, 94, 0.35);
}

.router-btn.btn-open:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(34, 197, 94, 0.5);
}

.router-btn.btn-edit {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: #fff;
    box-shadow: 0 4px 20px rgba(59, 130, 246, 0.35);
}

.router-btn.btn-edit:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(59, 130, 246, 0.5);
}

.router-btn.btn-delete {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: #fff;
    box-shadow: 0 4px 20px rgba(239, 68, 68, 0.35);
}

.router-btn.btn-delete:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(239, 68, 68, 0.5);
}

/* Empty State */
.empty-router {
    text-align: center;
    padding: 60px 30px;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 20px;
    border: 2px dashed rgba(255, 255, 255, 0.15);
}

.empty-router i {
    font-size: 60px;
    color: rgba(255, 255, 255, 0.2);
    margin-bottom: 20px;
    display: block;
}

.empty-router p {
    color: rgba(255, 255, 255, 0.5);
    font-size: 15px;
    margin: 0;
    line-height: 1.8;
}

/* Admin Panel - Premium Style */
.admin-panel {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    overflow: hidden;
}

.admin-panel-header {
    background: linear-gradient(135deg, rgba(249, 115, 22, 0.2) 0%, rgba(251, 146, 60, 0.1) 100%);
    padding: 28px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    display: flex;
    align-items: center;
    gap: 18px;
}

.admin-panel-header-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #f97316 0%, #fb923c 100%);
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #fff;
    box-shadow: 0 10px 40px rgba(249, 115, 22, 0.4);
}

.admin-panel-header-text h3 {
    margin: 0;
    font-size: 22px;
    font-weight: 800;
    color: #fff;
}

.admin-panel-header-text p {
    margin: 5px 0 0;
    font-size: 13px;
    color: rgba(255, 255, 255, 0.6);
}

.admin-panel-body {
    padding: 30px;
}

/* Form Styling */
.form-group {
    margin-bottom: 25px;
}

.form-group:last-of-type {
    margin-bottom: 0;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
    font-size: 14px;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.9);
}

.form-label i {
    width: 36px;
    height: 36px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #f97316;
    font-size: 14px;
}

.form-control {
    width: 100%;
    padding: 16px 20px;
    background: rgba(255, 255, 255, 0.06);
    border: 2px solid rgba(255, 255, 255, 0.1);
    border-radius: 14px;
    font-size: 15px;
    color: #fff;
    transition: all 0.3s ease;
    box-sizing: border-box;
}

.form-control::placeholder {
    color: rgba(255, 255, 255, 0.4);
}

.form-control:focus {
    border-color: #f97316;
    background: rgba(255, 255, 255, 0.1);
    box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.15);
    outline: none;
}

.form-control option {
    background: #1e1b4b;
    color: #fff;
}

/* Password Input */
.password-input-wrapper {
    display: flex;
    gap: 12px;
}

.password-input-wrapper .form-control {
    flex: 1;
}

.password-toggle-btn {
    width: 54px;
    height: 54px;
    background: rgba(255, 255, 255, 0.06);
    border: 2px solid rgba(255, 255, 255, 0.1);
    border-radius: 14px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    flex-shrink: 0;
    accent-color: #f97316;
}

.password-toggle-btn:hover {
    border-color: #f97316;
    background: rgba(249, 115, 22, 0.1);
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 35px;
    padding-top: 30px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.btn-primary {
    flex: 1;
    padding: 18px 30px;
    background: linear-gradient(135deg, #f97316 0%, #fb923c 100%);
    color: #fff;
    border: none;
    border-radius: 14px;
    cursor: pointer;
    font-size: 15px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    transition: all 0.4s ease;
    box-shadow: 0 10px 40px rgba(249, 115, 22, 0.4);
    position: relative;
    overflow: hidden;
}

.btn-primary::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s ease;
}

.btn-primary:hover {
    transform: translateY(-4px);
    box-shadow: 0 15px 50px rgba(249, 115, 22, 0.5);
}

.btn-primary:hover::before {
    left: 100%;
}

.btn-secondary {
    width: 56px;
    height: 56px;
    background: rgba(255, 255, 255, 0.06);
    border: 2px solid rgba(255, 255, 255, 0.1);
    border-radius: 14px;
    color: rgba(255, 255, 255, 0.7);
    font-size: 18px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.2);
    color: #fff;
    transform: rotate(180deg);
}

/* Version Box */
.version-box {
    margin-top: 30px;
    padding: 22px;
    background: rgba(255, 255, 255, 0.04);
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.version-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.version-label {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.5);
    display: flex;
    align-items: center;
    gap: 10px;
}

.version-label i {
    color: #f97316;
}

.version-tag {
    padding: 8px 18px;
    background: linear-gradient(135deg, rgba(77, 68, 181, 0.3) 0%, rgba(99, 102, 241, 0.2) 100%);
    border-radius: 10px;
    font-size: 14px;
    font-weight: 700;
    color: #fff;
    border: 1px solid rgba(99, 102, 241, 0.3);
}

.version-update {
    margin-top: 15px;
}

/* Responsive */
@media (max-width: 768px) {
    .router-card {
        flex-direction: column;
        text-align: center;
        padding: 25px 20px;
    }
    
    .router-card::before {
        width: 100%;
        height: 5px;
        bottom: auto;
    }
    
    .router-info {
        width: 100%;
    }
    
    .router-actions {
        justify-content: center;
        width: 100%;
    }
    
    .password-input-wrapper {
        flex-direction: column;
    }
    
    .password-toggle-btn {
        width: 100%;
        height: 50px;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn-secondary {
        width: 100%;
        height: 50px;
    }
}

/* Alert Messages */
.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    font-weight: 500;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.alert-success {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: #fff;
    border-left: 4px solid #15803d;
}

.alert-warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: #fff;
    border-left: 4px solid #b45309;
}

.alert-danger {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: #fff;
    border-left: 4px solid #b91c1c;
}

.alert i {
    font-size: 18px;
}
</style>

<!-- Background Effect -->
<div class="settings-page-bg"></div>

<div style="padding: 20px; max-width: 1400px; margin: 0 auto;">
    <!-- Success/Error Messages -->
    <?php
    if (isset($_GET['msg'])) {
        $msg = $_GET['msg'];
        $alertClass = 'alert-success';
        $alertIcon = 'fa-check-circle';
        $alertMessage = '';
        
        switch($msg) {
            case 'deleted':
                $alertMessage = 'Router session berhasil dihapus!';
                break;
            case 'not_found':
                $alertClass = 'alert-warning';
                $alertIcon = 'fa-exclamation-triangle';
                $alertMessage = 'Router session tidak ditemukan!';
                break;
            case 'cannot_delete_mikpay':
                $alertClass = 'alert-danger';
                $alertIcon = 'fa-ban';
                $alertMessage = 'Tidak dapat menghapus session mikpay utama!';
                break;
            case 'config_not_found':
                $alertClass = 'alert-danger';
                $alertIcon = 'fa-exclamation-circle';
                $alertMessage = 'File config tidak ditemukan!';
                break;
            case 'config_not_writable':
                $alertClass = 'alert-danger';
                $alertIcon = 'fa-lock';
                $alertMessage = 'File config tidak dapat ditulis! Silakan periksa izin file.';
                break;
            case 'read_error':
                $alertClass = 'alert-danger';
                $alertIcon = 'fa-exclamation-circle';
                $alertMessage = 'Gagal membaca file config!';
                break;
            case 'write_error':
                $alertClass = 'alert-danger';
                $alertIcon = 'fa-exclamation-circle';
                $alertMessage = 'Gagal menulis file config! Silakan periksa izin file.';
                break;
        }
        
        if (!empty($alertMessage)) {
            echo '<div class="alert ' . $alertClass . '" style="margin-bottom: 20px; padding: 15px; border-radius: 8px; display: flex; align-items: center; gap: 10px;">
                <i class="fa ' . $alertIcon . '"></i>
                <span>' . htmlspecialchars($alertMessage) . '</span>
            </div>';
        }
    }
    ?>
    <!-- Page Header -->
    <div class="settings-header">
        <div class="settings-header-icon">
            <i class="fa fa-cogs"></i>
        </div>
        <div class="settings-header-text">
            <h1><?= $_admin_settings ?></h1>
            <p>Configure your system and manage routers</p>
        </div>
        <div class="settings-header-actions">
            <button class="btn-reload" onclick="location.reload();" title="Reload">
                <i class="fa fa-refresh"></i>
            </button>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="settings-container">
        <!-- Left Column - Router List -->
        <div class="glass-card">
            <div class="glass-card-header">
                <div class="glass-card-header-icon">
                    <i class="fa fa-server"></i>
                </div>
                <div class="glass-card-header-text">
                    <h2><?= $_router_list ?></h2>
                    <p>Manage your MikroTik routers</p>
                </div>
            </div>
            <div class="glass-card-body">
                <div class="router-list">
                    <?php
                    $hasRouters = false;
                    // Improved parsing: use regex to find valid session definitions
                    $configFile = './include/config.php';
                    if (file_exists($configFile)) {
                        $configLines = file($configFile);
                        foreach ($configLines as $line) {
                            // Match pattern: $data['SESSION_NAME'] = array(
                            if (preg_match("/\\\$data\['([^']+)'\]\s*=\s*array\s*\(/", $line, $matches)) {
                                $value = $matches[1];
                                // Skip admin config and empty values
                                if ($value == "" || $value == "mikpay") {
                                    continue;
                                }
                                // Check if session exists in $data array and has required fields
                                if (isset($data[$value]) && is_array($data[$value]) && isset($data[$value][4])) {
                                    $hasRouters = true;
                                    // Safely extract router name
                                    $routerName = "Unknown Router";
                                    if (isset($data[$value][4]) && strpos($data[$value][4], '%') !== false) {
                                        $nameParts = explode('%', $data[$value][4]);
                                        if (isset($nameParts[1]) && !empty($nameParts[1])) {
                                            $routerName = $nameParts[1];
                                        }
                                    }
                    ?>
                            <div class="router-card">
                                <div class="router-icon connect" id="<?= htmlspecialchars($value); ?>">
                                    <i class="fa fa-server"></i>
                                </div>
                                <div class="router-info">
                                    <h4><?= htmlspecialchars($routerName); ?></h4>
                                    <span class="router-session">
                                        <i class="fa fa-tag"></i> <?= htmlspecialchars($value); ?>
                                    </span>
                                </div>
                                <div class="router-actions">
                                    <span class="router-btn btn-open connect" id="<?= htmlspecialchars($value); ?>"><i class="fa fa-play"></i> <?= $_open ?></span>
                                    <a class="router-btn btn-edit" href="./admin.php?id=settings&session=<?= htmlspecialchars($value); ?>"><i class="fa fa-cog"></i> <?= $_edit ?></a>
                                    <a class="router-btn btn-delete" href="javascript:void(0)" onclick="if(confirm('Are you sure to delete <?= htmlspecialchars($value); ?>?')){window.location.href='./admin.php?id=remove-session&session=<?= htmlspecialchars($value); ?>'}"><i class="fa fa-trash"></i></a>
                                </div>
                            </div>
                    <?php 
                                }
                            }
                        }
                    }
                    if (!$hasRouters) { ?>
                        <div class="empty-router">
                            <i class="fa fa-server"></i>
                            <p>No routers configured yet.<br>Click <strong>"Add Router"</strong> in the sidebar to get started.</p>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
        
        <!-- Right Column - Admin Settings -->
        <div>
            <form autocomplete="off" method="post" action="">
                <?= getCSRFTokenField() ?>
                <div class="admin-panel">
                    <div class="admin-panel-header">
                        <div class="admin-panel-header-icon">
                            <i class="fa fa-shield"></i>
                        </div>
                        <div class="admin-panel-header-text">
                            <h3><?= $_admin ?> Account</h3>
                            <p>Secure your administrator access</p>
                        </div>
                    </div>
                    <div class="admin-panel-body">
                        <?php if (!empty($errorMsg)): ?>
                        <div style="padding: 12px; margin-bottom: 20px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; color: #fca5a5;">
                            <i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($errorMsg) ?>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($successMsg)): ?>
                        <div style="padding: 12px; margin-bottom: 20px; background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 8px; color: #86efac;">
                            <i class="fa fa-check-circle"></i> <?= htmlspecialchars($successMsg) ?>
                        </div>
                        <?php endif; ?>
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fa fa-user"></i>
                                <?= $_user_name ?>
                            </label>
                            <input class="form-control" id="useradm" type="text" name="useradm" value="<?= $useradm; ?>" required placeholder="Enter admin username"/>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fa fa-lock"></i>
                                <?= $_password ?>
                            </label>
                            <div class="password-input-wrapper">
                                <input class="form-control" id="passadm" type="password" name="passadm" value="<?= decrypt($passadm); ?>" required placeholder="Enter secure password"/>
                                <input class="password-toggle-btn" type="checkbox" onclick="Pass('passadm')" title="Show/Hide">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fa fa-qrcode"></i>
                                <?= $_quick_print ?> QR
                            </label>
                            <select class="form-control" name="qrbt">
                                <option><?= $qrbt ?></option>
                                <option value="enable">Enable</option>
                                <option value="disable">Disable</option>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="save" class="btn-primary">
                                <i class="fa fa-save"></i> <?= $_save ?> Changes
                            </button>
                            <button type="button" onclick="location.reload();" class="btn-secondary">
                                <i class="fa fa-refresh"></i>
                            </button>
                        </div>
                        
                        <div class="version-box">
                            <div class="version-content">
                                <span class="version-label">
                                    <i class="fa fa-code-fork"></i> Current Version
                                </span>
                                <span class="version-tag" id="loadV">v<?= $_SESSION['v']; ?></span>
                            </div>
                            <div class="version-update">
                                <span id="newVer" class="text-green"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
  var _0x7470=["\x68\x6F\x73\x74\x6E\x61\x6D\x65","\x6C\x6F\x63\x61\x74\x69\x6F\x6E","\x2E","\x73\x70\x6C\x69\x74","\x6D\x69\x6B\x68\x6D\x6F\x6E\x2E\x6F\x6E\x6C\x69\x6E\x65","\x78\x62\x61\x6E\x2E\x78\x79\x7A","\x6C\x6F\x67\x61\x6D\x2E\x69\x64","\x6D\x69\x6E\x69\x73\x2E\x69\x64","\x69\x6E\x64\x65\x78\x4F\x66","\x3C\x73\x70\x61\x6E\x20\x3E\x3C\x69\x20\x63\x6C\x61\x73\x73\x3D\x22\x74\x65\x78\x74\x2D\x77\x68\x69\x74\x65\x20\x66\x61\x20\x66\x61\x2D\x69\x6E\x66\x6F\x2D\x63\x69\x72\x63\x6C\x65\x22\x3E\x3C\x2F\x69\x3E\x20\x3C\x61\x20\x63\x6C\x61\x73\x73\x3D\x22\x74\x65\x78\x74\x2D\x62\x6C\x75\x65\x22\x20\x68\x72\x65\x66\x3D\x22\x2E\x2F\x61\x64\x6D\x69\x6E\x2E\x70\x68\x70\x3F\x69\x64\x3D\x61\x62\x6F\x75\x74\x22\x3E\x43\x68\x65\x63\x6B\x20\x55\x70\x64\x61\x74\x65\x3C\x2F\x61\x3E\x3C\x2F\x73\x70\x61\x6E\x3E","\x68\x74\x6D\x6C","\x23\x6E\x65\x77\x56\x65\x72","\x68\x74\x74\x70\x73\x3A\x2F\x2F\x72\x61\x77\x2E\x67\x69\x74\x68\x75\x62\x75\x73\x65\x72\x63\x6F\x6E\x74\x65\x6E\x74\x2E\x63\x6F\x6D\x2F\x6C\x61\x6B\x73\x61\x31\x39\x2F\x6D\x69\x6B\x68\x6D\x6F\x6E\x76\x33\x2F\x6D\x61\x73\x74\x65\x72\x2F\x76\x65\x72\x73\x6F\x6E\x2E\x74\x78\x74\x3F\x74\x3D","\x72\x61\x6E\x64\x6F\x6D","\x66\x6C\x6F\x6F\x72","\x76","\x76\x65\x72\x73\x69\x6F\x6E","","\x72\x65\x70\x6C\x61\x63\x65","\x69\x6E\x6E\x65\x72\x48\x54\x4D\x4C","\x6C\x6F\x61\x64\x56","\x67\x65\x74\x45\x6C\x65\x6D\x65\x6E\x74\x42\x79\x49\x64","\x20","\x75\x70\x64\x61\x74\x65\x64","\x2D","\x4E\x65\x77\x20\x56\x65\x72\x73\x69\x6F\x6E\x20","\x3C\x62\x72\x3E\x3C\x73\x70\x61\x6E\x20\x3E\x3C\x69\x20\x63\x6C\x61\x73\x73\x3D\x22\x74\x65\x78\x74\x2D\x77\x68\x69\x74\x65\x20\x66\x61\x20\x66\x61\x2D\x69\x6E\x66\x6F\x2D\x63\x69\x72\x63\x6C\x65\x22\x3E\x3C\x2F\x69\x3E\x20\x3C\x61\x20\x63\x6C\x61\x73\x73\x3D\x22\x74\x65\x78\x74\x2D\x62\x6C\x75\x65\x22\x20\x68\x72\x65\x66\x3D\x22\x2E\x2F\x61\x64\x6D\x69\x6E\x2E\x70\x68\x70\x3F\x69\x64\x3D\x61\x62\x6F\x75\x74\x22\x3E\x43\x68\x65\x63\x6B\x20\x55\x70\x64\x61\x74\x65\x3C\x2F\x61\x3E\x3C\x2F\x73\x70\x61\x6E\x3E","\x67\x65\x74\x4A\x53\x4F\x4E"];var hname=window[_0x7470[1]][_0x7470[0]];var dom=hname[_0x7470[3]](_0x7470[2])[1]+ _0x7470[2]+ hname[_0x7470[3]](_0x7470[2])[2];var domArray=[_0x7470[4],_0x7470[5],_0x7470[6],_0x7470[7]];var a=domArray[_0x7470[8]](hname);var b=domArray[_0x7470[8]](dom);if(dom== _0x7470[4]){$(_0x7470[11])[_0x7470[10]](_0x7470[9])}else {if(a> 0|| b> 0){}else {$[_0x7470[27]](_0x7470[12]+ (Math[_0x7470[14]]((Math[_0x7470[13]]()* 999999999)+ 1))* 128,function(_0xc1b4x6){getNewVer= (_0xc1b4x6[_0x7470[16]])[_0x7470[3]](_0x7470[15])[1];var _0xc1b4x7=parseInt(getNewVer[_0x7470[18]](_0x7470[2],_0x7470[17]));var _0xc1b4x8=document[_0x7470[21]](_0x7470[20])[_0x7470[19]];var _0xc1b4x9=(_0xc1b4x8[_0x7470[3]](_0x7470[22])[0])[_0x7470[3]](_0x7470[15])[1];var _0xc1b4xa=parseInt(_0xc1b4x9[_0x7470[18]](_0x7470[2],_0x7470[17]));var _0xc1b4xb=(_0xc1b4x7- _0xc1b4xa);getNewVer= (_0xc1b4x6[_0x7470[16]])[_0x7470[3]](_0x7470[15])[1];var _0xc1b4x7=parseInt(getNewVer[_0x7470[18]](_0x7470[2],_0x7470[17]));var _0xc1b4x8=document[_0x7470[21]](_0x7470[20])[_0x7470[19]];var _0xc1b4x9=(_0xc1b4x8[_0x7470[3]](_0x7470[22])[0])[_0x7470[3]](_0x7470[15])[1];var _0xc1b4xa=parseInt(_0xc1b4x9[_0x7470[18]](_0x7470[2],_0x7470[17]));var _0xc1b4xb=(_0xc1b4x7- _0xc1b4xa);getNewD= (_0xc1b4x6[_0x7470[23]])[_0x7470[3]](_0x7470[22])[0];newD= parseInt((getNewD)[_0x7470[3]](_0x7470[24])[2]+ (getNewD)[_0x7470[3]](_0x7470[24])[0]+ (getNewD)[_0x7470[3]](_0x7470[24])[1]);var _0xc1b4xc=parseInt((_0xc1b4x8[_0x7470[3]](_0x7470[22])[1])[_0x7470[3]](_0x7470[24])[2]+ (_0xc1b4x8[_0x7470[3]](_0x7470[22])[1])[_0x7470[3]](_0x7470[24])[0]+ (_0xc1b4x8[_0x7470[3]](_0x7470[22])[1][_0x7470[3]](_0x7470[24]))[1]);var _0xc1b4xd=(newD- _0xc1b4xc);if(_0xc1b4xb> 0|| _0xc1b4xd> 0){$(_0x7470[11])[_0x7470[10]](_0x7470[25]+ _0xc1b4x6[_0x7470[16]]+ _0x7470[22]+ _0xc1b4x6[_0x7470[23]]+ _0x7470[26])}})}}
</script>









