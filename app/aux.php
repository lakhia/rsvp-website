<?php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Content-Type: application/json; charset=UTF-8");

require_once 'oo_db.php';

$db = new DB();

$thaali_cookie = isset($_COOKIE['thaali']) ? $_COOKIE['thaali'] : '';
$email_cookie = isset($_COOKIE['email']) ? $_COOKIE['email'] : '';
$method_server = $_SERVER['REQUEST_METHOD'];

class Helper
{
    public static function create_token($email, $thaali)
    {
        return hash('md4', $thaali . $_SERVER["SERVER_NAME"] . $email);
    }

    public static function verify_token($email, $thaali)
    {
        $received_token = $_COOKIE['token'];

        $token = self::create_token($email, $thaali);
        return ($token == $received_token);
    }

    public static function get_name($db, $email, $thaali)
    {
        $sql = "SELECT * FROM `family` WHERE `thaali` = '$thaali'";

        if (!self::is_admin($email)) {
            $sql .= " AND `email` = '$email'";
        }
        $sql .= " LIMIT 1;";

        $result = $db->query($sql) or die("{ message: 'DB query failed.' }");
        if (!$result || $result->num_rows != 1) {
            return;
        }

        $row = $result->fetch_assoc();
        return $row['firstName'] . " " . $row['lastName'];
    }

    // Does not use cookie's email address because that assumes that login was
    // successful and limits usage only after login. Instead, the email needs
    // to always be passed in.
    public static function is_admin($email)
    {
        if ($email == "admin@sfjamaat.org") {
            return true;
        }
        return false;
    }

    // Response via json contains data and optional message
    public static function convert_array_to_json($array, $msg)
    {
        $wrapper = array();
        if ($msg) {
            $wrapper["message"] = $msg;
        }
        if ($array) {
            $wrapper["data"] = $array;
        }
        return json_encode($wrapper) . "\n";
    }

    // Get cutoff date where RSVP becomes readonly and cannot be modified
    // unless you are admin
    public static function get_cutoff_time($override_admin)
    {
        // If admin override is enabled, then return a very old date so that
        // everything is modifiable
        if ($override_admin && self::is_admin($_COOKIE['email'])) {
            return '1970-1-1';
        }

        $cutoff = strtotime('today 7pm');
        $now = time();

        return date('Y-m-d', strtotime( ($now > $cutoff) ? '+2 day' : '+1 day' ) );
    }
}
?>
