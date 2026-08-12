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

    $query = "SELECT * FROM events WHERE date >= '" .
        $from . "' AND date < '" . $to . "' ORDER BY date, event_index;";

    $result = $db->query($query);

    // Group DB rows by date
    $events_by_date = [];
    while ($row = $result->fetch_assoc()) {
        $events_by_date[$row['date']][] = $row;
    }

    // Build output: one placeholder per date that has no events
    $period = new DatePeriod(
                  new DateTime($from),
                  new DateInterval('P1D'),
                  new DateTime($to));

    foreach ($period as $d) {
        $ds = $d->format('Y-m-d');
        if (isset($events_by_date[$ds])) {
            foreach ($events_by_date[$ds] as $ev) {
                $rows[] = $ev;
            }
        } else {
            $rows[] = ['date' => $ds, 'event_index' => 0];
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
    $stmt = $db->prepare("INSERT INTO events (date, event_index, details, enabled, niyaz) " .
                         "VALUES (?, ?, ?, ?, ?) " .
                         "ON DUPLICATE KEY UPDATE " .
                         "details = ?, enabled = ?, niyaz = ?");

    foreach ($data as $i) {
        $date = $i->date;
        $event_index = isset($i->event_index) ? (int)$i->event_index : 0;

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
            $query = "DELETE FROM events WHERE date = '$date' AND event_index = $event_index;";
            if (!$db->query($query)) {
                $msg = $db->error;
                break;
            }
        } else {
            $details = fix_details($details);
            $stmt->bind_param("sisiisii",
                              $date, $event_index, $details, $enabled, $niyaz,
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
