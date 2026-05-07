<?php
require_once "bootstrap.php";
require_once "MenuNames.php";

// If token is invalid, return an empty response
if (!AuthService::is_admin($email_cookie) ||
    !AuthService::verify_token($db, $email_cookie, $thaali_cookie)) {
    Helper::json_error("Login failed, please logout and login again");
}

// Get offset
$offset = Helper::get_param('offset', 0);
$len = Helper::get_param('len', 10);

// POST or GET?
if ($method_server == "POST") {
    measure_post($db, $offset, $len);
} else {
    measure_get($db, $offset, $len, "");
}

// Get details for measurement
function measure_get($db, $offset, $len, $msg = "")
{
    $query = "SELECT * FROM menus ORDER BY menu LIMIT " .
        $offset . "," . $len . ";";
    $result = $db->query($query);

    while($row = $result->fetch_assoc()) {
        get_ingredients($db, $row);
        $rows[] = $row;
    }

    Helper::print_to_json($rows, $msg);
}

function get_ingredients($db, &$menu_row) {
    $ingredients = array();
    $query = "SELECT ingredients.id, name, multiplier, unit FROM cooking " .
             "LEFT JOIN menus on menu_id = id " .
             "LEFT JOIN ingredients on ingred_id = ingredients.id " .
             "WHERE menu_id = '" . $menu_row['id'] . "';";
    $result = $db->query($query);
    while($row = $result->fetch_assoc()) {
        array_push($ingredients, $row);
    }
    array_push($ingredients, ["name" => ""]);
    $menu_row['ingred'] = $ingredients;
}

function measure_post($db, $offset, $len) {
    $msg = "Thank you, changes have been saved";
    $data = json_decode(file_get_contents('php://input'), false);
    foreach ($data as $menu) {
        if (!isset($menu->ingred)) {
            $msg = "Missing ingredients data";
            break;
        }
        $name = MenuNames::canonicalize($menu->menu ?? '');
        $rice = (isset($menu->rice) && $menu->rice) ? 1 : 0;

        if (empty($menu->id)) {
            // New menu — skip if name is blank
            if (empty($name)) continue;
            $next_id = $db->query("SELECT COALESCE(MAX(id), 0) + 1 FROM menus")->fetch_row()[0];
            $stmt = $db->prepare("INSERT INTO menus (id, menu, rice) VALUES (?, ?, ?)");
            $stmt->bind_param("isi", $next_id, $name, $rice);
            $stmt->execute();
            $menu->id = $next_id;
        } else {
            $id = (int)$menu->id;
            $stmt = $db->prepare("UPDATE menus SET menu = ?, rice = ? WHERE id = ?");
            $stmt->bind_param("sii", $name, $rice, $id);
            $stmt->execute();
        }

        $db->query("DELETE FROM cooking WHERE menu_id = " . $menu->id);
        foreach ($menu->ingred as $ingred) {
            if (!empty($ingred->name) && isset($ingred->id) && !empty($ingred->multiplier)) {
                $db->query("INSERT INTO cooking VALUES(" . $menu->id . ", " . $ingred->id . ","
                    . $ingred->multiplier . ");");
            }
        }
    }
    return measure_get($db, $offset, $len, $msg);
}

?>
