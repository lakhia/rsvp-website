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
    public static $resp = "";

    public static function create_token($email, $thaali)
    {
        return hash('md4', $thaali . $_SERVER["SERVER_NAME"] . $email);
    }

    public static function get_responsibility() {
        return self::$resp;
    }

    public static function verify_token($db, $email, $thaali)
    {
        $received_token = $_COOKIE['token'];

        // Does record match with database?
        $name = self::get_name($db, $email, $thaali);
        if ($name == "") {
            return false;
        }

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

        $result = $db->query($sql) or die("{ msg: 'DB query failed.' }");
        if (!$result || $result->num_rows != 1) {
            return "";
        }

        $row = $result->fetch_assoc();

        self::$resp = $row['resp'];
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

    // Response via json contains data, message and date
    public static function print_to_json($data, $msg, $date=NULL, $other=NULL)
    {
        $response = array(
            "msg" => $msg,
            "date" => $date,
            "data" => $data,
            "other" => $other
        );
        echo json_encode($response);
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

    public static function is_save_available($offset)
    {
        if (self::is_admin($_COOKIE['email'])) {
            return 1;
        }
        return ($offset == 0 && strpos(self::get_responsibility(), "F") !== false);
    }

    // Get beginning week date in mysql format
    public static function get_week($offset)
    {
        $date = new DateTime();

        // Saturday is cutoff to show next week
        $day = $date->format("w");
        if ($day == 6) {
            $day = -1;
        }
        return self::get_offset($date, $offset + 1 - $day);
    }

    // Get day of interest in mysql format
    public static function get_day($offset)
    {
        $date = new DateTime();
        return self::get_offset($date, $offset);
    }

    // Get if defined, otherwise return default
    public static function get_if_defined(&$var, $default=null) {
        return isset($var) ? $var : $default;
    }

    // Given a date and offset, return mysql date
    public static function get_offset($date, $offset) {
        if ($offset >= 0) {
            $interval = "P" . $offset . "D";
        } else {
            $interval = "P" . (-$offset) . "D";
        }
        $interval = new DateInterval($interval);

        // PHP DateInterval doesn't like negative numbers
        if ($offset >= 0) {
            $date->add($interval);
        } else {
            $date->sub($interval);
        }
        return $date->format('Y-m-d');
    }
}

?>
