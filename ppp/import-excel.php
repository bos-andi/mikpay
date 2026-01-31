<?php
/*
 * Import Excel Template untuk Update Data Pelanggan
 * Membaca file Excel/CSV dan update data pelanggan yang masih kosong
 */

// Set error handling - capture all errors
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to catch any unexpected output
ob_start();

session_start();
header('Content-Type: application/json; charset=utf-8');

// Function to send JSON response and exit
function sendJsonResponse($data) {
    ob_clean(); // Clear any output
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!isset($_SESSION["mikpay"])) {
    sendJsonResponse(['success' => false, 'message' => 'Session expired']);
}

$session = $_POST['session'] ?? $_GET['session'] ?? '';

if (empty($session)) {
    sendJsonResponse(['success' => false, 'message' => 'Session tidak ditemukan']);
}

// Include billing data functions
include_once(dirname(__FILE__) . '/billing-data.php');

// Check if file was uploaded
if (!isset($_FILES['excel_file'])) {
    sendJsonResponse(['success' => false, 'message' => 'File tidak ditemukan. Pastikan file sudah dipilih.']);
}

if ($_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => 'File terlalu besar (melebihi upload_max_filesize di php.ini)',
        UPLOAD_ERR_FORM_SIZE => 'File terlalu besar (melebihi MAX_FILE_SIZE di form)',
        UPLOAD_ERR_PARTIAL => 'File hanya ter-upload sebagian',
        UPLOAD_ERR_NO_FILE => 'Tidak ada file yang di-upload',
        UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan',
        UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk',
        UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh extension PHP'
    ];
    $errorMsg = isset($errorMessages[$_FILES['excel_file']['error']]) 
        ? $errorMessages[$_FILES['excel_file']['error']] 
        : 'Error upload file: ' . $_FILES['excel_file']['error'];
    sendJsonResponse(['success' => false, 'message' => $errorMsg]);
}

$file = $_FILES['excel_file'];
$fileName = $file['name'];
$fileTmpName = $file['tmp_name'];
$fileSize = $file['size'];
$fileError = $file['error'];

// Check file size (max 5MB)
if ($fileSize > 5 * 1024 * 1024) {
    sendJsonResponse(['success' => false, 'message' => 'File terlalu besar. Maksimal 5MB']);
}

// Check file size (min 1 byte)
if ($fileSize < 1) {
    sendJsonResponse(['success' => false, 'message' => 'File kosong atau tidak valid']);
}

// Check file extension - RESMI: hanya CSV
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
if ($fileExt !== 'csv') {
    sendJsonResponse([
        'success' => false, 
        'message' => 'Format file tidak didukung. Gunakan file CSV (.csv) dari tombol Template CSV. ' .
                     'Jika file Anda .xlsx / .xls, buka di Excel lalu pilih File > Save As > CSV (Comma delimited).'
    ]);
}

// Read file
$data = array();
$errors = array();
$successCount = 0;
$skipCount = 0;

