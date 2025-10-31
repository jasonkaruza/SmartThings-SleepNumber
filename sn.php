<?php
// sn2.php
require_once "./oauth/loader.php";
require_once "./SleepyqPHP/sleepyq.php";
require_once "./helpers.php";

$headers = getallheaders();
$authentication = $requestId = null;

// Accessors within response payloads
define('ACCESS_TOKEN_REQUEST', 'accessTokenRequest');
define('ACCESSTOKEN', 'accessToken');
define('AUTHENTICATION', 'authentication');
define('AUTHORIZATION_CODE', 'authorization_code');
define('AUTHENTICATION_TOKEN', 'token');
define('CALLBACK_ACCESS', 'callback_access');
define('CALLBACK_AUTHENTICATION', 'callbackAuthentication');
define('CALLBACK_URLS', 'callbackUrls');
define('CLIENT_ID', 'clientId');
define('CLIENT_SECRET', 'clientSecret');
define('CODE', 'code');
define('COMMAND_REQUEST', 'commandRequest');
define('COMMAND_RESPONSE', 'commandResponse');
define('DEVICES', 'devices');
define('DEVICE_STATE', 'deviceState');
define('DISCOVERY_REQUEST', 'discoveryRequest');
define('DISCOVERY_RESPONSE', 'discoveryResponse');
define('EXTERNAL_DEVICE_ID', 'externalDeviceId');
define('GRANT_CALLBACK_ACCESS', 'grantCallbackAccess');
define('GRANT_TYPE', 'grantType');
define('HEADERS', 'headers');
define('INTEGRATION_DELETED', 'integrationDeleted');
define('INTERACTION_RESULT', 'interactionResult');
define('INTERACTION_TYPE', 'interactionType');
define('OAUTH_TOKEN', 'oauthToken');
define('REQUEST_ID', 'requestId');
define('STATE_CALLBACK', 'stateCallback');
define('STATE_REFRESH_REQUEST', 'stateRefreshRequest');
define('STATE_REFRESH_RESPONSE', 'stateRefreshResponse');
define('TOKEN', 'token');
define('TOKEN_TYPE', 'tokenType');

// SmartThings switch values
define('SWITCH_ON', 'on');
define('SWITCH_OFF', 'off');

// Bed Preset values (0 can also be returned, which means not in a preset state)
define('FAVORITE', SleepyqPHP::FAVORITE);
define('READ', SleepyqPHP::READ);
define('WATCH_TV', SleepyqPHP::WATCH_TV);
define('FLAT', SleepyqPHP::FLAT);
define('ZERO_G', SleepyqPHP::ZERO_G);
define('SNORE', SleepyqPHP::SNORE);
$BED_PRESETS = [
    FAVORITE => 'Favorite',
    READ => 'Read',
    WATCH_TV => 'Watch TV',
    FLAT => 'Flat',
    ZERO_G => 'Zero G',
    SNORE => 'Snore',
];
$BED_PRESETS_MAP = array_flip($BED_PRESETS);
// We set this in case 0 is provided from the API
// (which means not currently in a defined preset position)
define('DEFAULT_PRESET', FAVORITE);

define('BED_COMMAND', 'command');
define('BED_FAVORITES', 'favorites');
define('BED_ID', 'bed_id');
define('BED_IDS', 'bed_ids');
define('BEDS', 'beds');
define('BED_PASSWORD', 'password');
define('BED_RESET', 'reset');
define('BED_STATE', 'state');
define('BED_USER', 'user');

// Base foot-warming
define('FOOTWARM_AVAILABLE', 'present'); // For SmartThings presenceSensor
define('FOOTWARM_NOT_AVAILABLE', 'not present'); // For SmartThings presenceSensor
define('FOOTWARM_MODE_DELIM', ' - ');

// Bed presence
define('IN_BED_PRESENT', 'present');
define('IN_BED_NOT_PRESENT', 'not present');

// Footwarming temperature values
define('FOOTWARM_TEMP_OFF', 'Off');
define('FOOTWARM_TEMP_LOW', 'Low');
define('FOOTWARM_TEMP_MEDIUM', 'Medium');
define('FOOTWARM_TEMP_HIGH', 'High');
$FOOTWARM_TEMPS = [
    SleepyqPHP::FOOTWARM_OFF => FOOTWARM_TEMP_OFF,
    SleepyqPHP::FOOTWARM_LOW => FOOTWARM_TEMP_LOW,
    SleepyqPHP::FOOTWARM_MEDIUM => FOOTWARM_TEMP_MEDIUM,
    SleepyqPHP::FOOTWARM_HIGH => FOOTWARM_TEMP_HIGH,
];
$FOOTWARM_TEMPS_MAP = array_flip($FOOTWARM_TEMPS);

// Footwarming duration values
define('FOOTWARM_TIME_30_MIN', '30 min');
define('FOOTWARM_TIME_1_HR', '1 hr');
define('FOOTWARM_TIME_2_HR', '2 hrs');
define('FOOTWARM_TIME_3_HR', '3 hrs');
define('FOOTWARM_TIME_4_HR', '4 hrs');
define('FOOTWARM_TIME_5_HR', '5 hrs');
define('FOOTWARM_TIME_6_HR', '6 hrs');
$FOOTWARM_TIMES = [
    SleepyqPHP::FOOTWARM_30 => FOOTWARM_TIME_30_MIN,
    SleepyqPHP::FOOTWARM_60 => FOOTWARM_TIME_1_HR,
    SleepyqPHP::FOOTWARM_120 => FOOTWARM_TIME_2_HR,
    SleepyqPHP::FOOTWARM_180 => FOOTWARM_TIME_3_HR,
    SleepyqPHP::FOOTWARM_240 => FOOTWARM_TIME_4_HR,
    SleepyqPHP::FOOTWARM_300 => FOOTWARM_TIME_5_HR,
    SleepyqPHP::FOOTWARM_360 => FOOTWARM_TIME_6_HR,
];
$FOOTWARM_TIMES_MAP = array_flip($FOOTWARM_TIMES);

// Create full list of combos
$FOOTWARM_MODES = [];
foreach ($FOOTWARM_TEMPS as $temp) {
    if ($temp == FOOTWARM_TEMP_OFF) {
        $FOOTWARM_MODES[] = FOOTWARM_TEMP_OFF;
        continue;
    }
    foreach ($FOOTWARM_TIMES as $time) {
        $FOOTWARM_MODES[] = $temp . FOOTWARM_MODE_DELIM . $time;
    }
}

// Underbed Lighting settings
define('UNDERBED_LIGHTING_AVAILABLE', 'present');
define('UNDERBED_LIGHTING_NOT_AVAILABLE', 'not present');
define('UNDERBED_LIGHTING_AUTO', 'Auto');
define('UNDERBED_LIGHTING_OFF', 'Off');
define('UNDERBED_LIGHTING_ON', 'On');
define('SLEEPYQPHP_LIGHTING_AUTO', -1); // SleepyqPHP uses -1 for Auto
$UNDERBED_LIGHTING_SETTINGS = [
    SleepyqPHP::LIGHT_SETTINGS_OFF => UNDERBED_LIGHTING_OFF,
    SleepyqPHP::LIGHT_SETTINGS_ON => UNDERBED_LIGHTING_ON,
    SLEEPYQPHP_LIGHTING_AUTO => UNDERBED_LIGHTING_AUTO, // This is a custom mapping
];
$UNDERBED_LIGHTING_SETTINGS_MAP = array_flip($UNDERBED_LIGHTING_SETTINGS);
define('DEFAULT_UNDERBED_LIGHTING_SETTING', SleepyqPHP::LIGHT_SETTINGS_OFF); // Default underbed lighting setting if not set

// Underbed Lighting brightness levels
define('UNDERBED_LIGHTING_BRIGHTNESS_OFF', 'Off');
define('UNDERBED_LIGHTING_BRIGHTNESS_LOW', 'Low');
define('UNDERBED_LIGHTING_BRIGHTNESS_MEDIUM', 'Medium');
define('UNDERBED_LIGHTING_BRIGHTNESS_HIGH', 'High');
$UNDERBED_LIGHTING_BRIGHTNESS = [
    SleepyqPHP::LIGHT_BRIGHTNESS_OFF => UNDERBED_LIGHTING_BRIGHTNESS_OFF,
    SleepyqPHP::LIGHT_BRIGHTNESS_LOW => UNDERBED_LIGHTING_BRIGHTNESS_LOW,
    SleepyqPHP::LIGHT_BRIGHTNESS_MEDIUM => UNDERBED_LIGHTING_BRIGHTNESS_MEDIUM,
    SleepyqPHP::LIGHT_BRIGHTNESS_HIGH => UNDERBED_LIGHTING_BRIGHTNESS_HIGH,
];
$UNDERBED_LIGHTING_BRIGHTNESS_MAP = array_flip($UNDERBED_LIGHTING_BRIGHTNESS);
define('DEFAULT_UNDERBED_LIGHTING_BRIGHTNESS', SleepyqPHP::LIGHT_BRIGHTNESS_OFF); // Default underbed lighting brightness if not set

// Underbed Lighting timer durations
define('UNDERBED_LIGHTING_TIME_OFF', 'Off');
define('UNDERBED_LIGHTING_TIME_15_MIN', '15 min');
define('UNDERBED_LIGHTING_TIME_30_MIN', '30 min');
define('UNDERBED_LIGHTING_TIME_45_MIN', '45 min');
define('UNDERBED_LIGHTING_TIME_1_HR', '1 hr');
define('UNDERBED_LIGHTING_TIME_2_HR', '2 hrs');
define('UNDERBED_LIGHTING_TIME_3_HR', '3 hrs');
define('SLEEPYQPHP_LIGHTING_TIME_OFF', 0); // SleepyqPHP uses -1 for Off
$UNDERBED_LIGHTING_TIMES = [
    SLEEPYQPHP_LIGHTING_TIME_OFF => UNDERBED_LIGHTING_TIME_OFF,
    SleepyqPHP::LIGHT_TIMER_15 => UNDERBED_LIGHTING_TIME_15_MIN,
    SleepyqPHP::LIGHT_TIMER_30 => UNDERBED_LIGHTING_TIME_30_MIN,
    SleepyqPHP::LIGHT_TIMER_45 => UNDERBED_LIGHTING_TIME_45_MIN,
    SleepyqPHP::LIGHT_TIMER_60 => UNDERBED_LIGHTING_TIME_1_HR,
    SleepyqPHP::LIGHT_TIMER_120 => UNDERBED_LIGHTING_TIME_2_HR,
    SleepyqPHP::LIGHT_TIMER_180 => UNDERBED_LIGHTING_TIME_3_HR,
];
$UNDERBED_LIGHTING_TIMES_MAP = array_flip($UNDERBED_LIGHTING_TIMES);
define('DEFAULT_UNDERBED_LIGHTING_TIME', SLEEPYQPHP_LIGHTING_TIME_OFF); // Default underbed lighting time if not set

