<?php
require_once "bootstrap.php";
require_once "EstimationService.php";

// If token is invalid, return an empty response
if (!AuthService::verify_token($db, $email_cookie, $thaali_cookie)) {
    Helper::json_error("Login failed, please logout and login again");
}

shopping_get($db);

// Get details for shopping
function shopping_get($db)
{
    $offset = Helper::get_param("offset", 0);
    $date = Helper::get_param("date", "");
    $len = Helper::get_param("len", 7);

    $from = Helper::get_week($date, $offset);
    $to = Helper::get_week($date, $offset + $len);

    // Make query — fetch all events including multiple per date
    $query =
        "SELECT * FROM events WHERE date >= '" .
        $from .
        "' AND date < '" .
        $to .
        "' ORDER BY date, event_index;";
    $result = $db->query($query);

    // Fetch all events into memory and group by date
    $events_by_date = [];
    while ($row = $result->fetch_assoc()) {
        $events_by_date[$row['date']][] = $row;
    }

    // Get all dates between range
    $period = new DatePeriod(
        new DateTime($from),
        new DateInterval("P1D"),
        new DateTime($to),
    );

    // Process each date — aggregate all events on the same date
    $total = [];
    $rows = [];
    foreach ($period as $date) {
        $d = $date->format("Y-m-d");
        if (!isset($events_by_date[$d])) {
            continue;
        }
        $day_count = null;
        $day_ingred = [];
        foreach ($events_by_date[$d] as $ev) {
            $shop = ingredients_for_event($db, $ev, $total);
            if (!$shop) {
                continue;
            }
            if ($day_count === null) {
                $day_count = $shop['count'];
            } else {
                foreach ($shop['count'] as $k => $v) {
                    $day_count[$k] = ($day_count[$k] ?? 0) + $v;
                }
            }
            foreach ($shop['ingred'] ?? [] as $dish => $items) {
                $day_ingred[$dish] = $items;
            }
        }
        if ($day_count !== null) {
            $rows[$d] = ['count' => $day_count, 'ingred' => $day_ingred];
        }
    }

    // Save totals
    $rows["Total"]["ingred"][""] = compute_total($total);

    Helper::print_to_json($rows, "", $from);
}

/* Calculate ingredients for a single event */
function ingredients_for_event($db, &$data, &$total)
{
    $result = [];
    if ($data["enabled"] && !$data["niyaz"]) {
        $count = total_rsvp_for_event($db, $data["date"], (int)$data["event_index"]);
        $ingredients = EstimationService::get_ingredients(
            $db,
            $data["details"],
            $count,
            $total,
        );
        $result["ingred"] = $ingredients;
        $result["count"] = $count;
    }
    return $result;
}

/* Compute total RSVP in 3 different ways:
       Count, normalized for size, and adjusted for less rice
*/
function total_rsvp_for_event($db, $date, $event_index)
{
    // Get total RSVP for given event
    $query =
        "SELECT rsvps.size, lessRice FROM `rsvps` " .
        "LEFT JOIN `family` on family.thaali = rsvps.thaali_id " .
        "WHERE `rsvp` = 1 AND `date` = '" . $date . "' AND `event_index` = " . $event_index . ";";
    $result = $db->query($query);

    $count = ["count" => 0];
    while ($row = $result->fetch_assoc()) {
        // Count RSVPs
        $count["count"]++;

        // Count normalized thaali
        $size = EstimationService::get_factor_from_size($row["size"], 10) / 10;
        if (!isset($count["normalized"])) {
            $count["normalized"] = $size;
        } else {
            $count["normalized"] += $size;
        }

        // Count rice
        if ($row["lessRice"]) {
            $size = 0;
        }
        if (!isset($count["rice+bread"])) {
            $count["rice+bread"] = 0;
        }
        $count["rice+bread"] += $size;
    }
    return $count;
}

/* Output total values in same format */
function compute_total(&$total)
{
    $new_total = [];
    foreach ($total as $key => $value) {
        array_push($new_total, round($value, 1) . " " . $key);
    }
    return $new_total;
}
?>
