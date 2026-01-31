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

	$idhr = $_GET['idhr'];
	$idbl = $_GET['idbl'];
	$idbl2 = explode("/",$idhr)[0].explode("/",$idhr)[2];
	if ($idhr != ""){
		$_SESSION['report'] = "&idhr=".$idhr;
	} elseif ($idbl != ""){
		$_SESSION['report'] = "&idbl=".$idbl;
	} else {
		$_SESSION['report'] = "";
	}
	$_SESSION['idbl'] = $idbl;
	$remdata = ($_POST['remdata']);
	$prefix = $_GET['prefix'];
	

	$gettimezone = $API->comm("/system/clock/print");
	$timezone = $gettimezone[0]['time-zone-name'];
	date_default_timezone_set($timezone);

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

	if ($prefix != "") {
		$fprefix = "-prefix-[" . $prefix . "]";
	} else {
		$fprefix = "";
	}
	if (strlen($idhr) > "0") {
		if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
			$getData = $API->comm("/system/script/print", array(
				"?source" => "$idhr",
			));
			$TotalReg = count($getData);
		}
		$filedownload = $idhr;
		$shf = "hidden";
		$shd = "inline-block";
	} elseif (strlen($idbl) > "0") {
		if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
			$getData = $API->comm("/system/script/print", array(
				"?owner" => "$idbl",
			));
			$TotalReg = count($getData);
		}
		$filedownload = $idbl;
		$shf = "hidden";
		$shd = "inline-block";
	} elseif ($idhr == "" || $idbl == "") {
		if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
			$getData = $API->comm("/system/script/print", array(
				"?comment" => "mikpay",
			));
			$TotalReg = count($getData);
		}
		$filedownload = "all";
		$shf = "text";
		$shd = "none";
	} elseif (strlen($idbl) > "0" ) {
		if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
			$getData = $API->comm("/system/script/print", array(
				"?owner" => "$idbl",
			));
			$TotalReg = count($getData);
		}
		$filedownload = $idbl;
		$shf = "hidden";
		$shd = "inline-block";
	}
	
}
?>
		<script>
			function downloadCSV(csv, filename) {
			  var csvFile;
			  var downloadLink;
			  // CSV file
			  csvFile = new Blob([csv], {type: "text/csv"});
			  // Download link
			  downloadLink = document.createElement("a");
			  // File name
			  downloadLink.download = filename;
			  // Create a link to the file
			  downloadLink.href = window.URL.createObjectURL(csvFile);
			  // Hide download link
			  downloadLink.style.display = "none";
			  // Add the link to DOM
			  document.body.appendChild(downloadLink);
			  // Click download link
			  downloadLink.click();
			  }
			  
			  function exportTableToCSV(filename) {
			    var csv = [];
			    var rows = document.querySelectorAll("#dataTable tr");
			    
			   for (var i = 0; i < rows.length; i++) {
			      var row = [], cols = rows[i].querySelectorAll("td, th");
			   for (var j = 0; j < cols.length; j++)
            row.push(cols[j].innerText);
        csv.push(row.join(","));
        }
        // Download CSV file
        downloadCSV(csv.join("\n"), filename);
        }

// https://stackoverflow.com/questions/33218607/use-inline-css-to-apply-usd-currency-format-within-html-table
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
        }
		</script>

