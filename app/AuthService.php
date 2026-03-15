<?php

require_once "Helper.php";

class AuthService
{
    public static $resp = "";

    public static function create_token(string $email, string $thaali): string
    {
        return hash("md4", $thaali . $_SERVER["SERVER_NAME"] . $email);
    }

    public static function get_responsibility(): string
    {
        return self::$resp;
    }

    public static function verify_token(
        $db,
        string $email,
        string $thaali,
    ): bool {
        $received_token = Helper::get_if_defined($_COOKIE["token"], "");

        // Does record match with database?
        $name = self::get_name($db, $email, $thaali);
        if ($name == "") {
            return false;
        }

        $token = self::create_token($email, $thaali);
        return $token == $received_token;
    }

    public static function get_name($db, string $email, string $thaali): string
    {
        $sql = "SELECT * FROM `family` WHERE `thaali` = '$thaali'";

        if (!self::is_admin($email)) {
            $sql .= " AND `email` = '$email'";
        }
        $sql .= " LIMIT 1;";

        ($result = $db->query($sql)) or die("{ msg: 'DB query failed.' }");
        if (!$result || $result->num_rows != 1) {
            return "";
        }

        $row = $result->fetch_assoc();

        self::$resp = $row["resp"];
        return $row["firstName"] . " " . $row["lastName"];
    }

    // Does not use cookie's email address because that assumes that login was
    // successful and limits usage only after login. Instead, the email needs
    // to always be passed in.
    public static function is_admin(string $email): bool
    {
        if ($email == "admin@sfjamaat.org") {
            return true;
        }
        return false;
    }

    // Get cutoff date where RSVP becomes readonly and cannot be modified
    // unless you are admin
    public static function get_cutoff_time(int $override_admin): string
    {
        // Set timezone from config
        date_default_timezone_set(Config::TIMEZONE);

        // If admin override is enabled, then return a very old date so that
        // everything is modifiable
        if ($override_admin && self::is_admin($_COOKIE["email"])) {
            return "1970-1-1";
        }

        // Cutoff logic selected based on config
        $functionName = "cutoff_" . Config::CUTOFF_MODE;
        if (function_exists($functionName)) {
            return $functionName();
        } else {
            die(
                "Cutoff selection not configured correctly: " .
                    Config::CUTOFF_MODE
            );
        }
    }

    public static function is_save_available(int $offset): bool
    {
        if (self::is_admin($_COOKIE["email"])) {
            return true;
        }
        return $offset == 0 &&
            strpos(self::get_responsibility(), "F") !== false;
    }
}
