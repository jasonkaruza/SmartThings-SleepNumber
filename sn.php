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

// Bed Preset values
define('FAVORITE', 1);
define('READ', 2);
define('WATCH_TV', 3);
define('FLAT', 4);
define('ZERO_G', 5);
define('SNORE', 6);
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

# Base foot-warming
define('FOOTWARM_OFF', 0);
define('FOOTWARM_LOW', 31);
define('FOOTWARM_MEDIUM', 57);
define('FOOTWARM_HIGH', 72);
$FOOTWARM_TEMPS = [
    FOOTWARM_OFF => 'Off',
    FOOTWARM_LOW => 'Low',
    FOOTWARM_MEDIUM => 'Medium',
    FOOTWARM_HIGH => 'High',
];

define('FOOTWARM_30', 30);
define('FOOTWARM_60', 60);
define('FOOTWARM_120', 120);
define('FOOTWARM_180', 180);
define('FOOTWARM_240', 240);
define('FOOTWARM_300', 300);
define('FOOTWARM_360', 360);
$FOOTWARM_TIMES = [
    FOOTWARM_30 => '30 min',
    FOOTWARM_60 => '1 hr',
    FOOTWARM_120 => '2 hrs',
    FOOTWARM_180 => '3 hrs',
    FOOTWARM_240 => '4 hrs',
    FOOTWARM_300 => '5 hrs',
    FOOTWARM_360 => '6 hrs',
];

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
     * Test commands: (DELETE ME)
     * - php sn2.php --itype=discoveryRequest
     * - php sn2.php --itype=stateRefreshRequest --ids=<id>:right
     * - php sn2.php --itype=commandRequest --devices='[{"externalDeviceId":"<id>:right","deviceCookie":{"updatedcookie":"12345"},"commands":[{"component":"main","capability":"st.switch","command":"on","arguments":[]}]}]'
     * - php sn2.php --itype=commandRequest --devices='[{"externalDeviceId":"<id>:left","deviceCookie":[],"commands":[{"component":"main","capability":"st.mode","command":"setMode","arguments":["Flat"]},{"component":"main","capability":"st.level","command":"setLevel","arguments":[80]}]},{"externalDeviceId":"<id>:right","deviceCookie":[],"commands":[{"component":"main","capability":"st.mode","command":"setMode","arguments":["Flat"]},{"component":"main","capability":"st.level","command":"setLevel","arguments":[85]}]}]'
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
logtext("RESPONSE: " . print_r($response, true));

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
    $beds = getBeds();

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
    global $BED_PRESETS;
    $idsAndSides = extractExternalDeviceIds($devices);
    $ids = array_keys($idsAndSides);
    $client = getClient();
    $beds = $client->getBedState($ids);

    $output = [
        "headers" => [
            "schema" => "st-schema",
            "version" => "1.0",
            "interactionType" => STATE_REFRESH_RESPONSE,
            "requestId" => $reqId
        ]
    ];
    $beds = $client->getBedState($ids);

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
                    $mode = mapModeToBedPreset($mode);
                    $snCommands[] = [
                        "id" => $bedId,
                        "side" => $side,
                        "mode" => $mode,
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
    global $BED_PRESETS;

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
                        [
                            "component" => "main",
                            "capability" => "st.airConditionerMode",
                            "attribute" => "airConditionerMode",
                            "value" => extractOverride($overrides, $id, $side_name, 'mode') ?: $BED_PRESETS[$side['preset']],
                        ],
                        [
                            "component" => "main",
                            "capability" => "st.airConditionerMode",
                            "attribute" => "supportedAcModes",
                            "value" => array_values($BED_PRESETS),
                        ],
                        [
                            "component" => "main",
                            "capability" => "st.switchLevel",
                            "attribute" => "level",
                            "value" => extractOverride($overrides, $id, $side_name, 'number') ?: $side['sleepnumber'],
                        ],
                        [
                            "component" => "main",
                            "capability" => "st.switch",
                            "attribute" => "switch",
                            "value" => extractOverride($overrides, $id, $side_name, 'fave') ?: ((($side['preset'] == FAVORITE) && ($side['sleepnumber'] == $side['fave'])) ? "on" : "off")
                        ]
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
 * @param $externalDeviceIds array of externalDeviceId values
 * @return array of stripped externalDeviceId values to the core SleepNumber Bed ID values
 **/
function stripSidesFromExternalDeviceIds($externalDeviceIds)
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


///////////////// SLEEPYQ FUNCTIONS /////////////////


/**
 * Get Beds
 */
function getBeds()
{
    $client = getClient();
    $beds = $client->beds();
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
 */
function getBedState($bedIds = [])
{
    $client = getClient();
    return $client->getBedState($bedIds);
} // End function getBedState

/**
 * Reset bed to favorite settings
 */
function setBedFavorites($bedIds)
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
 */
function resetBed($bedId)
{
    $client = getClient();
    $client->resetBed($bedId);
} // End function resetBed


/**
 * Execute commands to modify the bed settings
 * Commands {id => {side => {'id'/'side'/'mode'/'number'/'temp'/'timer'}}}
 * Returns a set of unique bed IDs updated
 */
function sendBedCommands($commands)
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
        if (in_array('temp', $keys) && in_array('timer', $keys)) {
            $client->setFoundationFootwarming($side, $command, $command['timer'], $id);
        }
    }
    return $ids;
} // End function sendBedCommands


/**
 * Get the SleepyqPHP client
 * @return SleepyqPHP object
 */
function getClient()
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
