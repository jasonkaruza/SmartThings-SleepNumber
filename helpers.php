<?php
require_once "oauth/settings.php";

/** Helper functions **/

/**
 * Get the Database connection. This should be called at the top of any function
 * that relies on using the database.
 * @return Database object
 */
function getDb()
{
    require_once "./db.php";
    global $DB;
    if (!$DB) {
        $DB = new Database(DB_HOST, DB_NAME, DB_USER, DB_PASS);
    }
    return $DB;
}

/**
 * A helper function to take an array of associative arrays and return an associative
 * array with the key being the value of the $key parameter.
 * @param array $array The array to convert
 * @param string $key The key to use for the associative array
 * @return array The associative array with the key being the value of the $key parameter
 */
function assocByKey(array $array, string $key): array
{
    $result = [];
    foreach ($array as $item) {
        if (array_key_exists($key, $item)) {
            $result[$item[$key]] = $item;
        }
    }
    return $result;
} // End function assocByKey

/**
 * 
 * Check if the external device ID is a test device and return the value if set
 * @param mixed $externalDeviceId
 * @return bool
 */
function isOrGetTestDevice($externalDeviceId)
{
    global $TEST_EXTERNAL_DEVICE_ID_DEVICE_PROFILE_MAP;
    return is_array($TEST_EXTERNAL_DEVICE_ID_DEVICE_PROFILE_MAP) && array_key_exists($externalDeviceId, $TEST_EXTERNAL_DEVICE_ID_DEVICE_PROFILE_MAP)
        ? $TEST_EXTERNAL_DEVICE_ID_DEVICE_PROFILE_MAP[$externalDeviceId]
        : false;
} // End function isTestDevice
