<?php

require_once('auxil.php');

// If token is invalid, return an empty response
if (!Helper::is_admin($email_cookie) ||
    !Helper::verify_token($db, $email_cookie, $thaali_cookie)) {
    die('{ "msg": "Login failed, please logout and login again" }');
}

// POST or GET?
if ($method_server == "POST") {
    family_post($db, $thaali_cookie);
} else {
    family_get($db, $thaali_cookie, "");
}

// Get details for all families
function family_get($db, $thaali, $msg)
{
    $offset = 0;
    if (isset($_GET['offset'])) {
        $offset = $_GET['offset'];
    }
    $end = $offset + 10;

    // Make query
    $query = "SELECT * FROM family WHERE thaali >= " .
             $offset . " AND thaali < " . $end . ";";
    $result = $db->query($query);

    // Get rows and insert place holders when needed
    for ($i = $offset; $i < $end; $i++) {
        if (!isset($row)) {
            $row = $result->fetch_assoc();
        }
        if ($i != $row["thaali"]) {
            $k = array("thaali" => $i);
            $rows[] = $k;
        } else {
            $rows[] = $row;
            unset($row);
        }
    }

    // Send data
    if (isset($rows)) {
        Helper::print_to_json($rows, $msg);
    } else {
        die('{ "msg": "No families found" }');
    }
}

// Create or update or delete
function family_post($db, $thaali) {
    $msg = "";
    $data = json_decode(file_get_contents('php://input'), false);
    foreach ($data as $i) {
        // No email means delete user because they cannot login without it
        if (!isset($i->email) || $i->email == "") {
            $query = "DELETE FROM family WHERE thaali = " . $i->thaali;
        } else {
            if (!isset($i->lastName) || !isset($i->firstName) ||
                $i->lastName == "" || $i->firstName == "") {
                $msg .= ", name is required";
                continue;
            }
            if (!isset($i->phone)) {
                $i->phone = "";
            }
            if ($i->size == 'M') {
                $i->size = '';
            }
            // Insert or update
            $query = "insert into family" .
	        "(thaali, lastName, firstName, size, area, email, phone, resp) " .
                "values($i->thaali, '$i->lastName', '$i->firstName', '$i->size'," .
                "'$i->area','$i->email', '$i->phone', '$i->resp')" .
                "on duplicate KEY " .
                "update lastName='$i->lastName', firstName='$i->firstName'," .
                "size='$i->size', area='$i->area', email='$i->email', " .
                "phone='$i->phone', resp='$i->resp'";
        }
        $result = $db->query($query);
        if (!$result) {
            $msg .= ", Error: " . $db->error;
        }
    }
    if (!$msg) {
        $msg = "Thank you, changes have been saved";
        return family_get($db, $thaali, $msg);
    } else {
        $msg = "Please fix" . $msg;
    }
    die('{ "msg": "' . $msg . '" }');
}

?>
