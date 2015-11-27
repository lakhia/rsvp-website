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
    $offset = 0;
    if (isset($_GET['offset'])) {
        $offset = $_GET['offset'];
    }
    $from = Helper::get_week($offset);
    $to = Helper::get_week($offset + 7);

    // Make query
    $query = "SELECT * FROM events WHERE date >= '" . 
        $from . "' AND date < '" . $to . "' order by date;";

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
        Helper::print_to_json($rows, $msg, $from);
    } else {
        die('{ "msg": "No details available for week of ' . $from . '" }');
    }
}

// Post update to details
function event_post($db)
{
    $data = json_decode(file_get_contents('php://input'), false);

    foreach ($data as $i) {
        $date = $i->date;

        // Take care of uninit variables
        $enabled = 0;
        if (isset($i->enabled) && $i->enabled) {
            $enabled = $i->enabled;
        }
        $details = "";
        if (isset($i->details)) {
            $details = $i->details;
        }

        if ($details == "") {
            $query = "DELETE FROM events WHERE date = '$date';";
        } else {
            $query = "INSERT INTO events(date, details, enabled) " .
                     "VALUES(\"$date\", \"$details\", $enabled) " .
                     "ON DUPLICATE KEY " .
                     "UPDATE details=\"$details\", enabled=$enabled";
        }
        $result = $db->query($query);
        if (!$result) {
            $msg =  $db->error;
            break;
        } else {
            $msg = "Thank you, changes have been saved!";
        }
    }
    die('{ "msg": "' . $msg . '" }');
}

?>
