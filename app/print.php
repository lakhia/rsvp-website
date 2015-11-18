<?php

require_once('aux.php');

// If token is invalid, return an empty response
if (!Helper::verify_token($email_cookie, $thaali_cookie)) {
    return;
}

print_filling($db);

// Get details for filling team
function print_filling($db) {
    $from = $_GET['from'];

    // Make query
    $query = "SELECT `thaali`, `lastName`, `firstName` FROM `rsvps` " .
        "LEFT JOIN `family` on family.thaali = rsvps.thaali_id " .
        "WHERE `rsvp` = 1 AND `date` = '" . $from . "' ORDER BY thaali;";

    $result = $db->query($query);

    // Append first name and last
    while($row = $result->fetch_assoc()) {
        $row['name'] = $row['firstName'] . " " . $row['lastName'];
        unset($row['firstName']);
        unset($row['lastName']);
        $rows[] = $row;
    }

    // Create message
    if (isset($rows)) {
        $count = count($rows);
        $msg = $count . " thaalis, " . round($count * 1.2, 1) . " estimate";
        if ($from >= Helper::get_cutoff_time(0)) {
            $msg .= ", not locked";
        }
        echo Helper::convert_array_to_json($rows, $msg);
    } else {
        die('{ "message": "No responses available for ' . $from . '" }');
    }
}

?>
