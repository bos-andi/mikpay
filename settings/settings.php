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

// hide all error
error_reporting(0);

// Include business config
include_once('./include/business_config.php');
$businessSettings = getBusinessSettings();

if (!isset($_SESSION["mikpay"])) {
  header("Location:../admin.php?id=login");
} else {

  // Handle save business settings
  if (isset($_POST['save_business'])) {
    $businessSettings['business_name'] = trim($_POST['business_name'] ?? 'MIKPAY');
    $businessSettings['business_address'] = trim($_POST['business_address'] ?? '');
    $businessSettings['business_phone'] = trim($_POST['business_phone'] ?? '');
    saveBusinessSettings($businessSettings);
  }

  if ($id == "settings" && explode("-",$router)[0] == "new") {
    include_once('./include/router_logger.php');
    
    $configFile = './include/config.php';
    
    // Check if config file exists and is writable
    if (!file_exists($configFile)) {
      logRouterCreate($router, false, 'Config file not found');
      logFilePermissionError($configFile, 'CREATE_ROUTER');
      echo "<script>alert('Config file not found!'); window.location='./admin.php?id=sessions'</script>";
      exit;
    }
    
    if (!is_writable($configFile)) {
      logRouterCreate($router, false, 'Config file not writable');
      logFilePermissionError($configFile, 'CREATE_ROUTER');
      echo "<script>alert('Config file is not writable! Please check file permissions.'); window.location='./admin.php?id=sessions'</script>";
      exit;
    }
    
    // Check if router session already exists
    $content = file_get_contents($configFile);
    if (strpos($content, "'".$router."'") !== false) {
      logRouterCreate($router, false, 'Router session already exists');
      echo "<script>alert('Router session already exists!'); window.location='./admin.php?id=settings&session=" . $router . "'</script>";
      exit;
    }
    
    $data = '$data';
    $f = fopen($configFile, 'a');
    
    if ($f === false) {
      logRouterCreate($router, false, 'Failed to open config file for writing');
      logFilePermissionError($configFile, 'CREATE_ROUTER');
      echo "<script>alert('Failed to open config file for writing! Please check file permissions.'); window.location='./admin.php?id=sessions'</script>";
      exit;
    }
    
    $writeResult = fwrite($f, "\n'$'data['".$router."'] = array ('1'=>'".$router."!','".$router."@|@','".$router."#|#','".$router."%','".$router."^','".$router."&Rp','".$router."*10','".$router."(1','".$router.")','".$router."=10','".$router."@!@disable');");
    fclose($f);
    
    if ($writeResult === false) {
      logRouterCreate($router, false, 'Failed to write to config file');
      logFilePermissionError($configFile, 'CREATE_ROUTER');
      echo "<script>alert('Failed to write to config file! Please check file permissions.'); window.location='./admin.php?id=sessions'</script>";
      exit;
    }
    
    // Replace the temporary '$'data with actual $data
    $search = "'$'data";
    $replace = (string)"$data";
    $file = file($configFile);
    $content = file_get_contents($configFile);
    $newcontent = str_replace((string)$search, (string)$replace, "$content");
    
    $putResult = file_put_contents($configFile, "$newcontent");
    
    if ($putResult === false) {
      logRouterCreate($router, false, 'Failed to update config file');
      logFilePermissionError($configFile, 'CREATE_ROUTER');
      echo "<script>alert('Failed to update config file! Please check file permissions.'); window.location='./admin.php?id=sessions'</script>";
      exit;
    }
    
    logRouterCreate($router, true);
    echo "<script>window.location='./admin.php?id=settings&session=" . $router . "'</script>";
    exit;
  }

  if (isset($_POST['save'])) {

    $siphost = (preg_replace('/\s+/', '', $_POST['ipmik']));
    $suserhost = ($_POST['usermik']);
    $spasswdhost = encrypt($_POST['passmik']);
    $shotspotname = str_replace("'","",$_POST['hotspotname']);
    $sdnsname = ($_POST['dnsname']);
    $scurrency = ($_POST['currency']);
    $sreload = ($_POST['areload']);
    if ($sreload < 10) {
      $sreload = 10;
    } else {
      $sreload = $sreload;
    }
    $siface = ($_POST['iface']);
    $sinfolp = implode(unpack("H*", $_POST['infolp']));
    //$sinfolp = encrypt($_POST['infolp']);
    //$sinfolp = ($_POST['infolp']);
    $sidleto = ($_POST['idleto']);

    $sesname = (preg_replace('/\s+/', '-', $_POST['sessname']));
    $slivereport = ($_POST['livereport']);

    $search = array('1' => "$session!$iphost", "$session@|@$userhost", "$session#|#$passwdhost", "$session%$hotspotname", "$session^$dnsname", "$session&$currency", "$session*$areload", "$session($iface", "$session)$infolp", "$session=$idleto", "'$session'", "$session@!@$livereport");

    $replace = array('1' => "$sesname!$siphost", "$sesname@|@$suserhost", "$sesname#|#$spasswdhost", "$sesname%$shotspotname", "$sesname^$sdnsname", "$sesname&$scurrency", "$sesname*$sreload", "$sesname($siface", "$sesname)$sinfolp", "$sesname=$sidleto", "'$sesname'", "$sesname@!@$slivereport");

    for ($i = 1; $i < 15; $i++) {
      $file = file("./include/config.php");
      $content = file_get_contents("./include/config.php");
      $newcontent = str_replace((string)$search[$i], (string)$replace[$i], "$content");
      file_put_contents("./include/config.php", "$newcontent");
    }
    
    $_SESSION["connect"] = "";
    echo "<script>window.location='./admin.php?id=settings&session=" . $sesname . "'</script>";
  }
  if ($currency == "") {
    echo "<script>window.location='./admin.php?id=settings&session=" . $session . "'</script>";
  }
}
?>
<script>
  function PassMk(){
    var x = document.getElementById('passmk');
    if (x.type === 'password') {
    x.type = 'text';
    } else {
    x.type = 'password';
    }}
    function PassAdm(){
    var x = document.getElementById('passadm');
    if (x.type === 'password') {
    x.type = 'text';
    } else {
    x.type = 'password';
  }}
  
