<?php

require_once('auxil.php');
require_once('estimation.php');

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
    $offset = Helper::get_if_defined($_GET['offset'], 1);
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
        $email = Helper::get_if_defined($i->email, '');
        if ($email == '') {
            // No email means delete user because they cannot login without it
            $query = "DELETE FROM family WHERE thaali = " . $i->thaali;
        } else {
            $lastName = Helper::get_if_defined($i->lastName, '');
            $firstName = Helper::get_if_defined($i->firstName, '');
            $phone = Helper::get_if_defined($i->phone, '');
            $size = strtoupper(Helper::get_if_defined($i->size, 'M'));
            $area = Helper::get_if_defined($i->area, '');
            $resp = Helper::get_if_defined($i->resp, '');

            if (!in_array($size, Estimation::$sizes)) {
                $size = "M";
            }
            if ($lastName == '' || $firstName == '') {
                $msg .= ", name is required";
                continue;
            }
            // Insert or update
            $query = "INSERT INTO family" .
	        "(thaali, lastName, firstName, size, area, email, phone, resp) " .
                "values($i->thaali, '$lastName', '$firstName', '$size'," .
                "'$area', '$email', '$phone', '$resp')" .
                "on duplicate KEY " .
                "update lastName='$lastName', firstName='$firstName'," .
                "size='$size', area='$area', email='$email', " .
                "phone='$phone', resp='$resp'";
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
