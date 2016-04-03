<?php
include_once("db-config.php");

function add_user($firstname, $lastname, $type_id) {
	//add new user
	$db = new db();
	$password = generate_password($firstname);

	$firstname = $db->clean($firstname);
	$lastname = $db->clean($lastname);
	$password = $db->clean($password);
	
	$query = "INSERT INTO `user` (`firstname`, `lastname`, `type_id`, `password`) VALUES ('{$firstname}', '{$lastname}', MD5('{$password}'))";
	$db->query($query);
	
	return $password;
}

function generate_password($firsthalf) {
	//generate a new password, made of a user-supplied first half and a random animal 
	$animals[] = "bear";
	$animals[] = "bison";
	$animals[] = "camel";
	$animals[] = "dingo";
	$animals[] = "eagle";
	$animals[] = "fish";
	$animals[] = "frog";
	$animals[] = "gecko";
	$animals[] = "goose";
	$animals[] = "horse";
	$animals[] = "koala";
	$animals[] = "lemur";
	$animals[] = "lion";
	$animals[] = "moose";
	$animals[] = "newt";
	$animals[] = "otter";
	$animals[] = "pony";
	$animals[] = "quail";
	$animals[] = "rhino";
	$animals[] = "shark";
	$animals[] = "sheep";
	$animals[] = "skunk";
	$animals[] = "snake";
	$animals[] = "squid";
	$animals[] = "tiger";
	$animals[] = "whale";
	$animals[] = "wolf";
	$animals[] = "zebra";
	
	$index = time() % sizeof($animals);
	
	return $firsthalf . $animals[$index];
}

function get_can($can_id) {
	//return can info
	$db = new db();
	
	$query = "SELECT `can_id`, `type_id`, `latitude`, `longitude` FROM `can` WHERE `can_id` = {$can_id}";
	$can = $db->get_row($query);

	$query = "SELECT * FROM `can_type` WHERE `type_id` = {$can['type_id']}";
	$can['type'] = $db->get_row($query);
	
	//determine what status the can is in
	$query = "SELECT * FROM `pickup_event` WHERE `pickup_date` IS NULL AND `can_id` = {$can_id}"
	$can['event'] = $db->get_row($query);
	
	if(!$current_event) {
		$can['status_id'] = 1; //No Action Necessary (Gray)
	}
	else {
		$can['event']['pdp_user_name'] = $db->get_one("SELECT CONCAT_WS(' ', firstname, lastname) FROM `user` WHERE `user_id` = {$can['event']['pdp_user_id']}");

		if($current['reserve_date']) {
			$can['event']['dpw_user_name'] = $db->get_one("SELECT CONCAT_WS(' ', firstname, lastname) FROM `user` WHERE `user_id` = {$can['event']['dpw_user_id']}");
			$can['status_id'] = 2; //Reserved For Pickup (Blue)
		}
		else {
			$two_hours_ago = strtotime("-2 hour");
			$four_hours_ago = strtotime("-4 hour");
			
			if(strtotime("{$can['event']['bag_date']} {$can['event']['bag_time']}") > $two_hours_ago)
				$can['status_id'] = 3; //Bagged Under Two Hours Ago (Green)
			elseif(strtotime("{$can['event']['bag_date']} {$can['event']['bag_time']}") > $four_hours_ago)
				$can['status_id'] = 4; //Bagged Under Four Hours Ago (Yellow)
			else
				$can['status_id'] = 5; //Bagged Over Four Hours Ago (Red)
		}
	}
	
	return $can;
}

function get_cans() {
	//returns list of all cans, and their "status"
	$db = new db();
	
	$query = "SELECT `can_id` FROM `can` WHERE `valid` = 1";
	$can_ids = $db->get_all($query);
	
	$cans = array();
	foreach($can_ids as $can_id)
		$cans[] = get_can($can_id);
	
	return $cans;
}

function get_user_types() {
	//returns list of user types
	$db = new db();
	
	return $db->get_all("SELECT * FROM `user_types`");
}

function get_users($type_id = 0) {
	//returns list of valid users of selected type (all valid users by default)
	$db = new db();

	$query = "SELECT `user_id`, `firstname`, `lastname`, `type_id` FROM `user` WHERE `valid` = 1";
	if($type_id)
		$query .= "AND `type_id` = {$type_id}";
	
	return $db->get_all($query);
}

function login($user_id, $password) {
	//stores the user in session data, if submitted password is correct
	$db = new db();

	$password = $db->clean($password);
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
	$db = new db();

	$query = <<<EOS
UPDATE `pickup_events` SET `dpw_user_id` = NULL, `reserve_date` = NULL, `reserve_time` = NULL
WHERE `pickup_date` IS NULL AND `user_id` = {$user_id}
EOS;
	$db->query($query);
	
	return TRUE;
}

function remove_user($user_id) {
	//remove the given use from view
	$db = new db();
	
	$query = "UPDATE `user` SET `valid` = 0 WHERE `user_id` = {$user_id}";
	$db->query($query);
	
	return TRUE;
}

function reset_password($user_id) {
	//set a new random password for the given user
	$db = new db();
	
	$firstname = $db->get_one("SELECT `firstname` FROM `user` WHERE `user_id` = {$user_id}");
	$password = generate_password($firstname);
	$password = $db->clean($password);
	
	$query = "UPDATE `user` SET `password` = MD5('{$password}') WHERE `user_id` = {$user_id}";
	$db->query($query);
	
	return $password;
}

function set_password($user_id, $password) {
	//manually set a new password for the given user
	$db = new db();
	
	$password = $db->clean($password);

	$query = "UPDATE `user` SET `password` = MD5('{$password}') WHERE `user_id` = {$user_id}";
	$db->query($query);
	
	return TRUE;
}

?>