<?php
/*
 * Password Security Helper
 * Functions untuk secure password hashing dan verification
 */

/**
 * Hash password dengan password_hash (untuk user passwords)
 * @param string $password - Plain text password
 * @return string - Hashed password
 */
function secureHashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password dengan password_verify
 * @param string $password - Plain text password
 * @param string $hash - Hashed password
 * @return bool - True if password matches
 */
function verifySecurePassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check if password is hashed (password_hash format)
 * @param string $password - Password string to check
 * @return bool - True if it's a password_hash format
 */
function isPasswordHashed($password) {
    // password_hash menghasilkan string dengan format: $2y$10$...
    return preg_match('/^\$2[ayb]\$.{56}$/', $password);
}

/**
 * Migrate old password to hash (for backward compatibility)
 * @param string $oldPassword - Old password (plain text or base64)
 * @return string - New hashed password
 */
function migratePasswordToHash($oldPassword) {
    // If already hashed, return as is
    if (isPasswordHashed($oldPassword)) {
        return $oldPassword;
    }
    // If base64 encoded, decode first
    if (base64_encode(base64_decode($oldPassword, true)) === $oldPassword) {
        $decoded = base64_decode($oldPassword, true);
        if ($decoded !== false) {
            return secureHashPassword($decoded);
        }
    }
    // Otherwise, hash the plain text
    return secureHashPassword($oldPassword);
}
