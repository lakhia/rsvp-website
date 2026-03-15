<?php

require_once "bootstrap.php";
require_once "RsvpService.php";

// If token is invalid, return an empty response
if (!AuthService::verify_token($db, $email_cookie, $thaali_cookie)) {
    Helper::json_error("Login failed, please logout and login again");
}

$service = new RsvpService($db);
$default_size = $service->getDefaultSize($thaali_cookie);
$eligible_sizes = $service->getEligibleSizes(AuthService::is_admin($email_cookie), $default_size);

// POST or GET?
if ($method_server == "POST") {
    details_post($db, $service, $thaali_cookie, $eligible_sizes, $default_size);
} else {
    details_get($db, $service, $thaali_cookie, $eligible_sizes, $default_size, "");
}

// Get details for specific dates
function details_get($db, $service, $thaali, $eligible_sizes, $default_size, $msg)
{
    $offset = Helper::get_param('offset', 0);
    $date = Helper::get_param('date', "");
    $from = Helper::get_week($date, $offset);
    $to = Helper::get_week($date, $offset + 7);

    // Make query
    $query = "SELECT events.date, adults, kids, niyaz, enabled, " .
        " details, rsvp, size, lessRice FROM events " .
        "LEFT JOIN rsvps ON rsvps.date = events.date AND rsvps.thaali_id = " .
        $thaali . " WHERE details > '' AND events.date >= '" .
        $from . "' AND events.date < '" . $to . "' order by date;";

    $result = $db->query($query);

    // Get cutoff time for disabling entry
    $cutoff = AuthService::get_cutoff_time(1);

    // Convert rows
    while ($row = $result->fetch_assoc()) {
        $rows[] = $service->normalizeRow($row, $cutoff, $default_size);
    }
    if (isset($rows)) {
        Helper::print_to_json($rows, $msg, $date, $eligible_sizes);
    } else {
        Helper::json_error("No details available for week of $from");
    }
}

// Post update to details
function details_post($db, $service, $thaali, $eligible_sizes, $default_size)
{
    // Get cutoff time for disabling entry
    $cutoff = AuthService::get_cutoff_time(1);
    $data = json_decode(file_get_contents('php://input'), true);

    foreach ($data as $date => $v) {
        try {
            $entry = $service->validateEntry($date, $cutoff, $v, $eligible_sizes, $default_size);
        } catch (\InvalidArgumentException $e) {
            $msg = $e->getMessage();
            break;
        }

        if ($entry === null) {
            continue;
        }

        [$cols, $placeholders, $updates, $types, $values] = Helper::dict_to_upsert_parts($entry);

        $stmt = $db->prepare("INSERT INTO rsvps (date, thaali_id, $cols) " .
                             "VALUES (?, ?, $placeholders) " .
                             "ON DUPLICATE KEY UPDATE $updates");
        $stmt->bind_param("si$types", $date, $thaali, ...$values);
        if ($stmt->execute()) {
            $msg = "Thank you, changes have been saved!";
        } else {
            $msg = $stmt->error;
        }
    }

    details_get($db, $service, $thaali, $eligible_sizes, $default_size, $msg);
}

?>
