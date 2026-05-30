<?php
require_once "bootstrap.php";

if (!AuthService::verify_token($db, $email_cookie, $thaali_cookie)) {
    Helper::json_error("Login failed, please logout and login again");
}

$offset = (int) Helper::get_param('offset', 0);

if (Config::DOWNLOAD_WEEK_RANGE) {
    $from = Helper::get_week("", $offset);
    $to   = Helper::get_week("", $offset + 7);
    $filename = 'rsvps_week_' . $from . '.csv';
    $date_where = "rsvps.date >= '$from' AND rsvps.date < '$to'";
} else {
    $date = Helper::get_day($offset);
    $filename = 'rsvps_' . $date . '.csv';
    $date_where = "rsvps.date = '$date'";
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');
fputcsv($output, ['date', 'menu', 'thaali', 'name', 'area', 'size', 'adults', 'kids', 'here', 'filled', 'norice'], ',', '"', '\\');

$query =
    "SELECT events.date, events.details AS menu, " .
    "rsvps.thaali_id AS thaali, CONCAT(family.firstName, ' ', family.lastName) AS name, " .
    "family.area, rsvps.size, rsvps.adults, rsvps.kids, rsvps.here, rsvps.filled, rsvps.lessRice AS norice " .
    "FROM rsvps " .
    "LEFT JOIN family ON family.thaali = rsvps.thaali_id " .
    "LEFT JOIN events ON events.date = rsvps.date AND events.event_index = rsvps.event_index " .
    "WHERE rsvps.rsvp = 1 AND $date_where " .
    "ORDER BY rsvps.date, rsvps.thaali_id;";

$result = $db->query($query);
while ($row = $result->fetch_assoc()) {
    fputcsv($output, array_values($row), ',', '"', '\\');
}
?>
