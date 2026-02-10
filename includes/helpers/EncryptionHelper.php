<?php
namespace PBay\Helpers;

/**
 * Encryption Helper for Policy Wallet Storage
 * Uses WordPress salts for encryption key derivation
 * Adapted from cardano-mint-pay
 */
class EncryptionHelper {

    public static function encrypt($plaintext) {
        if (empty($plaintext)) {
            return '';
        }

        $key = self::deriveKey();
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            return '';
        }

        return base64_encode($iv . $encrypted);
    }

    public static function decrypt($ciphertext) {
        if (empty($ciphertext)) {
            return false;
        }

        $key = self::deriveKey();
        $data = base64_decode($ciphertext, true);

        if ($data === false || strlen($data) < 17) {
            return false;
        }

        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);

        $plaintext = openssl_decrypt($encrypted, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        if ($plaintext === false) {
            return false;
        }

        return $plaintext;
    }

    private static function deriveKey() {
        $salt_data = AUTH_KEY . SECURE_AUTH_KEY . LOGGED_IN_KEY . NONCE_KEY;
        return hash('sha256', $salt_data, true);
    }

    public static function test() {
        $test_data = 'test_encryption_' . time();
        $encrypted = self::encrypt($test_data);
        $decrypted = self::decrypt($encrypted);
        return ($decrypted === $test_data);
    }
}
