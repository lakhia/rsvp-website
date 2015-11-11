<?php

require_once("aux.php");

// If token is invalid, return an empty response
if (!Helper::is_admin($email_cookie) ||
    !Helper::verify_token($email_cookie, $thaali_cookie)) {
    return;
}

// POST or GET?
if ($method_server == "POST") {
    event_post($db);
} else {
    event_get($db, "");
}

// Get details for specific dates
function event_get($db, $msg)
{
    $from = $_GET['from'];
    $to = $_GET['to'];

    // Make query
    $query = "SELECT * FROM events";
    if ($from) {
        $query .= " WHERE date >= '"
            . $from . "' AND date < '" . $to . "';";
    } else {
        $query .= ";";
    }
    $result = $db->query($query);

    // Get all dates between range
    $period = new DatePeriod(
                  new DateTime($from),
                  new DateInterval('P1D'),
                  new DateTime($to));

    // Save rows, add place holder dates when needed
    foreach($period as $date) {
        $d = $date->format('Y-m-d');
        if (!isset($row)) {
            $row = $result->fetch_assoc();
        }
        if ($d != $row["date"]) {
            $k = array("date" => $d);
            $rows[] = $k;
        } else {
            $rows[] = $row;
            unset($row);
        }
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
        $date = $i->date;
        $details = $i->details;
        $enabled = $i->enabled;
        if (!isset($enabled) || $enabled == "") $enabled = 0;
        $result = $db->query("insert into events(date, details, enabled) " .
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
