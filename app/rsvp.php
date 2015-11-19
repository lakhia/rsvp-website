<?php

require_once("aux.php");

// If token is invalid, return an empty response
if (!Helper::verify_token($email_cookie, $thaali_cookie)) {
    return;
}

// POST or GET?
if ($method_server == "POST") {
    details_post($db, $thaali_cookie);
} else {
    details_get($db, $thaali_cookie, "");
}

// Get details for specific dates
function details_get($db, $thaali, $msg)
{
    $offset = 0;
    if (isset($_GET['offset'])) {
        $offset = $_GET['offset'];
    }
    $from = Helper::get_week($offset);
    $to = Helper::get_week($offset + 7);

    // Make query
    $query = "SELECT events.date, enabled, details, rsvp FROM events " .
        "LEFT JOIN rsvps ON rsvps.date = events.date " .
        "AND rsvps.thaali_id = " . $thaali . " WHERE details > ''";
    if ($from) {
        $query .= " AND events.date >= '"
            . $from . "' AND events.date < '" . $to . "';";
    } else {
        $query .= ";";
    }

    $result = $db->query($query);

    // Get cutoff time for disabling entry
    $cutoff = Helper::get_cutoff_time(1);

    // Convert rows
    // TODO: Check if $result is non-object
    while($row = $result->fetch_assoc()) {

        // Editing is only allowed for dates past cutoff
        if ($row["date"] < $cutoff) {
            $row["readonly"] = "1";
        }

        // Convert rsvp boolean to text
        if ($row["rsvp"]) {
            $row["rsvp"] = "Yes";
        } else {
            $row["rsvp"] = "No";
        }

        $rows[] = $row;
    }
    if (isset($rows)) {
        Helper::print_to_json($rows, $msg, NULL);
    } else {
        die('{ "msg": "No details available for week of ' . $from . '" }');
    }
}

// Post update to details
function details_post($db, $thaali)
{
    // Get cutoff time for disabling entry
    $cutoff = Helper::get_cutoff_time(1);

    $data = json_decode(file_get_contents('php://input'), true);

    foreach ($data as $k => $v) {

        // Editing is only allowed for dates past cutoff
        if ($k < $cutoff) {
            continue;
        }

        // Convert "Yes" back to boolean
        $response = 0;
        if ($v == "Yes") {
            $response = 1;
        }
        $result = $db->query("insert into rsvps(date, thaali_id, rsvp) " .
                               "values(\"$k\", $thaali, $response) " .
                               "on duplicate KEY update rsvp=$response");
        if (!$result) {
            $msg =  $db->error;
        } else {
            $msg = "Thank you, changes have been saved!";
        }
    }

    details_get($db, $thaali, $msg);
}

?>
