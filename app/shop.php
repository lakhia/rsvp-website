<?php
require_once('auxil.php');
require_once('estimation.php');

// If token is invalid, return an empty response
if (!Helper::verify_token($db, $email_cookie, $thaali_cookie)) {
    die('{ "msg": "Login failed, please logout and login again" }');
}

shopping_get($db);

// Get details for shopping
function shopping_get($db)
{
    $offset = Helper::get_if_defined($_GET['offset'], 0);
    $date = Helper::get_if_defined($_GET['date'], "");
    $len = Helper::get_if_defined($_GET['len'], 7);

    $from = Helper::get_week($date, $offset);
    $to = Helper::get_week($date, $offset + $len);

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
        if (isset($row['date']) && $d == $row['date']) {
            $shop = ingredients_for_date($db, $row, $total);
            $rows[$d] = $shop;
            unset($row);
        }
    }

    // Save totals
    $rows['Total']['ingred'][''] = compute_total($total);

    Helper::print_to_json($rows, "", $from);
}

/* Calculate ingredients for a single date */
function ingredients_for_date($db, &$data, &$total) {
    $result = array();
    if ($data['enabled'] && !$data['niyaz']) {
        $count = total_rsvp_for_date($db, $data['date']);
        $ingredients = Estimation::get_ingredients($db, $data['details'], $count, $total);
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
        $size = Estimation::get_factor_from_size($row['size'], 10) / 10;
        if (!isset($count['normalized'])) {
            $count['normalized'] = $size;
        } else {
            $count['normalized'] += $size;
        }

        // Count rice
        if ($row['lessRice']) {
            $size = 0;
        }
        if (!isset($count['rice+bread'])) {
            $count['rice+bread'] = 0;
        }
        $count['rice+bread'] += $size;
    }
    return $count;
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
