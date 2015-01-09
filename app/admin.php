<?php

require_once('aux.php');

$data = json_decode(file_get_contents('php://input'), true);
insert_week($db, $data['date'], $data['details']);

function insert_week($db, $date, $details)
{
	$sql = "INSERT INTO `week` (`date`, `details`) " .
        "VALUES ('$date', '$details') " .
        "on duplicate KEY update details='$details'";
	$msg = !$db->query($sql) ? $db->error : "Successfully added for $date";
	echo Helper::convert_array_to_json("", $msg);
}

function delete_week($db, $date)
{
	$sql = "DELETE FROM `week` WHERE `date` = '$date';";
	$msg = !$db->query($sql) ? $db->error : "Successfully deleted for $date";
	echo Helper::convert_array_to_json("", $msg);
}


?>