<?php

require_once("auxil.php");

// If token is invalid, return an empty response
if (!Helper::verify_token($db, $email_cookie, $thaali_cookie)) {
    die('{ "msg": "Login failed, please logout and login again" }');
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
    $offset = Helper::get_if_defined($_GET['offset'], 0);
    $from = Helper::get_week($offset);
    $to = Helper::get_week($offset + 7);

    // Make query
    $query = "SELECT events.date, adults, kids, niyaz, enabled, details, rsvp, lessRice FROM events " .
        "LEFT JOIN rsvps ON rsvps.date = events.date AND rsvps.thaali_id = " .
        $thaali . " WHERE details > '' AND events.date >= '" .
        $from . "' AND events.date < '" . $to . "' order by date;";

    $result = $db->query($query);

    // Get cutoff time for disabling entry
    $cutoff = Helper::get_cutoff_time(1);

    // Convert rows
    while($row = $result->fetch_assoc()) {
        // Editing is only allowed for dates past cutoff
        if ($row["date"] < $cutoff) {
            $row["readonly"] = "1";
        }
        if (!$row["niyaz"]) {
            unset($row['niyaz']);
        }
        if (!$row["rsvp"]) {
            unset($row['rsvp']);
            if ($row['adults'] == 0) {
                unset($row['adults']);
            }
            if ($row['kids'] == 0) {
                unset($row['kids']);
            }
        }
        if (!$row["lessRice"]) {
            unset($row['lessRice']);
        }
        $rows[] = $row;
    }
    if (isset($rows)) {
        Helper::print_to_json($rows, $msg);
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

        // Retrieve items
        $response = Helper::get_if_defined($v['rsvp']) ? 1 : 0;
        $lessRice = Helper::get_if_defined($v['lessRice'], "null");
        $adults = intval(Helper::get_if_defined($v['adults'], 0));
        $kids = intval(Helper::get_if_defined($v['kids'], 0));

        // Validate
        if ($adults < 0) {
            $adults = 0;
        }
        if ($kids < 0) {
            $kids = 0;
        }
        if (!$response) {
            $lessRice = 0;
        }
        if (isset($v['adults']) && $adults + $kids == 0) {
            // If adults is set then this was Niyaz RSVP
            $response = 0;
        }

        $result = $db->query("insert into rsvps(date, thaali_id, rsvp, lessRice, adults, kids) " .
                             "values(\"$k\", $thaali, $response, $lessRice, $adults, $kids) " .
                             "on duplicate KEY update rsvp=$response, lessRice=$lessRice, " .
                             "adults=$adults, kids=$kids");
        if (!$result) {
            $msg =  $db->error;
        } else {
            $msg = "Thank you, changes have been saved!";
        }
    }

    details_get($db, $thaali, $msg);
}

?>
