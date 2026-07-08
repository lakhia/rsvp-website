<?php

require_once "bootstrap.php";

if ($method_server !== "POST") {
    Helper::json_error("Method not allowed");
}

$data = json_decode(file_get_contents('php://input'), false);
if (!$data) {
    Helper::json_error("Invalid request");
}

$firstName     = trim(Helper::get_if_defined($data->firstName,     ''));
$lastName      = trim(Helper::get_if_defined($data->lastName,      ''));
$email         = trim(Helper::get_if_defined($data->email,         ''));
$phone         = trim(Helper::get_if_defined($data->phone,         ''));
$its           = trim(Helper::get_if_defined($data->its,           ''));
$area          = trim(Helper::get_if_defined($data->area,          ''));
$householdSize = trim(Helper::get_if_defined($data->householdSize, ''));

if ($firstName === '' || $lastName === '' || $email === '' || $its === '') {
    Helper::json_error("First name, last name, email, and ITS number are required");
}

if ($phone === '') {
    Helper::json_error("A WhatsApp phone number is required");
}

// Strip common formatting characters and verify it looks like a phone number
$phoneDigits = preg_replace('/[\s\-().+]/', '', $phone);
if (!preg_match('/^\d{7,15}$/', $phoneDigits)) {
    Helper::json_error("Please enter a valid phone number (7–15 digits)");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    Helper::json_error("Invalid email");
}

if (!preg_match('/^\d{8}$/', $its)) {
    Helper::json_error("ITS number must be exactly 8 digits");
}

// Check if email already registered
$stmt = $db->prepare("SELECT thaali FROM family WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    Helper::json_error("This email is already registered");
}

// Check if ITS number already registered
$stmt = $db->prepare("SELECT thaali FROM family WHERE its = ? LIMIT 1");
$stmt->bind_param("s", $its);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    Helper::json_error("This ITS number is already registered");
}

// Non-admin registrations must have an ITS on the whitelist
$cookie_email  = Helper::get_if_defined($_COOKIE["email"],  "");
$cookie_thaali = Helper::get_if_defined($_COOKIE["thaali"], "");
$is_admin = $cookie_email !== "" &&
            AuthService::verify_token($db, $cookie_email, $cookie_thaali) &&
            AuthService::is_admin($cookie_email);

$whitelist_thaali = 500;
if (!$is_admin) {
    $stmt = $db->prepare("SELECT its, thaali FROM whitelist WHERE its = ? LIMIT 1");
    $stmt->bind_param("s", $its);
    $stmt->execute();
    $whitelist_row = $stmt->get_result()->fetch_assoc();
    if (!$whitelist_row) {
        Helper::json_error("This ITS number is not authorized to register");
    }
    $whitelist_thaali = (int) $whitelist_row['thaali'];
}

// Find next available thaali above the appropriate floor
// Admins get floor 500; non-admins use the thaali recorded in the whitelist
$stmt  = $db->prepare("SELECT COALESCE(MAX(thaali), ?) + 1 AS next_id FROM family WHERE thaali >= ?");
$stmt->bind_param("ii", $whitelist_thaali, $whitelist_thaali);
$stmt->execute();
$thaali = (int) $stmt->get_result()->fetch_assoc()['next_id'];

// Map household count to the next-higher thaali size tier (e.g. 5 → LG, 20 → XL)
// Tiers: 1→XS, ≤2→SM, ≤4→MD, ≤6→LG, anything larger→XL
// Defaults to SM when not provided
$n = (int) $householdSize;
if ($n <= 0) {
    $size = 'SM';
} elseif ($n <= 1) {
    $size = 'XS';
} elseif ($n <= 2) {
    $size = 'SM';
} elseif ($n <= 4) {
    $size = 'MD';
} elseif ($n <= 6) {
    $size = 'LG';
} else {
    $size = 'XL';
}

$area = $area !== '' ? $area : 'Z';
$stmt = $db->prepare(
    "INSERT INTO family (thaali, its, lastName, firstName, size, area, email, phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param("isssssss", $thaali, $its, $lastName, $firstName, $size, $area, $email, $phone);
if (!$stmt->execute()) {
    Helper::json_error("Registration failed, please try again");
}

AuthService::set_session_cookies($email, $thaali);

$greet = $firstName . " " . $lastName . ", #" . $thaali;
Helper::print_to_json(['greet' => $greet, 'thaali' => $thaali], null);

?>