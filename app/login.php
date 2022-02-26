<?php

require_once("auxil.php");

$data = json_decode(file_get_contents('php://input'), false);
if ($data) {
  $thaali = Helper::get_if_defined($data->pass, '');
  $email = Helper::get_if_defined($data->email, '');
} else {
  $thaali = '';
  $email = '';
}

// Get name from credentials
$name = Helper::get_name($db, $email, $thaali);
if ($name == "") {
    $msg = "Login failed";
    die('{ "msg": "' . $msg . '" }');
}

// Verified, set cookies for 60 days
$expires = time() + (86400 * 60); // 86400 = 1 day
setcookie("token", Helper::create_token($email, $thaali), $expires);
setcookie("thaali", $thaali, $expires);
setcookie("email", $email, $expires);
if (Helper::is_admin($email)) {
    setcookie("adv", "1", $expires);
}
$greet = $name . ", #" . $thaali;

// Send name to indicate successful login
Helper::print_to_json($greet, NULL);

?>
