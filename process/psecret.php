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
    $removesecr = $_GET['remove-secr'];
    $enablesecr = $_GET['enable-secr'];
    $disablesecr = $_GET['disable-secr'];

// load config
    include('./include/config.php');
    include('./include/readcfg.php');

// routeros api
    include_once('./lib/routeros_api.class.php');
    $API = new RouterosAPI();
    $API->debug = false;
    $API->connect($iphost, $userhost, decrypt($passwdhost));

    // Remove secret
    if (!empty($removesecr)) {
        $API->comm("/ppp/secret/remove", array(
            ".id" => $removesecr,
        ));
    }
    
    // Enable secret
    if (!empty($enablesecr)) {
        $API->comm("/ppp/secret/set", array(
            ".id" => $enablesecr,
            "disabled" => "no",
        ));
    }
    
    // Disable secret
    if (!empty($disablesecr)) {
        $API->comm("/ppp/secret/set", array(
            ".id" => $disablesecr,
            "disabled" => "yes",
        ));
    }

    echo "<script>window.location='./?ppp=secrets&session=" . $session . "'</script>";
}
?>
