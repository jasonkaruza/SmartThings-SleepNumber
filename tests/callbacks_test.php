<?php
/**
 * Tests for the SmartThings callback token refresh and state callback cron flow in
 * sn.php, and log sanitization in oauth/loader.php.
 *
 * Run with: php tests/callbacks_test.php
 *
 * sn.php handles a request at the top level, so it can't be included here. Instead,
 * its constants, functions and classes are loaded on their own, with stubs in place
 * of the SleepNumber calls. SmartThings is replaced by tests/mock_smartthings.php
 * running on PHP's built-in server, and the database by an in-memory SQLite one.
 * Nothing here reads settings.php or touches a real database, SleepNumber or SmartThings.
 */

define('ROOT', dirname(__DIR__));
define('TEST_DIR', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'st-callbacks-test-' . getmypid());
@mkdir(TEST_DIR);

// Settings normally provided by oauth/settings.php
define('LOG_DIR', TEST_DIR);
define('LOG_PATH', TEST_DIR . DIRECTORY_SEPARATOR . 'requestlogs.txt');
define('ST_CLIENT_ID', 'test-st-client-id');
define('ST_CLIENT_SECRET', 'test-st-client-secret-0123456789abcdef');
define('SN_CLIENT_ID', 'test-sn-client-id');
define('SN_CLIENT_SECRET', 'test-sn-client-secret');
define('ENCRYPTION_PEPPER', '');

///////////////////////// LOADING ///////////////////////////

/**
 * Eval the top-level functions, classes and define() calls in a file without running
 * anything else in it
 * @param string $file The PHP file to load from
 * @param array $skip Names of functions/classes not to load (so they can be stubbed)
 * @param array|null $only If set, load only these functions/classes and no constants
 */
function loadDefinitions(string $file, array $skip = [], ?array $only = null): void
{
    $tokens = token_get_all(file_get_contents($file));
    $count = count($tokens);
    $depth = 0;
    for ($i = 0; $i < $count; $i++) {
        $token = $tokens[$i];
        $id = is_array($token) ? $token[0] : $token;
        if ($depth == 0 && ($id === T_FUNCTION || $id === T_CLASS)) {
            $name = nextName($tokens, $i);
            [$code, $i] = captureUntil($tokens, $i, true);
            if (!in_array($name, $skip) && ($only === null || in_array($name, $only))) {
                eval($code);
            }
            continue;
        }
        if ($depth == 0 && $only === null && $id === T_STRING && $token[1] === 'define') {
            [$code, $i] = captureUntil($tokens, $i, false);
            try {
                eval($code);
            } catch (Throwable $e) {
                // e.g. constants defined from SleepyqPHP, which isn't loaded
            }
            continue;
        }
        if ($id === '{' || $id === T_CURLY_OPEN || $id === T_DOLLAR_OPEN_CURLY_BRACES) {
            $depth++;
        } elseif ($id === '}') {
            $depth--;
        }
    }
}

function nextName(array $tokens, int $i): string
{
    while (!is_array($tokens[$i]) || $tokens[$i][0] !== T_STRING) {
        $i++;
    }
    return $tokens[$i][1];
}

/**
 * Collect source from $i until the end of a brace block, or until a ';' outside parentheses
 * @return array [source, index of the last token collected]
 */
function captureUntil(array $tokens, int $i, bool $block): array
{
    $code = '';
    $depth = 0;
    $count = count($tokens);
    for (; $i < $count; $i++) {
        $token = $tokens[$i];
        $id = is_array($token) ? $token[0] : $token;
        $code .= is_array($token) ? $token[1] : $token;
        if ($block) {
            if ($id === '{' || $id === T_CURLY_OPEN || $id === T_DOLLAR_OPEN_CURLY_BRACES) {
                $depth++;
            } elseif ($id === '}' && --$depth == 0) {
                break;
            }
        } else {
            if ($id === '(') {
                $depth++;
            } elseif ($id === ')') {
                $depth--;
            } elseif ($id === ';' && $depth == 0) {
                break;
            }
        }
    }
    return [$code, $i];
}

require_once ROOT . '/db.php';
loadDefinitions(ROOT . '/oauth/loader.php', only: ['logtext']);
loadDefinitions(ROOT . '/sn.php', skip: ['getBeds', 'getBedState', 'getClient', 'parseBedState']);

///////////////////////// STUBS ///////////////////////////

function getDb()
{
    global $DB;
    return $DB;
}

function getBeds($withFoundationFeatures = false, ?string $userId = null): array
{
    $bed = new stdClass();
    $bed->id = "bed-$userId";
    $bed->sides = ['left'];
    return [$bed];
}

