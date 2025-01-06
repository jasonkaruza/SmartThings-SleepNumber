<?php
// sn2.php
require_once "./oauth/loader.php";
require_once "./SleepyqPHP/sleepyq.php";

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
     * - php sn.php --itype=discoveryRequest --token=XYZ
     * - php sn.php --itype=stateRefreshRequest --ids=<bed_id>:right
     * - php sn.php --itype=commandRequest --devices='[{"externalDeviceId":"<bed_id>:left","deviceCookie":[],"commands":[{"component":"main","capability":"st.mode","command":"setAirConditionerMode","arguments":["Flat"]},{"component":"main","capability":"st.level","command":"setLevel","arguments":[80]}]},{"externalDeviceId":"<bed_id>:right","deviceCookie":[],"commands":[{"component":"main","capability":"st.mode","command":"setMode","arguments":["Flat"]},{"component":"main","capability":"st.level","command":"setLevel","arguments":[85]}]}]'
     * - php sn.php --itype=commandRequest --devices='[{"externalDeviceId":"<bed_id>:right","deviceCookie":{"updatedcookie":"12345"},"commands":[{"component":"main","capability":"st.switch","command":"on","arguments":[]}]}]'
     * - php sn.php --itype=commandRequest --devices='[{"externalDeviceId":"<bed_id>:right","deviceCookie":{"updatedcookie":"12345"},"commands":[{"component":"footwarming","capability":"st.airConditionerFanMode","command":"setFanMode","arguments":["Low - 30 min"]}]}]'
     * - php sn.php --itype=commandRequest --devices='[{"externalDeviceId":"<bed_id>:right","deviceCookie":{"updatedcookie":"12345"},"commands":[{"component":"footwarming","capability":"st.airConditionerFanMode","command":"setFanMode","arguments":["Off"]}]}]'
     * * - php sn.php --token=<token> --itype=grantCallbackAccess --callbackAuthentication='{"grantType":"authorization_code","scope":"callback-access","code":"<longstring>","clientId":"<something>"}' --callbackUrls='{"oauthToken":"https:\/\/c2c-us.smartthings.com\/oauth\/token","stateCallback":"https:\/\/c2c-us.smartthings.com\/device\/events"}'
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
        );
        $options = getopt($shortopts, $longopts);
        if (!$options['itype']) {
            exit;
        }

        // If cron, we want to facilitate a callback to SmartThings with state
        // updates https://developer.smartthings.com/docs/devices/cloud-connected/interaction-types#reciprocal-access-token
        if (array_key_exists('iscron', $options)) {
        }


        $headers = [
            "schema" => "st-schema",
            "version" => "1.0",
            "interactionType" => $options['itype'],
            "requestId" => uuidv4(),
        ];
        $authentication = [
            "tokenType" => "Bearer",
            "token" => is_null($options['token']) ? "token received during oauth from partner" : $options['token'],
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
logtext("RESPONSE: " . json_encode($response, JSON_PRETTY_PRINT));

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
            $outDevices[] = [
                EXTERNAL_DEVICE_ID => $bed->id . DEVICE_ID_DELIM . $side,
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
                "deviceHandlerType" => DEVICE_PROFILE_ID,
                "deviceUniqueId" => $bed->id . DEVICE_ID_DELIM . $side
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

    // This will be used to shortcut overriding the sleep number if we got a
    // command to set a side to the Favorite position and number. This is due
    // to the API response not returning the target number in a timely manner
    // relative to the getting of the bed state after sending the command.
    $devicesSetToFave = [];

    // Iterate through each device
    foreach ($devices as $device) {
        $commands = $device['commands'];
        // Extract the bed ID and side
        list($bedId, $side) = explode(DEVICE_ID_DELIM, $device[EXTERNAL_DEVICE_ID]);

        // Iterate through each command and extract the action
        foreach ($commands as $command) {
            switch ($command['command']) {
                case 'setLevel':
                    $level = array_values($command['arguments'])[0];
                    $snCommands[] = [
                        "id" => $bedId,
                        "side" => $side,
                        "number" => $level,
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
                    ];
                    $overrides[$bedId][$side]['mode'] = $mode;
                    break;

                case 'on':
                    $snCommands[] = [
                        "id" => $bedId,
                        "side" => $side,
                        "fave" => "on",
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
                    ];
                    $overrides[$bedId][$side]['footwarmingMode'] = $rawmode;
                    $overrides[$bedId][$side]['footwarmingAvailable'] = FOOTWARM_AVAILABLE;
                    break;
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

    $beds = getBedState($ids);
    foreach ($devicesSetToFave as $bedId => $sides) {
        foreach ($sides as $side) {
            $overrides[$bedId][$side]['number'] = $beds[$bedId]['sides'][$side]['fave'];
        }
    }
    parseBedState($beds, $output, $idsAndSides, $overrides);
    return $output;
} // End function commandRequest


/**
 * Helper function to parse $beds assoc array returned from the SN script and
 * convert the structure into our desired format via the $output array. It also
 * uses the $idsAndSides array to filter down to ONLY the ID+side combination
 * requested by ST cloud.
 * 
 * If $overrides assoc array is provided (format bedId => side => 
 * number/mode/fave => #/<mode>/on), those will replace the values being returned
 * by this function. This is because the SN API is slow to update the state after
 * making changes to the state of the bed, so rather than returning incorrect or
 * stale state, it assumes that because there was no failure from the SN script
 * that the new state is that which was provided in the commandRequest itself.
 */
function parseBedState($beds, &$output, $idsAndSides, $overrides = [])
{
    global $BED_PRESETS, $FOOTWARM_MODES;

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
                $output[DEVICE_STATE][] = [
                    EXTERNAL_DEVICE_ID => $id . DEVICE_ID_DELIM . $side_name,
                    "deviceCookie" => [],
                    "states" => [
                        // Foundation current preset mode
                        [
                            "component" => "main",
                            "capability" => "st.airConditionerMode",
                            "attribute" => "airConditionerMode",
                            // We use the extracted mode value if present, otherwise we use the text name for the bed preset if in our list. If not in the list, use the default
                            "value" => extractOverride($overrides, $id, $side_name, 'mode') ?: (array_key_exists($side['preset'], $BED_PRESETS) ? $BED_PRESETS[$side['preset']] : $BED_PRESETS[DEFAULT_PRESET]),
                        ],
                        // Foundation preset values
                        [
                            "component" => "main",
                            "capability" => "st.airConditionerMode",
                            "attribute" => "supportedAcModes",
                            "value" => array_values($BED_PRESETS),
                        ],
                        // Bed SleepNumber value
                        [
                            "component" => "main",
                            "capability" => "st.switchLevel",
                            "attribute" => "level",
                            "value" => extractOverride($overrides, $id, $side_name, 'number') ?: $side['sleepNumber'],
                        ],
                        // Switch to indicate if in Favorite configuration, or not
                        [
                            "component" => "main",
                            "capability" => "st.switch",
                            "attribute" => "switch",
                            "value" => extractOverride($overrides, $id, $side_name, 'fave') ?: ((($side['preset'] == FAVORITE) && ($side['sleepNumber'] == $side['fave']))
                                ? SWITCH_ON : SWITCH_OFF)
                        ],
                        // SmartThings presenceSensor indicating if footwarming is available or not
                        [
                            "component" => "footwarming",
                            "capability" => "st.presenceSensor",
                            "attribute" => "presence",
                            "value" => extractOverride($overrides, $id, $side_name, 'footwarmingAvailable') ?: ($side['footwarmingAvailable'] ? FOOTWARM_AVAILABLE : FOOTWARM_NOT_AVAILABLE)
                        ],
                        // Footwarming current value
                        [
                            "component" => "footwarming",
                            "capability" => "st.airConditionerFanMode",
                            "attribute" => "fanMode",
                            "value" => extractOverride($overrides, $id, $side_name, 'footwarmingMode') ?: $side['footwarmingMode'],
                        ],
                        // Footwarming possible values
                        [
                            "component" => "footwarming",
                            "capability" => "st.airConditionerFanMode",
                            "attribute" => "supportedAcFanModes",
                            "value" => array_values($FOOTWARM_MODES),
                        ],
                    ]
                ];
            }
        }
    }
} // End function parseBedState

/**
 * A helper function used to do the key checks and lookup of a particular
 * bedId+side+key_name combo from an $overrides assoc array.
 */
function extractOverride($overrides, $bedId, $side, $key)
{
    if (array_key_exists($bedId, $overrides)) {
        if (array_key_exists($side, $overrides[$bedId])) {
            if (array_key_exists($key, $overrides[$bedId][$side])) {
                return $overrides[$bedId][$side][$key];
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
    if ($arrayTimeAndTemp['temp'] == SleepyqPHP::FOOTWARM_OFF) {
        return FOOTWARM_TEMP_OFF;
    }
    return $FOOTWARM_TEMPS[$arrayTimeAndTemp['temp']] . FOOTWARM_MODE_DELIM . $FOOTWARM_TIMES[$arrayTimeAndTemp['time']];
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
function getBeds($withFoundationFeatures = false): array
{
    $client = getClient();
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
    // Get each bed's current sides' statuses
    $statuses = $client->getBedSidesStatuses();
    foreach ($bedIds as $bedId) {
        $sideFaves = $client->getBedFaves($bedId);
        $sidePresets = $client->getBedSidePresets($bedId);
        $foundationFeatures = $client->getFoundationFeatures($bedId);
        $foundationFootwarming = null;
        if ($foundationFeatures->hasFootWarming) {
            $foundationFootwarming = $client->getFoundationFootwarming($bedId);
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
                    if ($sideStatus) {
                        $data[$bedId]['sides'][$side]['sleepNumber'] = $sideStatus['sleepNumber'];
                        $data[$bedId]['sides'][$side]['fave'] = $sideFaves[$side];
                        $data[$bedId]['sides'][$side]['footwarmingAvailable'] = $foundationFeatures->hasFootWarming;
                        $data[$bedId]['sides'][$side]['footwarmingMode'] = ($foundationFootwarming != null) ? mapModeToFootWarming($foundationFootwarming->sides[$side]) : FOOTWARM_TEMP_OFF; // Array with 'temp' and 'time' keys
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
        if (in_array('temp', $keys) && in_array('time', $keys)) {
            $client->setFoundationFootwarming($side, $command['temp'], $command['time'], $id);
        }
    }
    return $ids;
} // End function sendBedCommands


/**
 * Get the SleepyqPHP client
 * @return SleepyqPHP object
 */
function getClient(): SleepyqPHP
{
    global $sleepyq, $authentication;

    $error = null;
    $success = true;
    $username = $password = '';

    if ($sleepyq == null) {

        // Single-account configuration
        if (SINGLE_ACCOUNT_CONFIG) {
            $username = SN_USER;
            $password = SN_PASS;
        }

        /**
         * Look up user by token
         * Get user's username and password (decrypted). Use these values to authenticate against SleepNumber API
         */
        else {
            $token = $authentication[AUTHENTICATION_TOKEN];
            require_once __DIR__ . '/oauth/server.php';
            $at = $server->getStorage(STORAGE_NAME)->getAccessToken($token);
            $userId = $at['user_id'];
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
            $success = false;
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
 * @param string $userId
 * @return string|null
 */
function getAccessToken(string $userId): string|null
{
    $accessCode = null;
    // Start by getting the latest code row for the user from the database
    $codeRow = getCodeByUserId($userId);
    if ($codeRow) {
        $codeId = $codeRow[ID];
        $tokenUri = $codeRow[TOKEN_URI];
        // We got a code, so now look up the newest token for that code
        $tokenRow = getTokenByCodeId($codeId);
        if ($tokenRow) {
            // We got a token row
            $expiresAt = new DateTime($tokenRow[EXPIRES_AT]);
            $now = new DateTime();
            if ($now >= $expiresAt) {
                // The latest token has expired, so we need to get a new one using the refresh token
                $refreshToken = $tokenRow[REFRESH_TOKEN];
                $accessCode = makeAccessTokenRequest($tokenUri, $codeId, $refreshToken);
            } else {
                // The latest token is still valid, so just return it
                $accessCode = $tokenRow[ACCESS_TOKEN];
            }
        }
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
