# SmartThingsSleepNumber
Integration for SleepNumber with Samsung SmartThings

# Repo installation and setup
- Rename `oauth/example.settings.php` to `oauth/settings.php` and update all of the `Required` define'd variables

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