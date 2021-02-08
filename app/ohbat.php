<?php

require_once("auxil.php");

// If token is invalid, return an empty response
if (!Helper::verify_token($db, $email_cookie, $thaali_cookie)) {
    die('{ "msg": "Login failed, please logout and login again" }');
}

// Start dump for special report
$offset = Helper::get_if_defined($_GET['offset'], 0);
$day = Helper::get_day($offset);
dump_get($db, $day);

// Get dump in CSV format
function dump_get($db, $day) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=ohbat.csv');

    // Open output file
    $output = fopen('php://output', 'w');
    fputcsv($output, ["Thaali","Name","Email","Phone","Area","POC","RSVP"]);

    // Make query
    $query = "SELECT thaali,
                CONCAT(firstName, ' ', lastName) AS name, email, phone, area, poc,
                CASE WHEN rsvp = 1 THEN 'Yes' ELSE 'No' END AS rsvp
              FROM family
              LEFT JOIN rsvps
              ON thaali_id=thaali AND date=\"" . $day . "\"
              WHERE thaali < 400";
    $result = $db->query($query);

    // Output rows
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
}

?>
