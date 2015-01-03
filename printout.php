<?php

include 'init.php';

if (!verify_token($email, $thaali)) {
    return;
}

header("Content-Type: application/json; charset=UTF-8");

// POST or GET?
if (strcmp($method, "POST") == 0) {
    // Not implemented
} else {
    printout_get();
}

// Get details for today
function printout_get() {

    header("Content-Type: application/json; charset=UTF-8");

    // Make query
    $query = "SELECT thaali, lastName, firstName from rsvps " .
        "left join family on family.thaali = rsvps.thaali_id " .
        "where rsvp = 1 and date = date_format(CURRENT_TIMESTAMP(),'%Y-%m-%d')";
    $result = $conn->query($query) or die(mysql_error());

    // Output JSON
    $first = 1;
    echo "[\n";

    // Output each row
    while($row = $result->fetch_assoc()) {
        if ($first) {
            $first = 0;
        } else {
            print ",\n";
        }
        echo '{"thaali":' . $row["thaali"] . ',"name":"' . $row["firstName"]
            . " " . $row["lastName"] . '", "notes":""';
        echo '}';
    }

    // Done
    echo "\n]\n";
}

// Post update to details
function details_post($conn) {
}

?>
