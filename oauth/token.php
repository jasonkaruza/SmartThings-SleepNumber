<?php
// include our OAuth2 Server object
require_once __DIR__ . '/server.php';

// Add the "Refresh Token" grant type
// https://bshaffer.github.io/oauth2-server-php-docs/grant-types/refresh-token/
$server->addGrantType(new OAuth2\GrantType\RefreshToken($storage), array(
    'allow_implicit' => true,
    'unset_refresh_token_after_use' => false,
    'always_issue_new_refresh_token' => false, // the refresh token grant request will have a "refresh_token" field, with a new refresh token on each request
    'refresh_token_lifetime' => 0, // 0 means the refresh token never expires. It is stored as OAuth2\Storage\Pdo::NEVER_EXPIRES (2999-12-31 23:59:59), since a 0 expiry would otherwise be written as a 1969 date that MySQL rejects. Set to a seconds value to expire instead, e.g. 1209600 (14 days) or 2419200 (28 days).
));

// Handle a request for an OAuth2.0 Access Token and send the response to the client
$request = OAuth2\Request::createFromGlobals();
logtext("Token request:\n" . print_r($request, true));
$response = $server->handleTokenRequest($request);
logtext("Token response:\n" . print_r($response, true));
$response->send();
