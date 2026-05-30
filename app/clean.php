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

        // Get all events for this date
        $query = "SELECT event_index, enabled FROM `events` WHERE `date` = '" . $d . "';";
        $result = $db->query($query);
        $events_for_date = [];
        while ($row = $result->fetch_assoc()) {
            $events_for_date[] = $row;
        }

        if (empty($events_for_date)) {
            // No events at all — delete all RSVPs for this date
            $db->query("DELETE FROM rsvps WHERE date = '" . $d . "';");
            if ($db->mysqli->affected_rows > 0) {
                $results[$d] = [
                    "type" => "no_event",
                    "delete" => $db->mysqli->affected_rows,
                ];
            }
            continue;
        }

        // Process each event individually
        $total_deleted = 0;
        foreach ($events_for_date as $ev) {
            $ei = (int)$ev['event_index'];
            if (!$ev["enabled"]) {
                $db->query("DELETE FROM rsvps WHERE date = '" . $d . "' AND event_index = " . $ei . ";");
                $total_deleted += $db->mysqli->affected_rows;
            } else {
                $db->query("DELETE FROM rsvps WHERE rsvp = 0 AND date = '" . $d . "' AND event_index = " . $ei . ";");
                $total_deleted += $db->mysqli->affected_rows;
            }
        }
        if ($total_deleted > 0) {
            $results[$d] = [
                "type" => "cleaned",
                "delete" => $total_deleted,
            ];
        }
    }

    Helper::print_to_json($results, "");
}
