<?php

include 'init.php';

// User query params instead of cookie
$thaali = $_GET['thaali'];
$email = $_GET['email'];

// Respond in JSON
header("Content-Type: application/json; charset=UTF-8");

// Get name from credentials
$name = get_name($conn, $email, $thaali);
if ($name == "") {
    echo convert_array_to_json(NULL, "Login failed");
    return;
}

// Verified, set cookies for 60 days
$expires = time() + (86400 * 60); // 86400 = 1 day
setcookie("token", create_token($email, $thaali), $expires);
setcookie("thaali", $thaali, $expires);
setcookie("email", $email, $expires);
setcookie("name", $name, $expires);
if (is_admin()) {
    setcookie("admin", "1", $expires);
}

// Returns true value to indicate successful login
echo convert_array_to_json($name, "");

?>
