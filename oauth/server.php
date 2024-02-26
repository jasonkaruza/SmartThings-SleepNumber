<?php
/**
 * Sample URL:
 * https://computerjourney.com/st/oauth/?
 * client_id=sl33pn#mBer!nt3Gr8ti0n
 * &redirect_uri=https%3A%2F%2Fc2c-us.smartthings.com%2Foauth%2Fcallback
 * &response_type=code
 * &state=eyJhbGciOiJIUzM4NCJ9.NGM0NDI3MmEtZmYwOC00MmYyLWFhZjktZDc0ZTc2Y2E1MjhhOnZpcGVyX2I0N2E2OWMwLTYxYWEtMTFlZS1hOGViLWZmZjYyNDk4OTM3ODoxNjk2NTYwMjA2OTE2OjMzN2M1MDEwLWFlMWMtNGFiZC1hZTZhLTg4NzFmYzljZDRjZDplbi1VUzo6dHJ1ZQ.-GN2yWD1Ch8-iI1TasKpiqAUvJEwg4jJ1A3QoQDKbVOpzCMgaN9DR6bQnAt_Amy-
 * &scope=read%2Cwrite
 **/ 
// Load the settings
require_once('./settings.php');

// https://bshaffer.github.io/oauth2-server-php-docs/
// Instructions for the server.php and token.php files, etc. 
// https://bshaffer.github.io/oauth2-server-php-docs/cookbook/
$dsn = 'mysql:dbname=' . DB_NAME . ';host=localhost';
$username = DB_USER;
$password = DB_PASS;

// Autoloading (composer is preferred, but for this example let's just do this)
require_once('./oauth2-server-php/src/OAuth2/Autoloader.php');
OAuth2\Autoloader::register();

// $dsn is the Data Source Name for your database, for exmaple "mysql:dbname=my_oauth2_db;host=localhost"
$storage = new OAuth2\Storage\Pdo(array('dsn' => $dsn, 'username' => $username, 'password' => $password));

// Pass a storage object or array of storage objects to the OAuth2 server class
$server = new OAuth2\Server($storage, array(
    'allow_implicit' => true,
    'unset_refresh_token_after_use' => false,
    'always_issue_new_refresh_token' => false, // the refresh token grant request will have a "refresh_token" field, with a new refresh token on each request
    'refresh_token_lifetime' => 0, // the refresh tokens now last 28 days vs 1209600 (14-days) vs 2419200 (28 days)
));

// Add the "Client Credentials" grant type (it is the simplest of the grant types)
$server->addGrantType(new OAuth2\GrantType\ClientCredentials($storage));

// Add the "Authorization Code" grant type (this is where the oauth magic happens)
$server->addGrantType(new OAuth2\GrantType\AuthorizationCode($storage));
?>
