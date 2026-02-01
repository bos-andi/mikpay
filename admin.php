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
// Include security helpers
include_once('./include/session_security.php');
include_once('./include/password_security.php');
include_once('./include/csrf.php');
include_once('./include/input_validation.php');

// Initialize secure session
initSecureSession();

// hide all error (but log them)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

ob_start("ob_gzhandler");

// check url
$url = $_SERVER['REQUEST_URI'];

// load session MikroTik
$session = $_GET['session'];
$id = $_GET['id'];
$c = $_GET['c'];
$router = $_GET['router'];
$logo = $_GET['logo'];

$ids = array(
  "editor",
  "uplogo",
  "settings",
);

// lang
include('./lang/isocodelang.php');
include('./include/lang.php');
include('./lang/'.$langid.'.php');

// quick bt
include('./include/quickbt.php');

// theme
include('./include/theme.php');
include('./settings/settheme.php');
include('./settings/setlang.php');
if ($_SESSION['theme'] == "") {
    $theme = $theme;
    $themecolor = $themecolor;
  } else {
    $theme = $_SESSION['theme'];
    $themecolor = $_SESSION['themecolor'];
}


// load config
include_once('./include/headhtml.php');
include('./include/config.php');
include('./include/readcfg.php');

include_once('./lib/routeros_api.class.php');
include_once('./lib/formatbytesbites.php');
?>
    
