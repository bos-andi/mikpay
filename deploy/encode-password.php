<?php
/*
 * Password Encoder untuk MIKPAY
 * 
 * Cara penggunaan:
 * php encode-password.php your_password
 * 
 * Atau edit file ini dan isi $password, lalu jalankan:
 * php encode-password.php
 */

// Ambil password dari command line argument atau set manual
$password = isset($argv[1]) ? $argv[1] : '';

if (empty($password)) {
    // Jika tidak ada argument, gunakan password default atau minta input
    echo "========================================\n";
    echo "MIKPAY Password Encoder\n";
    echo "========================================\n\n";
    echo "Masukkan password yang ingin di-encode: ";
    $password = trim(fgets(STDIN));
}

if (empty($password)) {
    echo "Error: Password tidak boleh kosong!\n";
    echo "Usage: php encode-password.php your_password\n";
    exit(1);
}

// Encode password ke Base64
$encoded = base64_encode($password);

echo "\n========================================\n";
echo "Password Encoder Result\n";
echo "========================================\n";
echo "Password Asli: " . $password . "\n";
echo "Password Encoded (Base64): " . $encoded . "\n";
echo "\nGunakan string berikut di config.php:\n";
echo "ROUTER_NAME#|#" . $encoded . "\n";
echo "========================================\n";
