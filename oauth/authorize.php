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

$email = $password = '';
$problem = '';
$errorMessage = 'There was a problem with your email/password. Please try again.';

// Validation if submitted
if (isset($_POST['action'])) {
  $email = $_POST['email'];
  $password = $_POST['password'];

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $email = filter_var($email, FILTER_SANITIZE_EMAIL); // Just in case
    $problem = $errorMessage;
  } else if (!strlen($password)) {
    $problem = $errorMessage;
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
  </head>

  <body>
    <div class="container">
      <form method="post" class="center" style="width:100%">
        <?php
        if (strlen($problem)) { ?>
          <h2><?= $problem ?></h2>
        <?php } ?> <h1>>Enter your SleepNumber account login info</h1>
        <p><input type="email" id="email" name="email" width="30" value="<?= $email ?>" placeholder="Email:" /></p>
        <p><input type="password" id="password" name="password" width="30" value="<?= $password ?>" placeholder="Password:" /></p>
        <p><input type='submit' name='action' value='Login' id="login-button"></p>
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
/**
 * TODO
 * authorize.php
 * 1. Take username and password. Add captcha. Validate username is email and sanitize password.
 * 2. Authenticate it against SleepNumber API to confirm valid
 *  2a. If not valid, show an error "There was a problem with your username/password. Please try again". Pre-populate previous username and password values.
 * 3. If valid, do an upsert of record in user table of database
 * 4. Associate token with user
 * 5. Return successful authorization
 * 
 * sn.php
 * 1. Look up user by token
 * 2. Get user's username and password (decrypted). Use these values to authenticate against SleepNumber API
 */
// print the authorization code if the user has authorized your client
$is_authorized = ($_POST['authorized'] === 'yes');
$server->handleAuthorizeRequest($request, $response, $is_authorized);
/*
if ($is_authorized) {
  // this is only here so that you get to see your code in the cURL request. Otherwise, we'd redirect back to the client
  $code = substr($response->getHttpHeader('Location'), strpos($response->getHttpHeader('Location'), 'code=')+5, 40);
  exit("SUCCESS! Authorization Code: $code");
}
*/
$response->send();
