<?php
require "lib/fpdf/fpdf.php";
require_once "auxil.php";

// If token is invalid, return an empty response
if (!Helper::verify_token($db, $email_cookie, $thaali_cookie)) {
    die('{ "msg": "Login failed, please logout and login again" }');
}
// Check for a 'date' parameter
if (!isset($_GET["date"])) {
    die(
        "Error: Date parameter missing. Please provide a date (e.g. ?date=2025-06-15)."
    );
}

$filter_date = $_GET["date"];
// Sanitize string to prevent SQL injection
$filter_date = preg_replace("/[^_a-zA-Z0-9-]+/", "", $filter_date);
$event_query = 'SELECT details, enabled from events where date="' . $filter_date . '";';

$result = $db->query($event_query);
if (!$result || $result->num_rows != 1) {
    die("Error: Seems like there is no event on the day selected.");
}
$row = $result->fetch_assoc();
if (!$row["enabled"]) {
    die("Error: Seems like the event is not enabled.");
}
$dish = $row["details"];

// Get RSVP and family - sort order is optimized for labeling workflow
$query =
    "SELECT thaali_id as id, CONCAT(firstName, ' ', lastName) AS name, " .
    "rsvps.size, area FROM rsvps LEFT JOIN `family` on family.thaali = rsvps.thaali_id " .
    "WHERE `rsvp` = 1 AND `date` = '" .
    $filter_date .
    "' ORDER BY rsvps.size, area;";
$result = $db->query($query);

// Create PDF
$pdf = new FPDF("P", "in", "Letter");
$pdf->SetMargins(0, 0);
$pdf->AddPage();
$pdf->SetFont("Arial", "", 9); // Font size adjusted for label size

// Avery 5160 specs
// https://www.avery.com/templates/5160
$labelWidth = 2.625;
$labelHeight = 1;
$marginLeft = 0.1875;
$marginTop = 0.5;
$spaceX = 0.125;
$spaceY = 0;

$x = $marginLeft;
$y = $marginTop;
$col = 0;
$grid_row = 0;

while ($row = $result->fetch_assoc()) {
    // Optional debug border
    // $pdf->Rect($x, $y, $labelWidth, $labelHeight);

    // Position cursor
    $pdf->SetXY($x + 0.1, $y + 0.1);

    // Line 1: ID | City (bold)
    $pdf->SetFont("Arial", "B", 14);
    $pdf->Cell(
        $labelWidth - 0.2,
        0.15,
        $row["id"] . " | " . $row["area"] . " | " . $row["size"],
        0,
        2,
        "C"
    );
    $pdf->Cell($labelWidth - 0.2, 0.02, "", 0, 2);
    // Line 2: Dish Name (Size) (regular)
    $pdf->SetFont("Arial", "", 10);
    // Line 3: Name
    $pdf->Cell($labelWidth - 0.2, 0.02, "", 0, 2);
    $pdf->Cell($labelWidth - 0.2, 0.13, $dish, 0, 2, "C");
    $pdf->Cell($labelWidth - 0.2, 0.02, "", 0, 2);
    // Line 4: Date
    $pdf->Cell($labelWidth - 0.2, 0.15, $filter_date, 0, 2, "C");

    // Move to next label
    $col++;
    if ($col >= 3) {
        $col = 0;
        $grid_row++;
        $x = $marginLeft;
        $y += $labelHeight + $spaceY;
    } else {
        $x += $labelWidth + $spaceX;
    }

    // New page if needed
    if ($grid_row >= 10) {
        $pdf->AddPage();
        $x = $marginLeft;
        $y = $marginTop;
        $col = 0;
        $grid_row = 0;
    }
}

// Output the PDF automatically
$pdf->Output("D", "labels.pdf"); // 'D' triggers download
exit();
?>
