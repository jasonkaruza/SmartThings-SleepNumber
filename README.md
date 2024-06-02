# SmartThingsSleepNumber
Integration for SleepNumber with Samsung SmartThings. This makes use of [SleepyqPHP](https://github.com/jasonkaruza/SleepyqPHP) and [oauth2-server-php](https://github.com/jasonkaruza/oauth2-server-php). The latter requires setting up a database wherever the code will be executed for the Oauth functionality to work with Samsung.

The SmartThings device for the SleepNumber bed is a [Cloud Connected Device](https://developer.smartthings.com/devices/cloud-connected). This repo facilitates acting as a proxy between SmartThings and SleepNumber, allowing Samsung to make requests about the beds in your SleepNumber account, this code calling the SleepNumber API via SleepyqPHP, and giving the response back to SmartThings in the structure that's required for each of the required request [Interaction Types](https://developer.smartthings.com/docs/devices/cloud-connected/interaction-types/).

The [capabilities](https://developer.smartthings.com/docs/devices/capabilities/capabilities-reference) that make up the SmartThings device are Production Capabilities, chosen because the had the flexibility needed to override things like the SleepNumber bed's base position or footwarming modes. [Custom Capbililties](https://developer.smartthings.com/docs/devices/capabilities/custom-capabilities) would be optimal, but they are not currently supported with Works with SmartThings certification.

When setting up the Cloud Connector for the device in the Developers Portal, App Credentials are provided by SmartThings, including:
- App ID
- Client ID
- Client Secret (don't lose this)

These values will need to be configured within `oauth/settings.php` in the designated `define()`s. Additionally, under App Details->Device Cloud Credentials you will need to populate the following values:
- Client ID (add to `oauth/settings.php`)
- Client Secret (add to `oauth/settings.php`)
- Authorization URI (this should be the full web-accessible path to the `oauth/authorize.php` file. e.g. https://www.yourdomain.com/st/oauth/authorize.php)
- Token URI (this should be the full web-accessible path to the `oauth/token.php` file. e.g. https://www.yourdomain.com/st/oauth/token.php)
- Webhook URL (choose Webhook as the hosting type, and set the value to the full web-accessible path to the `sn.php` file. e.g. https://www.yourdomain.com/st/sn.php)

For the Device Profile, you will need to create a new Profile (I chose a Heated Mattress Pad Device Type, but it doesn't really matter because there is no Smart Bed option) and capture the Device Profile ID to be added to `oauth/settings.php`. The profile should include two components:
- main (which contains three capabilities)
  - Switch Level
  - Switch
  - Air Conditioner Mode
- footwarming
  - Presence Sensor
  - Air Conditioner Fan Mode

For the UI Display, you will choose to "Customize through device configuration file" and upload the `deviceConfigST2_1367c833-8aa0-487b-81c5-cf05302108fb.json` file.

# Repo installation and setup
- Rename `oauth/example.settings.php` to `oauth/settings.php` and update all of the `Required` define'd variables
- The SmartThings Developers account is located at https://developer.smartthings.com/workspace/projects
- You will also need to set an environment variable using the value for the `ENCRYPTION_ENV_VARIABLE_NAME` define (e.g. `SN_ENCRYPTION_PEPPER`) as the NAME of the environment variable, and set it to some other random string for extra security when encrypting/decrypting passwords in the database. Depending on if your code is running on Windows, Linux, MacOS, you'll need to set the environment variable differently and ensure that when the code us run on the server by a web request that the variable is correctly loaded for the code to use. Also keep in mind the length of the key that is used with the selected encryption cipher. E.g. with AES 256, the max length is 32 characters, so use half for the key in the settings file, and half for your pepper environment variable.

# Tips and Setup Instructions
In the SmartThings app, enable Developer Mode under Menu->Settings gear->Push and hold About SmartThings for 5 seconds->scroll down a bit and see Developer Mode toggle->toggle it on and restart the app.

To add a test device go to Devices tab->+ in top right->Add device->Partner Devices->My Testing Devices->Select the device

When making updates to the Device Profile JSON via https://developer.smartthings.com/workspace/deviceprofiles/edit, re-add the Test Device through the SmartThings app, kill the app, and give it a few minutes for the updates to appear.

To change the DetailView label, put it INSIDE the `values` array's object.

# Helpful links
- https://api.smartthings.com/v1/presentation?manufacturerName=f2sv&presentationId=ST_#
- https://api.smartthings.com/v1/devices?manufacturerName=SmartThingsCommunity&presentationId=ST_<id>&deviceId=<dev_id>
- https://my.smartthings.com/ - Web dashboard
- https://developer.smartthings.com/workspace/deviceprofiles - Dev area for my device
- https://developer.smartthings.com/docs/api/public/ - API docs

# [CLI Commands](https://github.com/SmartThingsCommunity/smartthings-cli?tab=readme-ov-file#smartthings-deviceprofiles-id)
- `smartthings deviceprofiles:publish <profileId>` - Publish a device profile (can't be edited after)
- `smartthings presentation ST_<id> f2sv -j`
- `smartthings presentation:device-config ST_e976b515-9b04-46a2-aa3e-d7a6a06c4cba 0AJJ -j -o=newDevConfigPres.json` - Clone a device profile