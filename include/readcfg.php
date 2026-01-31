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
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// hide all error
error_reporting(0);
if (substr($_SERVER["REQUEST_URI"], -11) == "readcfg.php") {
    header("Location:./");
};

// Try to load router from database if user is logged in via database
$routerFromDb = false;
if (isset($_SESSION["user_id"]) && !empty($session)) {
    try {
        if (file_exists(__DIR__ . '/database.php')) {
            include_once(__DIR__ . '/database.php');
            if (function_exists('getUserRouterBySession')) {
                $dbRouter = getUserRouterBySession($_SESSION["user_id"], $session);
                if ($dbRouter) {
                    $routerFromDb = true;
                    // Build $data array from database for backward compatibility
                    if (!isset($data)) {
                        $data = array();
                    }
                    $data[$session] = array(
                        '1' => $session . '!' . $dbRouter['host'],
                        $session . '@|@' . $dbRouter['username'],
                        $session . '#|#' . $dbRouter['password'],
                        $session . '%' . $dbRouter['router_name'],
                        $session . '^' . ($dbRouter['domain'] ?: ''),
                        $session . '&' . ($dbRouter['currency'] ?: 'Rp'),
                        $session . '*' . ($dbRouter['currency_position'] ?: 10),
                        $session . '(' . ($dbRouter['expiry_mode'] ?: 1),
                        $session . ')',
                        $session . '=' . ($dbRouter['expiry_days'] ?: 10),
                        $session . '@!@' . ($dbRouter['status'] ?: 'active')
                    );
                }
            }
        }
    } catch (Exception $e) {
        // Fallback to config.php
        $routerFromDb = false;
    }
}

// If router not from database, load from config.php (backward compatibility)
if (!$routerFromDb) {
    // Ensure $data is loaded from config.php
    if (!isset($data) || !isset($data[$session])) {
        // This should already be loaded from config.php, but ensure it exists
    }
}

// read config (backward compatible)
if (isset($data[$session]) && is_array($data[$session])) {
    $iphost = isset($data[$session][1]) ? explode('!', $data[$session][1])[1] : '';
    $userhost = isset($data[$session][2]) ? explode('@|@', $data[$session][2])[1] : '';
    $passwdhost = isset($data[$session][3]) ? explode('#|#', $data[$session][3])[1] : '';
    $hotspotname = isset($data[$session][4]) ? explode('%', $data[$session][4])[1] : '';
    $dnsname = isset($data[$session][5]) ? explode('^', $data[$session][5])[1] : '';
    $currency = isset($data[$session][6]) ? explode('&', $data[$session][6])[1] : 'Rp';
    $areload = isset($data[$session][7]) ? explode('*', $data[$session][7])[1] : '10';
    $iface = isset($data[$session][8]) ? explode('(', $data[$session][8])[1] : '1';
    $infolp = isset($data[$session][9]) ? explode(')', $data[$session][9])[1] : '';
    $idleto = isset($data[$session][10]) ? explode('=', $data[$session][10])[1] : '10';
    $sesname = isset($data[$session][10]) ? explode('+', $data[$session][10])[1] : '';
    $livereport = isset($data[$session][11]) ? explode('@!@', $data[$session][11])[1] : 'enable';
} else {
    // Default values if session not found
    $iphost = '';
    $userhost = '';
    $passwdhost = '';
    $hotspotname = '';
    $dnsname = '';
    $currency = 'Rp';
    $areload = '10';
    $iface = '1';
    $infolp = '';
    $idleto = '10';
    $sesname = '';
    $livereport = 'enable';
}

// Admin credentials (always from config.php for backward compatibility)
$useradm = isset($data['mikpay'][1]) ? explode('<|<', $data['mikpay'][1])[1] : 'mikpay';
$passadm = isset($data['mikpay'][2]) ? explode('>|>', $data['mikpay'][2])[1] : '';

$cekindo['indo'] = array(
    'RP', 'Rp', 'rp', 'IDR', 'idr', 'RP.', 'Rp.', 'rp.', 'IDR.', 'idr.',
);


