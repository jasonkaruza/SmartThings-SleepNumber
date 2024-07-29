<?php
/// SETTINGS FOR THE PROJECT ///
/// ENTER VALUES WHERE INDICATED ///

// error reporting (UPDATE IF DESIRED)
ini_set('display_errors', 1); // Optional: 1 - yes, 0 - no
ini_set("log_errors", 1); // Optional: 1 - yes, 0 - no
error_reporting(E_ALL); // Optional: https://www.php.net/manual/en/function.error-reporting.php

// Logging (UPDATE)
date_default_timezone_set('America/Los_Angeles'); // Optional: https://www.php.net/manual/en/timezones.php
define('BASE_PATH', '/Update/this/path/to/st'); // Required: Absolute path to root directory for this project
define('LOG_PATH', BASE_PATH . '/requestlogs.txt'); // Optional: Log file name
ini_set("error_log", LOG_PATH); // Optional: Where to log errors

// DB SETTINGS (SET ALL THREE)
define('DB_NAME', '<update_this>'); // Required: The name of the database storing Oauth data
define('DB_USER', '<update_this>'); // Required: The username for the database
define('DB_PASS', '<update_this>'); // Required: The password for the database user

// My generated Oauth creds for SmartThings
// Values I generated and saved here (App Details):
// https://smartthings.developer.samsung.com/workspace/projects/CPT-PARTNER/<id>/connector
// Should be stored in the DB oauth_client table
define('SN_CLIENT_ID', "<update_this>"); // Required: The App Details Client ID (not App Credentials) that you create in smartthings.developer.samsung.com for the device
define('SN_CLIENT_SECRET', "<update_this>"); // Required: The App Details Client Secret (not App Credentials)

// Provided by ST (App Credentials)
define('ST_CLIENT_ID', "<update_this>"); // Required: App Credentials Client ID (not App Details)
define('ST_CLIENT_SECRET', "<update_this>"); // Required: App Credentials Client Secret (not App Details)

// Device info https://smartthings.developer.samsung.com/workspace/projects/CPT-PARTNER/<id>/profile
define('DEVICE_PROFILE_ID', '<update_this>'); // AKA deviceHandlerType 

// Specifies if this is a single-account SmartThings integration (true), or generic for multiple people to authenticate and have their credentials stored in the DB (false)
define('SINGLE_ACCOUNT_CONFIG', true); // Optional: Defaults to true/single-account. Set to false to enable multi-account support.

// Sleepnumber Credentials (if hard-coding for a single account; otherwise if storing multiple accounts in the DB, these can be deleted/commented out)
define('SN_USER', '<update_this>'); // Required (if SINGLE_ACCOUNT_CONFIG is true): Your SleepNumber account username
define('SN_PASS', '<update_this>'); // Required (if SINGLE_ACCOUNT_CONFIG is true): Your SleepNumber account password

// CAPTCHA Google SiteKey (from https://www.google.com/recaptcha). If not using one, set to an empty string.
define('CAPTCHA_SITE_KEY', '<update_this_or_set_to_empty_string'); // Required: Your CAPTCHA site key if using a CAPTCHA in authorize.php or an empty string

// Encryption configuration
define('ENCRYPTION_METHOD', 'AES-256-CBC'); // This shouldn't be modified, but you can change it to another valid cipher if desired https://www.php.net/manual/en/function.openssl-get-cipher-methods.php
define('ENCRYPTION_KEY', '<update_this>'); // Required: Set this to a random string. For AES-256, the max length needed is 32 chars. If using a pepper below, set to half that length
/**
 * START PEPPER CODE
 * This is for extra security. If you don't want to use this, comment out this 
 * line and the following lines up until it says END PEPPER CODE. 
 * 
 * If you want to use it, don't change the code below, but use the value of
 * ENCRYPTION_ENV_VARIABLE_NAME as the name of an environment variable that you
 * store on your system and set to a random string for additional security with
 * encryption/decryption.
 * 
 * This may require setting it in .htaccess, php.ini, apache.conf, etc. to ensure
 * that it can be used by the HTTP handler.
 */
define('ENCRYPTION_ENV_VARIABLE_NAME', 'SN_ENCRYPTION_PEPPER');
define('ENCRYPTION_PEPPER', getenv(ENCRYPTION_ENV_VARIABLE_NAME) ? getenv(ENCRYPTION_ENV_VARIABLE_NAME) : (get_cfg_var(ENCRYPTION_ENV_VARIABLE_NAME) ? get_cfg_var(ENCRYPTION_ENV_VARIABLE_NAME) : (array_key_exists(ENCRYPTION_ENV_VARIABLE_NAME, $_SERVER) ? $_SERVER[ENCRYPTION_ENV_VARIABLE_NAME] : ''
)
)); // Don't change this unless you don't want to use it
// Confirm the environment variable's value was loaded, or throw an exception
if (ENCRYPTION_PEPPER === false) {
    throw new Exception("ENCRYPTION_PEPPER was not properly loaded from the environment variables. Exiting. Read more about this in the README and in the settings file.\n\n");
}
// END PEPPER CODE