<?php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Content-Type: application/json; charset=UTF-8");

require_once "oo_db.php";
require_once "config.php";
require_once "cutoff.php";
require_once "AuthService.php";

$db = new DB();

$thaali_cookie = isset($_COOKIE["thaali"]) ? $_COOKIE["thaali"] : "";
$email_cookie = isset($_COOKIE["email"]) ? $_COOKIE["email"] : "";
$method_server = $_SERVER["REQUEST_METHOD"];
