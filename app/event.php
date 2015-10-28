<?php

require_once("aux.php");

// If token is invalid, return an empty response
if (!Helper::verify_token($email_cookie, $thaali_cookie)) {
    return;
}

// POST or GET?
if ($method_server == "POST") {
    event_post($db, $thaali_cookie);
} else {
    event_get($db, $thaali_cookie, "");
}

// Get details for specific dates
function event_get($db, $thaali, $msg)
{
    $from = $_GET['from'];
    $to = $_GET['to'];

    // Make query
    $query = "SELECT * FROM week";
    if ($from) {
        $query .= " WHERE date >= '"
            . $from . "' AND date < '" . $to . "';";
    } else {
        $query .= ";";
    }

    $result = $db->query($query);

    // Convert rows
    while($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    if (isset($rows)) {
        echo Helper::convert_array_to_json($rows, $msg);
    } else {
        die('{ "message": "No details available for week of ' . $from . '" }');
    }
}

// Post update to details
function event_post($db)
{
    $data = json_decode(file_get_contents('php://input'), false);

    foreach ($data as $i) {
        $date = $i->{'date'};
        $details = $i->{'details'};
        $enabled = $i->{'enabled'};
        if (!isset($enabled) || $enabled == "") $enabled = 0;
        $result = $db->query("insert into week(date, details, enabled) " .
                             "values(\"$date\", \"$details\", $enabled) " .
                             "on duplicate KEY " .
                             "update details=\"$details\", enabled=$enabled");
        if (!$result) {
            $msg =  $db->error;
            break;
        } else {
            $msg = "Thank you, changes have been saved!";
        }
    }
    die('{ "message": "' . $msg . '" }');
}

?>
