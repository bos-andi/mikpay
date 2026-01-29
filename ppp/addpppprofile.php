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
    
    // Get IP pools for dropdown
    $getpools = $API->comm("/ip/pool/print");
    
    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $name = $_POST['name'];
        $localaddr = $_POST['local-address'];
        $remoteaddr = $_POST['remote-address'];
        $ratelimit = $_POST['rate-limit'];
        $onlyone = $_POST['only-one'];
        $addresslist = $_POST['address-list'];
        
        $params = array(
            "name" => $name,
        );
        
        if (!empty($localaddr)) {
            $params["local-address"] = $localaddr;
        }
        if (!empty($remoteaddr)) {
            $params["remote-address"] = $remoteaddr;
        }
        if (!empty($ratelimit)) {
            $params["rate-limit"] = $ratelimit;
        }
        if (!empty($onlyone)) {
            $params["only-one"] = $onlyone;
        }
        if (!empty($addresslist)) {
            $params["address-list"] = $addresslist;
        }
        
        $API->comm("/ppp/profile/add", $params);
        
        echo "<script>window.location='./?ppp=profiles&session=" . $session . "'</script>";
        exit;
    }
}
?>
<div class="row">
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h3><i class="fa fa-plus"></i> Add PPP Profile</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="row">
                    <div class="col-6">
                        <div class="mr-b-10">
                            <label><b>Name</b></label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mr-b-10">
                            <label><b>Local Address</b></label>
                            <input type="text" name="local-address" class="form-control" placeholder="IP or Pool name">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="mr-b-10">
                            <label><b>Remote Address</b></label>
                            <input type="text" name="remote-address" class="form-control" placeholder="IP or Pool name">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mr-b-10">
                            <label><b>Rate Limit (rx/tx)</b></label>
                            <input type="text" name="rate-limit" class="form-control" placeholder="e.g. 1M/2M">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="mr-b-10">
                            <label><b>Only One</b></label>
                            <select name="only-one" class="form-control">
                                <option value="default">default</option>
                                <option value="yes">yes</option>
                                <option value="no">no</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mr-b-10">
                            <label><b>Address List</b></label>
                            <input type="text" name="address-list" class="form-control" placeholder="Optional">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn bg-success text-white"><i class="fa fa-save"></i> Save</button>
                        <a href="./?ppp=profiles&session=<?= $session ?>" class="btn bg-secondary text-white"><i class="fa fa-arrow-left"></i> Back</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
