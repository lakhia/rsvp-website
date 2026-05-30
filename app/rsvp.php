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
    $from = Helper::get_week("", $offset);
    $to = Helper::get_week("", $offset + 7);

    // Make query
    $query = "SELECT events.date, events.event_index, adults, kids, niyaz, enabled, " .
        " details, rsvp, size, norice FROM events " .
        "LEFT JOIN rsvps ON rsvps.date = events.date AND rsvps.event_index = events.event_index " .
        " AND rsvps.thaali = " . $thaali .
        " WHERE details > '' AND events.date >= '" .
        $from . "' AND events.date < '" . $to .
        "' ORDER BY events.date, events.event_index;";

    $result = $db->query($query);

    // Get cutoff time for disabling entry
    $cutoff = AuthService::get_cutoff_time(1);

    // Convert rows
    while ($row = $result->fetch_assoc()) {
        $rows[] = $service->normalizeRow($row, $cutoff, $default_size);
    }
    if (isset($rows)) {
        Helper::print_to_json($rows, $msg, other: $eligible_sizes);
    } else {
        Helper::json_error("No details available for week of $from");
    }
}

// Post update to details — body is an array of event objects with date, event_index, and RSVP fields
function details_post($db, $service, $thaali, $eligible_sizes, $default_size)
{
    // Get cutoff time for disabling entry
    $cutoff = AuthService::get_cutoff_time(1);
    $data = json_decode(file_get_contents('php://input'), true);
    $msg = "";

    foreach ($data as $item) {
        $date = $item['date'];
        $event_index = (int)($item['event_index'] ?? 0);
        $entry_data = array_diff_key($item, array_flip(['date', 'event_index']));

        try {
            $entry = $service->validateEntry($date, $cutoff, $entry_data, $eligible_sizes, $default_size);
        } catch (\InvalidArgumentException $e) {
            $msg = $e->getMessage();
            break;
        }

        if ($entry === null) {
            continue;
        }

        [$cols, $placeholders, $updates, $types, $values] = Helper::dict_to_upsert_parts($entry);

        $stmt = $db->prepare("INSERT INTO rsvps (date, event_index, thaali, $cols) " .
                             "VALUES (?, ?, ?, $placeholders) " .
                             "ON DUPLICATE KEY UPDATE $updates");
        $stmt->bind_param("sii$types", $date, $event_index, $thaali, ...$values);
        if ($stmt->execute()) {
            $msg = "Thank you, changes have been saved!";
        } else {
            $msg = $stmt->error;
        }
    }

    details_get($db, $service, $thaali, $eligible_sizes, $default_size, $msg);
}

?>
