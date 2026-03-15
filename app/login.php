<?php

require_once "bootstrap.php";

$data = json_decode(file_get_contents('php://input'), false);
if ($data) {
  $thaali = Helper::get_if_defined($data->pass, '');
  $email = Helper::get_if_defined($data->email, '');
} else {
  $thaali = '';
  $email = '';
}

// Get name from credentials
$name = AuthService::get_name($db, $email, $thaali);
if ($name == "") {
    Helper::json_error("Login failed");
}

// Verified, set cookies for 60 days
$expires = time() + (86400 * 60); // 86400 = 1 day
setcookie("token", AuthService::create_token($email, $thaali), $expires);
setcookie("thaali", $thaali, $expires);
setcookie("email", $email, $expires);
if (AuthService::is_admin($email)) {
    setcookie("adv", "1", $expires);
}
$greet = $name . ", #" . $thaali;

// Send name to indicate successful login
Helper::print_to_json($greet, NULL);

?>
