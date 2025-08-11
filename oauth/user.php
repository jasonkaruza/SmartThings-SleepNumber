<?php
// include our OAuth2 Server object (and loader)
require_once __DIR__ . '/server.php';
$request = OAuth2\Request::createFromGlobals();
$response = new OAuth2\Response();
$timezone_identifiers = DateTimeZone::listIdentifiers();

/**
 * We need to collect the credentials and verify they work before storing them and their settings.
 */
$email = $password = $startTime = $endTime = $timeZone = '';
$problem = '';
$errorMessage = 'There was a problem with your email/password. Please try again.';

// Validation if submitted
if (isset($_POST['action'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $startTime = $_POST['start_time'];
    $endTime = $_POST['end_time'];
    $timeZone = $_POST['timezone'];
    $startTimeParsed = $endTimeParsed = null;

    // Take username and password. Validate username is email and sanitize password.
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL); // Just in case
        $problem = $errorMessage;
    } else if (!strlen($password)) {
        $problem = $errorMessage;
    }

    // Get the timezone and start/end times, validate them, and ensure that the delta sleep time isn't more than 16 hours
    if (!strlen($problem) && (!empty($timeZone) || !empty($startTime) || !empty($endTime))) {
        if (!in_array($timeZone, $timezone_identifiers)) {
            $problem = "Please select a valid timezone.";
        } else {
            $startTimeParsed = DateTime::createFromFormat('H:i', $startTime);
            $endTimeParsed = DateTime::createFromFormat('H:i', $endTime);
            if (!$startTimeParsed || !$endTimeParsed || $startTimeParsed === false || $endTimeParsed === false) {
                $problem = "Please enter valid start and end times.";
            } else if ($endTimeParsed->diff($startTimeParsed)->h >= 16) {
                $problem = "Start and end times cannot be more than 16 hours apart.";
            }
        }
    }

    if (!strlen($problem)) {
        // Authenticate it against SleepNumber API to confirm valid
        require_once "../SleepyqPHP/sleepyq.php";
        try {
            $sleepyq = new SleepyqPHP($email, $password);
            $sleepyq->login();
        } catch (Exception $v) {
            $problem = $errorMessage;
        }
    }

    if (!strlen($problem)) {
        require_once "../db.php";
        $DB = new Database(DB_HOST, DB_NAME, DB_USER, DB_PASS);

        /**
         * If valid, save their settings in the database
         */
        $insertData = [
            'user_id' => $email,
            'sleep_start_time' => $startTime ? $startTimeParsed->format('Y-m-d H:i:s') : null,
            'sleep_end_time' => $endTime ? $endTimeParsed->format('Y-m-d H:i:s') : null,
            'timezone' => $timeZone ?: null,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $updateData = [
            'user_id' => $email,
            'sleep_start_time' => $startTime ? $startTimeParsed->format('Y-m-d H:i:s') : null,
            'sleep_end_time' => $endTime ? $endTimeParsed->format('Y-m-d H:i:s') : null,
            'timezone' => $timeZone ?: null,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        try {
            // If the user already exists, update their settings; otherwise, insert a new record
            if (!$DB->upsert('st_user_settings', $insertData, $updateData)) {
                $problem = "There was a problem saving your settings. Please try again.";
            }
        } catch (Exception $e) {
            $problem = "Database error: " . $e->getMessage();
        }
    }
} //end if form posted

// display an authorization form
if (empty($_POST) || strlen($problem)) {
?>
    <html>

    <head>
        <style type='text/css'>
            html,
            body {
                height: 100%;
                margin: 0;
                padding: 20;
                box-sizing: border-box;
            }

            *,
            *:before,
            *:after {
                box-sizing: border-box;
            }


            .container {
                max-width: 900px;
                width: 100%;
                margin: 40px auto 0 auto;
                padding: 2.5em 2em;
                background: #fff;
                border-radius: 10px;
                box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
                position: static;
                text-align: center;
            }

            form {
                width: 100%;
            }

            .input,
            .em2,
            select {
                display: block;
                width: 100%;
                max-width: 100%;
                margin-bottom: 1.2em;
                font-size: 1.4em;
                padding: 0.8em 1em;
                border: 1.5px solid #888;
                border-radius: 5px;
                background: #fafbfc;
            }

            .bigbutton {
                width: 100%;
                font-size: 2em;
                padding: 0.7em 0;
                border-radius: 5px;
            }

            h1 {
                font-size: 2em;
                margin-bottom: 1em;
                text-align: center;
            }

            h2 {
                font-size: 1.2em;
                color: red;
                text-align: center;
            }

            .error {
                color: red;
                text-align: center;
                font-size: 1.2em;
            }

            .g-recaptcha>div>div {
                margin: 10px auto !important;
                text-align: center;
                width: auto !important;
                height: auto !important;
            }

            @media (max-width: 600px) {
                .container {
                    max-width: 98vw;
                    padding: 1em 0.5em;
                }

                h1 {
                    font-size: 1.1em;
                }

                .input,
                .em2,
                select {
                    font-size: 1.1em;
                    padding: 0.7em 0.5em;
                }

                .em3 {
                    font-size: 1.0em;
                    padding: 0.7em 0.5em;
                }

                .bigbutton {
                    font-size: 1.1em;
                    padding: 0.6em 0;
                }
            }
        </style>
        <?php
        // If doing a CAPTCHA. When testing locally, you may need to set this value to '' in order for the form to submit.
        if (strlen(CAPTCHA_SITE_KEY)) {
        ?>
            <!-- START CAPTCHA -->
            <script src="https://www.google.com/recaptcha/api.js"></script>
        <?php } // END CAPTCHA 
        ?>

        <script type="text/javascript">
            function toggleFormMessage(type = "error", hide = false) {
                document.getElementById(`form-${type}`).style.display = hide ?
                    "none" :
                    "inherit";
            }

            function onSuccess() {
                toggleFormMessage("error", true);
            }

            function onError() {
                toggleFormMessage("error");
            }

            <?php
            // If doing a CAPTCHA. When testing locally, you may need to set this value to '' in order for the form to submit.
            if (strlen(CAPTCHA_SITE_KEY)) {
            ?>
                // CAPTCHA
                let isRecaptchaValidated = false;

                function toggleRecaptchaFormMessage(type = "error", hide = false) {
                    document.getElementById(`recaptcha-form-${type}`).style.display = hide ?
                        "none" :
                        "inherit";
                }

                function onRecaptchaSuccess() {
                    isRecaptchaValidated = true;
                }

                function onRecaptchaError() {
                    toggleRecaptchaFormMessage("error");
                    toggleRecaptchaFormMessage("success", true);
                }

                function onRecaptchaResponseExpiry() {
                    onRecaptchaError();
                }
            <?php } // END CAPTCHA 
            ?>

            function formSubmit() {
                if (document.getElementById('email').value == "" || document.getElementById('password').value == "") {
                    onError();
                    return false;
                } else {
                    onSuccess();
                }

                <?php
                // If doing a CAPTCHA. When testing locally, you may need to set this value to '' in order for the form to submit.
                if (strlen(CAPTCHA_SITE_KEY)) {
                ?>
                    // captcha failure
                    if (!isRecaptchaValidated) {
                        toggleRecaptchaFormMessage("error");
                        toggleRecaptchaFormMessage("success", true);
                        return false;
                    }

                    // captcha success
                    toggleRecaptchaFormMessage("error", true);
                    toggleRecaptchaFormMessage("success");
                <?php } // END CAPTCHA 
                ?>
                document.getElementById('auth-form').submit();
                return true;
            }

            function clearSettings() {
                document.getElementById('start_time').value = '';
                document.getElementById('end_time').value = '';
                document.getElementById('timezone').value = '';
                var tzAuto = document.getElementById('timezone-autocomplete');
                if (tzAuto) tzAuto.value = '';
            }
        </script>
    </head>

    <body>
        <div class="container center" align="center">
            <form method="post" class="center" style="width:100%" id="auth-form" onsubmit="return formSubmit();">
                <?php
                if (strlen($problem)) { ?>
                    <h2><?= $problem ?></h2>
                <?php } ?> <h1>Enter SleepNumber login info</h1>
                <p><input class="input" type="email" id="email" name="email" width="30" value="<?= $email ?>" placeholder="Email:" /></p>
                <p><input class="input" type="password" id="password" name="password" width="30" value="<?= $password ?>" placeholder="Password:" /></p>
                <p class="em2">User settings <button onclick="clearSettings()">Clear</button></p>
                <p class="center em3">By setting your sleep start/end/timezone, you will be enabling updates to your SmartThings device for your presence EVERY MINUTE between the start and end time. PLEASE be courteous and only set start and end times within 12 hours of each other, and use <button onclick="clearSettings()">Clear</button> (then
                    <input type='submit' name='action' value='Save' id="save-button" />) to disable these settings if you no longer need to update your bed presence.
                </p>
                <p class="em2">Sleep Start Time: <input type="time" id="start_time" name="start_time" width="30" value="<?= $startTime ?>" placeholder="Start Time:" class="em2" /></p>
                <p class="em2">Sleep End Time: <input type="time" id="end_time" name="end_time" width="30" value="<?= $endTime ?>" placeholder="End Time:" class="em2" /></p>
                <p class="em2">Your timezone:
                    <input type="text" id="timezone-autocomplete" class="em2" placeholder="Type to search..." style="margin-bottom:0.5em;" autocomplete="off" />
                    <input type="hidden" id="timezone" name="timezone" value="<?= htmlspecialchars($timeZone) ?>" />
                <div id="timezone-list" class="em2" style="position:relative;z-index:10;background:#fff;border:1px solid #888;border-radius:5px;max-height:200px;overflow-y:auto;display:none;"></div>
                <script type="text/javascript">
                    // List of timezones from PHP
                    const timezoneList = [
                        <?php foreach ($timezone_identifiers as $tz) {
                            echo '"' . addslashes($tz) . '",';
                        } ?>
                    ];
                    const timezoneInput = document.getElementById('timezone-autocomplete');
                    const timezoneHidden = document.getElementById('timezone');
                    const timezoneDropdown = document.getElementById('timezone-list');
                    let selectedIndex = -1;

                    function showDropdown(matches) {
                        timezoneDropdown.innerHTML = '';
                        if (matches.length === 0) {
                            timezoneDropdown.style.display = 'none';
                            return;
                        }
                        matches.forEach((tz, idx) => {
                            const div = document.createElement('div');
                            div.textContent = tz;
                            div.style.padding = '0.5em 1em';
                            div.style.cursor = 'pointer';
                            div.onmousedown = function(e) { // use onmousedown to avoid blur before click
                                timezoneInput.value = tz;
                                timezoneHidden.value = tz;
                                timezoneDropdown.style.display = 'none';
                            };
                            if (idx === selectedIndex) {
                                div.style.background = '#e0e0e0';
                            }
                            timezoneDropdown.appendChild(div);
                        });
                        timezoneDropdown.style.display = 'block';
                    }

                    function filterTimezonesAutocomplete() {
                        const val = timezoneInput.value.toLowerCase();
                        const matches = timezoneList.filter(tz => tz.toLowerCase().includes(val));
                        selectedIndex = -1;
                        showDropdown(matches.slice(0, 20)); // limit to 20 results
                    }

                    timezoneInput.addEventListener('input', filterTimezonesAutocomplete);
                    timezoneInput.addEventListener('focus', filterTimezonesAutocomplete);
                    timezoneInput.addEventListener('blur', function() {
                        setTimeout(() => {
                            timezoneDropdown.style.display = 'none';
                        }, 150);
                    });
                    timezoneInput.addEventListener('keydown', function(e) {
                        const visible = timezoneDropdown.style.display === 'block';
                        const items = Array.from(timezoneDropdown.children);
                        if (!visible || items.length === 0) return;
                        if (e.key === 'ArrowDown') {
                            selectedIndex = (selectedIndex + 1) % items.length;
                            showDropdown(items.map(i => i.textContent));
                            e.preventDefault();
                        } else if (e.key === 'ArrowUp') {
                            selectedIndex = (selectedIndex - 1 + items.length) % items.length;
                            showDropdown(items.map(i => i.textContent));
                            e.preventDefault();
                        } else if (e.key === 'Enter') {
                            if (selectedIndex >= 0 && selectedIndex < items.length) {
                                timezoneInput.value = items[selectedIndex].textContent;
                                timezoneHidden.value = items[selectedIndex].textContent;
                                timezoneDropdown.style.display = 'none';
                                e.preventDefault();
                            }
                        }
                    });

                    // Set initial value if present
                    if (timezoneHidden.value) {
                        timezoneInput.value = timezoneHidden.value;
                    }
                </script>
                </p>
                <p><br /></p>
                <!-- Credentials Error -->
                <div id="form-error" style="display: none" class="error">
                    Please enter the email and password for your SleepNumber account.
                </div>

                <?php
                // If doing a CAPTCHA. When testing locally, you may need to set this value to '' in order for the form to submit.
                if (strlen(CAPTCHA_SITE_KEY)) {
                ?>
                    <!-- Recaptcha -->
                    <div align="center" class="g-recaptcha" data-sitekey="<?= CAPTCHA_SITE_KEY ?>" data-callback="onRecaptchaSuccess" data-expired-callback="onRecaptchaResponseExpiry" data-error-callback="onRecaptchaError"></div>

                    <!-- Recaptcha Error -->
                    <div id="recaptcha-form-error" style="display: none" class="error">
                        Please fill the recaptcha checkbox.
                    </div>

                    <!-- Recaptcha Success -->
                    <div id="recaptcha-form-success" style="display: none" class="bg-green-200 rounded py-1 px-2 text-sm sm:text-md">
                    </div>
                <?php } // END CAPTCHA 
                ?>
                <p><input type='submit' name='action' value='Save' id="login-button" class="bigbutton" /></p>
                <script type='text/javascript'>
                    document.getElementById('email').focus();
                    document.getElementById('email').select();
                </script>
            </form>
        </div>
        <script type="text/javascript">
            const userTimeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;

            function setInitialTimezone() {
                const timezoneSelect = document.getElementById('timezone');
                if (timezoneSelect && userTimeZone && timezoneSelect.value == "") {
                    timezoneSelect.value = userTimeZone;
                }
            }
            setInitialTimezone();
        </script>
    </body>

    </html>
<?php
    exit();
} // End if empty post or problem

// If we reach here, it means the form was submitted and validated successfully
?>
<html>

<head>
    <title>Settings Saved!</title>
</head>

<body>
    <h1> Your settings have been saved!</h1>
</body>

</html>