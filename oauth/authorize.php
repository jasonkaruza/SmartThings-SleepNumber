<?php
// include our OAuth2 Server object (and loader)
require_once __DIR__ . '/server.php';
$request = OAuth2\Request::createFromGlobals();
$response = new OAuth2\Response();
// validate the authorize request
if (!$server->validateAuthorizeRequest($request, $response)) {
  logtext("Validated authorization request!");
  logtext("Authorization request:\n" . print_r($request, true));
  logtext("Authorization response:\n" . print_r($response, true));
  $response->send();
  die;
}

/**
 * If the code is configured to operate in a single-account manner with hard-coded
 * SleepNumber credentials, then there is no need to enter credentials here. Show
 * a YES/NO option and proceed to authorize.
 */
if (SINGLE_ACCOUNT_CONFIG) {
  // display an authorization form
  if (empty($_POST)) {
    exit('
<style>
.bigbutton {
  width: 45%;
  font-size: 7em;
}

.container {
  height: 100%;
  width: 100%;
  position: relative;
}

h1 {
  font-size: 5em;
}

.vertical-center {
  margin: 0;
  position: absolute;
  top: 50%;
  -ms-transform: translateY(-50%);
  transform: translateY(-50%);
}

.center {
  margin: 0;
  position: absolute;
  top: 50%;
  left: 50%;
  -ms-transform: translate(-50%, -50%);
  transform: translate(-50%, -50%);
}
</style>
<div class="container">
<form method="post" class="center" style="width:100%">
  <h1><center>Do You Authorize TestClient?</center></h1><br />
  <center>
  <input type="submit" name="authorized" value="yes" class="bigbutton">
  <input type="submit" name="authorized" value="no" class="bigbutton">
  </center>
</form>
</div>');
  }

  // print the authorization code if the user has authorized your client
  $is_authorized = ($_POST['authorized'] === 'yes');
  $server->handleAuthorizeRequest($request, $response, $is_authorized);
}

/**
 * Otherwise, if this is configured for multiple SleepNumber users to authenticate
 * and have their credentials stored in the DB (encrypted), then we need to collect
 * the credentials and verify they work before storing them and generating a token.
 */
else {
  $email = $password = '';
  $problem = '';
  $errorMessage = 'There was a problem with your email/password. Please try again.';

  // Validation if submitted
  if (isset($_POST['action'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Take username and password. Validate username is email and sanitize password.
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $email = filter_var($email, FILTER_SANITIZE_EMAIL); // Just in case
      $problem = $errorMessage;
    } else if (!strlen($password)) {
      $problem = $errorMessage;
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
      /**
       * TODO
       * authorize.php
       * If valid, do an upsert of record in user table of database
       */
      if (!$server->getStorage(STORAGE_NAME)->setUser($email, encryptData($password), null, null, false)) {
        $problem = $errorMessage;
      }
    }
  } //end if login form posted

  // display an authorization form
  if (empty($_POST) || strlen($problem)) {
?>
    <html>

    <head>
      <style type='text/css'>
        .bigbutton {
          width: 45%;
          font-size: 3em;
        }

        .container {
          height: 100%;
          width: 100%;
          position: relative;
          text-align: center;
        }

        .error {
          color: red;
          text-align: center;
          font-size: 2em;
        }

        h1 {
          font-size: 5em;
          text-align: center;
        }

        h2 {
          font-size: 3em;
          color: red;
          text-align: center;
        }

        input {
          height: 3em;
          width: 90%;
          font-size: 3em;
        }

        .vertical-center {
          margin: 0;
          position: absolute;
          top: 50%;
          -ms-transform: translateY(-50%);
          transform: translateY(-50%);
        }

        .center {
          margin: 0;
          position: absolute;
          top: 50%;
          left: 50%;
          -ms-transform: translate(-50%, -50%);
          transform: translate(-50%, -50%);
        }

        .g-recaptcha>div>div {
          margin: 10px auto !important;
          text-align: center;
          width: auto !important;
          height: auto !important;
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
      </script>
    </head>

    <body>
      <div class="container">
        <form method="post" class="center" style="width:100%" id="auth-form" onsubmit="return formSubmit();">
          <?php
          if (strlen($problem)) { ?>
            <h2><?= $problem ?></h2>
          <?php } ?> <h1>Enter your SleepNumber account login info</h1>
          <p><input type="email" id="email" name="email" width="30" value="<?= $email ?>" placeholder="Email:" /></p>
          <p><input type="password" id="password" name="password" width="30" value="<?= $password ?>" placeholder="Password:" /></p>
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
          <p><input type='submit' name='action' value='Login' id="login-button" class="bigbutton" /></p>
          <script type='text/javascript'>
            document.getElementById('email').focus();
            document.getElementById('email').select();
          </script>
        </form>
      </div>
    </body>

    </html>
<?php
    exit();
  }

  // Associate token with user
  $server->handleAuthorizeRequest($request, $response, true, $email);
}

// print the authorization code if the user has authorized your client
logtext("Handled authorization request");
logtext("Authorization request:\n" . print_r($request, true));
logtext("Authorization response:\n" . print_r($response, true));
$response->send();
