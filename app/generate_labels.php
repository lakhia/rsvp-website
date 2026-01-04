<?php
require "lib/fpdf/fpdf.php";
require_once "auxil.php";

// If token is invalid, return an empty response
if (!Helper::verify_token($db, $email_cookie, $thaali_cookie)) {
    die('{ "msg": "Login failed, please logout and login again" }');
}

// Parameter handling
$filterDate  = preg_replace("/[^_a-zA-Z0-9-]+/", "", $_GET["date"] ?? date('Y-m-d'));
$filterArea  = $_GET['filterArea'] ?? '';
$filterSize  = $_GET['filterSize'] ?? '';
$filterHere  = $_GET['filterHere'] ?? '';
$sort        = $_GET['sort'] ?? '';

// Event details
$event_query = 'SELECT details, enabled from events where date="' . $filterDate . '";';
$result = $db->query($event_query);
if (!$result || $result->num_rows != 1) {
    die("Error: Seems like there is no event on the day selected.");
}
$row = $result->fetch_assoc();
if (!$row["enabled"]) {
    die("Error: Seems like the event is not enabled.");
}
$dish = $row["details"];

// Binding for prepare statement
$where[] = "`rsvp` = 1";
$where[] = "`date` = ?";
$params[] = $filterDate;
$types    = "s";

// Optional params
if ($filterArea !== '') {
    $where[] = "area = ?";
    $params[] = $filterArea;
    $types   .= "s";
}
if ($filterSize !== '') {
    $where[] = "rsvps.size = ?";
    $params[] = $filterSize;
    $types   .= "s";
}
if ($filterHere !== '') {
    $where[] = "here = ?";
    $params[] = $filterHere;
    $types   .= "s";
}
$allowedSorts = [
    'thaali' => 'thaali_id',
    'size'   => 'rsvps.size',
    'area'   => 'area'
];
$orderBy = $allowedSorts[$sort] ?? 'rsvps.size, area';

// Run query
$sql = "
    SELECT thaali_id AS id, rsvps.size, area
    FROM rsvps
    LEFT JOIN `family` on family.thaali = rsvps.thaali_id
    WHERE " . implode(" AND ", $where) . "
    ORDER BY $orderBy
";
$stmt = $db->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

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
$row = 0;

while ($r = $result->fetch_assoc()) {

    drawLabel($pdf, $x, $y, $r, $dish, $filterDate, $labelWidth, $labelHeight, 0.1);

    /* ---- Advance grid ---- */
    $col++;

    if ($col >= 3) {
        $col = 0;
        $row++;
        $x = $marginLeft;
        $y += $labelHeight + $spaceY;
    } else {
        $x += $labelWidth + $spaceX;
    }

    /* ---- New page ---- */
    if ($row >= 10) {
        $pdf->AddPage();
        $x = $marginLeft;
        $y = $marginTop;
        $col = 0;
        $row = 0;
    }
}

// Output the PDF automatically
$pdf->Output("D", "labels.pdf"); // 'D' triggers download


function drawLabel($pdf, $x, $y, $data, $dish, $date, $labelWidth, $labelHeight, $pad)
{
    $innerW = $labelWidth - ($pad * 2);

    // Optional debug border
    $pdf->Rect($x, $y, $labelWidth, $labelHeight);

    /*
     * Vertical layout (absolute, drift-free)
     */
    $yLine1 = $y + $pad;          // ID / Area / Size
    $yDish  = $y + 0.35;          // Dish name block
    $yDate  = $y + 0.80;          // Date

    /* ---------- Line 1: ID / Area (L) + Size (R) ---------- */
    $pdf->SetFont("Arial", "B", 14);

    $pdf->SetXY($x + $pad, $yLine1);
    $pdf->Cell($innerW, 0.18, "{$data['id']} {$data['area']}", 0, 0, "L");

    $pdf->SetXY($x + $pad, $yLine1);
    $pdf->Cell($innerW, 0.18, $data['size'], 0, 0, "R");

    /* ---------- Line 2: Dish name (wrapped, centered) ---------- */
    $pdf->SetFont("Arial", "", 10);

    $pdf->SetXY($x + $pad, $yDish);
    $pdf->MultiCell($innerW, 0.12, $dish, 0, "C");

    /* ---------- Line 3: Date ---------- */
    $pdf->SetXY($x + $pad, $yDate);
    $pdf->Cell($innerW, 0.15, $date, 0, 0, "C");
}

