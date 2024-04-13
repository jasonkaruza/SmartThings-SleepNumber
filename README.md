# SmartThingsSleepNumber
Integration for SleepNumber with Samsung SmartThings

# Tips and Setup Instructions

In the SmartThings app, enable Developer Mode under Menu->Settings gear->Push and hold About SmartThings for 5 seconds->scroll down a bit and see Developer Mode toggle->toggle it on and restart the app.

To add a test device go to Devices tab->+ in top right->Add device->Partner Devices->My Testing Devices->Select the device

When making updates to the Device Profile JSON via https://developer.smartthings.com/workspace/deviceprofiles/edit, re-add the Test Device through the SmartThings app, kill the app, and give it a few minutes for the updates to appear.

To change the DetailView label, put it INSIDE the `values` array's object.