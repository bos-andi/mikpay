<?php
/*
 *  Copyright (C) 2018 Muhammad Andi.
 */
session_start();
error_reporting(0);
if (!isset($_SESSION["mikpay"])) {
    header("Location:../admin.php?id=login");
} else {
    $session = $_GET['session'];
    include('../include/config.php');
    include('../include/readcfg.php');
    include('../include/lang.php');
    include('../lang/'.$langid.'.php');
    include_once('../lib/routeros_api.class.php');
    include_once('../lib/formatbytesbites.php');
    $API = new RouterosAPI();
    $API->debug = false;
    $API->connect($iphost, $userhost, decrypt($passwdhost));

    // Get PPP secrets
    $getpppsecrets = $API->comm("/ppp/secret/print");
    $TotalReg = count($getpppsecrets);
    $countpppsecrets = $API->comm("/ppp/secret/print", array("count-only" => ""));
    
    // Get PPP active connections to check online status
    $getpppactive = $API->comm("/ppp/active/print");
    $onlineUsers = array();
    foreach ($getpppactive as $active) {
        $onlineUsers[$active['name']] = $active;
    }
    $countOnline = count($onlineUsers);
    $countOffline = $countpppsecrets - $countOnline;
}
?>
<style>
.ppp-stats {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}
.ppp-stat-card {
    flex: 1;
    background: #FFFFFF;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 0 30px rgba(0,0,0,0.05);
}
.ppp-stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}
.ppp-stat-icon.total { background: rgba(77,68,181,0.1); color: #4D44B5; }
.ppp-stat-icon.online { background: rgba(30,186,98,0.1); color: #1EBA62; }
.ppp-stat-icon.offline { background: rgba(253,83,83,0.1); color: #fd5353; }
.ppp-stat-info h4 { margin: 0; font-size: 14px; color: #A098AE; font-weight: 400; }
.ppp-stat-info p { margin: 5px 0 0; font-size: 28px; font-weight: 700; color: #303972; }

.badge-online {
    display: inline-block;
    background: linear-gradient(135deg, #1EBA62 0%, #17a34a 100%);
    color: #FFFFFF;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    animation: pulse-online 2s infinite;
    box-shadow: 0 2px 8px rgba(30,186,98,0.4);
}
.badge-offline {
    display: inline-block;
    background: #E0E0E0;
    color: #888888;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
@keyframes pulse-online {
    0%, 100% { box-shadow: 0 2px 8px rgba(30,186,98,0.4); }
    50% { box-shadow: 0 2px 15px rgba(30,186,98,0.7); }
}
</style>

<!-- Stats Cards -->
<div class="ppp-stats">
    <div class="ppp-stat-card">
        <div class="ppp-stat-icon total"><i class="fa fa-key"></i></div>
        <div class="ppp-stat-info">
            <h4>Total Secrets</h4>
            <p><?= $countpppsecrets ?></p>
        </div>
    </div>
    <div class="ppp-stat-card">
        <div class="ppp-stat-icon online"><i class="fa fa-wifi"></i></div>
        <div class="ppp-stat-info">
            <h4>Online</h4>
            <p><?= $countOnline ?></p>
        </div>
    </div>
    <div class="ppp-stat-card">
        <div class="ppp-stat-icon offline"><i class="fa fa-power-off"></i></div>
        <div class="ppp-stat-info">
            <h4>Offline</h4>
            <p><?= $countOffline ?></p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="fa fa-users"></i> <?= $_ppp_secrets ?>
        <span style="float:right;">
            <a href="./?ppp=addsecret&session=<?= $session; ?>" class="btn bg-success text-white"><i class="fa fa-plus"></i> <?= $_add_user ?></a>
        </span>
        </h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th style="width:60px;"></th>
                    <th>#</th>
                    <th>Name</th>
                    <th>Service</th>
                    <th>Profile</th>
                    <th>Local Address</th>
                    <th>Remote Address</th>
                    <th><?= $_comment ?></th>
                </tr>
                </thead>
                <tbody>
<?php
for ($i = 0; $i < $TotalReg; $i++) {
    $secret = $getpppsecrets[$i];
    $id = $secret['.id'];
    $name = $secret['name'];
    $service = $secret['service'];
    $profile = $secret['profile'];
    $localaddr = $secret['local-address'];
    $remoteaddr = $secret['remote-address'];
    $comment = $secret['comment'];
    $disabled = $secret['disabled'];
    
    $uriremove = "'./?remove-secr=" . $id . "&session=" . $session . "'";
    
    // Check if user is online
    $isOnline = isset($onlineUsers[$name]);
    if ($isOnline) {
        $onlineBadge = "<span class='badge-online'><i class='fa fa-signal'></i> Online</span>";
    } else {
        $onlineBadge = "<span class='badge-offline'>Offline</span>";
    }
    
    if ($disabled == "true") {
        $urienable = "'./?enable-secr=" . $id . "&session=" . $session . "'";
        $togglebtn = "<span class='pointer' title='Enable' onclick=loadpage(".$urienable.")><i class='fa fa-toggle-off text-danger'></i></span>";
    } else {
        $uridisable = "'./?disable-secr=" . $id . "&session=" . $session . "'";
        $togglebtn = "<span class='pointer' title='Disable' onclick=loadpage(".$uridisable.")><i class='fa fa-toggle-on text-success'></i></span>";
    }
    
    echo "<tr>";
    echo "<td><span class='pointer' title='Remove' onclick=loadpage(".$uriremove.")><i class='fa fa-minus-square text-danger'></i></span> " . $togglebtn . "</td>";
    echo "<td>" . ($i + 1) . "</td>";
    echo "<td><a href='./?secret-by-name=" . $name . "&session=" . $session . "'><i class='fa fa-edit'></i> " . $name . "</a> " . $onlineBadge . "</td>";
    echo "<td>" . $service . "</td>";
    echo "<td>" . $profile . "</td>";
    echo "<td>" . $localaddr . "</td>";
    echo "<td>" . $remoteaddr . "</td>";
    echo "<td>" . $comment . "</td>";
    echo "</tr>";
}
?>
                </tbody>
            </table>
        </div>
    </div>
</div>
