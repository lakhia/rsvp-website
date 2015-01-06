<?php

require_once("aux.php");

if (!Helper::verify_token($email, $thaali)) {
    return;
}

// POST or GET?
if (strcmp($method, "POST") == 0) {
    details_post($db, $thaali);
} else {
    details_get($db, $thaali, ""); 
}

// Get details for specific dates
function details_get($db, $thaali, $msg) {

   // header("Content-Type: application/json; charset=UTF-8");

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
function details_post($db, $thaali) {
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
            $msg =  $db->error; // using object-oriented style
        } else {
            $msg = "Thank you, changes have been saved!";
        }
    }

    details_get($db, $thaali, $msg);
}

?>