</script>

<style>
/* Modern Settings Styling */
.settings-container {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}
.settings-col {
    flex: 1;
    min-width: 300px;
}
.settings-section {
    background: #fff;
    border-radius: 12px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    overflow: hidden;
}
.settings-section-header {
    background: linear-gradient(135deg, #4D44B5 0%, #3d3690 100%);
    color: #fff;
    padding: 15px 20px;
    font-size: 15px;
    font-weight: 600;
}
.settings-section-header i {
    margin-right: 10px;
}
.settings-section-body {
    padding: 20px;
}
.settings-row {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
    gap: 15px;
}
.settings-row:last-child {
    margin-bottom: 0;
}
.settings-label {
    min-width: 140px;
    font-size: 13px;
    font-weight: 500;
    color: #374151;
}
.settings-input {
    flex: 1;
}
.settings-input input,
.settings-input select {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 13px;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.settings-input input:focus,
.settings-input select:focus {
    outline: none;
    border-color: #4D44B5;
    box-shadow: 0 0 0 3px rgba(77,68,181,0.1);
}
.settings-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #f0f0f0;
}
.settings-actions .btn {
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.btn-save-settings {
    background: linear-gradient(135deg, #4D44B5 0%, #3d3690 100%);
    color: #fff;
}
.btn-connect {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: #fff;
}
.btn-ping {
    background: #f8f9fa;
    color: #374151;
    border: 1px solid #e0e0e0 !important;
}
.btn-reload {
    background: #f8f9fa;
    color: #374151;
    border: 1px solid #e0e0e0 !important;
}
.password-toggle {
    display: flex;
    align-items: center;
    gap: 10px;
}
.password-toggle input[type="password"],
.password-toggle input[type="text"] {
    flex: 1;
}
.password-toggle input[type="checkbox"] {
    width: 16px;
    height: 16px;
}
</style>

<form autocomplete="off" method="post" action="" name="settings">
<div class="card">
    <div class="card-header">
        <h3><i class="fa fa-gear"></i> <?= $_session_settings ?> 
            <span style="cursor:pointer; margin-left: 15px;" onclick="location.reload();" title="Reload data"><i class="fa fa-refresh"></i></span>
        </h3>
    </div>
    <div class="card-body" style="padding: 20px;">
        <div class="settings-container">
            <!-- Left Column -->
            <div class="settings-col">
                <!-- Session -->
                <div class="settings-section">
                    <div class="settings-section-header">
                        <i class="fa fa-bookmark"></i> <?= $_session ?>
                    </div>
                    <div class="settings-section-body">
                        <div class="settings-row">
                            <span class="settings-label"><?= $_session_name ?></span>
                            <div class="settings-input">
                                <input id="sessname" type="text" name="sessname" title="Session Name" value="<?php echo (explode("-",$session)[0] == "new") ? "" : $session; ?>" required="1"/>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- MikroTik -->
                <div class="settings-section">
                    <div class="settings-section-header">
                        <i class="fa fa-server"></i> MikroTik <?= $_SESSION["connect"]; ?>
                    </div>
                    <div class="settings-section-body">
                        <div class="settings-row">
                            <span class="settings-label">IP MikroTik</span>
                            <div class="settings-input">
                                <input type="text" name="ipmik" title="IP MikroTik / IP Cloud MikroTik" value="<?= $iphost; ?>" required="1"/>
                            </div>
                        </div>
                        <div class="settings-row">
                            <span class="settings-label">Username</span>
                            <div class="settings-input">
                                <input id="usermk" type="text" name="usermik" title="User MikroTik" value="<?= $userhost; ?>" required="1"/>
                            </div>
                        </div>
                        <div class="settings-row">
                            <span class="settings-label">Password</span>
                            <div class="settings-input">
                                <div class="password-toggle">
                                    <input id="passmk" type="password" name="passmik" title="Password MikroTik" value="<?= decrypt($passwdhost); ?>" required="1"/>
                                    <input title="Show/Hide Password" type="checkbox" onclick="PassMk()">
                                </div>
                            </div>
                        </div>
                        <div class="settings-actions">
                            <button type="submit" class="btn btn-save-settings" name="save"><i class="fa fa-save"></i> Save</button>
                            <span class="btn btn-connect connect" id="<?= $session; ?>&c=settings"><i class="fa fa-plug"></i> Connect</span>
                            <span class="btn btn-ping" id="ping_test"><i class="fa fa-signal"></i> Ping</span>
                            <span class="btn btn-reload" onclick="location.reload();"><i class="fa fa-refresh"></i></span>
                        </div>
                    </div>
                </div>
                
                <div id="ping"></div>
            </div>
            
            <!-- Right Column -->
            <div class="settings-col">
                <!-- Business Info -->
                <div class="settings-section">
                    <div class="settings-section-header">
                        <i class="fa fa-building"></i> Informasi Bisnis
                    </div>
                    <div class="settings-section-body">
                        <div class="settings-row">
                            <span class="settings-label">Nama Bisnis/Usaha</span>
                            <div class="settings-input">
                                <input type="text" maxlength="100" name="business_name" title="Nama Bisnis" value="<?= htmlspecialchars($businessSettings['business_name'] ?? 'MIKPAY'); ?>" placeholder="Contoh: WiFi Mantap Jaya"/>
                            </div>
                        </div>
                        <div class="settings-row">
                            <span class="settings-label">Alamat Bisnis</span>
                            <div class="settings-input">
                                <input type="text" maxlength="200" name="business_address" title="Alamat Bisnis" value="<?= htmlspecialchars($businessSettings['business_address'] ?? ''); ?>" placeholder="Jl. Contoh No. 123"/>
                            </div>
                        </div>
                        <div class="settings-row">
                            <span class="settings-label">No. Telepon/WA</span>
                            <div class="settings-input">
                                <input type="text" maxlength="20" name="business_phone" title="No. Telepon" value="<?= htmlspecialchars($businessSettings['business_phone'] ?? ''); ?>" placeholder="08123456789"/>
                            </div>
                        </div>
                        <div class="settings-row">
                            <span class="settings-label"></span>
                            <div class="settings-input">
                                <button type="submit" name="save_business" class="btn-business-save">
                                    <i class="fa fa-save"></i> Simpan Info Bisnis
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Mikpay Data -->
                <div class="settings-section">
                    <div class="settings-section-header">
                        <i class="fa fa-database"></i> Mikpay Data
                    </div>
                    <div class="settings-section-body">
                        <div class="settings-row">
                            <span class="settings-label"><?= $_hotspot_name ?></span>
                            <div class="settings-input">
                                <input type="text" maxlength="50" name="hotspotname" title="Hotspot Name" value="<?= $hotspotname; ?>" required="1"/>
                            </div>
                        </div>
                        <div class="settings-row">
                            <span class="settings-label"><?= $_dns_name ?></span>
                            <div class="settings-input">
                                <input type="text" maxlength="500" name="dnsname" title="DNS Name" value="<?= $dnsname; ?>" required="1"/>
                            </div>
                        </div>
                        <div class="settings-row">
                            <span class="settings-label"><?= $_currency ?></span>
                            <div class="settings-input">
                                <input type="text" maxlength="4" name="currency" title="Currency" value="<?= $currency; ?>" required="1"/>
                            </div>
                        </div>
                        <div class="settings-row">
                            <span class="settings-label"><?= $_auto_reload ?></span>
                            <div class="settings-input" style="display:flex; gap:10px; align-items:center;">
                                <input type="number" min="10" max="3600" name="areload" title="Auto Reload in sec [min 10]" value="<?= $areload; ?>" required="1" style="flex:1;"/>
                                <span style="color:#666; font-size:13px;"><?= $_sec ?></span>
                            </div>
                        </div>
                        <div class="settings-row">
                            <span class="settings-label"><?= $_idle_timeout ?></span>
                            <div class="settings-input" style="display:flex; gap:10px; align-items:center;">
                                <select name="idleto" required="1" style="flex:1;">
                                    <option value="<?= $idleto; ?>"><?= $idleto; ?></option>
                                    <option value="5">5</option>
                                    <option value="10">10</option>
                                    <option value="30">30</option>
                                    <option value="60">60</option>
                                    <option value="disable">disable</option>
                                </select>
                                <span style="color:#666; font-size:13px;"><?= $_min ?></span>
                            </div>
                        </div>
                        <div class="settings-row">
                            <span class="settings-label"><?= $_traffic_interface ?></span>
                            <div class="settings-input">
                                <input type="number" min="1" max="99" name="iface" title="Traffic Interface" value="<?= $iface; ?>" required="1"/>
                            </div>
                        </div>
                        <?php if (!empty($livereport)) { ?>
                        <div class="settings-row">
                            <span class="settings-label"><?= $_live_report ?></span>
                            <div class="settings-input">
                                <select name="livereport">
                                    <option value="<?= $livereport; ?>"><?= ucfirst($livereport); ?></option>
                                    <option value="enable">Enable</option>
                                    <option value="disable">Disable</option>
                                </select>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</form>
<script type="text/javascript">

// Ping test functionality
function pingTest(session) {
    $('#ping').load('./status/ping-test.php?ping&session=' + session);
}
var sessX = document.getElementById('sessname').value;
document.getElementById('ping_test').onclick = function() { pingTest(sessX); };
function closeX() { $('#pingX').hide(); }

// Session name validation
var sesname = document.settings.sessname;
function chksname() {
    if (sesname.value == "mikpay" || sesname.value == "MIKPAY" || sesname.value == "Mikpay") {
        alert("You cannot use " + sesname.value + " as a session name.");
        sesname.value = "";
        window.location.reload();
    }
}
sesname.onkeyup = chksname;
sesname.onchange = chksname;


</script>





