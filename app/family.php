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
    // Make query
    $query = "SELECT * FROM family";
    $result = $db->query($query);

    // Get date
    while($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    // Send data
    if (isset($rows)) {
        echo Helper::convert_array_to_json($rows, $msg);
    } else {
        die('{ "message": "No families found" }');
    }
}

function family_post($db, $thaali) {
    $data = json_decode(file_get_contents('php://input'), true);
}

?>
