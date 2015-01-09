<?php

require_once('aux.php');

if (!Helper::verify_token($email, $thaali_cookie)) {
    return;
}

// POST or GET?
if ($method_server == "POST") // because $method is set by the $_SERVER[] superglobal, strcmp() is unnecessary security, $method can only ever by POST or GET
{
    // Not implemented
}
else 
{
    printout_get($db);
}

// Get details for today
function printout_get($db) {

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

// Post update to details
function details_post($db) {
}

?>