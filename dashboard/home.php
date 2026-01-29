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
 *  but WITHOUT ANY implied warranty of
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

include_once('./include/business_config.php');
$dashLogo = getLogoPath($session, './');

// get MikroTik system clock
  $getclock = $API->comm("/system/clock/print");
  $clock = $getclock[0];
  $timezone = $getclock[0]['time-zone-name'];
  $_SESSION['timezone'] = $timezone;
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

  if ($livereport == "disable") {
    $logh = "457px";
    $lreport = "style='display:none;'";
  } else {
    $logh = "350px";
    $lreport = "style='display:block;'";
  }

  // Calculate CPU, Memory, HDD percentages
  $cpu_percent = $resource['cpu-load'];
  $memory_percent = round((($resource['total-memory'] - $resource['free-memory']) / $resource['total-memory']) * 100, 2);
  $hdd_percent = round((($resource['total-hdd-space'] - $resource['free-hdd-space']) / $resource['total-hdd-space']) * 100, 2);

  // Get PPP Stats
  $countpppactive = $API->comm("/ppp/active/print", array("count-only" => ""));
  $countpppsecrets = $API->comm("/ppp/secret/print", array("count-only" => ""));

  // ========== HOTSPOT INCOME CALCULATION ==========
  $hotspotIncomeToday = 0;
  $hotspotIncomeMonth = 0;
  
  if ($livereport != "disable") {
    $thisD = date("d");
    $thisM = strtolower(date("M"));
    $thisY = date("Y");
    if (strlen($thisD) == 1) {
      $thisD = "0" . $thisD;
    }
    $idhr = $thisM . "/" . $thisD . "/" . $thisY;
    $idbl = $thisM . $thisY;
    
    // Get selling report from MikroTik scripts
    $getSRBl = $API->comm("/system/script/print", array("?owner" => "$idbl"));
    if (is_array($getSRBl)) {
      foreach($getSRBl as $row) {
        $scriptName = isset($row['name']) ? $row['name'] : '';
        $parts = explode("-|-", $scriptName);
        if (count($parts) >= 4) {
          $price = floatval($parts[3]);
          $hotspotIncomeMonth += $price;
          // Check if it's today's sale
          if ($parts[0] == $idhr) {
            $hotspotIncomeToday += $price;
          }
        }
      }
    }
    $_SESSION[$session.'mincome'] = $hotspotIncomeMonth;
    $_SESSION[$session.'sdate'] = $idhr;
    $_SESSION[$session.'idhr'] = $idhr;
  }
  
  // ========== PPP INCOME CALCULATION ==========
  $pppIncomeMonth = 0;
  $pppIncomeToday = 0;
  
  // Include billing data functions
  $billingDataFile = './ppp/billing-data.php';
  if (file_exists($billingDataFile)) {
    include_once($billingDataFile);
    
    if (function_exists('getBillingPayments')) {
      $allPayments = getBillingPayments();
      $currentPeriod = date('Y-m');
      $todayDate = date('Y-m-d');
      
      foreach ($allPayments as $payment) {
        // This month's payments
        if (isset($payment['period']) && $payment['period'] === $currentPeriod) {
          $pppIncomeMonth += floatval($payment['amount']);
        }
        // Today's payments
        if (isset($payment['date']) && substr($payment['date'], 0, 10) === $todayDate) {
          $pppIncomeToday += floatval($payment['amount']);
        }
      }
    }
  }
}
?>
<link rel="stylesheet" href="../css/dashboard-custom.css?v=<?= time(); ?>">

