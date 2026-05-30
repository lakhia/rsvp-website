<?php

require_once "bootstrap.php";

// If token is invalid, return an empty response
if (!AuthService::verify_token($db, $email_cookie, $thaali_cookie)) {
    Helper::json_error("Login failed, please logout and login again");
}

// Start dump for special report
$date = Helper::get_param('date', 0);
if ($date == 0) {
   $offset = Helper::get_param('offset', 0);
   $date = Helper::get_day($offset);
}
$event_index = (int)Helper::get_param('event_index', 0);
dump_get($db, $date, $event_index);

// Get dump in CSV format
function dump_get($db, $date, $event_index) {
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
              ON thaali_id=thaali AND date=\"" . $date . "\" AND event_index=" . $event_index . "
              WHERE thaali < 400";
    $result = $db->query($query);

    // Output rows
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
}

?>
