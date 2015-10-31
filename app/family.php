<?php

require_once('aux.php');

// If token is invalid, return an empty response
if (!Helper::is_admin($email_cookie) ||
    !Helper::verify_token($email_cookie, $thaali_cookie)) {
    return;
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
    $offset = $_GET['offset'];
    if (!isset($offset)) {
        $offset = 0;
    }
    $offset += 1;
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
        echo Helper::convert_array_to_json($rows, $msg);
    } else {
        die('{ "message": "No families found" }');
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
            if (!isset($i->lastName) || !isset($i->firstName)) {
                $msg .= ", name is required";
                continue;
            }
            if (!isset($i->phone)) {
                $i->phone = "";
            }
            // Insert or update
            $query = "insert into family(thaali, lastName, firstName, email, phone) " .
                "values($i->thaali, '$i->lastName', '$i->firstName'," .
                "'$i->email', '$i->phone')" .
                "on duplicate KEY " .
                "update lastName='$i->lastName', firstName='$i->firstName'," .
                "email='$i->email', phone='$i->phone'";
        }
        $result = $db->query($query);
        if (!$result) {
            $msg .= ", Error: " . $db->error;
        }
    }
    if (!$msg) {
        $msg = "Thank you, changes have been saved";
    } else {
        $msg = "Please fix" . $msg;
    }
    die('{ "message": "' . $msg . '" }');
}

?>
