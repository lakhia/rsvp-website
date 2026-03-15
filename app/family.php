<?php

require_once "bootstrap.php";
require_once "EstimationService.php";
require_once "FamilyService.php";

// If token is invalid, return an empty response
if (!AuthService::is_admin($email_cookie) ||
    !AuthService::verify_token($db, $email_cookie, $thaali_cookie)) {
    Helper::json_error("Login failed, please logout and login again");
}

$familyService = new FamilyService();

// POST or GET?
if ($method_server == "POST") {
    family_post($db, $familyService, $thaali_cookie);
} else {
    family_get($db, $thaali_cookie, "");
}

// Get details for all families
function family_get($db, $thaali, $msg)
{
    $offset = Helper::get_param('offset', 1);
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
            $rows[] = ["thaali" => $i];
        } else {
            $rows[] = $row;
            unset($row);
        }
    }

    // Send data
    if (isset($rows)) {
        Helper::print_to_json($rows, $msg);
    } else {
        Helper::json_error("No families found");
    }
}

// Create or update or delete
function family_post($db, $familyService, $thaali) {
    $msg = "";
    $data = json_decode(file_get_contents('php://input'), false);
    $stmt = $db->prepare("INSERT INTO family " .
                         "(thaali, its, lastName, firstName, size, area, email, phone, poc, resp) " .
                         "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) " .
                         "ON DUPLICATE KEY UPDATE " .
                         "its = ?, lastName = ?, firstName = ?, size = ?, " .
                         "area = ?, email = ?, phone = ?, poc = ?, resp = ?");

    foreach ($data as $i) {
        try {
            $entry = $familyService->normalizeEntry($i);
        } catch (\InvalidArgumentException $e) {
            $msg .= ", " . $e->getMessage();
            continue;
        }

        if ($entry === null) {
            // No email means delete user because they cannot login without it
            $query = "DELETE FROM family WHERE thaali = " . $i->thaali;
            if (!$db->query($query)) {
                $msg .= ", Error: " . $db->error;
            }
        } else {
            $stmt->bind_param("issssssssssssssssss",
                              $entry['thaali'], $entry['its'], $entry['lastName'], $entry['firstName'],
                              $entry['size'], $entry['area'], $entry['email'], $entry['phone'],
                              $entry['poc'], $entry['resp'],
                              $entry['its'], $entry['lastName'], $entry['firstName'],
                              $entry['size'], $entry['area'], $entry['email'], $entry['phone'],
                              $entry['poc'], $entry['resp']);
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
    Helper::json_error($msg);
}

?>
