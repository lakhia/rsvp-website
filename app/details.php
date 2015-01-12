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
    $from = $_GET['from'];
    $to = $_GET['to'];

    // Make query
    $query = "SELECT week.date, details, rsvp FROM week " .
        "LEFT JOIN rsvps ON rsvps.date = week.date " .
        "AND rsvps.thaali_id = " . $thaali . "";
    if ($from) 
    {
        $query .= " WHERE week.date >= '"
            . $from . "' AND week.date < '" . $to . "';";
    }
    else
    {
        $query .= ";";
    }
    
    $result = $db->query($query);

    // Get cutoff time for disabling entry
    $cutoff = Helper::rsvp_disabled();

    // Convert rows
    // TODO: Check if $result is non-object
    while($row = $result->fetch_assoc()) {

        // Editing is only allowed for dates past cutoff
        if ($row["date"] >= $cutoff) {
            $row["enabled"] = "true";
        }

        // Convert rsvp boolean to text
        if ($row["rsvp"]) {
            $row["rsvp"] = "Yes";
        } else {
            $row["rsvp"] = "No";
        }

        $rows[] = $row;
    }
    echo Helper::convert_array_to_json($rows, $msg);
}

// Post update to details
function details_post($db, $thaali)
{
    $data = json_decode(file_get_contents('php://input'), true);

    foreach ($data as $k => $v) {
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