<div id="reloadHome" class="dashboard-container-modern">
    
    <!-- Dashboard Header -->
    <div class="dashboard-header-modern">
        <div class="brand-section">
            <?php if ($dashLogo['exists']): ?>
            <img src="<?= $dashLogo['path'] ?>?v=<?= time() ?>" alt="Logo" class="dashboard-brand-logo">
            <?php endif; ?>
            <div class="brand-text">MIKPAY</div>
        </div>
        <div class="time-section">
            <span id="timezone-display"><?= $timezone; ?></span> | 
            <span id="time-display"><?= $clock['time']; ?></span> | 
            <span id="date-display"><?= ucfirst($clock['date']); ?></span>
        </div>
    </div>

    <!-- Hotspot Stats Row -->
    <div class="stats-section-label">
        <i class="fa fa-wifi"></i> HOTSPOT
    </div>
    <div class="dashboard-cards-modern">
        <div class="dashboard-card-modern card-red">
            <div class="dashboard-card-number" id="hotspot-active-count"><?= $counthotspotactive; ?></div>
            <div class="dashboard-card-label">
                <i class="fa fa-wifi"></i> Active Hotspot
            </div>
            <div class="dashboard-card-icon">
                <i class="fa fa-wifi"></i>
            </div>
        </div>
        <div class="dashboard-card-modern card-yellow">
            <div class="dashboard-card-number" id="hotspot-users-count"><?= $countallusers; ?></div>
            <div class="dashboard-card-label">
                <i class="fa fa-users"></i> Total Users
            </div>
            <div class="dashboard-card-icon">
                <i class="fa fa-users"></i>
            </div>
        </div>
        <div class="dashboard-card-modern card-green">
            <div class="dashboard-card-number" id="income-display" style="font-size: 24px;">
                <?php 
                if ($livereport != "disable" && $hotspotIncomeMonth > 0) {
                    echo $currency . " " . number_format($hotspotIncomeMonth, 0, ',', '.');
                } else {
                    echo $currency . " 0";
                }
                ?>
            </div>
            <div class="dashboard-card-label">
                <i class="fa fa-money"></i> Hotspot Income
            </div>
            <div class="dashboard-card-sub">
                <?php if ($hotspotIncomeToday > 0): ?>
                <small>Hari ini: <?= $currency ?> <?= number_format($hotspotIncomeToday, 0, ',', '.') ?></small>
                <?php else: ?>
                <small>Bulan ini</small>
                <?php endif; ?>
            </div>
            <div class="dashboard-card-icon">
                <i class="fa fa-money"></i>
            </div>
        </div>
    </div>

    <!-- PPP Stats Row -->
    <div class="stats-section-label" style="margin-top: 15px;">
        <i class="fa fa-plug"></i> PPP / PPPOE
    </div>
    <div class="dashboard-cards-modern">
        <div class="dashboard-card-modern card-ppp-active">
            <div class="dashboard-card-number"><?= $countpppactive; ?></div>
            <div class="dashboard-card-label">
                <i class="fa fa-plug"></i> Active PPP
            </div>
            <div class="dashboard-card-icon">
                <i class="fa fa-plug"></i>
            </div>
        </div>
        <div class="dashboard-card-modern card-ppp-secrets">
            <div class="dashboard-card-number"><?= $countpppsecrets; ?></div>
            <div class="dashboard-card-label">
                <i class="fa fa-key"></i> Total Secrets
            </div>
            <div class="dashboard-card-icon">
                <i class="fa fa-key"></i>
            </div>
        </div>
        <div class="dashboard-card-modern card-ppp-income">
            <div class="dashboard-card-number" style="font-size: 24px;">
                <?php 
                if ($pppIncomeMonth > 0) {
                    echo $currency . " " . number_format($pppIncomeMonth, 0, ',', '.');
                } else {
                    echo $currency . " 0";
                }
                ?>
            </div>
            <div class="dashboard-card-label">
                <i class="fa fa-money"></i> PPP Income
            </div>
            <div class="dashboard-card-sub">
                <?php if ($pppIncomeToday > 0): ?>
                <small>Hari ini: <?= $currency ?> <?= number_format($pppIncomeToday, 0, ',', '.') ?></small>
                <?php else: ?>
                <small>Bulan ini</small>
                <?php endif; ?>
            </div>
            <div class="dashboard-card-icon">
                <i class="fa fa-money"></i>
            </div>
        </div>
    </div>

    <!-- Quick Actions Row -->
    <div class="row" style="margin-top: 25px; margin-bottom: 20px;">
        <div class="col-12">
            <div class="dashboard-panel-modern quick-actions-panel">
                <div class="dashboard-panel-header" style="background: linear-gradient(135deg, #f97316 0%, #fb923c 100%);">
                    <i class="fa fa-bolt"></i> Quick Actions
                </div>
                <div class="dashboard-panel-body" style="padding: 20px;">
                    <div class="quick-actions-grid quick-actions-fullwidth">
                        <a href="./?session=<?= $session ?>&id=generateuser" class="quick-action-btn qa-generate">
                            <div class="qa-icon"><i class="fa fa-magic"></i></div>
                            <div class="qa-text">
                                <span class="qa-title">Generate User</span>
                                <span class="qa-desc">Create hotspot vouchers</span>
                            </div>
                        </a>
                        <a href="./?session=<?= $session ?>&id=adduser" class="quick-action-btn qa-adduser">
                            <div class="qa-icon"><i class="fa fa-user-plus"></i></div>
                            <div class="qa-text">
                                <span class="qa-title">Add User</span>
                                <span class="qa-desc">Manual hotspot user</span>
                            </div>
                        </a>
                        <a href="./?session=<?= $session ?>&id=addsecret" class="quick-action-btn qa-ppp">
                            <div class="qa-icon"><i class="fa fa-plug"></i></div>
                            <div class="qa-text">
                                <span class="qa-title">Add PPP Secret</span>
                                <span class="qa-desc">Create PPP account</span>
                            </div>
                        </a>
                        <a href="./?session=<?= $session ?>&id=quickuser" class="quick-action-btn qa-print">
                            <div class="qa-icon"><i class="fa fa-print"></i></div>
                            <div class="qa-text">
                                <span class="qa-title">Quick Print</span>
                                <span class="qa-desc">Print vouchers</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Middle Row Panels -->
    <div class="row" style="margin-bottom: 20px;">
        <div class="col-8">
            <div class="dashboard-panel-modern">
                <div class="dashboard-panel-header">
                    <i class="fa fa-server"></i> <?= $identity; ?> 
                    <span style="float:right; font-weight: 400; font-size: 12px;">
                        <i class="fa fa-clock-o"></i> <?= formatDTM($resource['uptime']); ?>
                    </span>
                </div>
                <div class="dashboard-panel-body" id="r_1" style="padding: 20px;">
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
            </div>
        </div>
        <div class="col-4">
            <div class="dashboard-panel-modern">
                <div class="dashboard-panel-header">
                    <i class="fa fa-list"></i> App Log
                </div>
                <div class="dashboard-panel-body">
                    <div class="app-log-container" id="applog-container">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Log</th>
                                </tr>
                            </thead>
                            <tbody id="applog-tbody">
                                <tr><td colspan="2" class="text-center">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Row -->
    <div class="row">
        <div class="col-8">
            <div class="dashboard-panel-modern">
                <div class="dashboard-panel-header">
                    <i class="fa fa-line-chart"></i> Traffic Monitor
                </div>
                <div class="dashboard-panel-body">
                    <?php $getinterface = $API->comm("/interface/print");
                    $interface = $getinterface[$iface - 1]['name']; 
                    ?>
                    <div id="trafficMonitor"></div>
                    <script type="text/javascript"> 
                        var chart;
                        var sessiondata = "<?= $session ?>";
                        var interface = "<?= $interface ?>";
                        var n = 3000;
                        function requestDatta(session,iface) {
                          $.ajax({
                            url: './traffic/traffic.php?session='+session+'&iface='+iface,
                            datatype: "json",
                            success: function(data) {
                              var midata = JSON.parse(data);
                              if( midata.length > 0 ) {
                                var TX=parseInt(midata[0].data);
                                var RX=parseInt(midata[1].data);
                                var x = (new Date()).getTime(); 
                                shift=chart.series[0].data.length > 19;
                                chart.series[0].addPoint([x, TX], true, shift);
                                chart.series[1].addPoint([x, RX], true, shift);
                              }
                            },
                            error: function(XMLHttpRequest, textStatus, errorThrown) { 
                              console.error("Status: " + textStatus + " request: " + XMLHttpRequest); console.error("Error: " + errorThrown); 
                            }       
                          });
                        }	

                        $(document).ready(function() {
                            Highcharts.setOptions({
                              global: {
                                useUTC: false
                              }
                            });

                            Highcharts.addEvent(Highcharts.Series, 'afterInit', function () {
                              this.symbolUnicode = {
                                circle: '●',
                                diamond: '♦',
                                square: '■',
                                triangle: '▲',
                                'triangle-down': '▼'
                              }[this.symbol] || '●';
                            });

                            chart = new Highcharts.Chart({
                              chart: {
                                renderTo: 'trafficMonitor',
                                animation: Highcharts.svg,
                                type: 'areaspline',
                                events: {
                                  load: function () {
                                    setInterval(function () {
                                      requestDatta(sessiondata,interface);
                                    }, 8000);
                                  }				
                                }
                              },
                              title: {
                                text: '<?= $_interface ?> ' + interface
                              },
                              xAxis: {
                                type: 'datetime',
                                tickPixelInterval: 150,
                                maxZoom: 20 * 1000,
                              },
                              yAxis: {
                                  minPadding: 0.2,
                                  maxPadding: 0.2,
                                  title: {
                                    text: null
                                  },
                                  labels: {
                                    formatter: function () {      
                                      var bytes = this.value;                          
                                      var sizes = ['bps', 'kbps', 'Mbps', 'Gbps', 'Tbps'];
                                      if (bytes == 0) return '0 bps';
                                      var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
                                      return parseFloat((bytes / Math.pow(1024, i)).toFixed(2)) + ' ' + sizes[i];                    
                                    },
                                  },       
                              },
                              series: [{
                                name: 'Tx',
                                data: [],
                                marker: {
                                  symbol: 'circle'
                                }
                              }, {
                                name: 'Rx',
                                data: [],
                                marker: {
                                  symbol: 'circle'
                                }
                              }],
                              tooltip: {
                                formatter: function () { 
                                  var _0x2f7f=["\x70\x6F\x69\x6E\x74\x73","\x79","\x62\x70\x73","\x6B\x62\x70\x73","\x4D\x62\x70\x73","\x47\x62\x70\x73","\x54\x62\x70\x73","\x3C\x73\x70\x61\x6E\x20\x73\x74\x79\x6C\x65\x3D\x22\x63\x6F\x6C\x6F\x72\x3A","\x63\x6F\x6C\x6F\x72","\x73\x65\x72\x69\x65\x73","\x3B\x20\x66\x6F\x6E\x74\x2D\x73\x69\x7A\x65\x3A\x20\x31\x2E\x35\x65\x6D\x3B\x22\x3E","\x73\x79\x6D\x62\x6F\x6C\x55\x6E\x69\x63\x6F\x64\x65","\x3C\x2F\x73\x70\x61\x6E\x3E\x3C\x62\x3E","\x6E\x61\x6D\x65","\x3A\x3C\x2F\x62\x3E\x20\x30\x20\x62\x70\x73","\x70\x75\x73\x68","\x6C\x6F\x67","\x66\x6C\x6F\x6F\x72","\x3A\x3C\x2F\x62\x3E\x20","\x74\x6F\x46\x69\x78\x65\x64","\x70\x6F\x77","\x20","\x65\x61\x63\x68","\x3C\x62\x3E\x4D\x69\x6B\x68\x6D\x6F\x6E\x20\x54\x72\x61\x66\x66\x69\x63\x20\x4D\x6F\x6E\x69\x74\x6F\x72\x3C\x2F\x62\x3E\x3C\x62\x72\x20\x2F\x3E\x3C\x62\x3E\x54\x69\x6D\x65\x3A\x20\x3C\x2F\x62\x3E","\x25\x48\x3A\x25\x4D\x3A\x25\x53","\x78","\x64\x61\x74\x65\x46\x6F\x72\x6D\x61\x74","\x3C\x62\x72\x20\x2F\x3E","\x20\x3C\x62\x72\x2F\x3E\x20","\x6A\x6F\x69\x6E"];var s=[];$[_0x2f7f[22]](this[_0x2f7f[0]],function(_0x3735x2,_0x3735x3){var _0x3735x4=_0x3735x3[_0x2f7f[1]];var _0x3735x5=[_0x2f7f[2],_0x2f7f[3],_0x2f7f[4],_0x2f7f[5],_0x2f7f[6]];if(_0x3735x4== 0){s[_0x2f7f[15]](_0x2f7f[7]+ this[_0x2f7f[9]][_0x2f7f[8]]+ _0x2f7f[10]+ this[_0x2f7f[9]][_0x2f7f[11]]+ _0x2f7f[12]+ this[_0x2f7f[9]][_0x2f7f[13]]+ _0x2f7f[14])};var _0x3735x2=parseInt(Math[_0x2f7f[17]](Math[_0x2f7f[16]](_0x3735x4)/ Math[_0x2f7f[16]](1024)));s[_0x2f7f[15]](_0x2f7f[7]+ this[_0x2f7f[9]][_0x2f7f[8]]+ _0x2f7f[10]+ this[_0x2f7f[9]][_0x2f7f[11]]+ _0x2f7f[12]+ this[_0x2f7f[9]][_0x2f7f[13]]+ _0x2f7f[18]+ parseFloat((_0x3735x4/ Math[_0x2f7f[20]](1024,_0x3735x2))[_0x2f7f[19]](2))+ _0x2f7f[21]+ _0x3735x5[_0x3735x2])});return _0x2f7f[23]+ Highcharts[_0x2f7f[26]](_0x2f7f[24], new Date(this[_0x2f7f[25]]))+ _0x2f7f[27]+ s[_0x2f7f[29]](_0x2f7f[28])
                                },
                                shared: true                                                      
                              },
                            });
                        });
                    </script>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="dashboard-panel-modern">
                <div class="dashboard-panel-header">
                    <i class="fa fa-list"></i> Hotspot Log
                </div>
                <div class="dashboard-panel-body">
                    <div class="hotspot-log-container" id="r_3" style="height: <?= $logh; ?>;">
                        <table class="table table-sm table-bordered table-hover" style="font-size: 12px;">
                            <thead>
                                <tr>
                                    <th><?= $_time ?></th>
                                    <th><?= $_users ?> (IP)</th>
                                    <th><?= $_messages ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="3" class="text-center">
                                        <div id="loader"><i class='fa fa-circle-o-notch fa-spin'></i> <?= $_processing ?></div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

