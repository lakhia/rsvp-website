<?php
require_once('auxil.php');
require_once('estimation.php');

// If token is invalid, return an empty response
if (!Helper::verify_token($db, $email_cookie, $thaali_cookie)) {
    die('{ "msg": "Login failed, please logout and login again" }');
}
$offset = Helper::get_if_defined($_GET['offset'], 0);
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
        $query = "SELECT thaali_id as thaali, CONCAT(firstName, ' ', lastName) AS name, " .
            "adults, kids, rsvps.size, area, here, filled, lessRice FROM rsvps " .
            "LEFT JOIN `family` on family.thaali = rsvps.thaali_id " .
            "WHERE `rsvp` = 1 AND `date` = '" . $from . "' ORDER BY thaali;";
        $result = $db->query($query);
        $totalA = 0;
        $totalK = 0;

        while($row = $result->fetch_assoc()) {
            if ($details['niyaz']) {
                $totalA += $row['adults'];
                $totalK += $row['kids'];
                $row['size'] = $row['adults'] . " / " . $row['kids'];
                unset($row['adults']);
                unset($row['kids']);
            }

            // Convert lessRice boolean to text
            if ($row["lessRice"] == 1) {
                $row["bread+rice"] = "No";
            }
            unset($row["lessRice"]);

            $rows[] = $row;
        }
    }

    // Create message
    if (isset($rows)) {
        $save = Helper::is_save_available($offset) && !$details['niyaz'];
        $other = array("save" => $save, "niyaz" => $details['niyaz'],
                       "adults" => $totalA, "kids" => $totalK,
                       "serving" =>
                        Estimation::get_serving_guidance($db, $details['details']));
    } else {
        $rows = NULL;
        $msg = "No responses available for " . $from;
        $other = NULL;
    }
    Helper::print_to_json($rows, $msg, $from, $other);
}

function get_details($db, $date) {
    $query = 'SELECT details,niyaz,enabled from events where date="' . $date . '";';
    $result = $db->query($query);
    if (!$result || $result->num_rows != 1) {
        return "";
    }
    $row = $result->fetch_assoc();
    if (!$row['enabled']) {
        return NULL;
    }
    return $row;
}

// Post update to details
function print_post($db, $from, $offset)
{
    $msg = "";
    $data = json_decode(file_get_contents('php://input'), false);
    $save = Helper::is_save_available($offset);

    if ($save) {
        foreach ($data as $i) {
            $thaali_id = $i->thaali;

            $query = "UPDATE rsvps set here='". $i->here .
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
