<?php
require_once "bootstrap.php";
require_once "EstimationService.php";

// If token is invalid, return an empty response
if (!AuthService::verify_token($db, $email_cookie, $thaali_cookie)) {
    Helper::json_error("Login failed, please logout and login again");
}
$offset = Helper::get_param("offset", 0);
$event_index = (int)Helper::get_param("event_index", 0);
$from = Helper::get_day($offset);
// POST or GET?
if ($method_server == "POST") {
    print_post($db, $from, $event_index, $offset);
} else {
    print_filling($db, $from, $event_index, $offset);
}

// Get details for filling team
function print_filling($db, $from, $event_index, $offset, $msg = "")
{
    // All events on this date (for tab switcher / redirect when the
    // requested event_index doesn't exist, e.g. it was deleted)
    $events_result = $db->query(
        "SELECT event_index, details FROM events WHERE date = '$from' AND enabled = 1 ORDER BY event_index"
    );
    $date_events = [];
    while ($ev = $events_result->fetch_assoc()) {
        $date_events[] = $ev;
    }

    // Get details for date
    $details = get_details($db, $from, $event_index);

    $rows = [];
    if ($details) {
        // Get RSVP and family
        $query =
            "SELECT thaali_id as thaali, CONCAT(firstName, ' ', lastName) AS name, " .
            "adults, kids, rsvps.size, area, here, filled, lessRice AS norice FROM rsvps " .
            "LEFT JOIN `family` on family.thaali = rsvps.thaali_id " .
            "WHERE `rsvp` = 1 AND `date` = '" . $from . "' AND `event_index` = " . $event_index .
            " ORDER BY thaali;";
        $result = $db->query($query);
        $totalA = 0;
        $totalK = 0;

        while ($row = $result->fetch_assoc()) {
            if ($details["niyaz"]) {
                $totalA += $row["adults"];
                $totalK += $row["kids"];
                $row["count"] = $row["adults"] + $row["kids"];
                $row["size"] = $row["adults"] . " / " . $row["kids"];
                unset($row["adults"]);
                unset($row["kids"]);
            }
            $rows[] = $row;
        }
    }

    // Create message
    if ($details) {
        $save = AuthService::is_save_available($offset) && !$details["niyaz"];
        $other = [
            "save"        => $save,
            "niyaz"       => $details["niyaz"],
            "adults"      => $totalA,
            "kids"        => $totalK,
            "event_index" => $event_index,
            "events"      => $date_events,
            "serving"     => EstimationService::get_serving_guidance(
                $db,
                $details["details"],
            ),
        ];
        if (!$rows) {
            $msg = "No responses available for " . $from;
        }
    } else {
        // The requested event_index doesn't exist (or is disabled) for this
        // date. Still report the other events on this date (if any) so the
        // frontend can redirect to a valid event_index instead of getting stuck.
        $rows = null;
        $msg = "No responses available for " . $from;
        $other = [
            "event_index" => $event_index,
            "events"      => $date_events,
        ];
    }
    Helper::print_to_json($rows, $msg, $from, $other);
}

function get_details($db, $date, $event_index = 0)
{
    $query =
        'SELECT details,niyaz,enabled from events where date="' .
        $date . '" AND event_index=' . (int)$event_index . ';';
    $result = $db->query($query);
    if (!$result || $result->num_rows != 1) {
        return "";
    }
    $row = $result->fetch_assoc();
    if (!$row["enabled"]) {
        return null;
    }
    return $row;
}

// Post update to details
function print_post($db, $from, $event_index, $offset)
{
    $msg = "";
    $data = json_decode(file_get_contents("php://input"), false);
    $save = AuthService::is_save_available($offset);

    if ($save) {
        $stmt = $db->prepare(
            "UPDATE rsvps SET here = ?, filled = ? WHERE thaali_id = ? AND date = ? AND event_index = ?",
        );
        foreach ($data as $i) {
            $thaali_id = $i->thaali;
            $stmt->bind_param("iiisi", $i->here, $i->filled, $thaali_id, $from, $event_index);
            $result = $stmt->execute();
            if (!$result) {
                $msg = $stmt->error;
                break;
            }
        }
    } else {
        $msg = "Unable to save, please try later";
    }

    if (!$msg) {
        $msg = "Thank you, changes have been saved";
        return print_filling($db, $from, $event_index, $offset, $msg);
    } else {
        $msg = "Error: " . $msg;
    }

    Helper::json_error($msg);
}
?>
