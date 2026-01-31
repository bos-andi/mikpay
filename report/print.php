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

	$idhr = isset($_GET['idhr']) ? $_GET['idhr'] : '';
	$idbl = isset($_GET['idbl']) ? $_GET['idbl'] : '';
	$idbl2 = '';
	if ($idhr != ""){
		$idhrParts = explode("/", $idhr);
		if (count($idhrParts) >= 3) {
			$idbl2 = $idhrParts[0] . $idhrParts[2];
		}
		$_SESSION['report'] = "&idhr=".$idhr;
	} elseif ($idbl != ""){
		$_SESSION['report'] = "&idbl=".$idbl;
	} else {
		$_SESSION['report'] = "";
	}
	$_SESSION['idbl'] = $idbl;
	$remdata = isset($_POST['remdata']) ? $_POST['remdata'] : '';
	$prefix = isset($_GET['prefix']) ? $_GET['prefix'] : '';
	$fcomment = isset($_GET['comment']) ? $_GET['comment'] : '';
	$range = isset($_GET['range']) ? $_GET['range'] : '';
	if(!empty($range)){$trange = "[".$range."]";} else {$trange = "";}
	
	$pcomment = substr($prefix, 0,2);
	if($pcomment == "!!"){
		$fcomment = explode("!!",$prefix)[1];
	}else{$fcomment = $fcomment;}

	// Connect to router first before getting timezone
	if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
		$gettimezone = $API->comm("/system/clock/print");
		if (isset($gettimezone[0]['time-zone-name'])) {
			$timezone = $gettimezone[0]['time-zone-name'];
			date_default_timezone_set($timezone);
		} else {
			date_default_timezone_set('Asia/Jakarta');
		}
		$API->disconnect();
	} else {
		date_default_timezone_set('Asia/Jakarta');
	}

	if (isset($remdata)) {
		if (strlen($idhr) > "0") {
			if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
				$API->write('/system/script/print', false);
				$API->write('?source=' . $idhr . '', false);
				$API->write('=.proplist=.id');
				$ARREMD = $API->read();
				for ($i = 0; $i < count($ARREMD); $i++) {
					$API->write('/system/script/remove', false);
					$API->write('=.id=' . $ARREMD[$i]['.id']);
					$READ = $API->read();

				}
			}
		} elseif (strlen($idbl) > "0") {
			if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
				$API->write('/system/script/print', false);
				$API->write('?owner=' . $idbl . '', false);
				$API->write('=.proplist=.id');
				$ARREMD = $API->read();
				for ($i = 0; $i < count($ARREMD); $i++) {
					$API->write('/system/script/remove', false);
					$API->write('=.id=' . $ARREMD[$i]['.id']);
					$READ = $API->read();

				}
			}

		}
		echo "<script>window.location='./?report=selling&session=" . $session . "'</script>";
	}

	if ($pcomment == "!!"){
		$fprefix = "-comment-[" . $fcomment . "]";
	} else	if ($prefix != "") {
		$fprefix = "-prefix-[" . $prefix . "]";
	} else {
		$fprefix = "";
	}
	$getData = array();
	$TotalReg = 0;
	$filedownload = "all";
	$shf = "text";
	$shd = "none";
	
	if (strlen($idhr) > 0) {
		if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
			$getData = $API->comm("/system/script/print", array(
				"?source" => "$idhr",
			));
			$TotalReg = is_array($getData) ? count($getData) : 0;
			$API->disconnect();
		}
		$filedownload = $idhr;
		$shf = "hidden";
		$shd = "inline-block";
	} elseif (strlen($idbl) > 0) {
		if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
			$getData = $API->comm("/system/script/print", array(
				"?owner" => "$idbl",
			));
			$TotalReg = is_array($getData) ? count($getData) : 0;
			$API->disconnect();
		}
		$filedownload = $idbl;
		$shf = "hidden";
		$shd = "inline-block";
	} else {
		if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
			$getData = $API->comm("/system/script/print", array(
				"?comment" => "mikpay",
			));
			$TotalReg = is_array($getData) ? count($getData) : 0;
			$API->disconnect();
		}
		$filedownload = "all";
		$shf = "text";
		$shd = "none";
	}
	
}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>.:: MIKPAY <?= $hotspotname; ?> ::.</title>
		<meta charset="utf-8">
		<meta http-equiv="cache-control" content="private" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<!-- Tell the browser to be responsive to screen width -->
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<style>
	/*table*/
  .table {
    width: 100%;
    background-color: #FFFFFF;
    border-collapse: collapse !important;
  }
  
  .table td,
  .table th {
    padding: 5px;
  }
  
  .table td,
  th,
  a {
    color: #000;
    text-decoration: none;
  }
  
  .table-bordered th,
  .table-bordered td {
   border: 1px solid #000 !important;
  }
	@page
	{
   size: auto;
   margin-left: 7mm;
   margin-right: 3mm;
   margin-top: 9mm;
   margin-bottom: 3mm;
	}
	@media print
	{
   table { page-break-after:auto }
   tr    { page-break-inside:avoid; page-break-after:auto }
   td    { page-break-inside:avoid; page-break-after:auto }
   thead { display:table-header-group }
   tfoot { display:table-footer-group }
	}	 
	h3 {
		margin:0px;
	} 
		</style>
		
	</head>
	<body>
		<div class="wrapper">
		<script>
