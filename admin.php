<?php

require_once('aux.php');

$data = json_decode(file_get_contents('php://input'), true);

if ( $method ) //== "POST" )
{
	insert_week($db, $data['date'], $data['details']);
}


function insert_week($db, $date, $details)
{
	//$edate = $db->real_escape_string($date);
	//$edetails = $db->real_escape_string($details);

	$sql = "INSERT INTO `week` (`date`, `details`) VALUES ('" . $date ."', '" . $details . "');";

	$msg = ( !$db->query($sql) ) ? $db->error : "Successfully added meal on " . $date .".";

	echo Helper::convert_array_to_json("", $msg);

	return true;
}

function delete_week($db, $date)
{
	$sql = "DELETE FROM `week` WHERE `date` = '" . $date . "';";

	$msg = ( !$db->query($sql) ) ? $db->error : "Successfully deleted meal on " . $date . ".";

	echo Helper::convert_array_to_json("", $msg);
}


?>