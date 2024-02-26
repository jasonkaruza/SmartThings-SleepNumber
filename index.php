<?php
require_once "./oauth/settings.php";

// Don't touch from here down
$_GET = filter_input_array(INPUT_GET, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// If POSTing, run the python script
if (array_key_exists('action', $_POST) && $_POST['action'] == 'go') {
	$user = $_POST['user'];
	$pass = $_POST['pass'];
	$cmd = SN_SCRIPT . " -u $user -p $pass";

	print "cmd: $cmd\n";
	$output = shell_exec($cmd);

	print $output;
}

// Else we are going to the index page normal, so show a form
else {
?>
<html>
<head>Test</head>
<body>
<form method='POST'>
User: <input type='text' name='user' /><br>
Pass: <input type='password' name='pass' /><br>
<input type='submit' name='action' value='go' />
</form>
</body>
</html>
<?php
}
?>

