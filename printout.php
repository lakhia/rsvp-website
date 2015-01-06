<?php

require_once('aux.php');

if (!Helper::verify_token($email, $thaali)) {
    return;
}

// POST or GET?
if ($method == "POST") // because $method is set by the $_SERVER[] superglobal, strcmp() is unnecessary security, $method can only ever by POST or GET
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
    $query = "SELECT thaali, lastName, firstName from rsvps " .
        "LEFT JOIN family on family.thaali = rsvps.thaali_id " .
        "WHERE rsvp = 1 and date = CURDATE();";

    $result = $db->query($query);

    $output = array();

    // // Output JSON
    // $first = 1;
    // echo "[\n";

    // // Output each row
    // while($row = $result->fetch_assoc()) {
    //     if ($first) {
    //         $first = 0;
    //     } else {
    //         print ",\n";
    //     }
    //     echo '{"thaali":' . $row["thaali"] . ',"name":"' . $row["firstName"]
    //         . " " . $row["lastName"] . '", "notes":""';
    //     echo '}';
    // }

    while( $row = $result->fetch_assoc())
    {
        $temp = array();
            $temp['name'] = $row['firstName'] . " " . $row['lastName'];
            $temp['thaali'] = $row['thaali'];
        //    $temp['notes'] = $row['notes'];
        $output[] = $temp;
    }

    echo Helper::convert_array_to_json($output, "");
    // Done
    // echo "\n]\n";
}

// Post update to details
function details_post($db) {
}

?>