function getBedState($bedIds = []): array
{
    return [];
}

function parseBedState($beds, &$output, $idsAndSides, $overrides = [], $bedSideComponentCapabilityFilters = [])
{
    $output[DEVICE_STATE] = [];
}

// Run as a child process to check httpError() outside of a cron run
if (in_array('--child-http-error', $argv)) {
    httpError(418, 'teapot');
    print 'after httpError';
    exit;
}

///////////////////////// FIXTURES ///////////////////////////

$pdo = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$pdo->exec('CREATE TABLE st_callback_code (id INTEGER PRIMARY KEY AUTOINCREMENT, access_token TEXT, code TEXT, user_id TEXT, token_uri TEXT, state_uri TEXT)');
$pdo->exec('CREATE TABLE st_callback_token (id INTEGER PRIMARY KEY AUTOINCREMENT, access_token TEXT, refresh_token TEXT, st_callback_code_id INTEGER, expires_in INTEGER, expires_at TEXT)');
$DB = (new ReflectionClass(Database::class))->newInstanceWithoutConstructor();
(new ReflectionProperty(Database::class, 'pdo'))->setValue($DB, $pdo);

// Start the mock SmartThings server on a free port
$probe = stream_socket_server('tcp://127.0.0.1:0');
$port = (int) substr(strrchr(stream_socket_get_name($probe, false), ':'), 1);
fclose($probe);
define('MOCK_URL', "http://127.0.0.1:$port");
define('MOCK_LOG', TEST_DIR . DIRECTORY_SEPARATOR . 'mock.jsonl');
$devNull = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
$mock = proc_open(
    [PHP_BINARY, '-S', "127.0.0.1:$port", __DIR__ . '/mock_smartthings.php'],
    [1 => ['file', $devNull, 'w'], 2 => ['file', $devNull, 'w']],
    $pipes,
    null,
    getenv() + ['MOCK_LOG' => MOCK_LOG]
);
register_shutdown_function(function () use ($mock) {
    proc_terminate($mock);
    array_map('unlink', glob(TEST_DIR . DIRECTORY_SEPARATOR . '*'));
    @rmdir(TEST_DIR);
    // Code under test that calls exit (like httpError) would otherwise end the run looking successful
    if (!defined('TESTS_COMPLETED')) {
        print "\nFAIL: the test run ended early\n";
        exit(1);
    }
});
for ($tries = 0; !@fsockopen('127.0.0.1', $port) && $tries < 50; $tries++) {
    usleep(100000);
}

// The cron is the context these tests exercise
define('IS_CRON', true);

/**
 * Add a user with a callback code and a token that expires at $expiresAt
 * @return int The ST_CALLBACK_CODE row ID
 */
function addUser(string $userId, string $accessToken, string $refreshToken, string $expiresAt): int
{
    $codeId = insertCallbackCode("partner-token-$userId", "code-$userId", $userId, MOCK_URL . '/oauth/token', MOCK_URL . '/device/events');
    getDb()->insert(ST_CALLBACK_TOKEN, [
        ACCESS_TOKEN => $accessToken,
        REFRESH_TOKEN => $refreshToken,
        ST_CALLBACK_CODE_ID => $codeId,
        EXPIRES_IN => 86400,
        EXPIRES_AT => $expiresAt,
    ]);
    return $codeId;
}

/**
 * @return array The requests the mock received, in order
 */
