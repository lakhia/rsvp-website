<?php

class Estimation
{
    public static $sizes = array("XS", "S", "M", "L", "XL");

    private static function get_all_ingredients($db, $fullmenu) {
        $ingredients = array();
        $stmt = $db->prepare("SELECT name, multiplier, rice, unit FROM cooking " .
                             "LEFT JOIN menus on menu_id = id " .
                             "LEFT JOIN ingredients on ingred_id = ingredients.id " .
                             "WHERE menu = ?");
        foreach (explode(",", $fullmenu) as $menu) {
            if (strpos($menu, ":")) {
                $menu = trim(substr($menu, 1 + strpos($menu, ":")));
            }
            $stmt->bind_param("s", $menu);
            $stmt->execute();
            $result = $stmt->get_result();
            $ingredients[$menu] = array();
            while($row = $result->fetch_assoc()) {
                if (!isset($ingredients[$menu])) {
                    $ingredients[$menu] = array($row);
                } else {
                    array_push($ingredients[$menu], $row);
                }
            }
        }
        return $ingredients;
    }

    /* Get serving guidance for all different sizes */
    static function get_serving_guidance($db, $fullmenu) {
        $menus = self::get_all_ingredients($db, $fullmenu);
        foreach ($menus as $menu => $ingredients) {
            $menus[$menu] = array();
            foreach ($ingredients as $ingredient) {
                $unit = $ingredient['unit'];
                /* lbs and cups are not used for serving */
                if ($unit == "lbs" || $unit == "cups") {
                    continue;
                }
                foreach (self::$sizes as $size) {
                    array_push($menus[$menu], $size . ": " .
                       self::get_factor_from_size($size, $ingredient['multiplier']) . " " . $unit);
                }
            }
        }
        return $menus;
    }

    /* For each menu, compute all the ingredients and ingredient totals */
    static function get_ingredients($db, $fullmenu, &$count, &$total) {
        $menus = self::get_all_ingredients($db, $fullmenu);
        foreach ($menus as $menu => $ingredients) {
            $menus[$menu] = array();
            foreach ($ingredients as $ingredient) {
                /* Serving is not used for estimation */
                if ($ingredient['unit'] != 'serving') {
                    array_push($menus[$menu], self::adjust_for_count($ingredient, $count, $total));
                }
            }
        }
        return $menus;
    }

    /* Compute ingredient entry and ingredient totals from cumulative RSVP responses */
    static function adjust_for_count(&$ingredient, &$count, &$total) {
        if ($ingredient['rice']) {
            $quant = Helper::get_if_defined($count['rice+bread'], 0);
        } else {
            $quant = Helper::get_if_defined($count['normalized'], 0);
        }
        $ingred = $ingredient['name'];
        $quant *= $ingredient['multiplier'];
        $key = $ingredient['unit'] . " " . $ingred;
        if (!isset($total[$key])) {
            $total[$key] = $quant;
        } else {
            $total[$key] += $quant;
        }
        return round($quant, 1) . " " . $ingredient['unit'] . " " . $ingred;
    }

    /* Given thaali size, return factor */
    static function get_factor_from_size($size, $multiplier) {
        if ($size == 'XL') {
            $factor = 2;
        } else if ($size == 'L') {
            $factor = 1.5;
        } else if ($size == 'S') {
            $factor = 0.5;
        } else if ($size == 'XS') {
            $factor = 0.25;
        } else {
            $factor = 1.0;
        }
        return round($multiplier * $factor, 1);
    }
}

?>
