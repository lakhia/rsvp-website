<?php

require_once "bootstrap.php";
require_once "MenuNames.php";

// If token is invalid, return an empty response
if (!AuthService::is_admin($email_cookie) ||
    !AuthService::verify_token($db, $email_cookie, $thaali_cookie)) {
    Helper::json_error("Login failed, please logout and login again");
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
    $offset = Helper::get_param('offset', 0);
    $date = Helper::get_param('date', "");
    $from = Helper::get_week($date, $offset);
    $to = Helper::get_week($date, $offset + 7);

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
        if (!isset($row["date"]) || $d != $row["date"]) {
            $rows[] = array("date" => $d);
        } else {
            $rows[] = $row;
            unset($row);
        }
    }

    if (isset($rows)) {
        Helper::print_to_json($rows, $msg, $from);
    } else {
        Helper::json_error("No details available for week of $from");
    }
}


function fix_details(string $details): string
{
    $out = "";
    foreach (explode(",", $details) as $item) {
        $item = MenuNames::canonicalize($item);
        if ($item !== '') {
            $out .= ", " . $item;
        }
    }
    return substr($out, 2);
}

// Post update to details
function event_post($db)
{
    $msg = "";
    $data = json_decode(file_get_contents('php://input'), false);
    $stmt = $db->prepare("INSERT INTO events (date, details, enabled, niyaz) " .
                         "VALUES (?, ?, ?, ?) " .
                         "ON DUPLICATE KEY UPDATE " .
                         "details = ?, enabled = ?, niyaz = ?");

    foreach ($data as $i) {
        $date = $i->date;

        // Take care of uninit variables
        $enabled = 0;
        if (isset($i->enabled) && $i->enabled) {
            $enabled = 1;
        }
        $niyaz = 0;
        if ($enabled && isset($i->niyaz) && $i->niyaz) {
            $niyaz = 1;
        }
        $details = Helper::get_if_defined($i->details, "");
        if ($details == "") {
            $query = "DELETE FROM events WHERE date = '$date';";
            if (!$db->query($query)) {
                $msg =  $db->error;
                break;
            }
        } else {
            $details = fix_details($details);
            $stmt->bind_param("ssiisii",
                              $date, $details, $enabled, $niyaz, 
                              $details, $enabled, $niyaz);
            if (!$stmt->execute()) {
                $msg = $stmt->error;
                break;
            }
        }
    }
    if (!$msg) {
        $msg = "Thank you, changes have been saved!";
        return event_get($db, $msg);
    } else {
        Helper::json_error($msg);
    }
}

?>
