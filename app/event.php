<?php

require_once("auxil.php");

// If token is invalid, return an empty response
if (!Helper::is_admin($email_cookie) ||
    !Helper::verify_token($db, $email_cookie, $thaali_cookie)) {
    die('{ "msg": "Login failed, please logout and login again" }');
}

// POST or GET?
if ($method_server == "POST") {
    event_post($db);
} else {
    event_get($db, "");
}

// Get details for specific dates
function event_get($db, $msg)
{
    $offset = Helper::get_if_defined($_GET['offset'], 0);
    $date = Helper::get_if_defined($_GET['date'], "");
    $from = Helper::get_week($date, $offset);
    $to = Helper::get_week($date, $offset + 7);

    // Make query
    $query = "SELECT * FROM events WHERE date >= '" .
        $from . "' AND date < '" . $to . "' order by date;";

    $result = $db->query($query);

    // Get all dates between range
    $period = new DatePeriod(
                  new DateTime($from),
                  new DateInterval('P1D'),
                  new DateTime($to));

    // Save rows, add place holder dates when needed
    foreach($period as $date) {
        $d = $date->format('Y-m-d');
        if (!isset($row)) {
            $row = $result->fetch_assoc();
        }
        if (!isset($row["date"]) || $d != $row["date"]) {
            $rows[] = array("date" => $d);
        } else {
            $rows[] = $row;
            unset($row);
        }
    }

    if (isset($rows)) {
        Helper::print_to_json($rows, $msg, $from);
    } else {
        die('{ "msg": "No details available for week of ' . $from . '" }');
    }
}

function fix_details($details) {
    $new_details = "";
    foreach (explode(",", $details) as $menu) {
        $menu = ucwords(trim($menu));

        $menu = str_replace('Achaari', 'Achari', $menu);
        $menu = str_replace('Began', 'Baigan', $menu);
        $menu = str_replace('Begun', 'Baigan', $menu);
        $menu = str_replace('Bhaajiya', 'Bhajya', $menu);
        $menu = str_replace('Bhaji', 'Bhaaji', $menu);
        $menu = str_replace('Bhajji', 'Bhaaji', $menu);
        $menu = str_replace('Bhinda', 'Bhindi', $menu);
        $menu = str_replace('Chaval', 'Chawal', $menu);
        $menu = str_replace('Chawaal', 'Chawal', $menu);
        $menu = str_replace('Chickoli', 'Chikoli', $menu);
        $menu = str_replace('Chilly', 'Chilli', $menu);
        $menu = str_replace('Dal', 'Daal', $menu);
        $menu = str_replace('Doodi', 'Dudi', $menu);
        $menu = str_replace('Dudhi', 'Dudi', $menu);
        $menu = str_replace('Enchilladas', 'Enchiladas', $menu);
        $menu = str_replace('Guvar', 'Guvaar', $menu);
        $menu = str_replace('Guwar', 'Guvaar', $menu);
        $menu = str_replace('Kadahi', 'Karahi', $menu);
        $menu = str_replace('Kheema', 'Keema', $menu);
        $menu = str_replace('Khichro', 'Khichdo', $menu);
        $menu = str_replace('Malwi', 'Malvi', $menu);
        $menu = str_replace('Mathoo', 'Matho', $menu);
        $menu = str_replace('Mattar', 'Matar', $menu);
        $menu = str_replace('Mattur', 'Matar', $menu);
        $menu = str_replace('Mithas', 'Mithaas', $menu);
        $menu = str_replace('Mong', 'Moong', $menu);
        $menu = str_replace('Mutar',  'Matar', $menu);
        $menu = str_replace('Niyaaz', 'Niyaz', $menu);
        $menu = str_replace('Paaya', 'Paya', $menu);
        $menu = str_replace('Pau ', 'Pav ', $menu);
        $menu = str_replace('Halvo', 'Halwo', $menu);
        $menu = str_replace('Halwa', 'Halwo', $menu);
        $menu = str_replace('Paledo', 'Palidu', $menu);
        $menu = str_replace('Paleedo', 'Palidu', $menu);
        $menu = str_replace('Palido', 'Palidu', $menu);
        $menu = str_replace('Patrela', 'Patra', $menu);
        $menu = str_replace('Payaa', 'Paya', $menu);
        $menu = str_replace('Pulav', 'Pulao', $menu);
        $menu = str_replace('Sandwiches', 'Sandwich', $menu);
        $menu = str_replace('Seekh', 'Seek', $menu);
        $menu = str_replace('Suji', 'Sooji', $menu);
        $menu = str_replace('Urs', 'Urus', $menu);
        $menu = str_replace('Vegetables', 'Veg', $menu);
        $menu = str_replace('Vegetable', 'Veg', $menu);
        $menu = str_replace('Rigna', 'Ringna', $menu);
        $menu = str_replace('Sodanu', 'Sodannu', $menu);
        $menu = str_replace(' With ', ' w/ ', $menu);
        $menu = str_replace(' W/ ', ' w/ ', $menu);

        if ($menu == 'Kitchdi' || $menu == 'Khitchri' || $menu == 'Kitchri' ||
            $menu == 'Khichri' || $menu == 'Khichdi') {
            $menu = 'Khitchdi';
        } else if ($menu == 'Kadi' || $menu == 'Khadhi') {
            $menu = 'Kadhi';
        } else if ($menu == 'Kari') {
            $menu = 'Kaari';
        } else if ($menu == 'Khichdoo' || $menu == 'Khitchro') {
            $menu = 'Khitchdo';
        } else if ($menu == 'Nan') {
            $menu = 'Naan';
        }
        if ($menu != '') {
            $new_details .= ", " . $menu;
        }
    }
    return substr($new_details, 2);
}


// Post update to details
function event_post($db)
{
    $msg = "";
    $data = json_decode(file_get_contents('php://input'), false);
    $stmt = $db->prepare("INSERT INTO events (date, details, enabled, niyaz) " .
                         "VALUES (?, ?, ?, ?) " .
                         "ON DUPLICATE KEY UPDATE " .
                         "details = ?, enabled = ?, niyaz = ?");

    foreach ($data as $i) {
        $date = $i->date;

        // Take care of uninit variables
        $enabled = 0;
        if (isset($i->enabled) && $i->enabled) {
            $enabled = 1;
        }
        $niyaz = 0;
        if ($enabled && isset($i->niyaz) && $i->niyaz) {
            $niyaz = 1;
        }
        $details = Helper::get_if_defined($i->details, "");
        if ($details == "") {
            $query = "DELETE FROM events WHERE date = '$date';";
            if (!$db->query($query)) {
                $msg =  $db->error;
                break;
            }
        } else {
            $details = fix_details($details);
            $stmt->bind_param("ssiisii",
                              $date, $details, $enabled, $niyaz, 
                              $details, $enabled, $niyaz);
            if (!$stmt->execute()) {
                $msg = $stmt->error;
                break;
            }
        }
    }
    if (!$msg) {
        $msg = "Thank you, changes have been saved!";
        return event_get($db, $msg);
    } else {
        die('{ "msg": "' . $msg . '" }');
    }
}

?>
