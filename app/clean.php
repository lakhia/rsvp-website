<?php

require_once "bootstrap.php";

// If token is invalid, return an empty response
if (!AuthService::is_admin($email_cookie) ||
    !AuthService::verify_token($db, $email_cookie, $thaali_cookie)) {
    Helper::json_error("Login failed, please logout and login again");
}

// Process
$offset = Helper::get_param("offset", 0);
$len = Helper::get_param("len", 1);
clean($db, $offset, $len);

function clean($db, $offset, $len)
{
    $from = Helper::get_day($offset);
    $to = Helper::get_day($offset + $len);

    // Get all dates between range
    $period = new DatePeriod(
        new DateTime($from),
        new DateInterval("P1D"),
        new DateTime($to),
    );

    // Clean for each date
    $results = [];
    foreach ($period as $date) {
        $d = $date->format("Y-m-d");
        $query =
            "SELECT enabled FROM `events` " . "WHERE `date` = '" . $d . "';";
        $result = $db->query($query);
        $row = $result->fetch_assoc();
        if (!$row) {
            $db->query("DELETE FROM rsvps WHERE date = '" . $d . "';");
            if ($db->mysqli->affected_rows > 0) {
                $results[$d] = [
                    "type" => "no_event",
                    "delete" => $db->mysqli->affected_rows,
                ];
            }
            continue;
        }
        if (!$row["enabled"]) {
            $db->query("DELETE FROM rsvps WHERE date = '" . $d . "';");
            if ($db->mysqli->affected_rows > 0) {
                $results[$d] = [
                    "type" => "not_enabled",
                    "delete" => $db->mysqli->affected_rows,
                ];
            }
        } else {
            $db->query("DELETE FROM rsvps WHERE rsvp = 0 AND date = '" . $d . "';");
            if ($db->mysqli->affected_rows > 0) {
                $results[$d] = [
                    "type" => "enabled_no_rsvp",
                    "delete" => $db->mysqli->affected_rows,
                ];
            }
        }
    }

    Helper::print_to_json($results, "");
}
