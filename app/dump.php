<?php

require_once("aux.php");

$table = $_GET['table'];

// If token is invalid, return an empty response
if (!Helper::is_admin($email_cookie) ||
    !Helper::verify_token($db, $email_cookie, $thaali_cookie)) {
    die('{ "msg": "Login failed, please logout and login again" }');
}

// Sanitize string
$table = preg_replace("/[^_a-zA-Z0-9]+/", "", $table);

// Get column names for header
$result = $db->query("SHOW COLUMNS FROM " . $table);
$cols = array();
$types = array();
while ($row = $result->fetch_assoc()) {
    $cols[] = $row["Field"];
    $types[$row["Field"]] = $row["Type"];
}

// POST or GET?
if ($method_server == "POST") {
    dump_post($db, $table, $types);
} else {
    dump_get($db, $table, $cols);
}

// Get dump in CSV format
function dump_get($db, $table, $cols) {
    header('Content-Disposition: attachment; filename=' . $table . '.csv');

    // Open output file
    $output = fopen('php://output', 'w');

    fputcsv($output, $cols);

    // Make query
    $query = "SELECT * FROM  " . $table . ";";
    $result = $db->query($query);

    // Output rows
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
}

// Import dump in CSV format
function dump_post($db, $table, $types) {
    $input = fopen('php://input', 'r');
    $cols = array();
    $integers = array();
    $row = 0;
    $msg = "";

    // Process each line
    while (($buf = fgets($input, 4096)) !== false) {

        $data = str_getcsv($buf);

        // Figure out column names and types
        if (count($cols) == 0) {
            $cols = implode(",", $data);
            foreach ($data as $val) {
                $integers[] = strpos($types[$val], "int");
            }
            continue;
        }

        // If column is string, enclose in quotes
        for ($i=0; $i < count($data); $i++) {
            if ($integers[$i] === false) {
                $data[$i] = '"' . $data[$i] . '"';
            }
        }
        $data = implode(",", $data);

        // Run insert query
        $query = "INSERT into " . $table . "(" . $cols .
            ") values(" . $data . ");\n";
        $result = $db->query($query);

        $row++;
        $msg .= "Row " . $row . ", " . ($result ? "success" : "failure") . "\n";
    }

    die('{ "msg": "' . $msg . '" }');
}

?>
