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
session_start();
// hide all error
error_reporting(0);

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

  // Include database for multi-user login
  $dbLoaded = false;
  try {
    if (file_exists('./include/database.php')) {
      include_once('./include/database.php');
      $dbLoaded = function_exists('verifyUser');
    }
  } catch (Exception $e) {
    // Database not available, use old login
  }
  
  if (isset($_POST['login'])) {
    $user = $_POST['user'];
    $pass = $_POST['pass'];
    
    // Try database login first
    if ($dbLoaded) {
      try {
        $dbUser = verifyUser($user, $pass);
        
        if ($dbUser) {
          // Database user login successful
          $_SESSION["mikpay"] = $user;
          $_SESSION["user_id"] = $dbUser['id'];
          $_SESSION["user_role"] = $dbUser['role'];
          $_SESSION["user_username"] = $dbUser['username'];
          
          // Check subscription
          if (function_exists('isUserSubscriptionActive')) {
            if (!isUserSubscriptionActive($dbUser['id'])) {
              // Subscription expired - redirect to subscription page
              echo "<script>window.location='./?id=subscription&session=" . htmlspecialchars($user) . "'</script>";
              exit;
            }
          }
          
          echo "<script>window.location='./admin.php?id=sessions'</script>";
          exit;
        }
      } catch (Exception $e) {
        // Database error, fallback to old login
      }
    }
    
    // Fallback to old admin login (for backward compatibility)
    if ($user == $useradm && $pass == decrypt($passadm)) {
      $_SESSION["mikpay"] = $user;
      $_SESSION["user_role"] = 'admin';
      echo "<script>window.location='./admin.php?id=sessions'</script>";
      exit;
    }
    
    // Login failed
    $error = '<div style="width: 100%; padding:5px 0px 5px 0px; border-radius:5px;" class="bg-danger"><i class="fa fa-ban"></i> Alert!<br>Invalid username or password.</div>';
  }
  

  include_once('./include/login.php');
} elseif (!isset($_SESSION["mikpay"])) {
  echo "<script>window.location='./admin.php?id=login'</script>";
} elseif (substr($url, -1) == "/" || substr($url, -4) == ".php") {
  echo "<script>window.location='./admin.php?id=sessions'</script>";

} elseif ($id == "sessions") {
  $_SESSION["connect"] = "";
  
  // Include database for user check
  $dbLoaded = false;
  try {
    if (file_exists('./include/database.php')) {
      include_once('./include/database.php');
      $dbLoaded = true;
    }
  } catch (Exception $e) {
    // Continue without database
  }
  
  include_once('./include/menu.php');
  
  // Check if user has routers configured
  include('./include/config.php');
  include('./include/readcfg.php');
  
  // If user from database and no routers, show welcome message
  if ($dbLoaded && isset($_SESSION["user_id"])) {
    $hasRouters = false;
    if (isset($data) && is_array($data)) {
      foreach ($data as $key => $val) {
        if ($key != 'mikpay' && !empty($key)) {
          $hasRouters = true;
          break;
        }
      }
    }
    
    if (!$hasRouters) {
      // Show welcome message for new user
      echo '<div class="main-container">';
      echo '<div class="card">';
      echo '<div class="card-header">';
      echo '<h3><i class="fa fa-wifi"></i> Selamat Datang di MIKPAY</h3>';
      echo '</div>';
      echo '<div class="card-body" style="padding: 40px; text-align: center;">';
      echo '<div style="font-size: 60px; color: #4D44B5; margin-bottom: 20px;"><i class="fa fa-wifi"></i></div>';
      echo '<h2 style="color: #303972; margin-bottom: 15px;">Akun Anda Berhasil Diaktifkan!</h2>';
      echo '<p style="color: #64748b; font-size: 16px; margin-bottom: 30px;">Anda mendapat trial 5 hari. Silakan tambahkan router MikroTik Anda untuk memulai.</p>';
      echo '<a href="./admin.php?id=settings&router=new-' . rand(1111,9999) . '" class="btn bg-primary" style="padding: 15px 40px; font-size: 16px;">';
      echo '<i class="fa fa-plus"></i> Tambah Router Pertama';
      echo '</a>';
      echo '</div>';
      echo '</div>';
      echo '</div>';
      include('./include/info.php');
      echo '</body></html>';
      exit;
    }
  }
  
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
  include_once('./include/menu.php');
  $fc = file("./include/config.php" );
  $f = fopen("./include/config.php", "w");
  $q = "'";
  $rem = '$data['.$q.$session.$q.']';
  foreach ($fc as $line) {
    if (!strstr($line, $rem))
      fputs($f, $line);
  }
  fclose($f);
  echo "<script>window.location='./admin.php?id=sessions'</script>";
} elseif ($id == "subscription") {
  include_once('./include/menu.php');
  include_once('./settings/subscription.php');
} elseif ($id == "users") {
  include_once('./include/menu.php');
  include_once('./admin/users.php');
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

