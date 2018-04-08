<?php
require_once('auxil.php');

// If token is invalid, return an empty response
if (!Helper::is_admin($email_cookie) ||
    !Helper::verify_token($db, $email_cookie, $thaali_cookie)) {
    die('{ "msg": "Login failed, please logout and login again" }');
}
ingredients_get($db);

// Get details for ingredients
function ingredients_get($db)
{
    // Make query
    $query = "SELECT * FROM ingredients ORDER BY name;";
    $result = $db->query($query);

    while($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    Helper::print_to_json($rows, "");
}
