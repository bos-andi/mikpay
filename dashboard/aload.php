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
if (!isset($_SESSION["mikpay"])) {
  header("Location:../admin.php?id=login");
} else {
// load session MikroTik
  $session = $_GET['session'];
  $load = $_GET['load'];

// lang
include('../include/lang.php');
include('../lang/'.$langid.'.php');

// load config
  include('../include/config.php');
  include('../include/readcfg.php');

// routeros api
  include_once('../lib/routeros_api.class.php');
  include_once('../lib/formatbytesbites.php');
  $API = new RouterosAPI();
  $API->debug = false;



  if ($load == "sysresource") {

    $API->connect($iphost, $userhost, decrypt($passwdhost));

// get MikroTik system clock
    $getclock = $API->comm("/system/clock/print");
    $clock = $getclock[0];
    $timezone = $getclock[0]['time-zone-name'];
    date_default_timezone_set($timezone);

// get system resource MikroTik
    $getresource = $API->comm("/system/resource/print");
    $resource = $getresource[0];

// get routeboard info
    $getrouterboard = $API->comm("/system/routerboard/print");
    $routerboard = $getrouterboard[0];

// get sys identity
    $getidentity = $API->comm("/system/identity/print");
    $identity = $getidentity[0]['name'];

// get sys health info
    $getsyshealth = $API->comm("/system/health/print");
    $syshealth = $getsyshealth[0];

// Calculate percentages
    $cpu_percent = $resource['cpu-load'];
    $memory_percent = round((($resource['total-memory'] - $resource['free-memory']) / $resource['total-memory']) * 100, 2);
    $hdd_percent = round((($resource['total-hdd-space'] - $resource['free-hdd-space']) / $resource['total-hdd-space']) * 100, 2);
    ?>
    
    <div id="r_1" style="padding: 20px;">
        <!-- System Info Cards -->
        <div class="resource-info-row">
            <div class="resource-info-item">
                <div class="resource-info-icon" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);">
                    <i class="fa fa-microchip"></i>
                </div>
                <div class="resource-info-text">
                    <span class="resource-info-value"><?= $resource['board-name']; ?></span>
                    <span class="resource-info-label"><?= $routerboard['model']; ?></span>
                </div>
            </div>
            <div class="resource-info-item">
                <div class="resource-info-icon" style="background: linear-gradient(135deg, #22c55e, #16a34a);">
                    <i class="fa fa-cog"></i>
                </div>
                <div class="resource-info-text">
                    <span class="resource-info-value">RouterOS <?= explode(' ', $resource['version'])[0]; ?></span>
                    <span class="resource-info-label"><?= $resource['architecture-name']; ?></span>
                </div>
            </div>
            <div class="resource-info-item">
                <div class="resource-info-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    <i class="fa fa-bolt"></i>
                </div>
                <div class="resource-info-text">
                    <span class="resource-info-value"><?= isset($syshealth['voltage']) ? $syshealth['voltage'] . 'V' : '-'; ?></span>
                    <span class="resource-info-label"><?= isset($syshealth['temperature']) ? $syshealth['temperature'] . '°C' : 'Health'; ?></span>
                </div>
            </div>
        </div>
        
        <!-- Progress Bars -->
        <div class="resource-progress-section">
            <div class="resource-progress-item">
                <div class="resource-progress-header">
                    <span><i class="fa fa-tachometer"></i> CPU</span>
                    <span><?= $cpu_percent; ?>%</span>
                </div>
                <div class="progress-modern">
                    <div class="progress-bar-modern <?= $cpu_percent > 80 ? 'progress-danger' : ($cpu_percent > 50 ? 'progress-warning' : 'progress-success'); ?>" style="width: <?= $cpu_percent; ?>%"></div>
                </div>
                <div class="resource-progress-detail"><?= $resource['cpu-count']; ?>x <?= $resource['cpu-frequency']; ?> MHz</div>
            </div>
            <div class="resource-progress-item">
                <div class="resource-progress-header">
                    <span><i class="fa fa-database"></i> Memory</span>
                    <span><?= $memory_percent; ?>%</span>
                </div>
                <div class="progress-modern">
                    <div class="progress-bar-modern <?= $memory_percent > 80 ? 'progress-danger' : ($memory_percent > 50 ? 'progress-warning' : 'progress-success'); ?>" style="width: <?= $memory_percent; ?>%"></div>
                </div>
                <div class="resource-progress-detail"><?= formatBytes($resource['free-memory'], 2); ?> free / <?= formatBytes($resource['total-memory'], 2); ?></div>
            </div>
            <div class="resource-progress-item">
                <div class="resource-progress-header">
                    <span><i class="fa fa-hdd-o"></i> Storage</span>
                    <span><?= $hdd_percent; ?>%</span>
                </div>
                <div class="progress-modern">
                    <div class="progress-bar-modern <?= $hdd_percent > 80 ? 'progress-danger' : ($hdd_percent > 50 ? 'progress-warning' : 'progress-success'); ?>" style="width: <?= $hdd_percent; ?>%"></div>
                </div>
                <div class="resource-progress-detail"><?= formatBytes($resource['free-hdd-space'], 2); ?> free / <?= formatBytes($resource['total-hdd-space'], 2); ?></div>
            </div>
        </div>
    </div>

<?php 
} else if ($load == "hotspot") {

  $API->connect($iphost, $userhost, decrypt($passwdhost));
// get & counting hotspot users
  $countallusers = $API->comm("/ip/hotspot/user/print", array("count-only" => ""));
  if ($countallusers < 2) {
    $uunit = "item";
  } elseif ($countallusers > 1) {
    $uunit = "items";
  }

// get & counting hotspot active
  $counthotspotactive = $API->comm("/ip/hotspot/active/print", array("count-only" => ""));
  if ($counthotspotactive < 2) {
    $hunit = "item";
  } elseif ($counthotspotactive > 1) {
    $hunit = "items";
  }

  ?>
    
            <div id="r_2" class="card">
              <div class="card-header"><h3><i class="fa fa-wifi"></i> Hotspot</h3></div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-3 col-box-6">
                      <div class="box bg-blue bmh-75">
                        <a href="./?hotspot=active&session=<?= $session; ?>">
                          <h1><?= $counthotspotactive; ?>
                              <span style="font-size: 15px;"><?= $hunit; ?></span>
                            </h1>
                          <div>
                            <i class="fa fa-laptop"></i> <?= $_hotspot_active ?>
                          </div>
                        </a>
                      </div>
                    </div>
                    <div class="col-3 col-box-6">
                    <div class="box bg-green bmh-75">
                      <a href="./?hotspot=users&profile=all&session=<?= $session; ?>">
                            <h1><?= $countallusers; ?>
                              <span style="font-size: 15px;"><?= $uunit; ?></span>
                            </h1>
                      <div>
                            <i class="fa fa-users"></i> <?= $_hotspot_users ?>
                          </div>
                      </a>
                    </div>
                  </div>
                  <div class="col-3 col-box-6">
                    <div class="box bg-yellow bmh-75">
                      <a href="./?hotspot-user=add&session=<?= $session; ?>">
                        <div>
                          <h1><i class="fa fa-user-plus"></i>
                              <span style="font-size: 15px;"><?= $_add ?></span>
                          </h1>
                        </div>
                        <div>
                            <i class="fa fa-user-plus"></i> <?= $_hotspot_users ?>
                        </div>
                      </a>
                    </div>
                  </div>
                  <div class="col-3 col-box-6">
                    <div class="box bg-red bmh-75">
                      <a href="./?hotspot-user=generate&session=<?= $session; ?>">
                        <div>
                          <h1><i class="fa fa-user-plus"></i>
                              <span style="font-size: 15px;"><?= $_generate ?></span>
                          </h1>
                        </div>
                        <div>
                            <i class="fa fa-user-plus"></i> <?= $_hotspot_users ?>
                        </div>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
          </div>

<?php 
} else if ($load == "logs") {

  $API->connect($iphost, $userhost, decrypt($passwdhost));

  // move hotspot log to disk
  $getlogging = $API->comm("/system/logging/print", array("?prefix" => "->", ));
  $logging = $getlogging[0];
  if ($logging['prefix'] == "->") {
  } else {
    $API->comm("/system/logging/add", array("action" => "disk", "prefix" => "->", "topics" => "hotspot,info,debug", ));
  }
  
  // get hotspot log
  $getlog = $API->comm("/log/print", array("?topics" => "hotspot,info,debug", ));
  $log = array_reverse($getlog);
  //$THotspotLog = count($getlog);

  if ($livereport == "disable") {
    $logh = "457px";
    $lreport = "style='display:none;'";
  } else {
    $logh = "350px";
    $lreport = "style='display:block;'";
  }



  ?>
  
              <div id="r_3" class="row">
              <div class="card">
                <div class="card-header">
                  <h3><a href="./?hotspot=log&session=<?= $session; ?>" title="Open Hotspot Log" ><i class="fa fa-align-justify"></i> <?= $_hotspot_log ?></a></h3></div>
                    <div class="card-body">
                      <div style="padding: 5px; height: <?= $logh; ?> ;" class="mr-t-10 overflow">
                        <table class="table table-sm table-bordered table-hover" style="font-size: 12px; td.padding:2px;">
                          <thead>
                            <tr>
                            <th><?= $_time .$THotspotLog; ?></th>
                            <th><?= $_users ?> (IP)</th>
                            <th><?= $_messages ?></th>
                            </tr>
                          </thead>
                          <tbody>
                      
  <?php


  for ($i = 0; $i < 20; $i++) {
    $mess = explode(":", $log[$i]['message']);
    $time = $log[$i]['time'];
    echo "<tr>";
    if (substr($log[$i]['message'], 0, 2) == "->") {
      echo "<td>" . $time . "</td>";
    //echo substr($mess[1], 0,2);
      echo "<td>";
      if (count($mess) > 6) {
        echo $mess[1] . ":" . $mess[2] . ":" . $mess[3] . ":" . $mess[4] . ":" . $mess[5] . ":" . $mess[6];
      } else {
        echo $mess[1];
      }
      echo "</td>";
      echo "<td>";
      if (count($mess) > 6) {
        echo str_replace("trying to", "", $mess[7] . " " . $mess[8] . " " . $mess[9] . " " . $mess[10]);
      } else {
        echo str_replace("trying to", "", $mess[2] . " " . $mess[3] . " " . $mess[4] . " " . $mess[5]);
      }
      echo "</td>";
    } else {
    }
    echo "</tr>";
  }
  ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
                </div>

<?php 
}

}

?>
