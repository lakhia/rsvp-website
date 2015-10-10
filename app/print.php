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
        "WHERE `rsvp` = 1 AND `date` = '" . $from . "';";

    $result = $db->query($query);

    $output = array();
    while($row = $result->fetch_assoc()) {
        $temp = array();
        $row['name'] = $row['firstName'] . " " . $row['lastName'];
        unset($row['firstName']);
        unset($row['lastName']);
        $output[] = $row;
    }
    if (count($output) > 0) {
        echo Helper::convert_array_to_json($output, "");
    } else {
        die('{ "message": "No responses available for ' . $from . '" }');
    }
}

?>
