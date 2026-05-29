<?php

require_once "bootstrap.php";

if ($method_server !== "POST") {
    Helper::json_error("Method not allowed");
}

$data = json_decode(file_get_contents('php://input'), false);
if (!$data) {
    Helper::json_error("Invalid request");
}

$firstName = trim(Helper::get_if_defined($data->firstName, ''));
$lastName  = trim(Helper::get_if_defined($data->lastName,  ''));
$email     = trim(Helper::get_if_defined($data->email,     ''));
$phone     = trim(Helper::get_if_defined($data->phone,     ''));
$its       = trim(Helper::get_if_defined($data->its,       ''));
$area      = trim(Helper::get_if_defined($data->area,      ''));

if ($firstName === '' || $lastName === '' || $email === '' || $its === '') {
    Helper::json_error("First name, last name, email, and ITS number are required");
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
if (!$is_admin) {
    $stmt = $db->prepare("SELECT its FROM whitelist WHERE its = ? LIMIT 1");
    $stmt->bind_param("s", $its);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        Helper::json_error("This ITS number is not authorized to register");
    }
}

// Find next available thaali > 500
$result = $db->query("SELECT COALESCE(MAX(thaali), 500) + 1 AS next_id FROM family WHERE thaali > 500");
$thaali = (int) $result->fetch_assoc()['next_id'];

$size = 'LG';
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
