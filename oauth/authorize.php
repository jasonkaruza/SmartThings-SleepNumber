<?php
// include our OAuth2 Server object (and loader)
require_once __DIR__ . '/server.php';

$request = OAuth2\Request::createFromGlobals();
$response = new OAuth2\Response();
// validate the authorize request
if (!$server->validateAuthorizeRequest($request, $response)) {
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
          font-size: 7em;
        }

        .container {
          height: 100%;
          width: 100%;
          position: relative;
          text-align: center;
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
          height: 5em;
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
      <script src="https://www.google.com/recaptcha/api.js"></script>
      <script>
        function onSubmit(token) {
          document.getElementById("auth-form").submit();
        }
      </script>
    </head>

    <body>
      <div class="container">
        <form method="post" class="center" style="width:100%" id="auth-form">
          <?php
          if (strlen($problem)) { ?>
            <h2><?= $problem ?></h2>
          <?php } ?> <h1>>Enter your SleepNumber account login info</h1>
          <p><input type="email" id="email" name="email" width="30" value="<?= $email ?>" placeholder="Email:" /></p>
          <p><input type="password" id="password" name="password" width="30" value="<?= $password ?>" placeholder="Password:" /></p>
          <p><input type='submit' name='action' value='Login' id="login-button" class="g-recaptcha" data-sitekey="<?= CAPTCHA_SITE_KEY ?>" data-callback='onSubmit' data-action='submit'></p>
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

  $user = $server->getStorage(STORAGE_NAME)->getUser($username);
  // Associate token with user
  $server->handleAuthorizeRequest($request, $response, true, $user->user_id);
}
/*
if ($is_authorized) {
  // this is only here so that you get to see your code in the cURL request. Otherwise, we'd redirect back to the client
  $code = substr($response->getHttpHeader('Location'), strpos($response->getHttpHeader('Location'), 'code=')+5, 40);
  exit("SUCCESS! Authorization Code: $code");
}
*/
// print the authorization code if the user has authorized your client
$response->send();
