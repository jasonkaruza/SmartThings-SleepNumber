<?php
// Load the settings
require_once "settings.php";

// The 'name' of the storage object associated with the Oauth server
define('STORAGE_NAME', 'user_credentials');

logrequest(); // For logging

///////////////////////// HELPERS ///////////////////////////
function logrequest()
{
    $text = "***" . date("Y-m-d H:i:s", time()) . "\nREQUEST:" . print_r($_REQUEST, true) . "\n\nSERVER:" . print_r($_SERVER, true);
    //$fileContents = file_get_contents($file);
    //$text = $text . $fileContents;
    logtext($text);
}

function logtext($text, $newlines = true)
{
    $separator = $newlines ? "\n\n" : "";
    file_put_contents(LOG_PATH, $text . $separator, FILE_APPEND);
}

if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

// ENCRYPTION HELPERS //
// From https://medium.com/@london.lingo.01/unlocking-the-power-of-php-encryption-secure-data-transmission-and-encryption-algorithms-c5ed7a2cb481
// https://stackoverflow.com/questions/64022615/2-way-string-encryption-in-php-which-of-these-is-more-secure

/**
 * Compose the key from the settings key value and optional pepper env var
 * @return string The encryption key
 */
function getEncryptionKey()
{
    $key = ENCRYPTION_KEY;
    if (defined('ENCRYPTION_PEPPER')) {
        $key .= ENCRYPTION_PEPPER;
    }
    return $key;
}

/**
 * Encrypt some data. We will use this for securely storing passwords in the DB.
 * @param $data The data to encrypt.
 * @return string The encrypted value
 */
function encryptData($data)
{
    $key = getEncryptionKey();

    // Generate a random initialization vector (IV)
    // If needed, use openssl_get_cipher_methods() to check which encryption
    // methods/ciphers are supported on the system.
    $is_strong = null;
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(ENCRYPTION_METHOD), $is_strong);
    if (!$is_strong) {
        throw new Exception("The use of cipher to generate a random iv for encryption returned a non-strong value. You may want to try another cipher\n\n");
    }

    // Encrypt the data
    $encrypted = openssl_encrypt($data, ENCRYPTION_METHOD, $key, OPENSSL_RAW_DATA, $iv);

    // Concatenate the IV and the encrypted data
    $encrypted = base64_encode($iv . $encrypted);
    return $encrypted;
}

/**
 * Decrypt some data. We will use this for retrieving stored passwords in the DB.
 * @param $encryptedData The data to decrypt.
 * @return string The decrypted value
 */
function decryptData($encryptedData)
{
    $key = getEncryptionKey();

    // Decode the encrypted data
    $encrypted = base64_decode($encryptedData);

    // Extract the IV and the encrypted data
    $ivLength = openssl_cipher_iv_length(ENCRYPTION_METHOD);
    $iv = substr($encrypted, 0, $ivLength);
    $encrypted = substr($encrypted, $ivLength);

    // Decrypt the data
    $decrypted = openssl_decrypt($encrypted, ENCRYPTION_METHOD, $key, OPENSSL_RAW_DATA, $iv);
    return $decrypted;
}