// Others
define('DEVICE_ID_DELIM', ':');

// SmartThings Callback Tables and fields
define('ST_CALLBACK_CODE', 'st_callback_code');
define('ST_CALLBACK_TOKEN', 'st_callback_token');
define('ACCESS_TOKEN', 'access_token');
define('EXPIRES_AT', 'expires_at');
define('EXPIRES_IN', 'expires_in');
define('EXPIRESIN', 'expiresIn');
define('ID', 'id');
define('REFRESH_TOKEN', 'refresh_token');
define('REFRESHTOKEN', 'refreshToken');
define('SLEEP_END_TIME', 'sleep_end_time');
define('SLEEP_START_TIME', 'sleep_start_time');
define('STATE_URI', 'state_uri');
define('ST_CALLBACK_CODE_ID', 'st_callback_code_id');
define('ST_USER_SETTINGS', 'st_user_settings');
define('TIMEZONE', 'timezone');
define('TOKEN_URI', 'token_uri');
define('USER_ID', 'user_id');

// Various variables we will need to set
$object = null;
$devices = null;
$sleepyq = null;

// Make sure Content-Type is application/json 
$content_type = isset($headers['Content-Type']) ? $headers['Content-Type'] : '';
if (stripos($content_type, 'application/json') !== false) {
    // Read the input stream
    $body = file_get_contents("php://input");

    // Decode the JSON object
    $object = json_decode($body, true);

    // Throw an exception if decoding failed
    if (!is_array($object)) {
        logtext("Failed to decode JSON object: $body");
        httpError(400, json_encode(['error' => 'Received invalid JSON request.']));
    }

    // Extract out the headers and authentication from the request
    logtext("Got JSON request object:" . json_encode($object));
    if (!array_key_exists(HEADERS, $object) || !array_key_exists(AUTHENTICATION, $object)) {
        httpError(400, json_encode(['error' => 'Received invalid JSON request.']));
    }
    $headers = $object[HEADERS];
    $authentication = $object[AUTHENTICATION];
    if (!array_key_exists(REQUEST_ID, $headers)) {
        httpError(400, json_encode(['error' => 'Received invalid JSON request: request_id.']));
    }
    $requestId = $headers[REQUEST_ID];

    // If present, set the $devices variable
    if (array_key_exists(DEVICES, $object)) {
        $devices = $object[DEVICES];
    }

    logtext("Request ID: $requestId");
    logtext("New Headers: " . print_r($headers, true));
    logtext("Authentication: " . print_r($authentication, true));
}

// else not JSON
else {
    // If calling via CLI, it is for testing or cron purposes
    /**
     * Test command samples:
     * - php sn.php --itype=discoveryRequest --token=<oauth_access_token>
     * - php sn.php --itype=stateRefreshRequest --token=<oauth_access_token> --ids=<bed_id>:right
     * - php sn.php --itype=commandRequest --token=<oauth_access_token> --devices='[{"externalDeviceId":"e<bed_id>:left","deviceCookie":[],"commands":[{"component":"main","capability":"st.mode","command":"setAirConditionerMode","arguments":["Flat"]},{"component":"main","capability":"st.level","command":"setLevel","arguments":[80]}]},{"externalDeviceId":"<bed_id>:right","deviceCookie":[],"commands":[{"component":"main","capability":"st.mode","command":"setMode","arguments":["Flat"]},{"component":"main","capability":"st.level","command":"setLevel","arguments":[85]}]}]'
     * - php sn.php --itype=commandRequest --token=<oauth_access_token> --devices='[{"externalDeviceId":"<bed_id>:right","deviceCookie":{"updatedcookie":"12345"},"commands":[{"component":"main","capability":"st.switch","command":"on","arguments":[]}]}]'
     * - php sn.php --itype=commandRequest --token=<oauth_access_token> --devices='[{"externalDeviceId":"<bed_id>:right","deviceCookie":{"updatedcookie":"12345"},"commands":[{"component":"footwarming","capability":"st.airConditionerFanMode","command":"setFanMode","arguments":["Low - 30 min"]}]}]'
     * - php sn.php --itype=commandRequest --devices='[{"externalDeviceId":"<bed_id>:right","deviceCookie":{"updatedcookie":"12345"},"commands":[{"component":"footwarming","capability":"st.airConditionerFanMode","command":"setFanMode","arguments":["Off"]}]}]'
     * - php sn.php --token=<token> --itype=grantCallbackAccess --callbackAuthentication='{"grantType":"authorization_code","scope":"callback-access","code":"<longstring>","clientId":"<something>"}' --callbackUrls='{"oauthToken":"https:\/\/c2c-us.smartthings.com\/oauth\/token","stateCallback":"https:\/\/c2c-us.smartthings.com\/device\/events"}'
     * - php sn.php --itype=stateCallback --iscron=true
     * - php sn.php --itype=stateCallback --iscron=true --userids=<user_id_1>,<user_id_2>
     */
    if (php_sapi_name() == 'cgi-fcgi' || php_sapi_name() == 'cli') {
        $shortopts = '';
        $longopts = array(
            "itype:",     // Required value
            "ids::",    // Optional value
            "devices::", // Optional value
            "token::", // Optional value
            "callbackAuthentication::",    // Optional value
            "callbackUrls::",    // Optional value
            "iscron::", // Optional value
            "userids::", // Optional value
        );
        $options = getopt($shortopts, $longopts);
        if (!$options['itype']) {
            exit;
        }
        $iType = trim($options['itype']);

        // If users are specified, we will only update those users
        $userIds = null;
        if (array_key_exists('userids', $options)) {
            $userIds = explode(',', $options['userids']);
        }

        // If a cron job
        if (array_key_exists('iscron', $options)) {
            $cronStart = date('Y-m-d H:i:s');
            logtext("###$cronStart CRON JOB STARTED FOR ITYPE $iType");
            switch ($iType) {
                // We want to facilitate a callback to SmartThings with state
                // updates https://developer.smartthings.com/docs/devices/cloud-connected/interaction-types#reciprocal-access-token
                case STATE_CALLBACK:
                    // If no user IDs specified, we will get users that have callbacks enabled by virtue of sleep start/end time not being null
                    if (!$userIds) {
                        $usersSleepInfo = getUsersWithSleepSettingsEnabled();
                        $usersSleepInfo = assocByKey($usersSleepInfo, USER_ID);
                        filterUsersBySleepStartEndTime($usersSleepInfo);
                        $userIds = array_keys($usersSleepInfo);
                        logtext("Remaining users to perform callbacks on: " . implode(',', $userIds));
                    }
                    // If users to perform callbacks on
                    if ($userIds) {
                        performStateCallbacks($userIds);
                    }
                    break;
            }
            logtext("###$cronStart CRON JOB ENDED AT " . date('Y-m-d H:i:s') . " FOR ITYPE $iType");
            exit;
        }


        $headers = [
            "schema" => "st-schema",
            "version" => "1.0",
            "interactionType" => $options['itype'],
            "requestId" => uuidv4(),
        ];
        $authentication = [
            TOKEN_TYPE => "Bearer",
            TOKEN => is_null($options[TOKEN]) ? "token received during oauth from partner" : $options[TOKEN],
        ];
        if (array_key_exists('ids', $options)) {
            $eids = explode(',', $options['ids']);
            $devices = [];
            foreach ($eids as $eid) {
                $devices[] = [
                    EXTERNAL_DEVICE_ID => $eid,
                ];
            }
        }
        if (array_key_exists(DEVICES, $options)) {
            $devices = json_decode($options[DEVICES], true);
        }
        if (array_key_exists(CALLBACK_AUTHENTICATION, $options)) {
            $object[CALLBACK_AUTHENTICATION] = json_decode($options[CALLBACK_AUTHENTICATION], true);
        }
        if (array_key_exists(CALLBACK_URLS, $options)) {
            $object[CALLBACK_URLS] = json_decode($options[CALLBACK_URLS], true);
        }
    }
    // Else, exit
    else {
        exit;
    }
}

// https://developer.smartthings.com/docs/devices/cloud-connected/interaction-types
/* Sample request
    {
      "headers": {
        "schema": "st-schema",
        "version": "1.0",
        "interactionType": "discoveryRequest",
        "requestId": "abc-123-456"
      },
      "authentication": {
        "tokenType": "Bearer",
        "token": "token received during oauth from partner"
      }
    }
*/

$response = null;

// Handle the interactionType
if (array_key_exists(INTERACTION_TYPE, $headers)) {
    $interactionType = $headers[INTERACTION_TYPE];
    logtext("Got a $interactionType request!");
    switch ($interactionType) {
        case COMMAND_REQUEST:
            $response = commandRequest($requestId, $authentication, $devices);
            break;
        case DISCOVERY_REQUEST:
            $response = discoveryRequest($requestId, $authentication);
            break;
        case GRANT_CALLBACK_ACCESS:
            $response = grantCallbackAccess($authentication, $object);
            break;
        case INTEGRATION_DELETED:
            $response = integrationDeleted($requestId, $authentication);
            break;
        case INTERACTION_RESULT:
            $response = interactionResult($requestId, $authentication);
            break;
        case STATE_REFRESH_REQUEST:
            $response = stateRefreshRequest($requestId, $authentication, $devices);
            break;
        default:
            logtext("Got unexpected interactionType:$interactionType");
            httpError(400, json_encode(['error' => 'Invalid interactionType provided.']));
    }
} else {
    logtext("No interactionType in headers:" . print_r($headers, true));
    httpError(400, json_encode(['error' => 'No interactionType provided.']));
}

/// Encode the response as JSON
$responseJson = json_encode($response);
// Send the JSON response back to SmartThings
header('Content-Type: application/json');
print $responseJson;
logtext("$requestId $interactionType RESPONSE: " . json_encode($response, JSON_PRETTY_PRINT));

