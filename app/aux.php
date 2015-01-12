<?php

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
        if (self::is_admin($email)) {
            $result = $db->query("SELECT * FROM `family` WHERE `thaali` = "
                                 . $thaali . " LIMIT 1");
	    } else {
	        $result = $db->query("SELECT * FROM `family` WHERE `thaali` = "
                                 . $thaali . " AND `email` = \"" . $email . "\"");
	    }

	    if ($result->num_rows != 1) 
	    {
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

	public static function convert_array_to_json($array, $msg)
	{
	    $wrapper = array();
	    if ($msg) 
	    {
	        $wrapper["message"] = $msg;
	    }
	    if ($array) 
	    {
	        $wrapper["data"] = $array;
	    }
	    return json_encode($wrapper, JSON_PRETTY_PRINT) . "\n";
	}

	public static function rsvp_disabled() 
	{
        // Admin can change RSVP even if disabled for others
        if (self::is_admin($_COOKIE['email'])) {
	        return '1970-1-1';
	    }

	    $cutoff = strtotime('today 7pm');
	    $now = time();

	    return date('Y-m-d', strtotime( ($now > $cutoff) ? '+2 day' : '+1 day' ) );
	}

}
?>