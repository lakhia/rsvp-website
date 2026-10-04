<?php
require_once "bootstrap.php";

// If token is invalid, return an empty response
if (!AuthService::is_admin($email_cookie) ||
    !AuthService::verify_token($db, $email_cookie, $thaali_cookie)) {
    Helper::json_error("Login failed, please logout and login again");
}

if ($method_server == "POST") {
    ingredients_post($db);
} else {
    ingredients_get($db);
}

// Get details for ingredients
function ingredients_get($db)
{
    $query = "SELECT * FROM ingredients ORDER BY name;";
    $result = $db->query($query);

    while($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    Helper::print_to_json($rows, "");
}

function ingredients_post($db) {
    $data = json_decode(file_get_contents('php://input'), false);
    foreach ($data as $ingred) {
        $name = trim($ingred->name ?? '');
        $unit = trim($ingred->unit ?? '');
        if (empty($name) || empty($unit)) continue;

        if (empty($ingred->id)) {
            $stmt = $db->prepare("INSERT INTO ingredients (name, unit) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $unit);
            $stmt->execute();
        } else {
            $id = (int)$ingred->id;
            $stmt = $db->prepare("UPDATE ingredients SET name = ?, unit = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $unit, $id);
            $stmt->execute();
        }
    }
    return ingredients_get($db);
}