/////////// HANDLERS //////////////
/**
 * https://developer.smartthings.com/docs/devices/cloud-connected/interaction-types#discovery
 * Discovery is the first SmartThings request. Handle this request by retrieving a list of devices.
 * (
 *     [headers] => Array
 *        (
 *            [schema] => st-schema
 *            [version] => 1.0
 *            [interactionType] => discoveryRequest
 *            [requestId] => <reqId>
 *        )
 *     [authentication] => Array
 *        (
 *            [tokenType] => Bearer
 *            [token] => <token>
 *        )
 * )
 */
function discoveryRequest(string $reqId = null, array $auth)
{
    $beds = getBeds(false);

    $outDevices = [];
    foreach ($beds as $bed) {
        foreach ($bed->sides as $side) {
            $deviceId = $bed->id . DEVICE_ID_DELIM . $side;
            $outDevices[] = [
                EXTERNAL_DEVICE_ID => $deviceId,
                "deviceCookie" => ["updatedcookie" => "12345"],
                "friendlyName" => $bed->name . " $side",
                "manufacturerInfo" => [
                    "manufacturerName" => "SleepNumber",
                    "modelName" => $bed->model,
                    "hwVersion" => $bed->size,
                    // 	  "swVersion" => "1.0.0"
                ],
                //   "deviceContext"  => [
                // 	  "roomName" => "Master Bedroom",
                // 	  "groups" => ["Kitchen Lights", "House Bulbs"],
                // 	  "categories" => ["light", "switch"]
                //   ],
                "deviceHandlerType" => isOrGetTestDevice($deviceId) ?: DEVICE_PROFILE_ID,
                "deviceUniqueId" => $deviceId
            ];
        }
    }

    return [
        "headers" => [
            "schema" => "st-schema",
            "version" => "1.0",
            INTERACTION_TYPE => DISCOVERY_RESPONSE,
            REQUEST_ID => $reqId,
        ],
        "requestGrantCallbackAccess" => true,
        DEVICES => $outDevices
    ];
} // End function discoveryRequest


/**
 * https://developer.smartthings.com/docs/devices/cloud-connected/interaction-types#callback
 * When SmartThings receives an access token (obtained in an OAuth integration) from a third party, 
 * it sends a callback authentication code as seen below. The third party can use this code to 
 * request callback access tokens.
 * (
 *     [headers] => Array
 *         (
 *             [schema] => st-schema
 *             [version] => 1.0
 *             [interactionType] => grantCallbackAccess
 *             [requestId] => <reqId>
 *         )
 *     [authentication] => Array
 *         (
 *             [tokenType] => Bearer
 *             [token] => <token>
 *         )
 *     [callbackAuthentication] => Array
 *         (
 *             [grantType] => authorization_code
 *             [scope] => callback-access
 *             [code] => <codeString>
 *             [clientId] => <clientId>
 *         )
 *     [callbackUrls] => Array
 *         (
 *             [oauthToken] => https://c2c-us.smartthings.com/oauth/token
 *             [stateCallback] => https://c2c-us.smartthings.com/device/events
 *         )
 * )
 * Use an HTTPS POST call to the above oauthToken URL to request a callback access token.
 * A third party uses the callback access token to call into the SmartThings Cloud.
 */
function grantCallbackAccess(array $auth, array $requestObject)
{
    if (array_key_exists(CALLBACK_AUTHENTICATION, $requestObject)) {
        $callbackAuth = $requestObject[CALLBACK_AUTHENTICATION];
        if (array_key_exists(GRANT_TYPE, $callbackAuth) && $callbackAuth[GRANT_TYPE] == AUTHORIZATION_CODE && array_key_exists(CALLBACK_URLS, $requestObject)) {
            $code = $callbackAuth[CODE]; // Callback code to be stored
            $oauthTokenUri = $requestObject[CALLBACK_URLS][OAUTH_TOKEN];
            $stateCallbackUri = $requestObject[CALLBACK_URLS][STATE_CALLBACK];
            $token = $auth[AUTHENTICATION_TOKEN];
            require_once __DIR__ . '/oauth/server.php';
            $at = $server->getStorage(STORAGE_NAME)->getAccessToken($token);
            $userId = $at['user_id'];

            // Insert the code
            $codeId = insertCallbackCode($token, $code, $userId, $oauthTokenUri, $stateCallbackUri);

            // Use the code to request a callback access token
            $response = makeAccessTokenRequest($oauthTokenUri, $codeId, $code);
        }
    }
    return [];
} // End function grantCallbackAccess


/**
 * https://developer.smartthings.com/docs/devices/cloud-connected/interaction-types#interaction-result
 * This interaction notifies a third-party when a connected service is deleted.
 * {
 *   "headers": {
 *     "schema": "st-schema",
 *     "version": "1.0",
 *     "interactionType": "integrationDeleted",
 *     "requestId": "abc-123-456"
 *   },
 *   "authentication": {
 *     "tokenType": "Bearer",
 *     "token": "token received during oauth from partner"
 *   }
 * }
 */
function integrationDeleted($requestId, $authentication)
{
    return [];
} // End function integrationDeleted


/**
 * https://developer.smartthings.com/docs/devices/cloud-connected/interaction-types#interaction-result
 * An interaction result is a notification to a third-party partner of where issues were found in the response on a request from SmartThings Schema.
 *  authentication: is not provided for users/interactions that SmartThings Schema has not been able to obtain valid access tokens from the partner.
 *  originatingInteractionType: the interaction type causing the interactionResult to be sent.
 *  globalError: only sent if there was a major issue with the originatingInteractionType message.
 *  deviceState: only sent for and when there are issues with individual devices (devices with no issues will not be included).
 * (
 *     [headers] => Array
 *         (
 *             [schema] => st-schema
 *             [version] => 1.0
 *            [interactionType] => interactionResult
 *             [requestId] => <reqId>
 *         )
 *     [authentication] => Array
 *         (
 *             [tokenType] => Bearer
 *             [token] => <token>
 *         )
 *     [deviceState] => Array
 *         (
 *             [0] => Array
 *                 (
 *                     [deviceError] => Array
 *                         (
 *                             [0] => Array
 *                                 (
 *                                     [errorEnum] => BAD-RESPONSE
 *                                     [detail] => Incorrect interaction type, Expected - discoveryResponse,discoveryCallback
 *                                 )
 *                         )
 *                 )
 *         )
 *     [originatingInteractionType] => discoveryRequest
 * )
 */
function interactionResult($reqId, $auth)
{
    return [];
} // End function interactionResult


/**
 * https://developer.smartthings.com/docs/devices/cloud-connected/interaction-types#state-refresh
 * Handle the State Refresh request by retrieving the device states for the indicated list of devices.
 * (
 *     [headers] => Array
 *         (
 *             [schema] => st-schema
 *             [version] => 1.0
 *             [interactionType] => stateRefreshRequest
 *             [requestId] => <reqId>
 *         )
 * 
 *     [authentication] => Array
 *         (
 *             [tokenType] => Bearer
 *             [token] => <token>
 *         )
 * 
 *     [devices] => Array
 *         (
 *             [0] => Array
 *                 (
 *                     [externalDeviceId] => sleep-number-1
 *                     [deviceCookie] => Array
 *                         (
 *                             [updatedcookie] => 12345
 *                         )
 * 
 *                 )
 *         )
 *  )
 */
function stateRefreshRequest($reqId, $auth, $devices)
{
    $idsAndSides = extractExternalDeviceIds($devices);
    $ids = array_keys($idsAndSides);
    $beds = getBedState($ids);

    $output = [
        "headers" => [
            "schema" => "st-schema",
            "version" => "1.0",
            "interactionType" => STATE_REFRESH_RESPONSE,
            "requestId" => $reqId
        ]
    ];
    parseBedState($beds, $output, $idsAndSides);
    return $output;
} // End function stateRefreshRequest


/**
 * https://developer.smartthings.com/docs/devices/cloud-connected/interaction-types#command
 * Handle the Command request by triggering the commands for the list of devices.
 *  "devices": [
 *    {
 *     "externalDeviceId": "partner-device-id-1",
 *      "deviceCookie": {
 *      "lastcookie": "cookie value"
 *    },
 *     "commands": [
 *       {
 *         "component": "main",
 *         "capability": "st.airConditionerMode",
 *         "command": "setAirConditionerMode",
 *         "arguments": [
 *           {
 *             "<mode_str>"
 *           }
 *         ]
 *       },
 *       {
 *         "component": "main",
 *         "capability": "st.switchLevel",
 *         "command": "setLevel",
 *         "arguments": [<level_num>]
 *       },
 *       {
 *         "component": "main",
 *         "capability": "st.switch",
 *         "command": "on",
 *         "arguments": []
 *       }
 *     ]
 *   }
 * ]
 * }
 */