try {
    // Normalisasi satu baris CSV ke field
    function parseRowToFields($row) {
        return [
            'username' => isset($row[0]) ? trim((string)$row[0]) : '',
            'display_name' => isset($row[1]) ? trim((string)$row[1]) : '',
            'phone' => isset($row[2]) ? trim((string)$row[2]) : '',
            'due_day' => isset($row[3]) ? trim((string)$row[3]) : '',
            'monthly_fee' => isset($row[4]) ? trim((string)$row[4]) : '',
            'notes' => isset($row[5]) ? trim((string)$row[5]) : '',
        ];
    }

    // Check if file is readable
    if (!is_readable($fileTmpName)) {
        throw new Exception('File tidak bisa dibaca. Cek permission file.');
    }

    // Read CSV file
    $handle = fopen($fileTmpName, 'r');
    if ($handle === false) {
        throw new Exception('Tidak bisa membuka file. Pastikan file CSV valid.');
    }
        
        // Baca baris pertama (header) dan deteksi delimiter
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            throw new Exception('File kosong atau tidak bisa dibaca');
        }
        
        // Skip BOM jika ada
        if (substr($firstLine, 0, 3) === "\xEF\xBB\xBF") {
            $firstLine = substr($firstLine, 3);
        }
        
        // Deteksi delimiter: gunakan ';' jika lebih banyak dari ','
        $commaCount = substr_count($firstLine, ',');
        $semicolonCount = substr_count($firstLine, ';');
        $delimiter = ',';
        if ($semicolonCount > 0 && $semicolonCount >= $commaCount) {
            $delimiter = ';';
        }
        
        // Parse baris pertama sebagai header (walau saat ini tidak dipakai banyak)
        $header = str_getcsv($firstLine, $delimiter);
        
        // (Opsional) Validasi header dasar: pastikan kolom pertama adalah Username PPPoE
        // Jika user mengubah judul kolom, kita tetap lanjut selama struktur kolom tidak berubah.
        
        // Read data rows
        $rowNum = 1; // Start from 1 (header is row 0)
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNum++;
            
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }
            
            // Parse row data
            $fields = parseRowToFields($row);
            $username = $fields['username'];
            $displayName = $fields['display_name'];
            $phone = $fields['phone'];
            $dueDay = $fields['due_day'];
            $monthlyFee = $fields['monthly_fee'];
            $notes = $fields['notes'];
            
            // Skip if username is empty
            if (empty($username)) {
                $skipCount++;
                continue;
            }
            
            // Get existing customer data
            $existing = getCustomerBilling($username);
            $hasUpdate = false;

            // Build final values (preserve existing, fill only empty fields)
            $finalDisplayName = $existing['display_name'] ?? '';
            if ($finalDisplayName === '' && $displayName !== '') { $finalDisplayName = $displayName; $hasUpdate = true; }

            $finalPhone = $existing['phone'] ?? '';
            if ($finalPhone === '' && $phone !== '') { $finalPhone = $phone; $hasUpdate = true; }

            $finalNotes = $existing['notes'] ?? '';
            if ($finalNotes === '' && $notes !== '') { $finalNotes = $notes; $hasUpdate = true; }

            $finalDueDay = isset($existing['due_day']) ? intval($existing['due_day']) : 0;
            if ($finalDueDay === 0 && $dueDay !== '') {
                $dueDayInt = intval($dueDay);
                if ($dueDayInt < 1 || $dueDayInt > 31) {
                    $errors[] = "Baris $rowNum: Tanggal jatuh tempo tidak valid (harus 1-31)";
                    continue;
                }
                $finalDueDay = $dueDayInt;
                $hasUpdate = true;
            }

            $finalMonthlyFee = isset($existing['monthly_fee']) ? floatval($existing['monthly_fee']) : 0;
            if ($finalMonthlyFee == 0 && $monthlyFee !== '') {
                $feeDigits = preg_replace('/[^0-9]/', '', $monthlyFee);
                $feeFloat = floatval($feeDigits);
                if ($feeFloat > 0) {
                    $finalMonthlyFee = $feeFloat;
                    $hasUpdate = true;
                }
            }

            if (!$hasUpdate) {
                $skipCount++;
                continue;
            }

            try {
                saveCustomerBilling($username, $finalDueDay, $finalDisplayName, $finalPhone, $finalMonthlyFee, $finalNotes);
                $successCount++;
            } catch (Exception $e) {
                $errors[] = "Baris $rowNum: Error menyimpan data - " . $e->getMessage();
            }
        }
        
        fclose($handle);
    }
    
    // Prepare response
    $message = "Import selesai! ";
    $message .= "$successCount data berhasil diupdate. ";
    if ($skipCount > 0) {
        $message .= "$skipCount baris dilewati (data kosong atau sudah terisi). ";
    }
    if (!empty($errors)) {
        $message .= count($errors) . " error ditemukan.";
    }
    
    sendJsonResponse([
        'success' => true,
        'message' => $message,
        'stats' => [
            'success' => $successCount,
            'skipped' => $skipCount,
            'errors' => count($errors)
        ],
        'errors' => $errors
    ]);
    
} catch (Exception $e) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'error_detail' => $e->getFile() . ':' . $e->getLine()
    ]);
} catch (Error $e) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Fatal Error: ' . $e->getMessage(),
        'error_detail' => $e->getFile() . ':' . $e->getLine()
    ]);
}
