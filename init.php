<?php

date_default_timezone_set('America/Los_Angeles'); 
// should be set before the MySQL connection is made
// TZ problems sometimes occur otherwise

$thaali = $_COOKIE['thaali'];
$email = $_COOKIE['email'];
$method = $_SERVER['REQUEST_METHOD'];

// Create connection

$conn = new mysqli("127.0.0.1",   /* server */
                   "sffaiz",      /* username */
                   "sffaiz-pass", /* pass */
                   "sffaiz"       /* db */
                   );
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create token using thaali, server name and email address
function create_token($email, $thaali) {
    return hash('md4', $thaali . $_SERVER["SERVER_NAME"] . $email);
}

// Verify token
function verify_token($email, $thaali) {
    $received_token = $_COOKIE['token'];

    $token = create_token($email, $thaali);
    return ($token == $received_token);
}

// Given email and thaali, get name. Return empty string if not in system
function get_name($conn, $email, $thaali) {
    // If admin, get name. Otherwise, verify.
    if ($email == "admin@sfjamaat.org") {
        $result = $conn->query("SELECT * FROM `family` WHERE `thaali` = "
                               . $thaali . " LIMIT 1");
    } else {
        $result = $conn->query("SELECT * FROM `family` WHERE `thaali` = "
                               . $thaali . " AND `email` = \"" . $email . "\"");
    }
    if ($result->num_rows != 1) {
        return;
    }
    $row = $result->fetch_assoc();
    return $row["firstName"] . " " . $row["lastName"];
}

// Is user administrator?
function is_admin() {
    if ($_COOKIE['email'] == "admin@sfjamaat.org") {
        return 1;
    }
    return 0;
}

// Convert array to JSON string
function convert_array_to_json($array, $msg) // can use default args
{
    $wrapper = array();
    if ($msg) {
        $wrapper["message"] = $msg;
    }
    if ($array) {
        $wrapper["data"] = $array;
    }
    return json_encode($wrapper, JSON_PRETTY_PRINT) . "\n";
}

// Returns cutoff based on current time
function rsvp_disabled() {
    // Admin can do anything
    if ($_COOKIE['email'] == "admin@sfjamaat.org") {
        return '1970-1-1';
    }
    // date_default_timezone_set('America/Los_Angeles');
    $cutoff = strtotime('today 7pm');
    $now = time();
    if ($now > $cutoff) {
        return date('Y-m-d', strtotime('+2 day'));
    } else {
        return date('Y-m-d', strtotime('+1 day'));
    }
}

?>