function commandRequest($reqId, $auth, $devices)
{
    $output = [
        "headers" => [
            "schema" => "st-schema",
            "version" => "1.0",
            "interactionType" => COMMAND_RESPONSE,
            "requestId" => $reqId
        ]
    ];

    $snCommands = [];
    $overrides = [];

    $idsAndSides = extractExternalDeviceIds($devices);
    $ids = array_keys($idsAndSides);

    // We moved this up before sendBedCommands() to get the current state of bed
    // lights because the API call to set the lights requires on/off + 
    $beds = getBedState($ids);

    // This will be used to shortcut overriding the sleep number if we got a
    // command to set a side to the Favorite position and number. This is due
    // to the API response not returning the target number in a timely manner
    // relative to the getting of the bed state after sending the command.
    $devicesSetToFave = [];
    $bedSideComponentCapabilityFilters = [];

    // Iterate through each device
    foreach ($devices as $device) {
        $commands = $device['commands'];
        // Extract the bed ID and side
        list($bedId, $side) = explode(DEVICE_ID_DELIM, $device[EXTERNAL_DEVICE_ID]);

        // Extract out lighting settings to use or override if in the commands
        $lightingSettings = [
            'lightingAvailable' => $beds[$bedId]['sides'][$side]['lightingAvailable'],
            'lightingSetting' => $beds[$bedId]['sides'][$side]['lightingSetting'],
            'lightingBrightness' => $beds[$bedId]['sides'][$side]['lightingBrightness'],
            'lightingTimer' => $beds[$bedId]['sides'][$side]['lightingTimer'],
        ];

        // Iterate through each command and extract the action
        foreach ($commands as $command) {
            $bedSideComponentCapabilityFilters[$bedId][$side][$command['component']][$command['capability']] = true;
            // For main and footwarming components
            if (in_array($command['component'], ['main', 'footwarming'])) {
                switch ($command['command']) {
                    case 'setLevel':
                        $level = array_values($command['arguments'])[0];
                        $snCommands[] = [
                            "id" => $bedId,
                            "side" => $side,
                            "number" => $level,
                            "component" => $command['component'],
                        ];
                        $overrides[$bedId][$side]['number'] = $level;
                        break;

                    case 'setAirConditionerMode':
                        $mode = array_values($command['arguments'])[0];
                        $intMode = mapModeToBedPreset($mode);
                        $snCommands[] = [
                            "id" => $bedId,
                            "side" => $side,
                            "mode" => $intMode,
                            "component" => $command['component'],
                        ];
                        $overrides[$bedId][$side]['mode'] = $mode;
                        break;

                    case 'on':
                        $snCommands[] = [
                            "id" => $bedId,
                            "side" => $side,
                            "fave" => "on",
                            "component" => $command['component'],
                        ];
                        $overrides[$bedId][$side]['fave'] = 'on';

                        // This will let us know to set a sleep number override
                        // further down after getting the bed state
                        $devicesSetToFave[$bedId][$side] = $side;
                        break;

                    case 'setFanMode':
                        $rawmode = $mode = array_values($command['arguments'])[0];
                        // Check to see if the value contains the delimiter. If not,
                        // it's probably "Off" and we want to just add on a duration
                        // that will be ignored, but will allow the splitting to work
                        // consistently.
                        if (!str_contains($mode, FOOTWARM_MODE_DELIM)) {
                            $mode .= FOOTWARM_MODE_DELIM . FOOTWARM_TIME_30_MIN;
                        }
                        list($temp, $time) = explode(FOOTWARM_MODE_DELIM, $mode);

                        $snCommands[] = [
                            "id" => $bedId,
                            "side" => $side,
                            "temp" => mapFootWarmingTempToNumber($temp),
                            "time" => mapFootWarmingTimeToNumber($time),
                            "component" => $command['component'],
                        ];
                        $overrides[$bedId][$side]['footwarmingMode'] = $rawmode;
                        $overrides[$bedId][$side]['footwarmingAvailable'] = FOOTWARM_AVAILABLE;
                        break;
                }
            } // End if main or footwarming component

            // For underbed lights component
            else if ($command['component'] == 'lights') {
                if (!array_key_exists("lights$bedId", $snCommands)) {
                    // If the lights command is not already set, we will set it
                    $snCommands["lights$bedId"] = [
                        "id" => $bedId,
                        "side" => $side,
                        "lightingAvailable" => $lightingSettings['lightingAvailable'],
                        "lightingSetting" => $lightingSettings['lightingSetting'],
                        "lightingBrightness" => $lightingSettings['lightingBrightness'],
                        "lightingTimer" => $lightingSettings['lightingTimer'],
                        "component" => $command['component'],
                    ];
                }
                switch ($command['command']) {
                    // Setting
                    case 'setMode':
                        $setting = array_values($command['arguments'])[0];

                        $snCommands["lights$bedId"]['lightingSetting'] = mapLightSettingToNumber($setting);
                        $overrides[$bedId][$side]['lightingSetting'] = $setting;
                        $overrides[$bedId][$side]['lightingAvailable'] = UNDERBED_LIGHTING_AVAILABLE;
                        break;

                    // Brightness
                    case 'setSpinSpeed':
                        $brightness = array_values($command['arguments'])[0];

                        $snCommands["lights$bedId"]['lightingBrightness'] = mapLightBrightnessToNumber($brightness);
                        $overrides[$bedId][$side]['lightingBrightness'] = $brightness;
                        $overrides[$bedId][$side]['lightingAvailable'] = UNDERBED_LIGHTING_AVAILABLE;
                        break;

                    // Timer
                    case 'setFanMode':
                        $timer = array_values($command['arguments'])[0];

                        $snCommands["lights$bedId"]['lightingTimer'] = mapLightTimerToNumber($timer);
                        $overrides[$bedId][$side]['lightingTimer'] = $timer;
                        $overrides[$bedId][$side]['lightingAvailable'] = UNDERBED_LIGHTING_AVAILABLE;
                        break;
                }
            }
        }
    }

    // If commands to execute
    if (count($snCommands)) {
        $ids = sendBedCommands($snCommands);
    }
    // Else no commands to execute, so just get the state
    else {
        $ids = array_keys($idsAndSides);
    }

    foreach ($devicesSetToFave as $bedId => $sides) {
        foreach ($sides as $side) {
            $overrides[$bedId][$side]['number'] = $beds[$bedId]['sides'][$side]['fave'];
        }
    }
    parseBedState($beds, $output, $idsAndSides, $overrides, $bedSideComponentCapabilityFilters);
    return $output;
} // End function commandRequest


/**
 * Helper function to parse $beds assoc array returned from the SN script and
 * convert the structure into our desired format via the $output array. It also
 * uses the $idsAndSides array to filter down to ONLY the ID+side combination
 * requested by ST cloud.
 * 
 * If $overrides assoc array is provided (format bedId => side => 
 * number/mode/fave/etc => #/<mode>/on/etc), those will replace the values being returned
 * by this function. This is because the SN API is slow to update the state after
 * making changes to the state of the bed, so rather than returning incorrect or
 * stale state, it assumes that because there was no failure from the SN script
 * that the new state is that which was provided in the commandRequest itself.
 * 
 * $bedSideComponentCapabilityFilters is an array of $bedId => [$side => [$component => 
 * [$capability => true]]] filters that indicates a commandRequest was received
 * and only states that match that bed + component + capability combo should be
 * provided in the response.
 */
