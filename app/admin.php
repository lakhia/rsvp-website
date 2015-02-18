<?php

require_once('aux.php');

// If token is invalid, return an empty response
if (!Helper::is_admin($email_cookie) || 
    !Helper::verify_token($email_cookie, $thaali_cookie)) {
    return;
}

$data = json_decode(file_get_contents('php://input'), true);
insert_week($db, $data['date'], $data['details']);

function insert_week($db, $date, $details)
{
	$sql = "INSERT INTO `week` (`date`, `details`) " .
        "VALUES ('$date', '$details') " .
        "on duplicate KEY update details='$details'";
	$msg = !$db->query($sql) ? $db->error : "Successfully added '$details' on $date";
	echo Helper::convert_array_to_json("", $msg);
}

function delete_week($db, $date)
{
	$sql = "DELETE FROM `week` WHERE `date` = '$date';";
	$msg = !$db->query($sql) ? $db->error : "Successfully deleted for $date";
	echo Helper::convert_array_to_json("", $msg);
}

function push_data($db)
{
	$today = 
	$sql = "SELECT * FROM `week` WHERE `date` "
}

?>