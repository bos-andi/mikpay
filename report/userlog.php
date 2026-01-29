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
	$remdata = ($_POST['remdata']);


	if (strlen($idhr) > "0") {
		if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
			$API->write('/system/script/print', false);
			$API->write('?=source=' . $idhr . '');
			$ARRAY = $API->read();
			$API->disconnect();
		}
		$filedownload = $idhr;
		$shf = "hidden";
		$shd = "text";
	} elseif (strlen($idbl) > "0") {
		if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
			$API->write('/system/script/print', false);
			$API->write('?=owner=' . $idbl . '');
			$ARRAY = $API->read();
			$API->disconnect();
		}
		$filedownload = $idbl;
		$shf = "hidden";
		$shd = "text";
	} elseif ($idhr == "" || $idbl == "") {
		if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
			$API->write('/system/script/print', false);
			$API->write('?=comment=mikpay');
			$ARRAY = $API->read();
			$API->disconnect();
		}
		$filedownload = "all";
		$shf = "text";
		$shd = "hidden";
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

		</script>
<style>
.userlog-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 20px;
    align-items: center;
}
.userlog-filter {
    display: flex;
    gap: 5px;
    background: #f8f9fa;
    padding: 10px 15px;
    border-radius: 10px;
    margin-bottom: 20px;
}
.userlog-filter select {
    padding: 8px 12px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 13px;
    background: #fff;
}
.userlog-filter .filter-btn {
    padding: 8px 20px;
    background: #4D44B5;
    color: #fff;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
}
</style>

<div class="card">
    <div class="card-header">
        <h3><i class="fa fa-align-justify"></i> User Log <?= $idhr . $idbl; ?></h3>
    </div>
    <div class="card-body" style="padding: 20px;">
        
        <!-- Toolbar -->
        <div class="userlog-toolbar">
            <input id="filterTable" type="text" class="form-control" style="max-width: 150px;" placeholder="Search..">
            <button class="btn bg-primary" onclick="exportTableToCSV('user-log-mikpay-<?= $filedownload; ?>.csv')"><i class="fa fa-download"></i> CSV</button>
            <button class="btn bg-primary" onclick="location.href='./?report=userlog&session=<?= $session; ?>';"><i class="fa fa-search"></i> <?= $_all ?></button>
        </div>
        
        <!-- Filter -->
        <div class="userlog-filter">
            <select id="D" title="Day">
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
                else { echo "<option>" . date("Y") . "</option>"; }
                for ($Y = 2018; $Y <= date("Y"); $Y++) {
                    if ($Y != date("Y")) { echo "<option value='" . $Y . "'>" . $Y . "</option>"; }
                }
                ?>
            </select>
            <button class="filter-btn" onclick="filterR();"><i class="fa fa-search"></i> Filter</button>
        </div>
        <script>
        function filterR(){
            var D = document.getElementById('D').value;
            var M = document.getElementById('M').value;
            var Y = document.getElementById('Y').value;
            if(D !== ""){
                window.location='./?report=userlog&idhr='+M+'/'+D+'/'+Y+'&session=<?= $session; ?>';
            }else{
                window.location='./?report=userlog&idbl='+M+Y+'&session=<?= $session; ?>';
            }
        }
        </script>
        
        <!-- Table -->
        <div class="table-responsive">
            <table id="dataTable" class="table table-hover">
                <thead>
                <tr>
                    <th><?= $_date ?></th>
                    <th><?= $_time ?></th>
                    <th><?= $_user_name ?></th>
                    <th>Address</th>
                    <th>Mac Address</th>
                    <th><?= $_validity ?></th>
                </tr>
                </thead>
                <tbody>
				<?php
			$TotalReg = count($ARRAY);

			for ($i = 0; $i < $TotalReg; $i++) {
				$regtable = $ARRAY[$i];
				echo "<tr>";
				echo "<td>";
				$getname = explode("-|-", $regtable['name']);
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
				$addr = $getname[4];
				echo $addr;
				echo "</td>";
				echo "<td>";
				$mac = $getname[5];
				echo $mac;
				echo "</td>";
				echo "<td>";
				$val = $getname[6];
				echo $val;
				echo "</td>";
				echo "</tr>";
			}
            ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
