<?php

/**
 * Sample URL:
 * https://computerjourney.com/st/oauth/?
 * client_id=<client_id>
 * &redirect_uri=https%3A%2F%2Fc2c-us.smartthings.com%2Foauth%2Fcallback
 * &response_type=code
 * &state=<state>
 * &scope=read%2Cwrite
 **/
// Load the settings
require_once 'loader.php';

// Dev testing
if ((php_sapi_name() == 'cgi-fcgi' || php_sapi_name() == 'cli') && (str_contains($_SERVER['PHP_SELF'], 'oauth/'))) {
    $shortopts = '';
    $longopts = array(
        "grant_type:", // Required value
        "client_id::",     // Optional value
        "client_secret::",    // Optional value
        "code::", // Optional value
        "redirect_uri::", // Optional value
        "refresh_token::", // Optional value
        "request_method::", // Optional value
    );
    $options = getopt($shortopts, $longopts);
    if (!$options['grant_type']) {
        exit;
    }

    foreach ($options as $key => $val) {
        if (!is_null($options[$key])) {
            $_GET[$key] = $_POST[$key] = $_REQUEST[$key] = $val;
        }
    }

    // Defaults set in settings.php or passed by SmartThings
    if (!isset($options['client_id'])) {
        $_GET['client_id'] = $_POST['client_id'] = $_REQUEST['client_id'] = SN_CLIENT_ID;
    }
    if (!isset($options['client_secret'])) {
        $_GET['client_secret'] = $_POST['client_secret'] = $_REQUEST['client_secret'] = SN_CLIENT_SECRET;
    }
    if (!isset($options['redirect_uri'])) {
        $_GET['redirect_uri'] = $_POST['redirect_uri'] = $_REQUEST['redirect_uri'] = 'https://c2c-us.smartthings.com/oauth/callback';
    }
    if (!isset($options['request_method'])) {
        $_SERVER['REQUEST_METHOD'] = 'POST';
    }
    if (!isset($options['content_type'])) {
        $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
    }
    print_r($_REQUEST);
}

// https://bshaffer.github.io/oauth2-server-php-docs/
// Instructions for the server.php and token.php files, etc. 
// https://bshaffer.github.io/oauth2-server-php-docs/cookbook/
$dsn = 'mysql:dbname=' . DB_NAME . ';host=localhost';
$dbUser = DB_USER;
$dbPassword = DB_PASS;

// Autoloading (composer is preferred, but for this example let's just do this)
require_once __DIR__ . '/oauth2-server-php/src/OAuth2/Autoloader.php';
OAuth2\Autoloader::register();

// $dsn is the Data Source Name for your database, for exmaple "mysql:dbname=my_oauth2_db;host=localhost"
$storage = new OAuth2\Storage\Pdo(['dsn' => $dsn, 'username' => $dbUser, 'password' => $dbPassword]);

// Pass a storage object or array of storage objects to the OAuth2 server class
$server = new OAuth2\Server($storage, [
    'allow_implicit' => true,
    'unset_refresh_token_after_use' => false,
    'always_issue_new_refresh_token' => false, // the refresh token grant request will have a "refresh_token" field, with a new refresh token on each request
    'refresh_token_lifetime' => 0, // the refresh tokens now last 28 days vs 1209600 (14-days) vs 2419200 (28 days)
    'auth_code_lifetime' => 600, // Setting to 10 minutes instead of default 30 seconds. Used in setting override within getDefaultResponseTypes().
]);

// Add the "Client Credentials" grant type (it is the simplest of the grant types)
$server->addGrantType(new OAuth2\GrantType\ClientCredentials($storage));

// Add the "Authorization Code" grant type (this is where the oauth magic happens)
$server->addGrantType(new OAuth2\GrantType\AuthorizationCode($storage));
