<?php

require_once('aux.php');

// If token is invalid, return an empty response
if (!Helper::verify_token($email_cookie, $thaali_cookie)) {
    return;
}

printout_filling($db);

// Get details for filling team
function printout_filling($db) {

    // Make query
    $query = "SELECT `thaali`, `lastName`, `firstName` FROM `rsvps` " .
        "LEFT JOIN `family` on family.thaali = rsvps.thaali_id " .
        "WHERE `rsvp` = 1 AND `date` = CURDATE();";

    $result = $db->query($query);

    $output = array();

    while( $row = $result->fetch_assoc())
    {
        $temp = array();
            $temp['name'] = $row['firstName'] . " " . $row['lastName'];
            $temp['thaali'] = $row['thaali'];
        //    $temp['notes'] = $row['notes'];
        $output[] = $temp;
    }

    echo Helper::convert_array_to_json($output, "");
}

?>