function number_format(number, decimals, dec_point, thousands_sep) {

  number = (number + '')
    .replace(/[^0-9+\-Ee.]/g, '');
  var n = !isFinite(+number) ? 0 : +number,
    prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
    sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
    dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
    s = '',
    toFixedFix = function(n, prec) {
      var k = Math.pow(10, prec);
      return '' + (Math.round(n * k) / k)
        .toFixed(prec);
    };
  // Fix for IE parseFloat(0.55).toFixed(0) = 0;
  s = (prec ? toFixedFix(n, prec) : '' + Math.round(n))
    .split('.');
  if (s[0].length > 3) {
    s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
  }
  if ((s[1] || '')
    .length < prec) {
    s[1] = s[1] || '';
    s[1] += new Array(prec - s[1].length + 1)
      .join('0');
  }
  return s.join(dec);
}
		window.onload=function() {
          var sum = 0;
          var dataTable = document.getElementById("selling");
          
          // use querySelector to find all second table cells
          var cells = document.querySelectorAll("td + td + td + td + td + td");
          for (var i = 0; i < cells.length; i++)
          sum+=parseFloat(cells[i].firstChild.data);
          
          var th = document.getElementById('total');
          
    <?php if ($currency == in_array($currency, $cekindo['indo'])) {
      echo 'th.innerHTML = "'.$currency.' " + number_format(th.innerHTML + (sum),"","",".") ;';
		} else {
			echo 'th.innerHTML = "'.$currency.' " + number_format(th.innerHTML + (sum),2,".",",") ;';
		} ?>
		
		var tables = document.getElementsByTagName('tbody');
    var table = tables[tables.length -1];
    var rows = table.rows;
    for(var i = 0, td; i < rows.length; i++){
        td = document.createElement('td');
        td.appendChild(document.createTextNode(i + 1));
        rows[i].insertBefore(td, rows[i].firstChild);
    }
        
    window.print();
        }
        

		</script>


		  <div class="overflow box-bordered" style="max-height: 70vh">
			<table id="dataTable" class="table table-bordered table-hover text-nowrap">
				<thead><tr><th colspan="7"><?= "<h3>".$_selling_report."</h3>". $hotspotname ?></th></tr></thead>
				<tr>
				  <th style="text-align:left;" colspan=5 ><?= $_selling_report ?> <?= $trange.$filedownload . $fprefix; ?><b style="font-size:0;">,,,</b></th>
				  <th style="text-align:right;"><?= $_total ?></th>
				  <th style="text-align:right;" id="total"></th>
				</tr>
				<tr style="text-align:left;">
				  <th >&#8470;</th>
					<th ><?= $_date ?></th>
					<th ><?= $_time ?></th>
					<th ><?= $_user_name ?></th>
					<th ><?= $_profile ?></th>
					<th ><?= $_comment ?></th>
					<th style="text-align:right;"> <?= $_price; ?></th>
				</tr>
				
				<tbody id="tbody">
				<?php
			$dataresume = '';
			$totalresume = 0;
			
			if ($TotalReg > 0 && is_array($getData)) {
				if ($fcomment != "" || $pcomment == "!!") {
					for ($i = 0; $i < $TotalReg; $i++) {
						if (!isset($getData[$i]['name'])) continue;
						$getname = explode("-|-", $getData[$i]['name']);
						if (count($getname) >= 9 && isset($getname[8]) && strpos($getname[8], $fcomment) !== false){
							echo "<tr>";
							echo "<td>" . (isset($getname[0]) ? htmlspecialchars($getname[0]) : '') . "</td>";
							echo "<td>" . (isset($getname[1]) ? htmlspecialchars($getname[1]) : '') . "</td>";
							echo "<td>" . (isset($getname[2]) ? htmlspecialchars($getname[2]) : '') . "</td>";
							echo "<td>" . (isset($getname[7]) ? htmlspecialchars($getname[7]) : '') . "</td>";
							echo "<td>" . (isset($getname[8]) ? htmlspecialchars($getname[8]) : '') . "</td>";
							echo "<td style='text-align:right;'>" . (isset($getname[3]) ? htmlspecialchars($getname[3]) : '0') . "</td>";
							echo "</tr>";
						}
					}
				} elseif ($prefix != "") {
					for ($i = 0; $i < $TotalReg; $i++) {
						if (!isset($getData[$i]['name'])) continue;
						$getname = explode("-|-", $getData[$i]['name']);
						if (count($getname) >= 3 && isset($getname[2]) && substr($getname[2], 0, strlen($prefix)) == $prefix) {
							echo "<tr>";
							echo "<td>" . (isset($getname[0]) ? htmlspecialchars($getname[0]) : '') . "</td>";
							echo "<td>" . (isset($getname[1]) ? htmlspecialchars($getname[1]) : '') . "</td>";
							echo "<td>" . (isset($getname[2]) ? htmlspecialchars($getname[2]) : '') . "</td>";
							echo "<td>" . (isset($getname[7]) ? htmlspecialchars($getname[7]) : '') . "</td>";
							echo "<td>" . (isset($getname[8]) ? htmlspecialchars($getname[8]) : '') . "</td>";
							echo "<td style='text-align:right;'>" . (isset($getname[3]) ? htmlspecialchars($getname[3]) : '0') . "</td>";
							echo "</tr>";
						}
					}
				} elseif ($range != "") {
					$rangeParts = explode('-', $range);
					if (count($rangeParts) == 2) {
						$x = intval($rangeParts[0]);
						$y = intval($rangeParts[1]);
						$dayRange = range($x, $y);
						
						for ($i = 0; $i < $TotalReg; $i++) {
							if (!isset($getData[$i]['name'])) continue;
							$getname = explode("-|-", $getData[$i]['name']);
							if (count($getname) >= 1 && isset($getname[0])) {
								$day = substr($getname[0], 4, 2);
								if (substr($day, 0, 1) == "0") {
									$day = intval(substr($day, -1));
								} else {
									$day = intval($day);
								}
								if (in_array($day, $dayRange)) {
									echo "<tr>";
									echo "<td>" . htmlspecialchars($getname[0]) . "</td>";
									echo "<td>" . (isset($getname[1]) ? htmlspecialchars($getname[1]) : '') . "</td>";
									echo "<td>" . (isset($getname[2]) ? htmlspecialchars($getname[2]) : '') . "</td>";
									echo "<td>" . (isset($getname[7]) ? htmlspecialchars($getname[7]) : '') . "</td>";
									echo "<td>" . (isset($getname[8]) ? htmlspecialchars($getname[8]) : '') . "</td>";
									echo "<td style='text-align:right;'>" . (isset($getname[3]) ? htmlspecialchars($getname[3]) : '0') . "</td>";
									echo "</tr>";
								}
							}
						}
					}
				} else {
					for ($i = 0; $i < $TotalReg; $i++) {
						if (!isset($getData[$i]['name'])) continue;
						$getname = explode("-|-", $getData[$i]['name']);
						if (count($getname) >= 9) {
							echo "<tr>";
							echo "<td>" . (isset($getname[0]) ? htmlspecialchars($getname[0]) : '') . "</td>";
							echo "<td>" . (isset($getname[1]) ? htmlspecialchars($getname[1]) : '') . "</td>";
							echo "<td>" . (isset($getname[2]) ? htmlspecialchars($getname[2]) : '') . "</td>";
							echo "<td>" . (isset($getname[7]) ? htmlspecialchars($getname[7]) : '') . "</td>";
							echo "<td>" . (isset($getname[8]) ? htmlspecialchars($getname[8]) : '') . "</td>";
							echo "<td style='text-align:right;'>" . (isset($getname[3]) ? htmlspecialchars($getname[3]) : '0') . "</td>";
							echo "</tr>";
							
							if (isset($getname[0]) && isset($getname[3])) {
								$dataresume .= $getname[0] . $getname[3];
								$totalresume += floatval($getname[3]);
							}
						}
					}
					$_SESSION['dataresume'] = $dataresume;
					$_SESSION['totalresume'] = $TotalReg . '/' . $totalresume;
				}
			} else {
				echo "<tr><td colspan='6' style='text-align:center; padding:20px;'>Tidak ada data untuk ditampilkan</td></tr>";
			}
			?>
			</tbody>
			</table>
		</div>

</div>
</div>
</div>
</body>
</html>
