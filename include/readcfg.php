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
// read config

// Check if session exists in data array
if (!isset($data[$session]) || !is_array($data[$session])) {
    // Set default empty values for new routers
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
    $sesname = $session;
    $livereport = 'disable';
} else {
    // Safely extract values with defaults
    $iphost = isset($data[$session][1]) && strpos($data[$session][1], '!') !== false ? explode('!', $data[$session][1])[1] : '';
    $userhost = isset($data[$session][2]) && strpos($data[$session][2], '@|@') !== false ? explode('@|@', $data[$session][2])[1] : '';
    $passwdhost = isset($data[$session][3]) && strpos($data[$session][3], '#|#') !== false ? explode('#|#', $data[$session][3])[1] : '';
    $hotspotname = isset($data[$session][4]) && strpos($data[$session][4], '%') !== false ? explode('%', $data[$session][4])[1] : '';
    $dnsname = isset($data[$session][5]) && strpos($data[$session][5], '^') !== false ? explode('^', $data[$session][5])[1] : '';
    $currency = isset($data[$session][6]) && strpos($data[$session][6], '&') !== false ? explode('&', $data[$session][6])[1] : 'Rp';
    $areload = isset($data[$session][7]) && strpos($data[$session][7], '*') !== false ? explode('*', $data[$session][7])[1] : '10';
    $iface = isset($data[$session][8]) && strpos($data[$session][8], '(') !== false ? explode('(', $data[$session][8])[1] : '1';
    $infolp = isset($data[$session][9]) && strpos($data[$session][9], ')') !== false ? explode(')', $data[$session][9])[1] : '';
    $idleto = isset($data[$session][10]) && strpos($data[$session][10], '=') !== false ? explode('=', $data[$session][10])[1] : '10';
    $sesname = isset($data[$session][10]) && strpos($data[$session][10], '+') !== false ? explode('+', $data[$session][10])[1] : $session;
    $livereport = isset($data[$session][11]) && strpos($data[$session][11], '@!@') !== false ? explode('@!@', $data[$session][11])[1] : 'disable';
}

// Get admin credentials (always from mikpay session)
$useradm = isset($data['mikpay'][1]) && strpos($data['mikpay'][1], '<|<') !== false ? explode('<|<', $data['mikpay'][1])[1] : '';
$passadm = isset($data['mikpay'][2]) && strpos($data['mikpay'][2], '>|>') !== false ? explode('>|>', $data['mikpay'][2])[1] : '';

$cekindo['indo'] = array(
    'RP', 'Rp', 'rp', 'IDR', 'idr', 'RP.', 'Rp.', 'rp.', 'IDR.', 'idr.',
);


