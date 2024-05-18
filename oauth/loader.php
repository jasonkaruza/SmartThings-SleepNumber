<?php
// Load the settings
require_once "settings.php";

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
