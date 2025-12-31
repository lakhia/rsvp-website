<?php

require_once "config.php";
require_once "auxil.php";
require_once "sizes.php";

// If token is invalid, return an empty response
if (!Helper::verify_token($db, $email_cookie, $thaali_cookie)) {
    die('{ "msg": "Login failed, please logout and login again" }');
}
$default_size = get_default_size($db, $thaali_cookie);
$eligible_sizes = get_eligible_sizes($email_cookie, $default_size);

// POST or GET?
if ($method_server == "POST") {
    details_post($db, $thaali_cookie, $eligible_sizes, $default_size);
} else {
    details_get($db, $thaali_cookie, $eligible_sizes, $default_size, "");
}

/**
 * Determines which thaali sizes a user is eligible to select for their RSVP.
 * Admins always have access to all sizes regardless of the mode.
 *
 * Size selection is configurable. See config.php for all possible
 * values and what they mean.
 *
 * @param string $email User's email address (used to check admin status)
 * @param string $size User's default thaali size (e.g., "S", "M", "L")
 * @return array Array of eligible size strings (e.g., ["S", "M", "L"])
 */
function get_eligible_sizes($email, $size)
{
    $all_sizes = Config::THAALI_SIZES;

    // Admins can always select any size
    if (Helper::is_admin($email)) {
        return $all_sizes;
    }

    // Size selection logic selected based on config
    $functionName = "sizes_" . Config::SIZE_SELECTION_MODE;
    if (function_exists($functionName)) {
        return $functionName($size, $all_sizes);
    } else {
        die("Thaali size selection not configured correctly: " .
            Config::SIZE_SELECTION_MODE);
    }
}

function get_default_size($db, $thaali)
{
    $query = "SELECT size FROM family where thaali = " . $thaali;
    $result = $db->query($query);
    if (!$result || $result->num_rows != 1) {
        return "M";
    }
    $row = $result->fetch_assoc();
    return $row['size'];
}

// Get details for specific dates
function details_get($db, $thaali, $eligible_sizes, $default_size, $msg)
{
    $offset = Helper::get_if_defined($_GET['offset'], 0);
    $date = Helper::get_if_defined($_GET['date'], "");
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
    $cutoff = Helper::get_cutoff_time(1);

    // Convert rows
    while($row = $result->fetch_assoc()) {
        // Editing is only allowed for dates past cutoff
        if ($row["date"] < $cutoff) {
            $row["readonly"] = "1";
        }
        if (!$row["niyaz"]) {
            unset($row['niyaz']);
        }
        if (!$row["enabled"]) {
            unset($row["enabled"]);
        }
        if (!$row["rsvp"]) {
            unset($row['rsvp']);
            if ($row['adults'] == 0) {
                unset($row['adults']);
            }
            if ($row['kids'] == 0) {
                unset($row['kids']);
            }
        }
        if (!$row['size']) {
            $row['size'] = $default_size;
        }
        if (!$row["lessRice"]) {
            unset($row['lessRice']);
        }
        $rows[] = $row;
    }
    if (isset($rows)) {
        Helper::print_to_json($rows, $msg, $date, $eligible_sizes);
    } else {
        die('{ "msg": "No details available for week of ' . $from . '" }');
    }
}

// Post update to details
function details_post($db, $thaali, $eligible_sizes, $default_size)
{
    // Get cutoff time for disabling entry
    $cutoff = Helper::get_cutoff_time(1);
    $data = json_decode(file_get_contents('php://input'), true);

    foreach ($data as $date => $v) {

        // Editing is only allowed for dates past cutoff
        if ($date < $cutoff) {
            continue;
        }

        // Validate
        if (isset($v['adults'])) {
            // If adults is set then this was Niyaz RSVP
            if ($v['adults'] < 0) {
                $v['adults'] = 0;
            }
            if ($v['kids'] < 0) {
                $v['kids'] = 0;
            }
            if ($v['adults'] + $v['kids'] == 0) {
                $v['rsvp'] = False;
            }
        }
        // Retrieve changes from dict
        list($changes, $cols, $vals) = Helper::dict_to_sql_assignment($v, array("size"));

        // Set size to default if not set
        if (!isset($v['size'])) {
            $cols .= ", size";
            $vals .= ", \"" . $default_size . "\"";
        } else {
            if (!in_array($v['size'], $eligible_sizes)) {
                $msg = "You picked a size too large for your family, please try again!";
                break;
            }
        }

        $stmt = $db->prepare("INSERT INTO rsvps (date, thaali_id, $cols) " .
                             "VALUES (?, ?, $vals) " .
                             "ON DUPLICATE KEY UPDATE $changes");
        $stmt->bind_param("si", $date, $thaali);
        if ($stmt->execute()) {
            $msg = "Thank you, changes have been saved!";
        } else {
            $msg = $stmt->error;
        }
    }

    details_get($db, $thaali, $eligible_sizes, $default_size, $msg);
}

?>