<?php
if ($id == "login" || substr($url, -1) == "p") {
  if (isset($_POST['login'])) {
    // Validate CSRF token
    if (!validateCSRFPost()) {
      $error = '<div style="width: 100%; padding:5px 0px 5px 0px; border-radius:5px;" class="bg-danger"><i class="fa fa-ban"></i> Alert!<br>Invalid security token. Please refresh the page.</div>';
    } else {
      $user = sanitizeInput($_POST['user'], 'alphanumeric');
      $pass = $_POST['pass']; // Password tidak di-sanitize, langsung verify
      
      // Try admin login first (from config.php)
      if ($user == $useradm && $pass == decrypt($passadm)) {
        $_SESSION["mikpay"] = $user;
        regenerateSessionID(); // Regenerate session after login
        echo "<script>window.location='./admin.php?id=sessions'</script>";
        exit;
      }
      
      // Try user login from users.json
      include_once('./include/subscription.php');
      $jsonUser = getUser($user);
      if ($jsonUser && isset($jsonUser['password'])) {
        $storedPassword = $jsonUser['password'];
        
        // Check if password is hashed or plain text (for backward compatibility)
        $passwordValid = false;
        if (isPasswordHashed($storedPassword)) {
          // Password is hashed, use password_verify
          $passwordValid = verifySecurePassword($pass, $storedPassword);
        } else {
          // Old plain text password, compare directly (backward compatibility)
          $passwordValid = ($storedPassword === $pass);
          
          // Auto-migrate to hash if password matches
          if ($passwordValid) {
            $jsonUser['password'] = secureHashPassword($pass);
            saveUser($user, $jsonUser);
          }
        }
        
        if ($passwordValid) {
          // Check if user is active
          if (isset($jsonUser['status']) && $jsonUser['status'] === 'active') {
            $_SESSION["mikpay"] = $user;
            $_SESSION["user_id"] = $user;
            $_SESSION["user_from_json"] = true;
            regenerateSessionID(); // Regenerate session after login
            echo "<script>window.location='./admin.php?id=sessions'</script>";
            exit;
          } else {
            $error = '<div style="width: 100%; padding:5px 0px 5px 0px; border-radius:5px;" class="bg-danger"><i class="fa fa-ban"></i> Alert!<br>Akun Anda telah dinonaktifkan.</div>';
          }
        } else {
          $error = '<div style="width: 100%; padding:5px 0px 5px 0px; border-radius:5px;" class="bg-danger"><i class="fa fa-ban"></i> Alert!<br>Invalid username or password.</div>';
        }
      } else {
        $error = '<div style="width: 100%; padding:5px 0px 5px 0px; border-radius:5px;" class="bg-danger"><i class="fa fa-ban"></i> Alert!<br>Invalid username or password.</div>';
      }
    }
  }

  include_once('./include/login.php');
} elseif (!isset($_SESSION["mikpay"])) {
  echo "<script>window.location='./admin.php?id=login'</script>";
  exit;
} elseif (!checkSessionValidity()) {
  // Session expired or invalid
  destroySecureSession();
  echo "<script>window.location='./admin.php?id=login&msg=timeout'</script>";
  exit;
} elseif (substr($url, -1) == "/" || substr($url, -4) == ".php") {
  echo "<script>window.location='./admin.php?id=sessions'</script>";

} elseif ($id == "sessions") {
  $_SESSION["connect"] = "";
  include_once('./include/menu.php');
  include_once('./settings/sessions.php');
  /*echo '
  <script type="text/javascript">
    document.getElementById("sessname").onkeypress = function(e) {
    var chr = String.fromCharCode(e.which);
    if (" _!@#$%^&*()+=;|?,~".indexOf(chr) >= 0)
        return false;
    };
    </script>';*/
} elseif ($id == "settings" && !empty($session) || $id == "settings" && !empty($router)) {
  include_once('./include/menu.php');
  include_once('./settings/settings.php');
  echo '
  <script type="text/javascript">
    document.getElementById("sessname").onkeypress = function(e) {
    var chr = String.fromCharCode(e.which);
    if (" _!@#$%^&*()+=;|?,~".indexOf(chr) >= 0)
        return false;
    };
    </script>';
} elseif ($id == "connect"  && !empty($session)) {
  ini_set("max_execution_time",5);  
  include_once('./include/menu.php');
  $API = new RouterosAPI();
  $API->debug = false;
  if ($API->connect($iphost, $userhost, decrypt($passwdhost))){
    $_SESSION["connect"] = "<b class='text-green'>Connected</b>";
    echo "<script>window.location='./?session=" . $session . "'</script>";
  } else {
    $_SESSION["connect"] = "<b class='text-red'>Not Connected</b>";
    $nl = '\n';
    if ($currency == in_array($currency, $cekindo['indo'])) {
      echo "<script>alert('Mikpay not connected!".$nl."Silakan periksa kembali IP, User, Password dan port API harus enable.".$nl."Jika menggunakan koneksi VPN, pastikan VPN tersebut terkoneksi.')</script>";
    }else{
      echo "<script>alert('Mikpay not connected!".$nl."Please check the IP, User, Password and port API must be enabled.')</script>";
    }
    if($c == "settings"){
      echo "<script>window.location='./admin.php?id=settings&session=" . $session . "'</script>";
    }else{
      echo "<script>window.location='./admin.php?id=sessions'</script>";
    }
  }
} elseif ($id == "uplogo"  && !empty($session)) {
  include_once('./include/menu.php');
  include_once('./settings/uplogo.php');
} elseif ($id == "reboot"  && !empty($session)) {
  include_once('./process/reboot.php');
} elseif ($id == "shutdown"  && !empty($session)) {
  include_once('./process/shutdown.php');
} elseif ($id == "remove-session" && $session != "") {
  // Don't include menu here to avoid errors - just process deletion
  include_once('./include/router_logger.php');
  
  // Prevent deletion of mikpay session
  if ($session == "mikpay") {
    logRouterDelete($session, false, 'Attempted to delete main mikpay session');
    header("Location: ./admin.php?id=sessions&msg=cannot_delete_mikpay");
    exit;
  }
  
  $configFile = "./include/config.php";
  
  // Check if config file exists and is writable
  if (!file_exists($configFile)) {
    logRouterDelete($session, false, 'Config file not found');
    logFilePermissionError($configFile, 'DELETE_ROUTER');
    echo "<script>alert('Config file not found!'); window.location='./admin.php?id=sessions'</script>";
    exit;
  }
  
  if (!is_writable($configFile)) {
    logRouterDelete($session, false, 'Config file not writable');
    logFilePermissionError($configFile, 'DELETE_ROUTER');
    echo "<script>alert('Config file is not writable! Please check file permissions.'); window.location='./admin.php?id=sessions'</script>";
    exit;
  }
  
  // Read config file
  $fc = file($configFile);
  if ($fc === false) {
    logRouterDelete($session, false, 'Failed to read config file');
    logFilePermissionError($configFile, 'DELETE_ROUTER');
    echo "<script>alert('Failed to read config file!'); window.location='./admin.php?id=sessions'</script>";
    exit;
  }
  
  // Open file for writing
  $f = fopen($configFile, "w");
  if ($f === false) {
    logRouterDelete($session, false, 'Failed to open config file for writing');
    logFilePermissionError($configFile, 'DELETE_ROUTER');
    echo "<script>alert('Failed to open config file for writing! Please check file permissions.'); window.location='./admin.php?id=sessions'</script>";
    exit;
  }
  
  // Pattern untuk mencari baris router (format: $data['SESSION_NAME'] = array (...))
  $q = "'";
  $rem = '$data['.$q.$session.$q.']';
  $removed = false;
  
  foreach ($fc as $lineNum => $line) {
    // Cek apakah baris ini mengandung router yang akan dihapus
    // Format bisa: $data['SESSION_NAME'] = array (...)
    if (strpos($line, $rem) !== false) {
      // Baris ini adalah router yang akan dihapus, skip
      $removed = true;
      continue;
    }
    
    // Tulis baris jika bukan router yang akan dihapus
    fputs($f, $line);
  }
  
  fclose($f);
  
  if ($removed) {
    logRouterDelete($session, true);
    header("Location: ./admin.php?id=sessions&msg=deleted");
  } else {
    logRouterDelete($session, false, 'Router session not found in config');
    header("Location: ./admin.php?id=sessions&msg=not_found");
  }
  exit;
} elseif ($id == "subscription") {
  include_once('./include/menu.php');
  include_once('./settings/subscription.php');
} elseif ($id == "payment") {
  include_once('./include/menu.php');
  include_once('./settings/payment.php');
} elseif ($id == "users") {
  include_once('./include/menu.php');
  include_once('./admin/users.php');
} elseif ($id == "change-password") {
  include_once('./include/menu.php');
  include_once('./settings/change-password.php');
} elseif ($id == "delete-user" && isset($_GET['user_id'])) {
  include_once('./include/subscription.php');
  $userId = $_GET['user_id'];
  if (function_exists('deleteUser')) {
    deleteUser($userId);
  }
  echo "<script>window.location='./admin.php?id=users&msg=deleted'</script>";
  exit;
} elseif ($id == "logout") {
  include_once('./include/menu.php');
  echo "<b class='cl-w'><i class='fa fa-circle-o-notch fa-spin' style='font-size:24px'></i> Logout...</b>";
  session_destroy();
  echo "<script>window.location='./admin.php?id=login'</script>";
} elseif ($id == "remove-logo" && $logo != ""  && !empty($session)) {
  include_once('./include/menu.php');
  $logopath = "./img/";
  $remlogo = $logopath . $logo;
  unlink("$remlogo");
  echo "<script>window.location='./admin.php?id=uplogo&session=" . $session . "'</script>";
} elseif ($id == "editor"  && !empty($session)) {
  include_once('./include/menu.php');
  include_once('./settings/vouchereditor.php');
} elseif (empty($id)) {
  echo "<script>window.location='./admin.php?id=sessions'</script>";
} elseif(in_array($id, $ids) && empty($session)){
	echo "<script>window.location='./admin.php?id=sessions'</script>";
}
?>
<script src="js/mikpay-ui.<?= $theme; ?>.min.js"></script>
<script src="js/mikpay.js?t=<?= str_replace(" ","_",date("Y-m-d H:i:s")); ?>"></script>
<?php include('./include/info.php'); ?>
</body>
</html>