function mockRequests(): array
{
    $lines = file_exists(MOCK_LOG) ? file(MOCK_LOG, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
    return array_map(fn($line) => json_decode($line, true), $lines);
}

/**
 * @return int[] The number of callback codes for a user, and the number of tokens belonging to them
 */
function countCallbackRows(string $userId): array
{
    global $pdo;
    $codes = $pdo->query("SELECT COUNT(*) FROM st_callback_code WHERE user_id = " . $pdo->quote($userId))->fetchColumn();
    $tokens = $pdo->query("SELECT COUNT(*) FROM st_callback_token WHERE st_callback_code_id IN (SELECT id FROM st_callback_code WHERE user_id = " . $pdo->quote($userId) . ")")->fetchColumn();
    return [(int) $codes, (int) $tokens];
}

/**
 * @return int The number of tokens in the table, including any whose code was deleted
 */
function countAllTokens(): int
{
    global $pdo;
    return (int) $pdo->query('SELECT COUNT(*) FROM st_callback_token')->fetchColumn();
}

function logContents(): string
{
    return file_exists(LOG_PATH) ? file_get_contents(LOG_PATH) : '';
}

///////////////////////// RUNNER ///////////////////////////

$failures = 0;

function check(bool $condition, string $message): void
{
    global $failures;
    if (!$condition) {
        $failures++;
        print "    FAIL: $message\n";
    }
}

function test(string $name, callable $fn): void
{
    global $failures, $pdo;
    $pdo->exec('DELETE FROM st_callback_code');
    $pdo->exec('DELETE FROM st_callback_token');
    @unlink(MOCK_LOG);
    @unlink(LOG_PATH);
    $before = $failures;
    try {
        $fn();
    } catch (Throwable $e) {
        check(false, 'Unexpected ' . get_class($e) . ': ' . $e->getMessage());
    }
    print ($failures == $before ? 'PASS' : 'FAIL') . " $name\n";
}

$past = date('Y-m-d H:i:s', time() - 3600);
$future = date('Y-m-d H:i:s', time() + 3600);

///////////////////////// TESTS ///////////////////////////

test('expired token is refreshed with a refresh_token grant', function () use ($past) {
    $codeId = addUser('alice', 'expired-access', 'good-refresh', $past);
    performStateCallbacks(['alice']);

    $requests = mockRequests();
    check(count($requests) == 2, 'Expected a token request and a state callback, got ' . count($requests));
    $auth = $requests[0]['body'][CALLBACK_AUTHENTICATION] ?? [];
    check(($requests[0]['path'] ?? null) == '/oauth/token', 'First request should be to /oauth/token');
    check(($requests[0]['body'][HEADERS][INTERACTION_TYPE] ?? null) == 'refreshAccessTokens', 'interactionType should be refreshAccessTokens');
    check(($auth[GRANT_TYPE] ?? null) == 'refresh_token', 'grantType should be refresh_token');
    check(($auth[REFRESHTOKEN] ?? null) == 'good-refresh', 'refreshToken should be the stored refresh token');
    check(!array_key_exists(CODE, $auth), 'A refresh should not send a code');
    check(($auth[CLIENT_ID] ?? null) == ST_CLIENT_ID && ($auth[CLIENT_SECRET] ?? null) == ST_CLIENT_SECRET, 'Client credentials should be sent');

    check(($requests[1]['path'] ?? null) == '/device/events', 'Second request should be the state callback');
    check(($requests[1]['body'][AUTHENTICATION][TOKEN] ?? null) == 'access-from-refresh', 'State callback should use the refreshed access token');

    $token = getTokenByCodeId($codeId);
    check($token[ACCESS_TOKEN] == 'access-from-refresh', 'Refreshed access token should be stored');
    check($token[REFRESH_TOKEN] == 'good-refresh', 'Refresh token should be kept when the response omits it');
    check(new DateTime($token[EXPIRES_AT]) > new DateTime(), 'Refreshed token should expire in the future');
});

test('code exchange still uses an authorization_code grant', function () {
    $codeId = insertCallbackCode('partner-token-erin', 'good-code', 'erin', MOCK_URL . '/oauth/token', MOCK_URL . '/device/events');
    // As grantCallbackAccess calls it, then with the code looked up by ID
    $passed = makeAccessTokenRequest(MOCK_URL . '/oauth/token', $codeId, 'good-code');
    $lookedUp = makeAccessTokenRequest(MOCK_URL . '/oauth/token', $codeId);

    check($passed == 'access-from-code' && $lookedUp == 'access-from-code', 'Both calls should return the access token');
    foreach (mockRequests() as $request) {
        $auth = $request['body'][CALLBACK_AUTHENTICATION] ?? [];
        check(($request['body'][HEADERS][INTERACTION_TYPE] ?? null) == ACCESS_TOKEN_REQUEST, 'interactionType should be accessTokenRequest');
        check(($auth[GRANT_TYPE] ?? null) == AUTHORIZATION_CODE && ($auth[CODE] ?? null) == 'good-code', 'Should send the code as an authorization_code grant');
        check(!array_key_exists(REFRESHTOKEN, $auth), 'A code exchange should not send a refresh token');
    }
    check(getTokenByCodeId($codeId)[REFRESH_TOKEN] == 'refresh-from-code', 'Refresh token from the response should be stored');
});

test('a failed refresh for one user does not stop callbacks for the next', function () use ($past) {
    addUser('carol', 'expired-access', 'bad-refresh', $past);
    addUser('dave', 'expired-access', 'good-refresh', $past);
    performStateCallbacks(['carol', 'dave']);

    $requests = mockRequests();
    $paths = array_column($requests, 'path');
    check($paths == ['/oauth/token', '/oauth/token', '/device/events'], 'Expected two token requests then one state callback, got ' . json_encode($paths));
    check(($requests[0]['body'][CALLBACK_AUTHENTICATION][REFRESHTOKEN] ?? null) == 'bad-refresh', "carol's refresh should run first");
    check(($requests[2]['body'][AUTHENTICATION][TOKEN] ?? null) == 'access-from-refresh', "dave's state callback should use his refreshed token");
    check(str_contains(logContents(), 'State callback failed for userId carol: HttpErrorException 401'), "carol's failure should be logged");
    check(countCallbackRows('carol') == [1, 1], 'An error other than INVALID-TOKEN should not delete callback codes');
});

test("a revoked refresh token deletes that user's callback codes and tokens, and later runs skip them", function () use ($past, $future) {
    addUser('grace', 'old-access', 'old-refresh', $past);
    addUser('grace', 'older-access', 'older-refresh', $past);
    addUser('grace', 'expired-access', 'revoked-refresh', $past);
    addUser('heidi', 'still-valid', 'good-refresh', $future);
    performStateCallbacks(['grace', 'heidi']);

    $log = logContents();
    check(countCallbackRows('grace') == [0, 0], "All of grace's callback codes and tokens should be deleted, got " . json_encode(countCallbackRows('grace')));
    check(countAllTokens() == 1, 'No tokens should be left without a code');
    check(countCallbackRows('heidi') == [1, 1], "heidi's callback code and token should be kept");
    check(str_contains($log, '###Deleted 3 callback codes and 3 callback tokens for userId grace'), 'The deletion should be logged');
    check(str_contains($log, 'State callback failed for userId grace: HttpErrorException 401'), "grace's failure should still be logged");
    $paths = array_column(mockRequests(), 'path');
    check($paths == ['/oauth/token', '/device/events'], "grace's refresh then heidi's state callback should be sent, got " . json_encode($paths));

    // The next run makes no requests for grace
    @unlink(MOCK_LOG);
    performStateCallbacks(['grace', 'heidi']);
    $requests = mockRequests();
    check(count($requests) == 1 && ($requests[0]['body'][AUTHENTICATION][TOKEN] ?? null) == 'still-valid', 'Only heidi should get a state callback on the next run');
});

test('deleting callback codes keeps newer codes and other users', function () use ($future) {
    addUser('ivan', 'old-access', 'old-refresh', $future);
    $revokedCodeId = addUser('ivan', 'revoked-access', 'revoked-refresh', $future);
    // A new grantCallbackAccess that arrived after the revoked code
    $newCodeId = addUser('ivan', 'new-access', 'new-refresh', $future);
    addUser('judy', 'judy-access', 'judy-refresh', $future);
    deleteCallbackCodes('ivan', $revokedCodeId);

    check(countCallbackRows('ivan') == [1, 1] && getTokenByCodeId($newCodeId) !== false, 'Only the newer code and its token should remain for ivan');
    check(countCallbackRows('judy') == [1, 1], "Another user's code should be kept");
});

test('httpError still responds and exits outside a cron run', function () {
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' --child-http-error', $output, $exitCode);
    $output = implode("\n", $output);
    check($output == 'teapot', "Expected only the error content to be printed, got: $output");
    check($exitCode == 0, "Expected exit code 0, got $exitCode");
});