function parseBedState($beds, &$output, $idsAndSides, $overrides = [], $bedSideComponentCapabilityFilters = [])
{
    global $BED_PRESETS, $FOOTWARM_MODES, $UNDERBED_LIGHTING_SETTINGS, $UNDERBED_LIGHTING_BRIGHTNESS, $UNDERBED_LIGHTING_TIMES;

    // Iterate through each bed
    foreach ($beds as $id => $bed) {
        // Iterate through each side
        foreach ($bed['sides'] as $side_name => $side) {
            // If one of the IDs sought, add it to the response
            if (array_key_exists($id, $idsAndSides) && array_key_exists($side_name, $idsAndSides[$id])) {
                // If no foundation, default preset to null
                if (!array_key_exists('preset', $side)) {
                    $side['preset'] = null;
                }

                $states = [[
                    "component" => "main",
                    "capability" => "st.healthCheck",
                    "attribute" => "healthStatus",
                    "value" => "online",
                    "timestamp" => time(),
                ]];

                /////// MAIN ///////

                // Add various states to the states array
                if (!$bedSideComponentCapabilityFilters || extractOverride($bedSideComponentCapabilityFilters, $id, $side_name, 'main', 'st.airConditionerMode')) {
                    // Foundation current preset mode
                    $states[] = [
                        "component" => "main",
                        "capability" => "st.airConditionerMode",
                        "attribute" => "airConditionerMode",
                        // We use the extracted mode value if present, otherwise we use the text name for the bed preset if in our list. If not in the list, use the default
                        "value" => extractOverride($overrides, $id, $side_name, 'mode') ?: (array_key_exists($side['preset'], $BED_PRESETS) ? $BED_PRESETS[$side['preset']] : $BED_PRESETS[DEFAULT_PRESET]),
                    ];

                    // Foundation preset values
                    $states[] = [
                        "component" => "main",
                        "capability" => "st.airConditionerMode",
                        "attribute" => "supportedAcModes",
                        "value" => array_values($BED_PRESETS),
                    ];
                }

                if (!$bedSideComponentCapabilityFilters || extractOverride($bedSideComponentCapabilityFilters, $id, $side_name, 'main', 'st.switchLevel')) {
                    // Bed SleepNumber value
                    $states[] = [
                        "component" => "main",
                        "capability" => "st.switchLevel",
                        "attribute" => "level",
                        "value" => extractOverride($overrides, $id, $side_name, 'number') ?: $side['sleepNumber'],
                    ];
                }

                if (!$bedSideComponentCapabilityFilters || extractOverride($bedSideComponentCapabilityFilters, $id, $side_name, 'main', 'st.switch')) {
                    // Switch to indicate if in Favorite configuration, or not
                    $states[] = [
                        "component" => "main",
                        "capability" => "st.switch",
                        "attribute" => "switch",
                        "value" => extractOverride($overrides, $id, $side_name, 'fave') ?: ((($side['preset'] == FAVORITE) && ($side['sleepNumber'] == $side['fave']))
                            ? SWITCH_ON : SWITCH_OFF)
                    ];
                }

                if (!$bedSideComponentCapabilityFilters || extractOverride($bedSideComponentCapabilityFilters, $id, $side_name, 'main', 'st.presenceSensor')) {
                    // SmartThings presenceSensor indicating if the user is in bed or not
                    $states[] = [
                        "component" => "main",
                        "capability" => "st.presenceSensor",
                        "attribute" => "presence",
                        "value" => extractOverride($overrides, $id, $side_name, 'isInBed') ?: ($side['isInBed'] ? IN_BED_PRESENT : IN_BED_NOT_PRESENT),
                    ];
                }

                if (!$bedSideComponentCapabilityFilters || extractOverride($bedSideComponentCapabilityFilters, $id, $side_name, 'main', 'st.airQuality')) {
                    // Bed SleepNumber value
                    $states[] = [
                        "component" => "main",
                        "capability" => "st.airQualitySensor",
                        "attribute" => "airQuality",
                        "value" => extractOverride($overrides, $id, $side_name, 'score') ?: $side['sleepScore'],
                    ];
                }

                // If we are testing a new device profile, add additional
                // states to the response for the test device
                if (isOrGetTestDevice($id . DEVICE_ID_DELIM . $side_name)) {
                }

                /////// FOOTWARMING ///////

                if (!$bedSideComponentCapabilityFilters || extractOverride($bedSideComponentCapabilityFilters, $id, $side_name, 'footwarming', 'st.presenceSensor')) {
                    // SmartThings presenceSensor indicating if footwarming is available or not
                    $states[] = [
                        "component" => "footwarming",
                        "capability" => "st.presenceSensor",
                        "attribute" => "presence",
                        "value" => extractOverride($overrides, $id, $side_name, 'footwarmingAvailable') ?: ($side['footwarmingAvailable'] ? FOOTWARM_AVAILABLE : FOOTWARM_NOT_AVAILABLE)
                    ];
                }

                if (!$bedSideComponentCapabilityFilters || extractOverride($bedSideComponentCapabilityFilters, $id, $side_name, 'footwarming', 'st.airConditionerFanMode')) {
                    // Footwarming current value
                    $states[] = [
                        "component" => "footwarming",
                        "capability" => "st.airConditionerFanMode",
                        "attribute" => "fanMode",
                        "value" => extractOverride($overrides, $id, $side_name, 'footwarmingMode') ?: $side['footwarmingMode'],
                    ];

                    // Footwarming possible values
                    $states[] = [
                        "component" => "footwarming",
                        "capability" => "st.airConditionerFanMode",
                        "attribute" => "supportedAcFanModes",
                        "value" => array_values($FOOTWARM_MODES),
                    ];
                }

                /////// UNDERBED LIGHTING ///////

                if (!$bedSideComponentCapabilityFilters || extractOverride($bedSideComponentCapabilityFilters, $id, $side_name, 'lights', 'st.presenceSensor')) {
                    // SmartThings presenceSensor indicating if the bed has underbed lighting available or not
                    $states[] = [
                        "component" => "lights",
                        "capability" => "st.presenceSensor",
                        "attribute" => "presence",
                        "value" => extractOverride($overrides, $id, $side_name, 'lightingAvailable') ?: ($side['lightingAvailable'] ? UNDERBED_LIGHTING_AVAILABLE : UNDERBED_LIGHTING_NOT_AVAILABLE),
                    ];
                }

                if (!$bedSideComponentCapabilityFilters || extractOverride($bedSideComponentCapabilityFilters, $id, $side_name, 'lights', 'st.mode')) {
                    // Light setting mode
                    $states[] = [
                        "component" => "lights",
                        "capability" => "st.mode",
                        "attribute" => "mode",
                        // We use the extracted mode value if present, otherwise we use the text name for the setting if in our list. If not in the list, use the default
                        "value" => extractOverride($overrides, $id, $side_name, 'lightingSetting') ?: (array_key_exists($side['lightingSetting'], $UNDERBED_LIGHTING_SETTINGS) ? $UNDERBED_LIGHTING_SETTINGS[$side['lightingSetting']] : $UNDERBED_LIGHTING_SETTINGS[DEFAULT_UNDERBED_LIGHTING_SETTING]),
                    ];

                    // Light mode possible values
                    $states[] = [
                        "component" => "lights",
                        "capability" => "st.mode",
                        "attribute" => "supportedModes",
                        "value" => array_values($UNDERBED_LIGHTING_SETTINGS),
                    ];
                }

                if (!$bedSideComponentCapabilityFilters || extractOverride($bedSideComponentCapabilityFilters, $id, $side_name, 'lights', 'st.laundryWasherSpinSpeed')) {
                    // Brightness
                    $states[] = [
                        "component" => "lights",
                        "capability" => "st.laundryWasherSpinSpeed",
                        "attribute" => "spinSpeed",
                        // We use the extracted mode value if present, otherwise we use the text name for the setting if in our list. If not in the list, use the default
                        "value" => extractOverride($overrides, $id, $side_name, 'lightingBrightness') ?: (array_key_exists($side['lightingBrightness'], $UNDERBED_LIGHTING_BRIGHTNESS) ? $UNDERBED_LIGHTING_BRIGHTNESS[$side['lightingBrightness']] : $UNDERBED_LIGHTING_BRIGHTNESS[DEFAULT_UNDERBED_LIGHTING_BRIGHTNESS]),
                    ];

                    // Brightness possible values
                    $states[] = [
                        "component" => "lights",
                        "capability" => "st.laundryWasherSpinSpeed",
                        "attribute" => "supportedSpinSpeeds",
                        "value" => array_values($UNDERBED_LIGHTING_BRIGHTNESS),
                    ];
                }

                if (!$bedSideComponentCapabilityFilters || extractOverride($bedSideComponentCapabilityFilters, $id, $side_name, 'lights', 'st.airConditionerFanMode')) {
                    // Timer (mapped to the nearest key in the UNDERBED_LIGHTING_TIMES array)
                    $states[] = [
                        "component" => "lights",
                        "capability" => "st.airConditionerFanMode",
                        "attribute" => "fanMode",
                        // We use the extracted mode value if present, otherwise we use the text name for the setting if in our list. If not in the list, use the default
                        "value" => extractOverride($overrides, $id, $side_name, 'lightingTimer') ?: (array_key_exists($side['lightingTimer'], $UNDERBED_LIGHTING_TIMES) ? $UNDERBED_LIGHTING_TIMES[$side['lightingTimer']] : $UNDERBED_LIGHTING_TIMES[DEFAULT_UNDERBED_LIGHTING_TIME]),
                    ];

                    // Timer possible values
                    $states[] = [
                        "component" => "lights",
                        "capability" => "st.airConditionerFanMode",
                        "attribute" => "supportedAcFanModes",
                        "value" => array_values($UNDERBED_LIGHTING_TIMES),
                    ];
                }

                // If we are testing a new device profile, add additional
                // states to the response for the test device
                if (isOrGetTestDevice($id . DEVICE_ID_DELIM . $side_name)) {
                }

                // Add the states to the response
                $output[DEVICE_STATE][] = [
                    EXTERNAL_DEVICE_ID => $id . DEVICE_ID_DELIM . $side_name,
                    "deviceCookie" => [],
                    "states" => $states,
                ];
            }
        }
    }
} // End function parseBedState

/**
 * A helper function used to do the key checks and lookup of a particular
 * bedId+side+key_name combo from an $overrides assoc array. If key2 is also
 * supplied, it will check for that key within the next layer of the associative
 * array.
 * $bedId => [$side => [$key => [$optionalKey2 => value]]]
 */
function extractOverride($overrides, $bedId, $side, $key, $key2 = null)
{
    if (array_key_exists($bedId, $overrides)) {
        if (array_key_exists($side, $overrides[$bedId])) {
            if (array_key_exists($key, $overrides[$bedId][$side])) {
                if ($key2 === null) {
                    return $overrides[$bedId][$side][$key];
                } else {
                    if (array_key_exists($key2, $overrides[$bedId][$side][$key])) {
                        return $overrides[$bedId][$side][$key][$key2];
                    }
                }
            }
        }
    }
    return null;
}

/**
 * Takes an associative array of associative arrays that contain the externalDeviceId key values
 * and returns an array of those IDs.
 * @param $devices assoc array containing externalDeviceId values
 * @return array of device Id => side_name => side_name
 **/
function extractExternalDeviceIds(array $devices): array
{
    $ids = [];
    if ($devices) {
        foreach ($devices as $device) {
            if (array_key_exists(EXTERNAL_DEVICE_ID, $device)) {
                $bedId = $device[EXTERNAL_DEVICE_ID];
                list($id, $side) = explode(DEVICE_ID_DELIM, $bedId);
                $ids[$id][$side] = $side;
            }
        }
    }
    return $ids;
} // End function extractDeviceIds


/**
 * Iterates through externalIdValues and strips the DEVICE_ID_DELIM + side portion
 * then returns the remaining bit (the actual SleepNumber bed ID)
 * @param array $externalDeviceIds array of externalDeviceId values
 * @return array of stripped externalDeviceId values to the core SleepNumber Bed ID values
 **/
function stripSidesFromExternalDeviceIds(array $externalDeviceIds): array
{
    $bedIds = [];

    foreach ($externalDeviceIds as $edi) {
        $bedIds[] = substr($edi, 0, strpos($edi, DEVICE_ID_DELIM));
    }
    return $bedIds;
} // End function stripsidesFromExternalDeviceIds


/**
 * Look up $modeName in the $BED_PRESETS_MAP array and return the numeric value
 * associated if present. If not, return FAVORITE.
 * @param string $modeName
 * @return int value from $BED_PRESETS_MAP[$modeName] or FAVORITE if not found
 */
function mapModeToBedPreset($modeName)
{
    global $BED_PRESETS_MAP;

    if (array_key_exists($modeName, $BED_PRESETS_MAP)) {
        return $BED_PRESETS_MAP[$modeName];
    } else {
        return FAVORITE;
    }
} // End function mapModeToBedPreset

/**
 * Convert array with 'temp' and 'time' keys from SleepNumber API into combo
 * $FOOTWARM_MODES string.
 */
function mapModeToFootWarming($arrayTimeAndTemp)
{
    global $FOOTWARM_TEMPS, $FOOTWARM_TIMES;
    logtext("mapModeToFootWarming inputs: " . print_r($arrayTimeAndTemp, true));
    if ($arrayTimeAndTemp['temp'] == SleepyqPHP::FOOTWARM_OFF) {
        return FOOTWARM_TEMP_OFF;
    }
    return $FOOTWARM_TEMPS[$arrayTimeAndTemp['temp']] . FOOTWARM_MODE_DELIM . mapWarmingTimerValueToArrayOption($arrayTimeAndTemp['time']);
}

/**
 * Convert a string footwarming time value to the numeric equivalent from $FOOTWARM_TIMES_MAP.
 */
function mapFootWarmingTimeToNumber($timeStringValue)
{
    global $FOOTWARM_TIMES_MAP;
    if (array_key_exists($timeStringValue, $FOOTWARM_TIMES_MAP)) {
        return $FOOTWARM_TIMES_MAP[$timeStringValue];
    }
    return reset($FOOTWARM_TIMES_MAP);
}


/**
 * Convert a string footwarming temperature value to the numeric equivalent from $FOOTWARM_TEMPS_MAP.
 * @param string $tempStringValue The string temperature value to look up in $FOOTWARM_TEMPS_MAP
 * @return int Value in $FOOTWARM_TEMPS_MAP if present. The first value from $FOOTWARM_TEMPS_MAP otherwise.
 */
function mapFootWarmingTempToNumber(string $tempStringValue)
{
    global $FOOTWARM_TEMPS_MAP;
    if (array_key_exists($tempStringValue, $FOOTWARM_TEMPS_MAP)) {
        return $FOOTWARM_TEMPS_MAP[$tempStringValue];
    }
    return reset($FOOTWARM_TEMPS_MAP);
}

/**
 * Convert a string underbed light setting value to the numeric equivalent from $UNDERBED_LIGHTING_SETTINGS_MAP.
 * @param string $settingStringValue The string setting value to look up in $UNDERBED_LIGHTING_SETTINGS_MAP
 * @return int Value in $UNDERBED_LIGHTING_SETTINGS_MAP if present. The first value from $UNDERBED_LIGHTING_SETTINGS_MAP otherwise.
 */
