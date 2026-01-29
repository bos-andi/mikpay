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

    $getpppprofiles = $API->comm("/ppp/profile/print");
    $TotalReg = count($getpppprofiles);
    $countpppprofiles = $API->comm("/ppp/profile/print", array("count-only" => ""));
}
?>
<div class="card">
    <div class="card-header">
        <h3><i class="fa fa-pie-chart"></i> <?= $_ppp_profiles ?> <?= $countpppprofiles ?> items
        <span style="float:right;">
            <a href="./?ppp=add-profile&session=<?= $session; ?>" class="btn bg-success text-white"><i class="fa fa-plus"></i> Add Profile</a>
        </span>
        </h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th style="width:40px;"></th>
                    <th>#</th>
                    <th>Name</th>
                    <th>Local Address</th>
                    <th>Remote Address</th>
                    <th>Rate Limit</th>
                    <th>Only One</th>
                    <th>Address List</th>
                </tr>
                </thead>
                <tbody>
<?php
for ($i = 0; $i < $TotalReg; $i++) {
    $profile = $getpppprofiles[$i];
    $id = $profile['.id'];
    $name = $profile['name'];
    $localaddr = $profile['local-address'];
    $remoteaddr = $profile['remote-address'];
    $ratelimit = $profile['rate-limit'];
    $onlyone = $profile['only-one'];
    $addresslist = $profile['address-list'];
    $isdefault = $profile['default'];
    
    $uriremove = "'./?remove-pprofile=" . $id . "&session=" . $session . "'";
    
    echo "<tr>";
    echo "<td>";
    if ($isdefault != "true") {
        echo "<span class='pointer' title='Remove' onclick=loadpage(".$uriremove.")><i class='fa fa-minus-square text-danger'></i></span>";
    }
    echo "</td>";
    echo "<td>" . ($i + 1) . "</td>";
    echo "<td><a href='./?ppp=edit-profile&name=" . urlencode($name) . "&session=" . $session . "'><i class='fa fa-edit'></i> " . $name . "</a></td>";
    echo "<td>" . $localaddr . "</td>";
    echo "<td>" . $remoteaddr . "</td>";
    echo "<td>" . $ratelimit . "</td>";
    echo "<td>" . $onlyone . "</td>";
    echo "<td>" . $addresslist . "</td>";
    echo "</tr>";
}
?>
                </tbody>
            </table>
        </div>
    </div>
</div>
