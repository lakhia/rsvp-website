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

// Thaali must be a non-empty integer string
if (!ctype_digit($thaali) || $thaali === '') {
    Helper::json_error("Login failed");
}

// Get name from credentials
$name = AuthService::get_name($db, $email, $thaali);
if ($name == "") {
    Helper::json_error("Login failed");
}

// Verified, set cookies for 60 days
AuthService::set_session_cookies($email, $thaali);
$greet = $name . ", #" . $thaali;

// Send name to indicate successful login
Helper::print_to_json($greet, NULL);

?>