function mapLightSettingToNumber(string $settingStringValue)
{
    global $UNDERBED_LIGHTING_SETTINGS_MAP;
    if (array_key_exists($settingStringValue, $UNDERBED_LIGHTING_SETTINGS_MAP)) {
        return $UNDERBED_LIGHTING_SETTINGS_MAP[$settingStringValue];
    }
    return reset($UNDERBED_LIGHTING_SETTINGS_MAP);
}

/**
 * Convert a string underbed light brightness value to the numeric equivalent from $UNDERBED_LIGHTING_BRIGHTNESS_MAP.
 * @param string $brightnessStringValue The string brightness value to look up in $UNDERBED_LIGHTING_BRIGHTNESS_MAP
 * @return int Value in $UNDERBED_LIGHTING_BRIGHTNESS_MAP if present. The first value from $UNDERBED_LIGHTING_BRIGHTNESS_MAP otherwise.
 */
function mapLightBrightnessToNumber(string $brightnessStringValue)
{
    global $UNDERBED_LIGHTING_BRIGHTNESS_MAP;
    if (array_key_exists($brightnessStringValue, $UNDERBED_LIGHTING_BRIGHTNESS_MAP)) {
        return $UNDERBED_LIGHTING_BRIGHTNESS_MAP[$brightnessStringValue];
    }
    return reset($UNDERBED_LIGHTING_BRIGHTNESS_MAP);
}

/**
 * Convert a string underbed light timer value to the numeric equivalent from $UNDERBED_LIGHT.
 * @param string $timerStringValue The string timer value to look up in $UNDERBED_LIGHTING_TIMES_MAP
 * @return int Value in $UNDERBED_LIGHTING_TIMES_MAP if present. The first value from $UNDERBED_LIGHTING_TIMES_MAP otherwise.
 */
function mapLightTimerToNumber(string $timerStringValue)
{
    global $UNDERBED_LIGHTING_TIMES_MAP;
    if (array_key_exists($timerStringValue, $UNDERBED_LIGHTING_TIMES_MAP)) {
        return $UNDERBED_LIGHTING_TIMES_MAP[$timerStringValue];
    }
    return reset($UNDERBED_LIGHTING_TIMES_MAP);
}

/**
 * Input: An integer value (e.g., 0, 15, 30, 45, 60, 120, 180, or any value in between).
 * Reference: The keys of $UNDERBED_LIGHTING_TIMES (which are numeric, e.g., 0, 15, 30, 45, 60, 120, 180).
 * Behavior:
 * If the input matches a key, return that key.
 * If the input is between two keys, return the next highest key (the "ceiling").
 * If the input is higher than the highest key, return the highest key.
 * If the input is lower than the lowest key, return the lowest key (which is 0).
 * Example:
 * 
 * Input: 179 → Output: 180
 * Input: 121 → Output: 180
 * Input: 120 → Output: 120
 * Input: 61 → Output: 120
 * Input: 0 → Output: 0
 * @param int $timerValue
 */
function mapLightingTimerValueToArrayOption(int $timerValue)
{
    global $UNDERBED_LIGHTING_TIMES;

    // Get all keys and sort them numerically ascending
    $keys = array_keys($UNDERBED_LIGHTING_TIMES);
    sort($keys, SORT_NUMERIC);

    // If value is less than or equal to the lowest key, return the lowest key
    if ($timerValue <= $keys[0]) {
        return $keys[0];
    }

    // If value is greater than or equal to the highest key, return the highest key
    if ($timerValue >= end($keys)) {
        return end($keys);
    }

    // Otherwise, find the smallest key >= timerValue
    foreach ($keys as $key) {
        if ($timerValue <= $key) {
            return $key;
        }
    }

    // Fallback (should not be reached)
    return end($keys);
}

/**
 * Input: An integer value representing minutes (e.g., 1..360).
 * Reference: The keys of $FOOTWARM_TIMES (e.g., 30, 60, 120, 180, 240, 300, 360).
 * Behavior:
 * - If the input matches a key, return that key.
 * - If the input is between two keys, return the next highest key (the "ceiling").
 * - If the input is higher than the highest key, return the highest key.
 * - If the input is lower than the lowest key, return the lowest key.
 * Example:
 * - 59 → 60
 * - 120 → 120
 * - 121 → 180
 * - 361 → 360
 * @param int $timerValue
 */
function mapWarmingTimerValueToArrayOption(int $timerValue)
{
    global $FOOTWARM_TIMES;

    // Get all keys and sort them numerically ascending
    $keys = array_keys($FOOTWARM_TIMES);
    sort($keys, SORT_NUMERIC);

    // If value is less than or equal to the lowest key, return the lowest key
    if ($timerValue <= $keys[0]) {
        return $keys[0];
    }

    // If value is greater than or equal to the highest key, return the highest key
    if ($timerValue >= end($keys)) {
        return end($keys);
    }

    // Otherwise, find the smallest key >= timerValue
    foreach ($keys as $key) {
        if ($timerValue <= $key) {
            return $key;
        }
    }

    // Fallback (should not be reached)
    return end($keys);
}

/**
 * Take an array (or associative array) and convert the values to lowercase, and
 * optionally remove whitespaces, as well.
 * @param array $arrayOfStrings An array or associative array of strings that will have values converted to lowercase
 * @param bool $stripSpaces Defaults to true. If false, values will not have whitespace stripped, as well.
 * @return array The converted array
 */
function toLc(array $arrayOfStrings, bool $stripSpaces = true): array
{
    foreach ($arrayOfStrings as $key => $val) {
        $arrayOfStrings[$key] = strtolower($val);
        if ($stripSpaces) {
            $arrayOfStrings[$key] = preg_replace("/\s+/", "", $arrayOfStrings[$key]);
        }
    }
    return $arrayOfStrings;
}

///////////////// SLEEPYQ FUNCTIONS /////////////////


/**
 * Get Beds
 */
function getBeds($withFoundationFeatures = false, string $userId = null): array
{
    $client = getClient($userId);
    $beds = $client->beds($withFoundationFeatures);
    foreach ($beds as $k => $bed) {
        $bed->id = $bed->bedId;
        // If a single-sided bed, return just a single value, otherwise two sides
        $bed->sides = ($bed->sleeperLeftId == $bed->sleeperRightId ||
            !isset($bed->sleeperLeftId) ||
            !isset($bed->sleeperRightId)
        ) ? [SleepyqPHP::LEFT] : SleepyqPHP::SIDES_NAMES;
    }
    return $beds;
} // End function getBeds


/**
 * Get Bed state
 * @param $bedIds array
 */
function getBedState($bedIds = []): array
{
    $client = getClient();

    $statuses = [];
    // Get bed info and sleeper info for sleep score (sleepIq)
    $bedAndSleeperData = $client->bedsWithSleeperStatus();

    // Get each bed's current sides' statuses
    foreach ($bedAndSleeperData as $bed) {
        $statuses[$bed->bedId] = $bed->sides;
    }

    foreach ($bedIds as $bedId) {
        $sideFaves = $client->getBedFaves($bedId);
        $sidePresets = $client->getBedSidePresets($bedId);
        foreach ($sidePresets as $side => $preset) {
            if ($preset['preset'] === null) {
                unset($sidePresets[$side]);
            }
        }
        $foundationFeatures = $client->getFoundationFeatures($bedId); // Has <side>UnderbedLightPMW

        $foundationFootwarming = null;
        if ($foundationFeatures->hasFootWarming) {
            $foundationFootwarming = $client->getFoundationFootwarming($bedId);
        }
        $foundationLighting = null;
        if ($foundationFeatures->hasUnderbedLight) {
            $lightData = $client->getLight(bedId: $bedId);
            $autoEnabled = $client->isUnderBedLightingAutoModeEnabled($bedId);
            $foundationLighting = [
                'setting' => $autoEnabled ? SLEEPYQPHP_LIGHTING_AUTO : $lightData->setting,
                'timer' => $lightData->timer,
                'brightness' => $foundationFeatures->rightUnderbedLightPMW,
            ];
        }
        $data[$bedId] = [
            'id' => $bedId,
            'sides' => $sidePresets,
        ];

        // Ensure bedId in statuses dict
        if (array_key_exists($bedId, $statuses)) {
            $bedStatus = $statuses[$bedId];

            foreach (SleepyqPHP::SIDES_NAMES as $side) {
                if (array_key_exists($side, $bedStatus)) {
                    $sideStatus = $bedStatus[$side];
                    $sleeperId = $sideStatus->sleeper->sleeperId;
                    if ($sideStatus) {
                        $data[$bedId]['sides'][$side]['sleepNumber'] = $sideStatus->sleepNumber;
                        $data[$bedId]['sides'][$side]['isInBed'] = $sideStatus->isInBed;
                        $data[$bedId]['sides'][$side]['fave'] = $sideFaves[$side];
                        $data[$bedId]['sides'][$side]['footwarmingAvailable'] = $foundationFeatures->hasFootWarming;
                        $data[$bedId]['sides'][$side]['footwarmingMode'] = ($foundationFootwarming != null) ? mapModeToFootWarming($foundationFootwarming->sides[$side]) : FOOTWARM_TEMP_OFF; // Array with 'temp' and 'time' keys

                        // Add Light info
                        $data[$bedId]['sides'][$side]['lightingAvailable'] = $foundationFeatures->hasUnderbedLight;
                        $data[$bedId]['sides'][$side]['lightingSetting'] = ($foundationLighting != null) ? $foundationLighting['setting'] : SleepyqPHP::LIGHT_SETTINGS_OFF;
                        $data[$bedId]['sides'][$side]['lightingBrightness'] = ($foundationLighting != null) ? $foundationLighting['brightness'] : SleepyqPHP::LIGHT_BRIGHTNESS_OFF;
                        $data[$bedId]['sides'][$side]['lightingTimer'] = ($foundationLighting != null && is_int(
                            $foundationLighting['timer']
                        )) ? mapLightingTimerValueToArrayOption($foundationLighting['timer']) : SLEEPYQPHP_LIGHTING_TIME_OFF;

                        // Retrieve the sleeper info to get their sleep score
                        if ($sleeperId) {
                            $sleepData = $client->getSleepData($sleeperId, 'D')[0];
                            $data[$bedId]['sides'][$side]['sleepScore'] = $sleepData->avgSleepIQ;
                        }
                    }
                }
            }
        }
    }
    return $data;
} // End function getBedState

