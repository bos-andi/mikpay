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

    $getpppactive = $API->comm("/ppp/active/print");
    $TotalReg = count($getpppactive);
    $countpppactive = $API->comm("/ppp/active/print", array("count-only" => ""));
}
?>
<div class="card">
    <div class="card-header">
        <h3><i class="fa fa-wifi"></i> <?= $_ppp_active ?> <?= $countpppactive ?> items</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th style="width:40px;"></th>
                    <th>#</th>
                    <th>Name</th>
                    <th>Service</th>
                    <th>Caller ID</th>
                    <th>Address</th>
                    <th>Uptime</th>
                    <th>Encoding</th>
                </tr>
                </thead>
                <tbody>
<?php
for ($i = 0; $i < $TotalReg; $i++) {
    $pppactive = $getpppactive[$i];
    $id = $pppactive['.id'];
    $name = $pppactive['name'];
    $service = $pppactive['service'];
    $callerid = $pppactive['caller-id'];
    $address = $pppactive['address'];
    $uptime = formatDTM($pppactive['uptime']);
    $encoding = $pppactive['encoding'];
    
    $uriremove = "'./?remove-pactive=" . $id . "&session=" . $session . "'";
    
    echo "<tr>";
    echo "<td><span class='pointer' title='Disconnect' onclick=loadpage(".$uriremove.")><i class='fa fa-minus-square text-danger'></i></span></td>";
    echo "<td>" . ($i + 1) . "</td>";
    echo "<td><a href='./?secret-by-name=" . $name . "&session=" . $session . "'><i class='fa fa-user'></i> " . $name . "</a></td>";
    echo "<td>" . $service . "</td>";
    echo "<td>" . $callerid . "</td>";
    echo "<td>" . $address . "</td>";
    echo "<td>" . $uptime . "</td>";
    echo "<td>" . $encoding . "</td>";
    echo "</tr>";
}
?>
                </tbody>
            </table>
        </div>
    </div>
</div>
