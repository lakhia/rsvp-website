<?php

require_once('aux.php');

// If token is invalid, return an empty response
if (!Helper::verify_token($db, $email_cookie, $thaali_cookie)) {
    die('{ "msg": "Login failed, please logout and login again" }');
}

print_filling($db);

// Get details for filling team
function print_filling($db) {
    $offset = 0;
    if (isset($_GET['offset'])) {
        $offset = $_GET['offset'];
    }
    $from = Helper::get_day($offset);

    // Get details for date
    $details = get_details($db, $from);
    $people = 0;

    if ($details) {
        // Get RSVP and family
        $query = "SELECT thaali, lastName, firstName, size FROM `rsvps` " .
            "LEFT JOIN `family` on family.thaali = rsvps.thaali_id " .
            "WHERE `rsvp` = 1 AND `date` = '" . $from . "' ORDER BY thaali;";
        $result = $db->query($query);

        while($row = $result->fetch_assoc()) {

            // Append first name and last
            $row['name'] = $row['firstName'] . " " . $row['lastName'];
            unset($row['firstName']);
            unset($row['lastName']);

            // Count people, only show size if not medium
            $size = $row['size'];
            if ($size == 'L') {
                $people += 6;
            } else if ($size == 'S') {
                $people += 2;
            } else {
                unset($row['size']);
                $people += 4;
            }
            $rows[] = $row;
        }
    }
    // Create message
    if (isset($rows)) {
        $count = count($rows);
        $msg = $count . " thaalis, " . $people . " people";
        if ($from >= Helper::get_cutoff_time(0)) {
            $msg .= ", not locked";
        }
    } else {
        $rows = NULL;
        $msg = "No responses available for " . $from;
    }
    Helper::print_to_json($rows, $msg, $from);
}

function get_details($db, $date) {
    $query = 'SELECT details,enabled from events where date="' . $date . '";';
    $result = $db->query($query);
    if (!$result || $result->num_rows != 1) {
        return "";
    }
    $row = $result->fetch_assoc();
    if (!$row['enabled']) {
        return "";
    }
    return $row['details'];
}
?>
