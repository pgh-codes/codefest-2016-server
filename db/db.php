<?php
include_once("db-config.php");

function get_users($type_id = 0) {
	//returns list of valid users of selected type (all valid users by default)
	$query = "SELECT `user_id`, `firstname`, `lastname`, `type_id` FROM `user` WHERE `valid` = 1";
	if($type_id)
		$query .= "AND `type_id` = {$type_id}";
	
	return $db->get_all($query);
}

function login($user_id, $password) {
	//stores the user in session data, if submitted password is correct
	$password = mysqli_real_escape_string($db_link, $password);
	$query = "SELECT * FROM `user` WHERE `user_id` = {$user_id} AND `password` = MD5('{$password}')";
	
	$user = $db->get_row($query);
	if($user) {
		$_SESSION['info'] = $user;
		return TRUE;
	}
	else
		return FALSE;
}

function logout($user_id) {
	//clears out any user in session data
	//for drivers, releases any reserved, unfinished pickups
	if($_SESSION['info']['type_id'] == 4)
		release_events($user_id);

	unset($_SESSION['info']);
	
	return TRUE;
}

function release_events($user_id) {
	//for the given user, release unfinished pickups
	$query = <<<EOS
UPDATE `pickup_events` SET `dpw_user_id` = NULL, `reserve_date` = NULL, `reserve_time` = NULL
WHERE `pickup_date` IS NULL AND `user_id` = {$user_id}
EOS;
	$db->query($query);
	
	return TRUE;
}

?>