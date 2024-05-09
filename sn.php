<?php
// sn2.php
require_once "./oauth/settings.php";
require_once "./SleepyqPHP/sleepyq.php";

$headers = getallheaders();
$authentication = $requestId = null;

// Accessors within response payloads
define('AUTHENTICATION', 'authentication');
define('COMMAND_REQUEST', 'commandRequest');
define('COMMAND_RESPONSE', 'commandResponse');
define('DEVICES', 'devices');
define('DEVICE_STATE', 'deviceState');
define('DISCOVERY_REQUEST', 'discoveryRequest');
define('DISCOVERY_RESPONSE', 'discoveryResponse');
define('EXTERNAL_DEVICE_ID', 'externalDeviceId');
define('GRANT_CALLBACK_ACCESS', 'grantCallbackAccess');
define('HEADERS', 'headers');
define('INTEGRATION_DELETED', 'integrationDeleted');
define('INTERACTION_RESULT', 'interactionResult');
define('INTERACTION_TYPE', 'interactionType');
define('REQUEST_ID', 'requestId');
define('STATE_REFRESH_REQUEST', 'stateRefreshRequest');
define('STATE_REFRESH_RESPONSE', 'stateRefreshResponse');

// SmartThings switch values
define('SWITCH_ON', 'on');
define('SWITCH_OFF', 'off');

// Bed Preset values
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
        exit;
    }

    // Extract out the headers and authentication from the request
    logtext("Got JSON object:" . print_r($object, true));
    $headers = $object[HEADERS];
    $authentication = $object[AUTHENTICATION];
    $requestId = $headers[REQUEST_ID];

    // If present, set the $devices variable
    if (array_key_exists(DEVICES, $object)) {
        $devices = $object[DEVICES];
    }

    logtext("Request ID: $requestId");
    logtext("New Headers: " . print_r($headers, true));
    logtext("Authentication: " . print_r($authentication, true));
    logtext("Body JSON: $body");
}

// else not JSON
else {
    // If calling via CLI, it is for testing purposes
    /**
     * Test command samples:
     * - php sn.php --itype=discoveryRequest
     * - php sn.php --itype=stateRefreshRequest --ids=<bed_id>:right
     * - php sn.php --itype=commandRequest --devices='[{"externalDeviceId":"<bed_id>:left","deviceCookie":[],"commands":[{"component":"main","capability":"st.mode","command":"setAirConditionerMode","arguments":["Flat"]},{"component":"main","capability":"st.level","command":"setLevel","arguments":[80]}]},{"externalDeviceId":"<bed_id>:right","deviceCookie":[],"commands":[{"component":"main","capability":"st.mode","command":"setMode","arguments":["Flat"]},{"component":"main","capability":"st.level","command":"setLevel","arguments":[85]}]}]'
     * - php sn.php --itype=commandRequest --devices='[{"externalDeviceId":"<bed_id>:right","deviceCookie":{"updatedcookie":"12345"},"commands":[{"component":"main","capability":"st.switch","command":"on","arguments":[]}]}]'
     * - php sn.php --itype=commandRequest --devices='[{"externalDeviceId":"<bed_id>:right","deviceCookie":{"updatedcookie":"12345"},"commands":[{"component":"footwarming","capability":"st.airConditionerFanMode","command":"setFanMode","arguments":["Low - 30 min"]}]}]'
     * - php sn.php --itype=commandRequest --devices='[{"externalDeviceId":"<bed_id>:right","deviceCookie":{"updatedcookie":"12345"},"commands":[{"component":"footwarming","capability":"st.airConditionerFanMode","command":"setFanMode","arguments":["Off"]}]}]'
     */
    if (php_sapi_name() == 'cgi-fcgi' || php_sapi_name() == 'cli') {
        $shortopts = '';
        $longopts = array(
            "itype:",     // Required value
            "ids::",    // Optional value
            "devices::", // Optional value
        );
        $options = getopt($shortopts, $longopts);
        if (!$options['itype']) {
            exit;
        }

        $headers = [
            "schema" => "st-schema",
            "version" => "1.0",
            "interactionType" => $options['itype'],
            "requestId" => "abc-123-456"
        ];
        $authentication = [
            "tokenType" => "Bearer",
            "token" => "token received during oauth from partner"
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
        if (array_key_exists('devices', $options)) {
            $devices = json_decode($options['devices'], true);
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
            $response = grantCallbackAccess($requestId, $authentication);
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
    exit;
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
function grantCallbackAccess($reqId, $auth)
{
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
                $output[DEVICE_STATE][] = [
                    EXTERNAL_DEVICE_ID => $id . DEVICE_ID_DELIM . $side_name,
                    "deviceCookie" => [],
                    "states" => [
                        // Foundation current preset mode
                        [
                            "component" => "main",
                            "capability" => "st.airConditionerMode",
                            "attribute" => "airConditionerMode",
                            "value" => extractOverride($overrides, $id, $side_name, 'mode') ?: $BED_PRESETS[$side['preset']],
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
                            "value" => extractOverride($overrides, $id, $side_name, 'number') ?: $side['sleepnumber'],
                        ],
                        // Switch to indicate if in Favorite configuration, or not
                        [
                            "component" => "main",
                            "capability" => "st.switch",
                            "attribute" => "switch",
                            "value" => extractOverride($overrides, $id, $side_name, 'fave') ?: ((($side['preset'] == FAVORITE) && ($side['sleepnumber'] == $side['fave']))
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
                        $data[$bedId]['sides'][$side]['sleepnumber'] = $sideStatus['sleepnumber'];
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
    global $sleepyq;
    // Add the username and password
    $sargs[BED_USER] = SN_USER;
    $sargs[BED_PASSWORD] = SN_PASS;
    $error = null;
    $success = true;

    if ($sleepyq == null) {
        try {
            $sleepyq = new SleepyqPHP(SN_USER, SN_PASS);
            $sleepyq->login();
        } catch (Exception $v) {
            $error = $v;
            $success = false;
        }
    }
    return $sleepyq;
}
