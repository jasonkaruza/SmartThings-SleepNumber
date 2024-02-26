<?php
// include our OAuth2 Server object
require_once __DIR__.'/server.php';

$request = OAuth2\Request::createFromGlobals();
$response = new OAuth2\Response();
// validate the authorize request
if (!$server->validateAuthorizeRequest($request, $response)) {
    $response->send();
    die;
}
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
/*
if ($is_authorized) {
  // this is only here so that you get to see your code in the cURL request. Otherwise, we'd redirect back to the client
  $code = substr($response->getHttpHeader('Location'), strpos($response->getHttpHeader('Location'), 'code=')+5, 40);
  exit("SUCCESS! Authorization Code: $code");
}
*/
$response->send();
?>
