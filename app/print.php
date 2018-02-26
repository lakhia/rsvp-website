<?php

require_once('auxil.php');

// If token is invalid, return an empty response
if (!Helper::verify_token($db, $email_cookie, $thaali_cookie)) {
    die('{ "msg": "Login failed, please logout and login again" }');
}
$offset = 0;
if (isset($_GET['offset'])) {
    $offset = $_GET['offset'];
}
$from = Helper::get_day($offset);
// POST or GET?
if ($method_server == "POST") {
    print_post($db, $from, $offset);
} else {
    print_filling($db, $from, $offset);
}

// Get details for filling team
function print_filling($db, $from, $offset, $msg = "") {
    // Get details for date
    $details = get_details($db, $from);

    if ($details) {
        // Get RSVP and family
        $query = "SELECT thaali, lastName, firstName, size, area, avail, filled FROM `rsvps` " .
            "LEFT JOIN `family` on family.thaali = rsvps.thaali_id " .
            "WHERE `rsvp` = 1 AND `date` = '" . $from . "' ORDER BY thaali;";
        $result = $db->query($query);

        while($row = $result->fetch_assoc()) {

            // Append first name and last
            $row['name'] = $row['firstName'] . " " . $row['lastName'];
            unset($row['firstName']);
            unset($row['lastName']);

            // Count people, only show size if not medium
            $size = $row['size'];
            if ($size == 'M') {
                unset($row['size']);
            }
            $rows[] = $row;
        }
    }
    // Create message
    if (isset($rows)) {
        $count = count($rows);
        $save = Helper::is_save_available($offset);
    } else {
        $rows = NULL;
        $msg = "No responses available for " . $from;
        $save = NULL;
    }
    Helper::print_to_json($rows, $msg, $from, $save);
}

function get_details($db, $date) {
    $query = 'SELECT details,enabled from events where date="' . $date . '";';
    $result = $db->query($query);
    if (!$result || $result->num_rows != 1) {
        return "";
    }
    $row = $result->fetch_assoc();
    if (!$row['enabled']) {
        return "";
    }
    return $row['details'];
}

// Post update to details
function print_post($db, $from, $offset)
{
    $msg = "";
    $data = json_decode(file_get_contents('php://input'), false);
    $save = Helper::is_save_available($offset);

    if ($save) {
        foreach ($data as $i) {
            $thaali_id      = $i->thaali;

            $query = "UPDATE rsvps set avail='". $i->avail .
               "',  filled = '" . $i->filled . "'  " .
               "WHERE  thaali_id = '" . $thaali_id .
               "' and date = '" . $from . "'";

            $result = $db->query($query);
            if (!$result) {
                $msg =   $db->error ;
                break;
            };
        };
    } else {
        $msg = "Unable to save, please try later";
    }

    if (!$msg) {
        $msg = "Thank you, changes have been saved";
        return  print_filling($db, $from, $offset, $msg);
    } else {
        $msg = "Error: " . $msg;
    }

    die('{ "msg": "' . $msg . '" }');
}
?>