<script>
$(document).ready(function(){
  $("#openResume").click(function(){
    notify("Calculating data");
    window.location = "./?report=resume-report&idbl=<?= $idbl;?>&session=<?= $session;?>"
  });
});
</script>
<style>
.report-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 20px;
    align-items: center;
}
.report-toolbar .btn {
    padding: 8px 15px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    border: none;
}
.report-filter {
    display: flex;
    gap: 5px;
    background: #f8f9fa;
    padding: 10px 15px;
    border-radius: 10px;
    margin-bottom: 20px;
}
.report-filter select {
    padding: 8px 12px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 13px;
    background: #fff;
}
.report-filter .filter-btn {
    padding: 8px 20px;
    background: #4D44B5;
    color: #fff;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
}
.report-total-card {
    background: linear-gradient(135deg, #4D44B5 0%, #3d3690 100%);
    color: #fff;
    padding: 15px 25px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.report-total-card h4 { margin: 0; font-size: 14px; opacity: 0.9; }
.report-total-card p { margin: 5px 0 0; font-size: 28px; font-weight: 700; }

/* ========================================
   TABLE LAYOUT FIX - Report Selling
   ======================================== */

/* Card body - ensure full width and proper padding */
.report-selling .card-body {
    width: 100% !important;
    max-width: 100% !important;
    padding: 20px !important;
    box-sizing: border-box !important;
    overflow: visible !important;
}

/* Table responsive wrapper - Desktop: fit content, Mobile: scroll */
.report-selling .table-responsive {
    width: 100% !important;
    max-width: 100% !important;
    overflow-x: auto !important;
    overflow-y: visible !important;
    -webkit-overflow-scrolling: touch !important;
    margin: 0 !important;
    padding: 0 !important;
    box-sizing: border-box !important;
}

/* Desktop: Table should fit naturally without horizontal scroll */
@media (min-width: 992px) {
    .report-selling .table-responsive {
        overflow-x: visible !important;
    }
    
    .report-selling .table {
        width: 100% !important;
        table-layout: auto !important;
        min-width: 100% !important;
    }
}

/* Mobile: Allow horizontal scroll */
@media (max-width: 991px) {
    .report-selling .table-responsive {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch !important;
    }
    
    .report-selling .table {
        min-width: 800px !important;
        width: auto !important;
    }
}

/* Table styling - ensure proper column widths */
.report-selling .table {
    width: 100% !important;
    border-collapse: collapse !important;
    margin: 0 !important;
    background: #FFFFFF !important;
}

/* Table header - sticky and aligned */
.report-selling .table thead {
    background: #4D44B5 !important;
    position: relative !important;
}

.report-selling .table thead th {
    color: #FFFFFF !important;
    font-weight: 500 !important;
    padding: 12px 15px !important;
    border: none !important;
    font-size: 13px !important;
    white-space: nowrap !important;
    text-align: left !important;
    vertical-align: middle !important;
}

/* Column width optimization */
.report-selling .table thead th:first-child,
.report-selling .table tbody td:first-child {
    width: 50px !important;
    min-width: 50px !important;
    max-width: 50px !important;
    text-align: center !important;
}

.report-selling .table thead th:nth-child(2),
.report-selling .table tbody td:nth-child(2) {
    width: 100px !important;
    min-width: 100px !important;
}

.report-selling .table thead th:nth-child(3),
.report-selling .table tbody td:nth-child(3) {
    width: 80px !important;
    min-width: 80px !important;
}

.report-selling .table thead th:nth-child(4),
.report-selling .table tbody td:nth-child(4) {
    width: 120px !important;
    min-width: 120px !important;
}

.report-selling .table thead th:nth-child(5),
.report-selling .table tbody td:nth-child(5) {
    width: 100px !important;
    min-width: 100px !important;
}

.report-selling .table thead th:nth-child(6),
.report-selling .table tbody td:nth-child(6) {
    width: auto !important;
    min-width: 150px !important;
}

.report-selling .table thead th:last-child,
.report-selling .table tbody td:last-child {
    width: 100px !important;
    min-width: 100px !important;
    text-align: right !important;
}

/* Table body - ensure alignment with header */
.report-selling .table tbody td {
    padding: 10px 15px !important;
    border: none !important;
    border-bottom: 1px solid #f0f0f0 !important;
    color: #303972 !important;
    vertical-align: middle !important;
    font-size: 13px !important;
    white-space: nowrap !important;
}

.report-selling .table tbody tr:hover {
    background: #f8f9ff !important;
}

.report-selling .table tbody tr:last-child td {
    border-bottom: none !important;
}

/* Ensure header and body columns align */
.report-selling .table thead th,
.report-selling .table tbody td {
    box-sizing: border-box !important;
}
</style>

<div class="card report-selling">
    <div class="card-header">
        <h3><i class="fa fa-money"></i> <?= $_selling_report ?> <?= ucfirst($idhr) . ucfirst(substr($idbl,0,3).' '.substr($idbl,3,5)); if ($prefix != "") {echo " prefix [" . $prefix . "]";} ?>
        <small id="loader" style="display: none;"><i class='fa fa-circle-o-notch fa-spin'></i> <?= $_processing ?></small>
        </h3>
    </div>
    <div class="card-body">
        
        <!-- Toolbar -->
        <div class="report-toolbar">
            <input id="filterTable" type="text" class="form-control" style="max-width: 150px;" placeholder="<?= $_search ?>">
            <button class="btn bg-primary" onclick="location.href='#help';"><i class="fa fa-question"></i> <?= $_help ?></button>
            <button class="btn bg-primary" onclick="exportTableToCSV('report-mikpay-<?= $filedownload . $fprefix; ?>.csv')"><i class="fa fa-download"></i> CSV</button>
            <button class="btn bg-primary" onclick="location.href='./?report=selling&session=<?= $session; ?>';"><i class="fa fa-search"></i> <?= $_all ?></button>
            <?php if(!empty($idbl)){
                echo '<button id="openResume" class="btn bg-primary"><i class="fa fa-area-chart"></i> '.$_resume.'</button>';
            } else {
                echo '<a class="btn bg-primary" href="./?report=selling&idbl='.$idbl2.'&session='.$session.'"><i class="fa fa-search"></i> '.ucfirst(substr($idbl2,0,3).' '.substr($idbl2,3,5)).'</a>';
            }?>
            <button class="btn bg-primary" onclick="window.open('./report/print.php?<?= explode("?report=selling&",$url)[1] ?>','_blank');"><i class="fa fa-print"></i> <?= $_print ?></button>
            <button style="display: <?= $shd; ?>;" class="btn bg-danger" onclick="location.href='#remdata';"><i class="fa fa-trash"></i> <?= $_delete_data.' '. $filedownload; ?></button>
            <button id="remSelected" style="display: none;" class="btn bg-danger" onclick="MikpayRemoveReportSelected()"><i class="fa fa-trash"></i> <span id="selected"></span> <?= $_selected ?></button>
        </div>
        
        <!-- Filter -->
        <div class="report-filter">
            <select id="D" title="<?= $_days ?>">
                <?php
                $day = explode("/", $idhr)[1];
                if ($day != "") { echo "<option value='" . $day . "'>" . $day . "</option>"; }
                echo "<option value=''>Day</option>";
                for ($x = 1; $x <= 31; $x++) {
                    $x = (strlen($x) == 1) ? "0" . $x : $x;
                    echo "<option value='" . $x . "'>" . $x . "</option>";
                }
                ?>
            </select>
            <select id="M" title="Month">
                <?php
                $idbls = array(1 => "jan", "feb", "mar", "apr", "may", "jun", "jul", "aug", "sep", "oct", "nov", "dec");
                $idblf = array(1 => "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
                $month = explode("/", $idhr)[0];
                $month1 = substr($idbl, 0, 3);
                if ($month != "") {
                    $fm = array_search($month, $idbls);
                    echo "<option value='" . $month . "'>" . $idblf[$fm] . "</option>";
                } elseif ($month1 != "") {
                    $fm = array_search($month1, $idbls);
                    echo "<option value=" . $month1 . ">" . $idblf[$fm] . "</option>";
                } else {
                    echo "<option value=" . $idbls[date("n")] . ">" . $idblf[date("n")] . "</option>";
                }
                for ($x = 1; $x <= 12; $x++) {
                    echo "<option value='" . $idbls[$x] . "'>" . $idblf[$x] . "</option>";
                }
                ?>
            </select>
            <select id="Y" title="Year">
                <?php
                $year = explode("/", $idhr)[2];
                $year1 = substr($idbl, 3, 4);
                if ($year != "") { echo "<option>" . $year . "</option>"; }
                elseif ($year1 != "") { echo "<option>" . $year1 . "</option>"; }
                echo "<option>" . date("Y") . "</option>";
                for ($Y = 2018; $Y <= date("Y"); $Y++) {
                    if ($Y != date("Y")) { echo "<option value='" . $Y . "'>" . $Y . "</option>"; }
                }
                ?>
            </select>
            <button class="filter-btn" onclick="filterR(); loader();"><i class="fa fa-search"></i> Filter</button>
        </div>
        <script>
        function filterR(){
            var D = document.getElementById('D').value;
            var M = document.getElementById('M').value;
            var Y = document.getElementById('Y').value;
            var X = document.getElementById('filterTable').value;
            if(D !== ""){
                window.location='./?report=selling&idhr='+M+'/'+D+'/'+Y+'&prefix='+X+'&session=<?= $session; ?>';
            }else{
                window.location='./?report=selling&idbl='+M+Y+'&prefix='+X+'&session=<?= $session; ?>';
            }
        }
        </script>
        
        <!-- Total Card -->
        <div class="report-total-card">
            <div>
                <h4><?= $_selling_report ?> <?= $filedownload . $fprefix; ?></h4>
                <p><?= $TotalReg ?> Transaksi</p>
            </div>
            <div style="text-align:right;">
                <h4><?= $_total ?></h4>
                <p id="total"></p>
            </div>
        </div>
        
        <!-- Table -->
        <div class="table-responsive">
            <table id="dataTable" class="table table-hover">
                <thead>
                <tr>
                    <th>&#8470;</th>
                    <th><?= $_date ?></th>
                    <th><?= $_time ?></th>
                    <th><?= $_user_name ?></th>
                    <th><?= $_profile ?></th>
                    <th><?= $_comment ?></th>
                    <th style="text-align:right;"><?= $_price ?></th>
                </tr>
                </thead>
				<tbody>
				<?php
			if ($prefix != "") {
				for ($i = 0; $i < $TotalReg; $i++) {
					$getname = explode("-|-", $getData[$i]['name']);
					if (substr($getname[2], 0, strlen($prefix)) == $prefix) {
						echo "<tr>";
						echo "<td>";
						$tgl = $getname[0];
						echo $tgl;
						echo "</td>";
						echo "<td>";
						$ltime = $getname[1];
						echo $ltime;
						echo "</td>";
						echo "<td>";
						$username = $getname[2];
						echo $username;
						echo "</td>";
						echo "<td>";
						$profile = $getname[7];
						echo $profile;
						echo "</td>";
						echo "<td>";
						$comment = $getname[8];
						echo $comment;
						echo "</td>";
						echo "<td style='text-align:right;'>";
						$price = $getname[3];
						echo $price;
						echo "</td>";
						echo "</tr>";
					}
				}
			} else {
				for ($i = 0; $i < $TotalReg; $i++) {
					$getname = explode("-|-", $getData[$i]['name']);
					echo "<tr>";
					echo "<td>";
					$tgl = $getname[0];
					echo $tgl;
					echo "</td>";
					echo "<td>";
					$ltime = $getname[1];
					echo $ltime;
					echo "</td>";
					echo "<td>";
					$username = $getname[2];
					echo $username;
					echo "</td>";
					echo "<td>";
					$profile = $getname[7];
					echo $profile;
					echo "</td>";
					echo "<td>";
					$comment = $getname[8];
					echo $comment;
					echo "</td>";
					echo "<td style='text-align:right;'>";
					$price = $getname[3];
					echo $price;
					echo "</td>";
					echo "</tr>";
				
				$dataresume .= $getname[0].$getname[3];
				$totalresume += $getname[3];
				$_SESSION['dataresume'] = $dataresume;
				$_SESSION['totalresume'] = $TotalReg.'/'.$totalresume;
				}
					
			}

			?>
	                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal-window" id="remdata" aria-hidden="true">
  <div>
  	<header><h1><?= $_confirm ?></h1></header>
  	<a style="font-weight:bold;" href="#" title="Close" class="modal-close">X</a>
	<p>
			<?= $_delete_report ?>
	</p>
	<form autocomplete="off" method="post" action="">
	<center>
	<button type="submit" name="remdata" title="Yes" class="btn bg-primary">Yes</button>&nbsp;
	<a class="btn bg-secondary" href="#" title="Close" class="modal-close">No</a>
	</center>
	</form>
  </div>
</div>
<div class="modal-window" id="help" aria-hidden="true">
  <div>
  	<header><h1><?= $_help ?></h1></header>
  	<a style="font-weight:bold;" href="#" title="Close" class="modal-close">X</a>
	<p>
			<?= $_help_report ?>
	</p>
  </div>
</div>
</div>
