<?php
/// SETTINGS FOR THE PROJECT ///
/// ENTER VALUES WERE INDICATED ///

// error reporting (UPDATE IF DESIRED)
ini_set('display_errors', 1); // Optional: 1 - yes, 0 - no
ini_set("log_errors", 1); // Optional: 1 - yes, 0 - no
error_reporting(E_ALL); // Optional: https://www.php.net/manual/en/function.error-reporting.php

// Logging (UPDATE)
date_default_timezone_set('America/Los_Angeles'); // Optional: https://www.php.net/manual/en/timezones.php
define('BASE_PATH', '\\Update\\this\\path\\to\\st'); // Required: Absolute path to root directory for this project
define('LOG_PATH', BASE_PATH . '\\requestlogs.txt'); // Optional: Log file name
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

// Sleepnumber Credentials
define('SN_USER', '<update_this>'); // Required: Your SleepNumber account username
define('SN_PASS', '<update_this>'); // Required: Your SleepNumber account password
