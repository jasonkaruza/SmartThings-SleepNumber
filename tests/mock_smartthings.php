<?php
/**
 * Mock of the SmartThings Schema callback endpoints, for use with PHP's built-in server:
 *   php -S 127.0.0.1:<port> tests/mock_smartthings.php
 * Every request body is appended as a JSON line to the file in the MOCK_LOG env var.
 *
 * /oauth/token
 *   - authorization_code with code "good-code": 200 with access and refresh tokens
 *   - refresh_token with refreshToken "good-refresh": 200 with an access token only
 *     (the refresh token is static, so the response doesn't repeat it)
 *   - refresh_token with refreshToken "revoked-refresh": 401 INVALID-TOKEN, as production
 *     returns when the integration has been removed
 *   - anything else: 401 INVALID-CODE, as production returns for a bad code
 * /device/events
 *   - 200 with an empty body, as production returns
 */
$body = file_get_contents('php://input');
$request = json_decode($body, true) ?: [];
file_put_contents(getenv('MOCK_LOG'), json_encode([
    'path' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
    'body' => $request,
]) . "\n", FILE_APPEND);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$auth = $request['callbackAuthentication'] ?? [];
$grantType = $auth['grantType'] ?? null;

if ($path == '/oauth/token') {
    $headers = [
        'schema' => 'st-schema',
        'version' => '1.0',
        'interactionType' => 'accessTokenResponse',
        'requestId' => $request['headers']['requestId'] ?? null,
    ];
    if ($grantType == 'authorization_code' && ($auth['code'] ?? null) == 'good-code') {
        print json_encode([
            'headers' => $headers,
            'callbackAuthentication' => [
                'tokenType' => 'Bearer',
                'accessToken' => 'access-from-code',
                'refreshToken' => 'refresh-from-code',
                'expiresIn' => 86400,
            ],
        ]);
    } elseif ($grantType == 'refresh_token' && ($auth['refreshToken'] ?? null) == 'good-refresh') {
        print json_encode([
            'headers' => $headers,
            'callbackAuthentication' => [
                'tokenType' => 'Bearer',
                'accessToken' => 'access-from-refresh',
                'expiresIn' => 86400,
            ],
        ]);
    } elseif ($grantType == 'refresh_token' && ($auth['refreshToken'] ?? null) == 'revoked-refresh') {
        http_response_code(401);
        print json_encode([
            'headers' => ['interactionType' => 'refreshAccessTokens'] + $headers,
            'globalError' => ['errorEnum' => 'INVALID-TOKEN', 'detail' => 'refresh token provided is revoked'],
        ]);
    } else {
        http_response_code(401);
        print json_encode([
            'headers' => $headers,
            'globalError' => ['errorEnum' => 'INVALID-CODE', 'detail' => 'the provided code is invalid'],
        ]);
    }
} elseif ($path == '/device/events') {
    http_response_code(200);
} else {
    http_response_code(404);
}