test('client secret and Authorization header are redacted from logs, other fields are kept', function () use ($past) {
    addUser('frank', 'expired-access', 'good-refresh', $past);
    performStateCallbacks(['frank']);
    logtext("SERVER:Array\n(\n    [HTTP_AUTHORIZATION] => Basic dGVzdDpzZWNyZXQ=\n    [password] => hunter2\n    [token] => partner-token-frank\n)\n");

    $log = logContents();
    check(str_contains($log, 'Making POST request to ' . MOCK_URL . '/oauth/token'), 'Token request should be logged');
    check(!str_contains($log, ST_CLIENT_SECRET), 'ST client secret should not be logged');
    check(!str_contains($log, 'dGVzdDpzZWNyZXQ=') && str_contains($log, '[HTTP_AUTHORIZATION] => ***'), 'The Authorization header should be redacted');
    check(!str_contains($log, 'hunter2'), 'Passwords should still be redacted');
    check(str_contains($log, '"refreshToken":"good-refresh"'), 'Refresh token should still be logged for triage');
    check(str_contains($log, 'access-from-refresh'), 'Access token should still be logged for triage');
    check(str_contains($log, '[token] => partner-token-frank'), 'Partner token should still be logged for triage');
});

define('TESTS_COMPLETED', true);
print $failures ? "\n$failures check(s) failed\n" : "\nAll tests passed\n";
exit($failures ? 1 : 0);
