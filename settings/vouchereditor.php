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
?>
<?php
error_reporting(0);
if (!isset($_SESSION["mikpay"])) {
	header("Location:../admin.php?id=login");
} else {
// load session MikroTik
	$session = $_GET['session'];

// load config
include('../include/config.php');
include('../include/readcfg.php');



$url = $_SERVER['REQUEST_URI'];
$telplate = $_GET['template'];
if ($telplate == "default" || $telplate == "rdefault") {
	$telplatet = "template";
	$popup = "javascript:window.open('./voucher/vpreview.php?usermode=up&qr=no&session=" . $session . "','_blank','width=310,height=310')";
	$popupQR = "javascript:window.open('./voucher/vpreview.php?usermode=up&qr=yes&session=" . $session . "','_blank','width=310,height=310')";
} elseif ($telplate == "thermal" || $telplate == "rthermal") {
	$telplatet = "template-thermal";
	$popup = "javascript:window.open('./voucher/vpreview.php?usermode=up&user=m&qr=no&session=" . $session . "','_blank','width=310,height=310')";
	$popupQR = "javascript:window.open('./voucher/vpreview.php?usermode=up&user=m&qr=yes&session=" . $session . "','_blank','width=310,height=310')";
} elseif ($telplate == "small" || $telplate == "rsmall") {
	$telplatet = "template-small";
	$popup = "javascript:window.open('./voucher/vpreview.php?usermode=up&small=yes&qr=no&session=" . $session . "','_blank','width=310,height=310')";
	$popupQR = "javascript:window.open('./voucher/vpreview.php?usermode=up&small=yes&qr=yes&session=" . $session . "','_blank','width=310,height=310')";
}
if (isset($_POST['save'])) {
	$template = './voucher/' . $telplatet . '.php';
	$handle = fopen($template, 'w') or die('Cannot open file:  ' . $template);

	$data = ($_POST['editor']);

	fwrite($handle, $data);
		
		//header("Location:$url");
}

}
?>
<!-- Create a simple CodeMirror instance -->
<link rel="stylesheet" href="./css/editor.min.css">
<script src="./js/editor.min.js"></script>	

<style>
/* Modern Editor Styling */
.editor-container {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}
.editor-main {
    flex: 1;
    min-width: 0;
}
.editor-sidebar {
    width: 280px;
    flex-shrink: 0;
}
@media (max-width: 992px) {
    .editor-sidebar {
        width: 100%;
    }
}
.editor-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 20px;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
}
.editor-toolbar .btn {
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.editor-toolbar .btn-save {
    background: linear-gradient(135deg, #4D44B5 0%, #3d3690 100%);
    color: #fff;
}
.editor-toolbar .btn-preview {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: #fff;
}
.editor-select-group {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-left: auto;
}
.editor-select-group label {
    font-size: 13px;
    font-weight: 500;
    color: #4D44B5;
    margin: 0;
}
.editor-select-group select {
    padding: 8px 12px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 13px;
    background: #fff;
    min-width: 120px;
}
.CodeMirror {
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    height: 520px;
    font-size: 13px;
}
.variable-box {
    background: #1e1e2d;
    color: #a0aec0;
    border-radius: 10px;
    padding: 15px;
    font-size: 12px;
    font-family: 'Monaco', 'Menlo', monospace;
    height: 520px;
    overflow-y: auto;
    white-space: pre-wrap;
    word-break: break-all;
}
</style>

<div class="editor-container">
    <!-- Main Editor -->
    <div class="editor-main">
        <div class="card">
            <div class="card-header">
                <h3><i class="fa fa-edit"></i> <?= $_template_editor ?></h3>
            </div>
            <div class="card-body" style="padding: 20px;">
                <form autocomplete="off" method="post" action="">
                    <!-- Toolbar -->
                    <div class="editor-toolbar">
                        <button type="submit" title="Save template" class="btn btn-save" name="save">
                            <i class="fa fa-save"></i> <?= $_save ?>
                        </button>
                        <a class="btn btn-preview" href="<?= $popup?>" title="View voucher with Logo">
                            <i class="fa fa-image"></i> Preview
                        </a>
                        <a class="btn btn-preview" href="<?= $popupQR?>" title="View voucher with QR">
                            <i class="fa fa-qrcode"></i> QR Code
                        </a>
                        
                        <div class="editor-select-group">
                            <label>Template:</label>
                            <select onchange="window.location.href=this.value+'&session=<?= $session; ?>';">
                                <option><?= ucfirst($telplate); ?></option>
                                <option value="./admin.php?id=editor&template=default">Default</option>
                                <option value="./admin.php?id=editor&template=thermal">Thermal</option>
                                <option value="./admin.php?id=editor&template=small">Small</option>
                            </select>
                            
                            <label>Reset:</label>
                            <select onchange="window.location.href=this.value+'&session=<?= $session; ?>';">
                                <option><?= ucfirst($telplate); ?></option>
                                <option value="./admin.php?id=editor&template=rdefault">Default</option>
                                <option value="./admin.php?id=editor&template=rthermal">Thermal</option>
                                <option value="./admin.php?id=editor&template=rsmall">Small</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Editor -->
                    <textarea id="editorMikpay" name="editor" style="display:none;">
<?php if ($telplate == "default") {
    echo file_get_contents('./voucher/template.php');
} elseif ($telplate == "thermal") {
    echo file_get_contents('./voucher/template-thermal.php');
} elseif ($telplate == "small") {
    echo file_get_contents('./voucher/template-small.php');
} elseif ($telplate == "rdefault") {
    echo file_get_contents('./voucher/default.php');
} elseif ($telplate == "rthermal") {
    echo file_get_contents('./voucher/default-thermal.php');
} elseif ($telplate == "rsmall") {
    echo file_get_contents('./voucher/default-small.php');
} ?>
                    </textarea>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Sidebar - Variables -->
    <div class="editor-sidebar">
        <div class="card">
            <div class="card-header">
                <h3><i class="fa fa-code"></i> Variable</h3>
            </div>
            <div class="card-body" style="padding: 15px;">
                <div class="variable-box"><?= htmlspecialchars(file_get_contents('./voucher/variable.php')); ?></div>
            </div>
        </div>
    </div>
</div>

<script>
// Session storage for editor
if (typeof(Storage) !== "undefined") {
    var sessionEl = document.getElementById("MikpaySession");
    if (sessionEl) {
        sessionStorage.setItem("MikpaySession", sessionEl.innerHTML);
    }
} else {
    alert("Please use Google Chrome");
}

// Initialize CodeMirror editor
var editor = CodeMirror.fromTextArea(document.getElementById("editorMikpay"), {
    lineNumbers: true,
    matchBrackets: true,
    mode: "application/x-httpd-php",
    indentUnit: 4,
    indentWithTabs: true,
    lineWrapping: true,
    viewportMargin: Infinity,
    matchTags: {bothTags: true},
    extraKeys: {"Ctrl-J": "toMatchingTag"}
});
editor.setOption("theme", "material");
</script>


