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
    $secretbyname = $_GET['secret-by-name'];

// load config
    include('../include/config.php');
    include('../include/readcfg.php');
    
// lang
    include('../include/lang.php');
    include('../lang/'.$langid.'.php');

// routeros api
    include_once('../lib/routeros_api.class.php');
    $API = new RouterosAPI();
    $API->debug = false;
    $API->connect($iphost, $userhost, decrypt($passwdhost));

    // Get PPP profiles for dropdown
    $getpppprofiles = $API->comm("/ppp/profile/print");
    
    // Get secret data
    $getsecret = $API->comm("/ppp/secret/print", array("?name" => $secretbyname));
    $secret = $getsecret[0];
    
    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $id = $secret['.id'];
        $password = $_POST['password'];
        $service = $_POST['service'];
        $profile = $_POST['profile'];
        $localaddr = $_POST['local-address'];
        $remoteaddr = $_POST['remote-address'];
        $comment = $_POST['comment'];
        
        $params = array(
            ".id" => $id,
            "password" => $password,
            "service" => $service,
            "profile" => $profile,
            "local-address" => $localaddr,
            "remote-address" => $remoteaddr,
            "comment" => $comment,
        );
        
        $API->comm("/ppp/secret/set", $params);
        
        echo "<script>window.location='./?ppp=secrets&session=" . $session . "'</script>";
        exit;
    }
}
?>
<div class="row">
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h3><i class="fa fa-edit"></i> Edit PPP Secret: <?= $secretbyname ?></h3>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="row">
                    <div class="col-6">
                        <div class="mr-b-10">
                            <label><b>Name</b></label>
                            <input type="text" class="form-control" value="<?= $secret['name'] ?>" disabled>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mr-b-10">
                            <label><b>Password</b></label>
                            <input type="text" name="password" class="form-control" value="<?= $secret['password'] ?>">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="mr-b-10">
                            <label><b>Service</b></label>
                            <select name="service" class="form-control">
                                <option value="any" <?= $secret['service'] == 'any' ? 'selected' : '' ?>>any</option>
                                <option value="async" <?= $secret['service'] == 'async' ? 'selected' : '' ?>>async</option>
                                <option value="l2tp" <?= $secret['service'] == 'l2tp' ? 'selected' : '' ?>>l2tp</option>
                                <option value="ovpn" <?= $secret['service'] == 'ovpn' ? 'selected' : '' ?>>ovpn</option>
                                <option value="pppoe" <?= $secret['service'] == 'pppoe' ? 'selected' : '' ?>>pppoe</option>
                                <option value="pptp" <?= $secret['service'] == 'pptp' ? 'selected' : '' ?>>pptp</option>
                                <option value="sstp" <?= $secret['service'] == 'sstp' ? 'selected' : '' ?>>sstp</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mr-b-10">
                            <label><b>Profile</b></label>
                            <select name="profile" class="form-control">
                                <?php foreach ($getpppprofiles as $prof) { ?>
                                    <option value="<?= $prof['name'] ?>" <?= $secret['profile'] == $prof['name'] ? 'selected' : '' ?>><?= $prof['name'] ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="mr-b-10">
                            <label><b>Local Address</b></label>
                            <input type="text" name="local-address" class="form-control" value="<?= $secret['local-address'] ?>">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mr-b-10">
                            <label><b>Remote Address</b></label>
                            <input type="text" name="remote-address" class="form-control" value="<?= $secret['remote-address'] ?>">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="mr-b-10">
                            <label><b><?= $_comment ?></b></label>
                            <input type="text" name="comment" class="form-control" value="<?= $secret['comment'] ?>">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn bg-success text-white"><i class="fa fa-save"></i> Save</button>
                        <a href="./?ppp=secrets&session=<?= $session ?>" class="btn bg-secondary text-white"><i class="fa fa-arrow-left"></i> Back</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
