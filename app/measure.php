<?php
require_once('auxil.php');

// If token is invalid, return an empty response
if (!Helper::is_admin($email_cookie) ||
    !Helper::verify_token($db, $email_cookie, $thaali_cookie)) {
    die('{ "msg": "Login failed, please logout and login again" }');
}

// Get offset
$offset = Helper::get_if_defined($_GET['offset'], 0);
$len = Helper::get_if_defined($_GET['len'], 10);

// POST or GET?
if ($method_server == "POST") {
    measure_post($db, $offset, $len);
} else {
    measure_get($db, $offset, $len, "");
}

// Get details for measurement
function measure_get($db, $offset, $len, $msg = "")
{
    // Make query
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
    if (!isset($menu->id) || !isset($menu->ingred)) {
      $msg = "Lost the ID or the ingredients";
      break;
    }
    $db->query("DELETE FROM cooking WHERE menu_id = " . $menu->id);
    foreach ($menu->ingred as $ingred) {
      if (!empty($ingred->name)) {
        $db->query("INSERT INTO cooking VALUES(" . $menu->id . ", " . $ingred->id . "," 
            . $ingred->multiplier . ");");
      }
    }
  }
  return measure_get($db, $offset, $len, $msg);
}

?>
