# Overview
In the past, there was an integration that allowed for controlling a [SleepNumber bed via SmartThings](https://community.smartthings.com/t/obsolete-sleep-number-sleepiq-smartthings-integration/169038), but after the SmartThings platform migration, that integration became obsolete. This project aims to pick up where that left off.

You have two options:
1. SIMPLEST: You can request an invite to the SmartThings integration by [direct-messaging me in the SmartThings community forum](https://community.smartthings.com/u/smrtdrmmr). This integration cannot be published officially within the SmartThings catalog because 1) I am not a corporation, and 2) I am not affiliated with SleepNumber.
> 
> [!CAUTION]
> Your SleepNumber credentials will be stored encrypted in a database using the code from this repository because (to my knowledge) SleepNumber does NOT use Oauth. Efforts have been made to protect your password by using two different secrets stored separately (see `oauth/example.settings.php`'s mentions of `SALT` and `PEPPER`). It is possible that your password could be exposed through malicious efforts, so by providing your credentials in the integration, you assume all risk. **DO NOT REUSE YOUR PASSWORD FOR SleepNumber WITH ANY OTHER WEBSITES.**

If you request an invite, it will take you into your SmartThings account and ask you if you want to Proceed. You will be asked to select your Home/Location that you want to add the integration to, then will take you to the login page for the integration, which will require you to enter your SleepNumber email and password to pull your bed(s) to be added to your SmartThings location.

2. SAFEST: You can create your own integration by creating a device in the SmartThings Developers console using the device profile in this repo, run the server-side PHP code on top of a SQL database, and follow the instructions for setting up your Cloud-Connected Device integration to point to wherever you are hosting your server code.


> [!CAUTION]
> **I am in no way affiliated with SleepNumber, this integration could break at any point. Use of the integration, whether by invite or by this repo's code, must be considered "as is", with all risks and liabilities assumed by yourself. The author, the author's host, and all other parties involved are not liable for any harm or problems incurred by use.**

If you have a bed with two sides, two separate Bed devices will be created. The Right side is from the perspective of being IN THE BED facing the foot and sleeping on the right side of the bed.

If you would like to utilize option 2, read on.

---------------------

# SmartThings SleepNumber
This integration for SleepNumber with Samsung SmartThings makes use of [SleepyqPHP](https://github.com/jasonkaruza/SleepyqPHP) and a modified fork of [oauth2-server-php](https://github.com/jasonkaruza/oauth2-server-php). The latter requires setting up a database wherever the code will be executed for the Oauth functionality to work with Samsung.

The SmartThings device for the SleepNumber bed is a [Cloud-Connected Device](https://developer.smartthings.com/devices/cloud-connected). This repo facilitates acting as a proxy between SmartThings and SleepNumber, allowing Samsung to make requests about the beds in your SleepNumber account, this code calling the SleepNumber API via SleepyqPHP, and giving the response back to SmartThings in the structure that's required for each of the required request [Interaction Types](https://developer.smartthings.com/docs/devices/cloud-connected/interaction-types/).

The [capabilities](https://developer.smartthings.com/docs/devices/capabilities/capabilities-reference) that make up the SmartThings device are Production Capabilities, chosen because they had the flexibility needed to override things like the SleepNumber bed's base position or footwarming modes. [Custom Capbililties](https://developer.smartthings.com/docs/devices/capabilities/custom-capabilities) would be optimal, but they are not currently supported with the "Works with SmartThings" certification.

## Server - Repo installation and setup
1. Clone this repo and the submodules `git clone --recurse-submodules git@github.com:jasonkaruza/SmartThingsSleepNumber.git`
2. Initialize the [oauth2-server-php](https://github.com/jasonkaruza/oauth2-server-php) database by creating [all of the needed tables](https://bshaffer.github.io/oauth2-server-php-docs/cookbook/) and a user that will be able to connect to it from PHP.
  ```
  CREATE TABLE `oauth_access_tokens` (
  `access_token` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `client_id` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL,
  `expires` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `scope` varchar(4000) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`access_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
  
  CREATE TABLE `oauth_authorization_codes` (
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

CREATE TABLE `oauth_clients` (
  `client_id` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `client_secret` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL,
  `redirect_uri` varchar(2000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `grant_types` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL,
  `scope` varchar(4000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_id` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

CREATE TABLE `oauth_jwt` (
  `client_id` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `subject` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL,
  `public_key` varchar(2000) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

CREATE TABLE `oauth_refresh_tokens` (
  `refresh_token` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `client_id` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL,
  `expires` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `scope` varchar(4000) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`refresh_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

CREATE TABLE `oauth_scopes` (
  `scope` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `is_default` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`scope`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

CREATE TABLE `oauth_users` (
  `username` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL,
  `first_name` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL,
  `last_name` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT NULL,
  `scope` varchar(4000) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
```
3. Rename `oauth/example.settings.php` to `oauth/settings.php` and update all of the `Required` `define()`'d variables as indicated below...

## SmartThings Developer Console - Create a New project
1. Go to https://developer.smartthings.com/workspace/projects/ and select `+ NEW PROJECT`.
2. Select Device Integration
3. Select SmartThings Cloud Connector
4. Give it a name such as SleepNumber Bed
5. Select Register App
6. Choose WebHook Endpoint
7. Enter the target URL. This should be the full https:// URL path to wherever this repo's sn.php will be hosted
8. Create the following values (will need to be configured within `oauth/settings.php` in the designated `define()`s):
   1. Client ID (SleepNumber Client ID - add to `oauth/settings.php` as `SN_CLIENT_ID`)
   2. Client Secret (SleepNumber Client Secret - add to `oauth/settings.php` as `SN_CLIENT_SECRET`)
   3. Authorization URI (this should be the full web-accessible path to the `oauth/authorize.php` file. e.g. https://www.yourdomain.com/st/oauth/authorize.php)
   4. Token URI (this should be the full web-accessible path to the `oauth/token.php` file. e.g. https://www.yourdomain.com/st/oauth/token.php)
   5. Alert Notification Email (some email that you want to receive notifications at)
   6. Oauth scopes (leave blank)
   7. Android App Link (leave blank)
   8. iOS App Link (leave blank)
9. Next, give the app a display name such as SleepNumber Integration and a logo. You can use the supplied `logo.png` from this repo if you'd like.
   1.  Hit Save
10. From there, App Credentials will be provided by SmartThings, including (DON'T LOSE THESE):
    1.  Client ID (SmartThings Client ID - add to `oauth/settings.php` as `ST_CLIENT_ID`)
    2.  Client Secret (SmartThings Client Secret - add to `oauth/settings.php` as `ST_CLIENT_SECRET`)
11. Select Deploy to Test
12. Go to Device Profile and choose Option 2->Add a Device Profile / Create a New One
13. Enter a Device Profile Name like SleepNumber Integration
14. Enter a Description if you'd like
15. Choose a Device Type such as `Bed`
16. Next, choose + ADD CAPABILITY and add the following capabilities to the `main` component using the search and `+` button on the matching result:
    1.  Switch Level
    2.  Switch
    3.  Air Conditioner Mode
17. Click + ADD ANOTHER COMPONENT, and name it `footwarming`, then add the following capabilities:
    1.  Presence Sensor
    2.  Air Conditioner Fan Mode
18. Next, under UI Display, choose Customize through Device Configuration File.
19. Click the Download link for the pre-built Device Configuration .json file. It should be called something like `deviceConfigST_1367c833-8aa0-487b-81c5-cf05302108fb.json`. Download the file to your computer.
20. Open that .json file in a text editor and copy the values for the `mnmn` (e.g. `z3dd`) and `vid` (e.g. `"ST_1367c833-8aa0-487b-81c5-cf05302108fb`) keys. Set those aside in another file somewhere.
21. Open the  `deviceConfigST2_1367c833-8aa0-487b-81c5-cf05302108fb.json` file in this repo and copy all contents, replacing the contents of your downloaded .json file with those copied contents, then replace the values for the `mnmn` and `vid` keys with the original values that you had downloaded, saved and set aside. Save the results.
22. Upload the updated device profile .json file by clicking where it says `Drop a file here or browse file` and choose that .json file.
23. Click Save and it may warn you that you are going to overwrite the device profile, but go ahead and proceed. There should be no errors created.
24. That should do it for the SmartThings Developers console.

## Other Settings
Additionally, you will need to populate the following values in `oauth/settings.php`:
- `DEVICE_PROFILE_ID` - Device Profile ID - The `vid` value from your device profile .json file (e.g. `ST_1367c833-8aa0-487b-81c5-cf05302108fb`)
- `SINGLE_ACCOUNT_CONFIG` defaults to `true` and requires hard-coded SleepNumber credentials to be populated in the `SN_USER` and `SN_PASS` `define()`s within `oauth/settings.php` for when operating in a single-account scenario (dedicated for **just you**). If you are setting up a server for multiple people to authenticate against, their credentials will be stored in the database (encrypted) and requires that `SINGLE_ACCOUNT_CONFIG` be set to `false`.
- `CAPTCHA_SITE_KEY` - CAPTCHA Site key (add to `oauth/settings.php`) if using a Google Recaptcha in `oauth/authorize.php`
- `ENCRYPTION_KEY` - This is the SALT. Some random string up to 32 characters (depending on the encryption method and if using a PEPPER)

# Initializing the Database Values
1. Once you have your SmartThings Client ID and Secret, insert them into the `oauth_clients` table along with the `redirect_uri`. The Client ID should match the value of `SN_CLIENT_ID` and Client Secret should match `SN_CLIENT_SECRET` `define()` values from `oauth/settings.php`
2. You will also need to set an environment variable using the value for the `ENCRYPTION_ENV_VARIABLE_NAME` define (e.g. `SN_ENCRYPTION_PEPPER`) as the NAME of the environment variable, and set it to some other random string for extra security when encrypting/decrypting passwords in the database. Depending on if your code is running on Windows, Linux, MacOS, you'll need to set the environment variable differently and ensure that when the code is run on the server by a web request that the variable is correctly loaded for the code to use. Also keep in mind the length of the key that is used with the selected encryption cipher. E.g. with AES 256, the max length is 32 characters, so use half for the key in the settings file, and half for your pepper environment variable.

# SmartThings App (Android) Device Adding
1. In the SmartThings app, enable Developer Mode under Menu->Settings gear->Push and hold About SmartThings for 5 seconds->scroll down a bit and see Developer Mode toggle->toggle it on and restart the app.
2. To add a test device go to Devices tab->+ in top right->Add device->Partner Devices->My Testing Devices->Select the device

# Other Tips and Troubleshooting
- When making updates to the Device Profile JSON via https://developer.smartthings.com/workspace/deviceprofiles/edit, re-add the Test Device through the SmartThings app, kill the app, and give it a few minutes for the updates to appear.
- To change the DetailView label, put it INSIDE the `values` array's object.
- If you encounter exceptions when trying to save a Refresh or Access token that has a `0` expiry value (it may evaluate to 1969-12-31 16:00:00), make sure that your MySQL database `my.cnf`/`my.ini` config does not have `NO_ZERO_IN_DATE,NO_ZERO_DATE,STRICT_TRANS_TABLES` in the `sql-mode`. You can check this by running `SHOW VARIABLES LIKE 'sql_mode';` from a MySQL client.

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