/**
 * Reset bed to favorite settings and return results.
 * @param array $bedIds An array of bed ID values
 * @return bool Results of setting. True if successful. False otherwise
 */
function setBedFavorites(array $bedIds): bool
{
    $client = getClient();
    foreach ($bedIds as $bedId) {
        $response = $client->setBedToFavorites($bedId);
        if (!$response) {
            return false;
        }
    }
    return $response;
} // End function setBedFavorites


/**
 * Reset bed to flat and 100
 * @param string $bedId The bed ID to reset
 */
function resetBed(string $bedId)
{
    $client = getClient();
    $client->resetBed($bedId);
} // End function resetBed


/**
 * Execute commands to modify the bed settings
 * @param $commands Commands {id => {side => {'id'/'side'/'mode'/'number'/'temp'/'time'}}}
 * @return array Returns a set of unique bed IDs updated
 */
function sendBedCommands(array $commands): array
{
    $client = getClient();
    $ids = [];
    foreach ($commands as $command) {
        $keys = array_keys($command);
        $ids[] = $id = $command['id'];
        $side = $command['side'];
        $component = $command['component'] ?? 'main';

        //// MAIN COMPONENT COMMANDS ////

        // set the mode for the bed
        if (in_array('mode', $keys)) {
            $client->setBedMode($id, $side, $command['mode']);
        }
        // set the sleep number
        if (in_array('number', $keys)) {
            $client->setBedSleepNumber($id, $side, $command['number']);
        }
        if (in_array('fave', $keys)) {
            $client->setBedSideToFavorite($id, $side);
        }

        //// FOOTWARMING COMPONENT COMMANDS ////

        if (in_array('temp', $keys) && in_array('time', $keys)) {
            $client->setFoundationFootwarming($side, $command['temp'], $command['time'], $id);
        }

        //// UNDERBED LIGHTING COMPONENT COMMANDS ////

        // In this case we do a single "hybrid" command for all facets of lighting because it requires multiple API calls to coordinate
        if (in_array('lightingAvailable', $keys)) {
            // Auto mode means we set the manual setting to OFF but with the designated timer, then turn on auto mode
            if ($command['lightingSetting'] == SLEEPYQPHP_LIGHTING_AUTO) {
                $client->setLightSettingAndTimer(UNDERBED_LIGHTING_OFF, timer: $command['lightingTimer'], bedId: $id);
                $client->enableOrDisableUnderBedLighting(true, $id);
            }
            // Manual mode means we disable auto mode, then set the manual mode and timer
            else {
                $client->enableOrDisableUnderBedLighting(false, $id);
                $client->setLightSettingAndTimer($command['lightingSetting'], timer: $command['lightingTimer'], bedId: $id);
            }
            //  Set the brightness
            $client->setLightBrightness($command['lightingBrightness'], $id);
        }
    }
    return $ids;
} // End function sendBedCommands


/**
 * Get the SleepyqPHP client
 * @param string $userId Optional. If provided, user this user ID for lookup
 * @return SleepyqPHP object
 */
function getClient(string $userId = null): SleepyqPHP
{
    global $sleepyq, $authentication, $server;

    $error = null;
    $username = $password = '';

    // If the sleepyq client has not been initialized OR a userId was provided (for cron context)
    if ($sleepyq == null || $userId) {

        // Single-account configuration
        if (SINGLE_ACCOUNT_CONFIG) {
            $username = SN_USER;
            $password = SN_PASS;
        }

        /**
         * Look up user by token or user the provided userId
         * Get user's username and password (decrypted). Use these values to authenticate against SleepNumber API
         */
        else {
            // No user ID provided, so look up by token
            require_once __DIR__ . '/oauth/server.php';
            if (!$userId) {
                $token = $authentication[AUTHENTICATION_TOKEN];
                $at = $server->getStorage(STORAGE_NAME)->getAccessToken($token);
                $userId = $at['user_id'];
            }
            $user = $server->getStorage(STORAGE_NAME)->getUser($userId);

            if ($user) {
                $username = $userId;
                $password = decryptData($user['password']);
            } else {
                logtext("There was a problem looking up user by token: $token");
            }
        }
        try {
            $sleepyq = new SleepyqPHP($username, $password);
            $sleepyq->login();
        } catch (Exception $v) {
            $error = $v;
            logtext("There was a problem logging into SleepNumber: $error");
        }
    }
    return $sleepyq;
}

/**
 * Set an HTTP response code and optionally print some output, then exit execution.
 * @param int $code
 * @param mixed $content
 * @return void
 */
function httpError(int $code = 400, string $content = null)
{
    http_response_code($code);
    if ($content) {
        print $content;
    }
    logtext("HTTP ERROR $code: $content");
    exit;
}

/**
 * Generate a UUIDv4 string
 * @return string
 */
