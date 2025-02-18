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
        if (!isset($row["thaali"]) || $i != $row["thaali"]) {
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
    $stmt = $db->prepare("INSERT INTO family " .
                         "(thaali, its, lastName, firstName, size, area, email, phone, poc, resp) " .
                         "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) " .
                         "ON DUPLICATE KEY UPDATE " .
                         "its = ?, lastName = ?, firstName = ?, size = ?, " .
                         "area = ?, email = ?, phone = ?, poc = ?, resp = ?");

    foreach ($data as $i) {
        $email = Helper::get_if_defined($i->email, '');
        if ($email == '') {
            // No email means delete user because they cannot login without it
            $query = "DELETE FROM family WHERE thaali = " . $i->thaali;
            if (!$db->query($query)) {
                $msg .= ", Error: " . $db->error;
            }
        } else {
            $lastName = Helper::get_if_defined($i->lastName, '');
            $firstName = Helper::get_if_defined($i->firstName, '');
            $phone = Helper::get_if_defined($i->phone, '');
            $size = strtoupper(Helper::get_if_defined($i->size, 'M'));
            $area = Helper::get_if_defined($i->area, '');
            $resp = Helper::get_if_defined($i->resp, '');
            $poc = Helper::get_if_defined($i->poc, '');
            $its = Helper::get_if_defined($i->its, '');

            if (!in_array($size, Estimation::$sizes)) {
                $size = "M";
            }
            if ($lastName == '' || $firstName == '') {
                $msg .= ", name is required";
                continue;
            }
            $stmt->bind_param("issssssssssssssssss", 
                              $i->thaali, $its, $lastName, $firstName, $size, $area, 
                              $email, $phone, $poc, $resp, 
                              $its, $lastName, $firstName, $size, $area, 
                              $email, $phone, $poc, $resp);
            if (!$stmt->execute()) {
                $msg .= ", Error: " . $stmt->error;
            }
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
