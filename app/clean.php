<?php

require_once("auxil.php");

// If token is invalid, return an empty response
if (!Helper::is_admin($email_cookie) ||
    !Helper::verify_token($db, $email_cookie, $thaali_cookie)) {
    die('{ "msg": "Login failed, please logout and login again" }');
}

// Process
$offset = Helper::get_if_defined($_GET['offset'], 0);
$len = Helper::get_if_defined($_GET['len'], 1);
clean($db, $offset, $len);

function clean($db, $offset, $len) {
    $from = Helper::get_day($offset);
    $to = Helper::get_day($offset + $len);

    // Get all dates between range
    $period = new DatePeriod(
                  new DateTime($from),
                  new DateInterval('P1D'),
                  new DateTime($to));

    // Clean for each date
    $results = [];
    foreach($period as $date) {
        $d = $date->format('Y-m-d');
        $query = "SELECT enabled FROM `events` " .
            "WHERE `date` = '" . $d . "';";
        $result = $db->query($query);
        $row = $result->fetch_assoc();
        if (!$row) {
            $db->query("DELETE FROM rsvps WHERE date = '" . $d . "';");
            if ($db->mysqli->affected_rows > 0) {
                $results[$d] = array("type"=>"no_event", "delete"=>$db->mysqli->affected_rows);
            }
            continue;
        }
        if (!$row['enabled']) {
            $db->query("DELETE FROM rsvps WHERE date = '" . $d . "';");
            if ($db->mysqli->affected_rows > 0) {
                $results[$d] = array("type"=>"not_enabled", "delete"=>$db->mysqli->affected_rows);
            }
        } else {
            $db->query("DELETE FROM rsvps WHERE rsvp = 0 AND date = '" . $d . "';");
            if ($db->mysqli->affected_rows > 0) {
                $results[$d] = array("type"=>"enabled_no_rsvp", "delete"=>$db->mysqli->affected_rows);
            }
        }
    }

    Helper::print_to_json($results, "");
}
