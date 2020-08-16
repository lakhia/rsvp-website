<?php
require_once('auxil.php');

// If token is invalid, return an empty response
if (!Helper::verify_token($db, $email_cookie, $thaali_cookie)) {
    die('{ "msg": "Login failed, please logout and login again" }');
}
$offset = Helper::get_if_defined($_GET['offset'], 0);
$len = Helper::get_if_defined($_GET['len'], 7);
shopping_get($db, $offset, $len);

// Get details for shopping
function shopping_get($db, $offset, $len, $msg = "")
{
    $from = Helper::get_week($offset);
    $to = Helper::get_week($offset + $len);

    // Make query
    $query = "SELECT * FROM events WHERE date >= '" .
        $from . "' AND date < '" . $to . "' order by date;";
    $result = $db->query($query);

    // Get all dates between range
    $period = new DatePeriod(
                  new DateTime($from),
                  new DateInterval('P1D'),
                  new DateTime($to));

    // Save rows, add place holder dates when needed
    $total = array();
    foreach($period as $date) {
        $d = $date->format('Y-m-d');
        if (!isset($row)) {
            $row = $result->fetch_assoc();
        }
        if ($d == $row['date']) {
            $shop = calculate($db, $row, $total);
            $rows[$d] = $shop;
            unset($row);
        }
    }

    // Save totals
    $rows['Total']['ingred'][''] = compute_total($total);

    Helper::print_to_json($rows, $msg, $from);
}

/* Top level function to calculate ingredients for a single date */
function calculate($db, &$data, &$total) {
    $result = array();
    if ($data['enabled'] && !$data['niyaz']) {
        $count = total_rsvp_for_date($db, $data['date']);
        $ingredients = get_ingredients($db, $data['details'], $count, $total);
        $result['ingred'] = $ingredients;
        $result['count'] = $count;
    }
    return $result;
}

/* Compute total RSVP in 3 different ways:
       Count, normalized for size, and adjusted for less rice
*/
function total_rsvp_for_date($db, $date) {
    // Get total RSVP for given date
    $query = "SELECT rsvps.size, lessRice FROM `rsvps` " .
        "LEFT JOIN `family` on family.thaali = rsvps.thaali_id " .
        "WHERE `rsvp` = 1 AND `date` = '" . $date . "';";
    $result = $db->query($query);

    $count = array('count' => 0);
    while($row = $result->fetch_assoc()) {
        // Count RSVPs
        $count['count']++;

        // Count normalized thaali
        $size = $row['size'];
        if ($size == 'XL') {
            $size = 2;
        } else if ($size == 'L') {
            $size = 1.5;
        } else if ($size == 'S') {
            $size = 0.5;
        } else if ($size == 'XS') {
            $size = 0.25;
        } else {
            $size = 1.0;
        }
        if (!isset($count['normalized'])) {
            $count['normalized'] = $size;
        } else {
            $count['normalized'] += $size;
        }

        // Count rice
        if ($row['lessRice']) {
            $size = round($size/3.0, 2);
        }
        if (!isset($count['lessRice'])) {
            $count['lessRice'] = $size;
        } else {
            $count['lessRice'] += $size;
        }
    }
    return $count;
}

/* For each menu, compute all the ingredients and ingredient totals */
function get_ingredients($db, $fullmenu, &$count, &$total) {
    $ingredients = array();
    foreach (explode(",", $fullmenu) as $menu) {
        $menu = trim($menu);
        $query = "SELECT name, multiplier, rice, unit FROM cooking " .
                 "LEFT JOIN menus on menu_id = id " .
                 "LEFT JOIN ingredients on ingred_id = ingredients.id " .
                 "WHERE menu = '" . $menu . "';";
        $result = $db->query($query);
        $ingredients[$menu] = array();
        while($row = $result->fetch_assoc()) {
            if (!isset($ingredients[$menu])) {
                $ingredients[$menu] = array();
            }
            array_push($ingredients[$menu], adjust_for_count($row, $count, $total));
        }
    }
    return $ingredients;
}

/* Compute ingredient entry and ingredient totals from cumulative RSVP responses */
function adjust_for_count(&$i, &$count, &$total) {
    if ($i['rice']) {
        $quant = Helper::get_if_defined($count['lessRice'], 0);
    } else {
        $quant = Helper::get_if_defined($count['normalized'], 0);
    }
    $ingred = $i['name'];
    $quant *= $i['multiplier'];
    $key = $i['unit'] . " " . $ingred;
    if (!isset($total[$key])) {
        $total[$key] = $quant;
    } else {
        $total[$key] += $quant;
    }
    return round($quant, 1) . " " . $i['unit'] . " " . $ingred;
}

/* Output total values in same format */
function compute_total(&$total) {
    $new_total = array();
    foreach($total as $key => $value) {
        array_push($new_total, round($value, 1) . " " . $key);
    }
    return $new_total;
}
?>
