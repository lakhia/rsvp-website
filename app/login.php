<?php

require_once("aux.php");

// User query params instead of cookie
$thaali = isset($_GET['thaali']) ? $_GET['thaali'] : '';
$email = isset($_GET['email']) ? $_GET['email'] : '';

// Get name from credentials
$name = Helper::get_name($db, $email, $thaali);
if ($name == "") {
    echo Helper::convert_array_to_json(NULL, "Login failed");
    return;
}

// Verified, set cookies for 60 days
$expires = time() + (86400 * 60); // 86400 = 1 day
setcookie("token", Helper::create_token($email, $thaali), $expires);
setcookie("thaali", $thaali, $expires);
setcookie("email", $email, $expires);
setcookie("name", $name, $expires);
if (Helper::is_admin($email)) {
    setcookie("adv", "1", $expires);
}

// Returns true value to indicate successful login
echo Helper::convert_array_to_json($name, "");

?>