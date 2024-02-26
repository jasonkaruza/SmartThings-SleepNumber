<?php
// include our OAuth2 Server object
require_once __DIR__.'/server.php';

// Add the "Refresh Token" grant type
// https://bshaffer.github.io/oauth2-server-php-docs/grant-types/refresh-token/
$server->addGrantType(new OAuth2\GrantType\RefreshToken($storage), array(
    'allow_implicit' => true,
    'unset_refresh_token_after_use' => false,
    'always_issue_new_refresh_token' => false, // the refresh token grant request will have a "refresh_token" field, with a new refresh token on each request
    'refresh_token_lifetime' => 0, // the refresh tokens now last 28 days vs 1209600 (14-days) vs 2419200 (28 days)
));

// Handle a request for an OAuth2.0 Access Token and send the response to the client
$request = OAuth2\Request::createFromGlobals();
logtext("Token request:\n" . print_r($request, true));
$response = $server->handleTokenRequest($request);
logtext("Token response:\n" . print_r($response, true));
$response->send();
?>
