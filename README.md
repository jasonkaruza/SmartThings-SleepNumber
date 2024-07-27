# SmartThingsSleepNumber
Integration for SleepNumber with Samsung SmartThings. This makes use of [SleepyqPHP](https://github.com/jasonkaruza/SleepyqPHP) and a modified fork of [oauth2-server-php](https://github.com/jasonkaruza/oauth2-server-php). The latter requires setting up a database wherever the code will be executed for the Oauth functionality to work with Samsung.

The SmartThings device for the SleepNumber bed is a [Cloud Connected Device](https://developer.smartthings.com/devices/cloud-connected). This repo facilitates acting as a proxy between SmartThings and SleepNumber, allowing Samsung to make requests about the beds in your SleepNumber account, this code calling the SleepNumber API via SleepyqPHP, and giving the response back to SmartThings in the structure that's required for each of the required request [Interaction Types](https://developer.smartthings.com/docs/devices/cloud-connected/interaction-types/).

The [capabilities](https://developer.smartthings.com/docs/devices/capabilities/capabilities-reference) that make up the SmartThings device are Production Capabilities, chosen because the had the flexibility needed to override things like the SleepNumber bed's base position or footwarming modes. [Custom Capbililties](https://developer.smartthings.com/docs/devices/capabilities/custom-capabilities) would be optimal, but they are not currently supported with Works with SmartThings certification.

When setting up the Cloud Connector for the device in the Developers Portal, App Credentials are provided by SmartThings, including:
- App ID
- Client ID
- Client Secret (don't lose this)

These values will need to be configured within `oauth/settings.php` in the designated `define()`s. Additionally, under App Details->Device Cloud Credentials you will need to populate the following values:
- SleepNumber Client ID (add to `oauth/settings.php`) - `SN_CLIENT_ID`
- SleepNumber Client Secret (add to `oauth/settings.php`) - `SN_CLIENT_SECRET`
- SmartThings Client ID (add to `oauth/settings.php`) - `ST_CLIENT_ID`
- SmartThings Client Secret (add to `oauth/settings.php`) - `ST_CLIENT_SECRET`
- Authorization URI (this should be the full web-accessible path to the `oauth/authorize.php` file. e.g. https://www.yourdomain.com/st/oauth/authorize.php)
- Token URI (this should be the full web-accessible path to the `oauth/token.php` file. e.g. https://www.yourdomain.com/st/oauth/token.php)
- Webhook URL (choose Webhook as the hosting type, and set the value to the full web-accessible path to the `sn.php` file. e.g. https://www.yourdomain.com/st/sn.php)
- CAPTCHA Site key (add to `oauth/settings.php`) if using a Google Recaptcha in `oauth/authorize.php`
- SINGLE_ACCOUNT_CONFIG defaults to `true` and requires hard-coded SleepNumber credentials to be populated in the `SN_USER` and `SN_PASS` `define`s within `oauth/settings.php` for when operating in a single-account scenario (dedicated for just you). If you are setting up a server for multiple people to authenticate against, their credentials will be stored in the database (encrypted) and requires that SINGLE_ACCOUNT_CONFIG be set to `false`.

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
- Clone this repo and the submodules `git clone --recurse-submodules https://github.com/jasonkaruza/SmartThingsSleepNumber.git`
- Initialize the [oauth2-server-php](https://github.com/jasonkaruza/oauth2-server-php) database by creating [all of the needed tables](https://bshaffer.github.io/oauth2-server-php-docs/cookbook/) and a user that will be able to connect to it from PHP.
  - CREATE TABLE `oauth_access_tokens` (
  `access_token` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `client_id` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL,
  `expires` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `scope` varchar(4000) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`access_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
  - CREATE TABLE `oauth_authorization_codes` (
  `authorization_code` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `client_id` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL,
  `redirect_uri` varchar(2000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `expires` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `scope` varchar(4000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `id_token` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `code_challenge` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `code_challenge_method` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`authorization_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
  - CREATE TABLE `oauth_clients` (
  `client_id` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `client_secret` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL,
  `redirect_uri` varchar(2000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `grant_types` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL,
  `scope` varchar(4000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_id` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
  - CREATE TABLE `oauth_jwt` (
  `client_id` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `subject` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL,
  `public_key` varchar(2000) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
  - CREATE TABLE `oauth_refresh_tokens` (
  `refresh_token` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `client_id` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL,
  `expires` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `scope` varchar(4000) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`refresh_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
  - CREATE TABLE `oauth_scopes` (
  `scope` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `is_default` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`scope`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
  - CREATE TABLE `oauth_users` (
  `username` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL,
  `first_name` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL,
  `last_name` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT NULL,
  `scope` varchar(4000) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
- Rename `oauth/example.settings.php` to `oauth/settings.php` and update all of the `Required` define'd variables
- The SmartThings Developers account is located at https://developer.smartthings.com/workspace/projects
- Once you have your SmartThings Client ID and Secret, insert them into the `oauth_clients` table along with the `redirect_uri`. The Client ID should match the value of `SN_CLIENT_ID` and Client Secret should match `SN_CLIENT_SECRET` `define()` values from `oauth/settings.php`.
- You will also need to set an environment variable using the value for the `ENCRYPTION_ENV_VARIABLE_NAME` define (e.g. `SN_ENCRYPTION_PEPPER`) as the NAME of the environment variable, and set it to some other random string for extra security when encrypting/decrypting passwords in the database. Depending on if your code is running on Windows, Linux, MacOS, you'll need to set the environment variable differently and ensure that when the code us run on the server by a web request that the variable is correctly loaded for the code to use. Also keep in mind the length of the key that is used with the selected encryption cipher. E.g. with AES 256, the max length is 32 characters, so use half for the key in the settings file, and half for your pepper environment variable.

# Tips and Setup Instructions
In the SmartThings app, enable Developer Mode under Menu->Settings gear->Push and hold About SmartThings for 5 seconds->scroll down a bit and see Developer Mode toggle->toggle it on and restart the app.

To add a test device go to Devices tab->+ in top right->Add device->Partner Devices->My Testing Devices->Select the device

When making updates to the Device Profile JSON via https://developer.smartthings.com/workspace/deviceprofiles/edit, re-add the Test Device through the SmartThings app, kill the app, and give it a few minutes for the updates to appear.

To change the DetailView label, put it INSIDE the `values` array's object.

If you encounter exceptions when trying to save a Refresh or Access token that has a `0` expiry value (it may evaluate to 1969-12-31 16:00:00), make sure that your MySQL database `my.cnf`/`my.ini` config does not have `NO_ZERO_IN_DATE,NO_ZERO_DATE,STRICT_TRANS_TABLES` in the `sql-mode`. You can check this by running `SHOW VARIABLES LIKE 'sql_mode';` from a MySQL client.

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