function uuidv4(): string
{
    $data = random_bytes(16);

    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Get an access token to make state update requests to the SmartThings API. If
 * there is a valid/unexpired access token in the database, use that. If not,
 * get a new one from the SmartThings API.
 * @param array $codeRow
 * @return string|null
 */
function getAccessTokenByCode(array $codeRow): string|null
{
    $accessCode = null;
    $userId = $codeRow[USER_ID];
    if ($codeRow) {
        $codeId = $codeRow[ID];
        $tokenUri = $codeRow[TOKEN_URI];
        logtext("Found code row for userId $userId with codeId $codeId");
        // We got a code, so now look up the newest token for that code
        $tokenRow = getTokenByCodeId($codeId);
        if ($tokenRow) {
            // We got a token row
            $expiresAt = new DateTime($tokenRow[EXPIRES_AT]);
            $now = new DateTime();
            if ($now >= $expiresAt) {
                logtext("The latest token has expired: {$tokenRow[EXPIRES_AT]} >= now");
                // The latest token has expired, so we need to get a new one using the refresh token
                $refreshToken = $tokenRow[REFRESH_TOKEN];
                $accessCode = makeAccessTokenRequest($tokenUri, $codeId, $refreshToken);
            } else {
                logtext("The latest token ID {$tokenRow[ID]} is still valid");
                // The latest token is still valid, so just return it
                $accessCode = $tokenRow[ACCESS_TOKEN];
            }
        } else {
            logtext("No token row found for codeId $codeId");
        }
    } else {
        logtext("No code row found for userId $userId");
    }

    return $accessCode;
}

/**
 * Get an access token and refresh token for making calls to the SmartThings API
 * The access token expires in 24 hours.
 * The refresh token is static and will not change until a new grantCallbackAccess interaction is sent from SmartThings. This happens when either:
 * A user needs to re-login to the linked account.
 * Your integration requests a refresh of the callbackTokens by setting requestGrantCallbackAccess: true in a discoveryResponse.
 * requestGrantCallbackAccess should only be used if a refresh of callbackToken fails using the static refresh token, and the failure is not due to an internal server error or a timeout.
 * If the access token has expired, use the provided refreshToken to request a new access token at the oauthToken URL used previously.
 * @param string $tokenUri The URL to call to get the token
 * @param int $codeId The ID of the code in the ST_CALLBACK_CODE table associated with this request
 * @param string $code The code to use for making the request. This could be a code from ST_CALLBACK_CODE, or a refresh token provided previously. If not provided, the code will be looked up by the $codeId from the ST_CALLBACK_CODE table
 * @return string|null The access token retrieved or null on failure
 */
function makeAccessTokenRequest(string $tokenUri, int $codeId, string $code = null): string|null
{
    /** 
     * https://developer.smartthings.com/docs/devices/cloud-connected/interaction-types#reciprocal-access-token
     * Example request:
     * {
     * "headers": {
     *     "schema": "st-schema",
     *     "version": "1.0",
     *     "interactionType": "accessTokenRequest",
     *     "requestId": "abc-123-456"
     * },
     * "callbackAuthentication": {
     *     "grantType": "authorization_code",
     *     "code": "xxxxxxxxxxx",
     *     "clientId": "client id given to partner in dev-workspace during app creation",
     *     "clientSecret": "client secret given to partner in dev-workspace during app creation"
     * }
     * }
     */
    // If the code wasn't provided, look it up in the database
    if (!$code) {
        $code = getCodeById($codeId, CODE);
        if (!$code) {
            httpError(500, "There was a problem looking up the code by ID: $codeId");
        }
    }
    $data = [
        HEADERS => [
            'schema' => 'st-schema',
            'version' => '1.0',
            INTERACTION_TYPE => ACCESS_TOKEN_REQUEST,
            REQUEST_ID => uuidv4(),
        ],
        CALLBACK_AUTHENTICATION => [
            GRANT_TYPE => AUTHORIZATION_CODE,
            CODE => $code,
            CLIENT_ID => ST_CLIENT_ID,
            CLIENT_SECRET => ST_CLIENT_SECRET,
        ]
    ];
    $response = makeRequest($tokenUri, $data, [], 'POST');
    if ($response) {
        /**
         * Example response:
         * {
         *     "headers": {
         *         "schema": "st-schema",
         *         "version": "1.0",
         *         "interactionType": "accessTokenResponse",
         *         "requestId": "abc-123-456"
         *     },
         *     "callbackAuthentication": {
         *         "tokenType": "Bearer",
         *         "accessToken": "xxxxxxxxxxx",
         *         "refreshToken": "yyyyyyyyyyy",
         *         "expiresIn": 86400
         *     }
         * }
         */
        if (array_key_exists(HEADERS, $response) && array_key_exists(CALLBACK_AUTHENTICATION, $response)) {
            $auth = $response[CALLBACK_AUTHENTICATION];
            if (array_key_exists(ACCESSTOKEN, $auth) && array_key_exists(REFRESHTOKEN, $auth)) {
                // Insert the callback token data into the database
                $lastId = insertCallbackToken($auth[ACCESSTOKEN], $auth[REFRESHTOKEN], $codeId, $auth[EXPIRESIN]);
                logtext("Inserted new callback token with ID: $lastId");
                return $auth[ACCESSTOKEN];
            }
        }
    }
    return null;
}

/**
 * Perform the state callbacks for each user. This will get the latest code for
 * each user, get the access token for each code, and then make the state
 * callback request to the SmartThings API.
 * @param array $userIds An optional array of user IDs to perform the callbacks for. If not provided, all users will be used.
 */
function performStateCallbacks(array $userIds = null)
{
    // Get the latest codes for each user
    $codeRows = getLatestUserCodes($userIds);
    logtext("###Retrieved " . count($codeRows) . " codes");

    // Get (or request) access tokens for each
    foreach ($codeRows as $codeRow) {
        $token = getAccessTokenByCode($codeRow);
        $stateCallbackUri = $codeRow[STATE_URI];
        if ($token) {
            $beds = getBeds(false, $codeRow[USER_ID]);

            $idsAndSides = [];
            foreach ($beds as $bed) {
                foreach ($bed->sides as $side) {
                    $idsAndSides[$bed->id][$side] = $side;
                }
            }
            // Make stateCallback requests for each
            makeStateCallbackRequest($token, $idsAndSides, $stateCallbackUri);
        }
    }
}

function makeStateCallbackRequest(string $token, array $idsAndSides, string $stateCallbackUri): mixed
{
    /**
     * Example from https://developer.smartthings.com/docs/devices/cloud-connected/interaction-types#device-state-callback
     * {
     *     "headers": {
     *         "schema": "st-schema",
     *         "version": "1.0",
     *         "interactionType": "stateCallback",
     *         "requestId": "abc-123-456"
     *     },
     *     "authentication": {
     *         "tokenType": "Bearer",
     *         "token": "token-received from SmartThings for callbacks"
     *     },
     *     "deviceState": 
     *         {
     *             "externalDeviceId": "partner-device-id-1",
     *             "states": [
     *                 {
     *                     "component": "main",
     *                     "capability": "st.switch",
     *                     "attribute": "switch",
     *                     "value": "on",
     *                     "timestamp": 1568248946010
     *                 },
     *                 {
     *                     "component": "main",
     *                     "capability": "st.switchLevel",
     *                     "attribute": "level",
     *                     "value": 80,
     *                     "timestamp": 1568249946020
     *                 }
     *             ]
     *         },
     *         {
     *             "externalDeviceId": "partner-device-id-2",
     *             "states": [
     *                 {
     *                     "component": "main",
     *                     "capability": "st.switch",
     *                     "attribute": "switch",
     *                     "value": "off",
     *                     "timestamp": 1568254946010
     *                 },
     *                 {
     *                     "component": "main",
     *                     "capability": "st.switchLevel",
     *                     "attribute": "level",
     *                     "value": 80,
     *                     "timestamp": 1568255946020
     *                 },
     *                 {
     *                     "component": "main",
     *                     "capability": "st.healthCheck",
     *                     "attribute": "healthStatus",
     *                     "value": "offline",
     *                     "timestamp": 1568257946030
     *                 }
     *             ]
     *         }
     *     ]
     * }
     */
    $request = [
        HEADERS => [
            'schema' => 'st-schema',
            'version' => '1.0',
            INTERACTION_TYPE => STATE_CALLBACK,
            REQUEST_ID => uuidv4(),
        ],
        AUTHENTICATION => [
            TOKEN_TYPE => 'Bearer',
            TOKEN => $token,
        ],
    ];
    $ids = array_keys($idsAndSides);
    $beds = getBedState($ids);

    parseBedState($beds, $request, $idsAndSides);
    $response = makeRequest($stateCallbackUri, $request, ['Content-Type' => 'application/json', 'charset' => 'utf-8'], 'POST');
    return $response;
}

/**
 * 
 * Make a call to the SmartThings API
 * @param mixed $url
 * @param mixed $data
 * @param mixed $headers
 * @param mixed $method
 * @return mixed
 */
function makeRequest($url, $data = null, $headers = [], $method = 'GET'): mixed
{
    logtext("Making $method request to $url with data:\n" . json_encode($data) . "\nheaders:\n" . json_encode($headers) . "\nmethod: $method");
    if ($method == 'GET' && $data) {
        $url .= '?' . http_build_query($data);
    }
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    if ($headers) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    if ($method == 'POST' && $data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    logtext("Request response:\n" . json_encode($response));
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($statusCode >= 400) {
        httpError($statusCode, json_encode($response));
    }

    return json_decode($response, true);
}

/**
 * Inserts a new callback code into the database
 * @param string $accessToken The token used in the response to this server
 * @param string $code The code provided for calls to SmartThings
 * @param string $userId The ID associated with the access token
 * @param string $tokenUri The URL to use to request a callback access token
 * @param string $stateUri The URL to send state updates to
 * @return bool|int
 */
function insertCallbackCode(string $accessToken, string $code, string $userId, string $tokenUri, string $stateUri): bool|int
{
    $db = getDb();
    $data = [
        ACCESS_TOKEN => $accessToken,
        CODE => $code,
        USER_ID => $userId,
        TOKEN_URI => $tokenUri,
        STATE_URI => $stateUri,
    ];
    $lastId = $db->insert(ST_CALLBACK_CODE, $data);
    // If the insert failed, throw an error and exit
    if ($lastId === false) {
        httpError(500, "There was a problem saving a new callback code to the database. Contents: " . json_encode($data));
    } else {
        logtext("Inserted new callback code with ID: $lastId");
    }

    return $lastId;
}

/**
 * Inserts a new callback token into the database
 * @param string $accessToken The token used in the response to this server
 * @param string $refreshToken The token used to refresh the access token
 * @param string $codeId The ID of the ST_CALLBACK_CODE row associated with the request to SmartThings
 * @param int $expiresIn The number of seconds the token is valid for
 * @return bool|int
 */
function insertCallbackToken(string $accessToken, string $refreshToken, int $codeId, int $expiresIn): bool|int
{
    $db = getDb();
    $data = [
        ACCESS_TOKEN => $accessToken,
        REFRESH_TOKEN => $refreshToken,
        ST_CALLBACK_CODE_ID => $codeId,
        EXPIRES_IN => $expiresIn,
        EXPIRES_AT => date('Y-m-d H:i:s', time() + $expiresIn),
    ];
    $lastId = $db->insert(ST_CALLBACK_TOKEN, $data);
    // If the insert failed, throw an error and exit
    if ($lastId === false) {
        httpError(500, "There was a problem saving a new callback token to the database. Contents: " . json_encode($data));
    } else {
        logtext("Inserted new callback token with ID: $lastId");
    }

    return $lastId;
}

/**
 * Get a ST_CALLBACK_CODE row or code by ID
 * @param int $codeId The ID of the code row to retrieve
 * @param string $specificField Defaults to null
 * @return mixed Either associative array for the row or a single field value
 */
function getCodeById(int $codeId, string $specificField = null)
{
    $db = getDb();
    return $db->getRowOrFieldById(ST_CALLBACK_CODE, $codeId, $specificField);
}

/**
 * Get a ST_CALLBACK_CODE row or code by user ID
 * @param string $userId The ID of the user to retrieve
 * @param string $specificField Defaults to null
 * @return mixed Either associative array for the row or a single field value
 */
function getCodeByUserId(string $userId, string $specificField = null)
{
    $db = getDb();
    return $db->getRowOrFieldByField(ST_CALLBACK_CODE, USER_ID, $userId, $specificField);
}


/**
 * Get a ST_CALLBACK_TOKEN row or field by code ID
 * @param int $codeId The ID of the code row to retrieve
 * @param string $specificField Defaults to null
 * @return mixed Either associative array for the row or a single field value
 */
function getTokenByCodeId(int $codeId, string $specificField = null): mixed
{
    $db = getDb();
    return $db->getRowOrFieldByField(ST_CALLBACK_TOKEN, ST_CALLBACK_CODE_ID, $codeId, $specificField);
}

/**
 * Get the latest callback code for each user
 * @param array $userIds An optional array of user IDs to filter by
 * @return array The latest callback codes for each user
 */
function getLatestUserCodes(array $userIds = null)
{
    $userIdStr = '';
    if ($userIds) {
        $userIdStr = 'AND t1.' . USER_ID . ' IN ("' . implode('","', $userIds) . '")';
    }
    $db = getDb();
    $sql = "SELECT t1.* FROM " . ST_CALLBACK_CODE . " t1 WHERE t1.id = (SELECT MAX(t2.id) FROM " . ST_CALLBACK_CODE . " t2 WHERE t2." . USER_ID . " = t1." . USER_ID . " $userIdStr)";
    return $db->raw($sql);
}

/**
 * Get user IDs, sleep start and end time for users that have those values set
 * @return array Of arrays with USER_ID, SLEEP_START_TIME, SLEEP_END_TIME, TIMEZONE
 */
function getUsersWithSleepSettingsEnabled()
{
    $db = getDb();
    $sql = "SELECT " . USER_ID . "," . SLEEP_START_TIME . "," . SLEEP_END_TIME . "," . TIMEZONE . " FROM " . ST_USER_SETTINGS . " WHERE " . SLEEP_START_TIME . " IS NOT NULL AND " . SLEEP_END_TIME . " IS NOT NULL";
    return $db->raw($sql);
}

/**
 * Get users that have sleep start and end times that are currently in the sleep window. The passed in users will be filtered to only those that are currently in the sleep window.
 * @param array $usersSleepInfo
 * @return void
 */
function filterUsersBySleepStartEndTime(array &$usersSleepInfo)
{
    foreach ($usersSleepInfo as $userId => $userSleepInfo) {
        $timezone = new DateTimeZone($userSleepInfo[TIMEZONE]);
        $now = new DateTime('now', $timezone);
        $nowTime = $now->format('H:i');

        // Extract only the time part from the stored datetime strings
        $startDT = new DateTime($userSleepInfo[SLEEP_START_TIME]);
        $startDT->setTimezone($timezone);
        $startTime = $startDT->format('H:i');
        $endDT = new DateTime($userSleepInfo[SLEEP_END_TIME]);
        $endDT->setTimezone($timezone);
        $endTime = $endDT->format('H:i');

        logtext("User $userId now: $nowTime, sleep start: $startTime, sleep end: $endTime");

        if ($startTime < $endTime) {
            // Sleep window does not cross midnight
            if (!($nowTime >= $startTime && $nowTime < $endTime)) {
                logtext("User $userId is NOT in sleep window (simple case). Removing from list.\n");
                unset($usersSleepInfo[$userId]);
            }
        } else {
            // Sleep window crosses midnight
            if (!($nowTime >= $startTime || $nowTime < $endTime)) {
                logtext("User $userId is NOT in sleep window (overnight case). Removing from list.\n");
                unset($usersSleepInfo[$userId]);
            }
        }
    }
}
