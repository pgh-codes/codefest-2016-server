<?php
include_once("db-config.php");

function add_can($type_id, $latitude, $longitude) {
	//add a new can
	$db = new db();

	$query = <<<EOS
INSERT INTO `can`
(`type_id`, `latitude`, `longitude`)
VALUES
({$type_id}, {$latitude}, {$longitude})
EOS;
	$db->query($query);

	$can_id = $db->get_one("SELECT LAST_INSERT_ID");

	return $can_id;
}

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

function create_route($user_id, $can_ids) {
	//reserve the cans needed to form a route

	foreach($can_ids as $can_id) {
		reserve_event($can_id, $user_id);
	}

	return TRUE;
}

function end_event($can_id) {
	//mark all events for given can as resolved
	$db = new db();

	//this will reserve any events, even those already reserved by others
	reserve_event($can_id, $_SESSION['info']['user_id'], 1);
	
	$query = <<<EOS
UPDATE `pickup_event`
SET `pickup_date` = CURRENT_DATE(), `pickup_time` = CURRENT_TIME()
WHERE `pickup_date` IS NULL
AND `can_id` = {$can_id}
EOS;
	$result = $db->query($query);

	return TRUE;
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

function generate_report($start_date, $end_date, $user_id = NULL) {
	//generate a list of events created (for PDP users) or ended (for DPW users)
	//over given date range
	$db = new db();

	$start_date = $db->clean($start_date);
	$end_date = $db->clean($end_date);


	$query = "SELECT * FROM `pickup_event` WHERE `bag_date` BETWEEN '{$start_date}' AND '{$end_date}'";

	if($user_id) {
		$user_type_id = $db->get_one("SELECT `type_id` FROM `user` WHERE `user_id` = {$user_id}");
		$query .= ($user_type_id == 3) ? " AND `pdp_user_id` = {$user_id}" : " AND `dpw_user_id` = {$user_id}";
	}

	$events = $db->get_all($query);

	foreach($events as &$event) {
		$event['can_type'] = $db->get_one("SELECT `name` FROM `can_type` JOIN `can` USING (`type_id`) WHERE can_id = {$event['can_id']}");
		$event['pdp_user_name'] = $db->get_one("SELECT CONCAT_WS(' ', firstname, lastname) FROM `user` WHERE `user_id` = {$event['pdp_user_id']}");
		if($event['dpw_user_id'])
			$event['dpw_user_name'] = $db->get_one("SELECT CONCAT_WS(' ', firstname, lastname) FROM `user` WHERE `user_id` = {$event['dpw_user_id']}");
	}
	unset($event);

	return $events;
}

function get_all_cans_perf($status_id) {

	$db = new db();

	$query = 'SELECT `c`.`can_id`,`c`.`approx_location`,`c`.`type_id`,`c`.`latitude`,`c`.`longitude` FROM `can` `c` LEFT OUTER JOIN `pickup_event` `p` ON `p`.`can_id` = `c`.`can_id` INNER JOIN `can_type` `t` ON `c`.`type_id` = `t`.`type_id` WHERE `p`.`pickup_date` IS NULL';
	$cans = $db->get_all($query);
	$query = "SELECT `t`.* FROM (SELECT `can_id`,`pickup_date`,`pickup_time` FROM `pickup_event` WHERE `pickup_date` IS NOT NULL ORDER BY `pickup_date` DESC, `pickup_time` DESC) `t` GROUP BY `t`.`can_id`";
	$last_pickups_un = $db->get_all($query);

	$query = "SELECT `t`.* FROM (SELECT `can_id`,`bag_date`,`bag_time`,`reserve_date`,`reserve_time` FROM `pickup_event` WHERE `pickup_date` IS NULL ORDER BY `reserve_date` ASC,`reserve_time` ASC) `t` GROUP BY `t`.`can_id`";
	$outstanding_pickups_un = $db->get_all($query);

	$query = "SELECT * FROM `can_status`";
	$status_types_un = $db->get_all($query);

	$last_pickups = array();
	foreach($last_pickups_un as $pickup) {
		$last_pickups[$pickup['can_id']] = $pickup['pickup_date'] . "-" . $pickup['pickup_time'];
	}

	$can_status = array();
	foreach($outstanding_pickups_un as $pickup) {
		if(isset($pickup['reserve_date']) && $pickup['reserve_date'] != "") {
			$can_status[$pickup['can_id']] = 2;
		} else {
			$time_active = time() - strtotime($pickup['bag_date'] . " " . $pickup['bag_time']);
			if($time_active > 14400) {
				$can_status[$pickup['can_id']] = 5;
			} elseif($time_active > 7200) {
				$can_status[$pickup['can_id']] = 4;
			} else {
				$can_status[$pickup['can_id']] = 3;
			}
		}
	}

	$status_colors = array();
	foreach($status_types_un as $status_type) {
		$status_colors[$status_type['status_id']] = $status_type['color'];
	}

	foreach($cans as $arr_num => $can) {
		if(!isset($can_status[$can['can_id']])) {
			$can_status[$can['can_id']] = 1;
		}
		if(!in_array($can_status[$can['can_id']], $status_id)) {
			unset($cans[$arr_num]);
		} else {
			if(isset($last_pickups[$can['can_id']]) && $last_pickups[$can['can_id']] != "") {
				$cans[$arr_num]['last_pickup'] = $last_pickups[$can['can_id']];
			} else {
				$cans[$arr_num]['last_pickup'] = 0;
			}
			if(isset($can_status[$can['can_id']]) && $can_status[$can['can_id']] != "") {
				$cans[$arr_num]['color'] = $status_colors[$can_status[$can['can_id']]];
			} else {
				$cans[$arr_num]['color'] = "gray";
			}
		}
	}

	return($cans);

}

function get_can($can_id) {
	//return can info
	$db = new db();

	$query = "SELECT `can_id`, `approx_location`, `type_id`, `latitude`, `longitude` FROM `can` WHERE `can_id` = {$can_id}";
	$can = $db->get_row($query);

	$query = "SELECT * FROM `can_type` WHERE `type_id` = {$can['type_id']}";
	$can['type'] = $db->get_row($query);

	$query = "SELECT CONCAT_WS(' ', `pickup_date`, `pickup_time`) FROM `pickup_event` WHERE `can_id` = {$can_id} ORDER BY `pickup_date` DESC, `pickup_time` DESC LIMIT 0, 1";
	$can['last_pickup_raw'] = $db->get_one($query);
	$can['last_pickup'] = ($can['last_pickup_raw']) ? date("n/j/y @ g:i A", strtotime($can['last_pickup_raw'])) : "";

	$one_month_ago = date("Y-m-d", strtotime("-1 month"));
	$query = "SELECT `bag_date`, `notes` FROM `pickup_event` WHERE `notes` <> '' AND `can_id` = {$can_id} AND `bag_date` >= '{$one_month_ago}' ORDER BY `bag_date` DESC, `bag_time` DESC LIMIT 0, 3";
	$can['recent_notes_raw'] = $db->get_all($query);
	$can['recent_notes'] = array();
	foreach($can['recent_notes_raw'] as $note) {
		$can['recent_notes'][] = $note['notes'] . " " . date("(n/j/y)", strtotime($note['bag_date']));
	}

	//determine what status the can is in
	$query = "SELECT * FROM `pickup_event` WHERE `pickup_date` IS NULL AND `can_id` = {$can_id}";
	$can['events'] = $db->get_all($query);

	if(!$can['events']){
		$can['status_id'] = 1; //No Action Necessary (Gray)
	}
	else {
		foreach($can['events'] as $event) {
			$event['pdp_user_name'] = $db->get_one("SELECT CONCAT_WS(' ', firstname, lastname) FROM `user` WHERE `user_id` = {$event['pdp_user_id']}");

			if($current['reserve_date']) {
				$event['dpw_user_name'] = $db->get_one("SELECT CONCAT_WS(' ', firstname, lastname) FROM `user` WHERE `user_id` = {$event['dpw_user_id']}");
				$can['status_id'] = ($can['status_id'] < 2) ? 2 : $can['status_id']; //Reserved For Pickup (Blue)
			}
			else {
				$two_hours_ago = time() - (60 * 60 * 2);
				$four_hours_ago = time() - (60 * 60 * 4);

				if(strtotime("{$event['bag_date']} {$event['bag_time']}") > $two_hours_ago)
					$can['status_id'] = ($can['status_id'] < 3) ? 3 : $can['status_id']; //Bagged Under Two Hours Ago (Green)
				elseif(strtotime("{$event['bag_date']} {$event['bag_time']}") > $four_hours_ago)
					$can['status_id'] = ($can['status_id'] < 4) ? 4 : $can['status_id']; //Bagged Under Four Hours Ago (Yellow)
				else
					$can['status_id'] = ($can['status_id'] < 5) ? 5 : $can['status_id']; //Bagged Over Four Hours Ago (Red)
			}
		}
	}

	$can['color'] = $db->get_one("SELECT `color` FROM `can_status` WHERE `status_id` = {$can['status_id']}");

	return $can;
}

function get_can_types() {
	//returns list of can types
	$db = new db();

	return $db->get_all("SELECT * FROM `can_type`");
}

function get_cans($status_id = 0) {
	//returns list of all cans, and their "status"
	$db = new db();

	$query = "SELECT `can_id` FROM `can` WHERE `valid` = 1";
	$can_ids = $db->get_col($query);

	$cans = array();
	foreach($can_ids as $can_id) {
		if($status_id)
		{
			$can = get_can($can_id);
			if(in_array($can['status_id'], $status_id)) {
				$cans[] = $can;
			}
		}
		else
			$cans[] = get_can($can_id);
	}

	return $cans;
}

function get_user($user_id) {
	//return data for given user
	$db = new db();

	$query = "SELECT `user_id`, `firstname`, `lastname`, `type_id` FROM `user` WHERE `user_id` = {$user_id}";
	$user = $db->get_row($query);

	return $user;
}

function get_user_types() {
	//returns list of user types
	$db = new db();

	return $db->get_all("SELECT * FROM `user_type`");
}

function get_users($type_id = 0) {
	//returns list of valid users of selected type (all valid users by default)
	$db = new db();

	$query = "SELECT `user_id` FROM `user` WHERE `valid` = 1";

	if($type_id)
		$query .= " AND `type_id` = {$type_id}";

	$user_ids = $db->get_col($query);

	foreach($user_ids as $user_id) {
		$users[] = get_user($user_id);
	}

	return $users;
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

function release_events($user_id) {
	//for the given user, release unfinished pickups
	$db = new db();

	$query = <<<EOS
UPDATE `pickup_event` SET `dpw_user_id` = NULL, `reserve_date` = NULL, `reserve_time` = NULL
WHERE `pickup_date` IS NULL AND `dpw_user_id` = {$user_id}
EOS;
	$db->query($query);

	return TRUE;
}

function remove_can($can_id) {
	//remove the given can from view
	$db = new db();

	$query = "UPDATE `can` SET `valid` = 0 WHERE `can_id` = {$can_id}";
	$db->query($query);

	return TRUE;
}

function remove_user($user_id) {
	//remove the given user from view
	$db = new db();

	$query = "UPDATE `user` SET `valid` = 0 WHERE `user_id` = {$user_id}";
	$db->query($query);

	return TRUE;
}

function reserve_event($can_id, $user_id, $force = 0) {
	//reserve any unreserved events at the given can
	$db = new db();

	$force_query = ($force) ? "(`reserve_date` IS NULL OR `dpw_user_id` <> {$user_id})" : "`reserve_date` IS NULL";
	
	$query = <<<EOS
UPDATE `pickup_event` SET `dpw_user_id` = {$user_id}, `reserve_date` = CURRENT_DATE(), `reserve_time` = CURRENT_TIME()
WHERE `can_id` = {$can_id} AND {$force_query}
EOS;
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

function start_event($can_id, $bag_count, $user_id, $notes) {
	//create a new event for the given can
	$db = new db();

	$notes = $db->clean($notes);

	//if an unfinished event for this can is already assigned
	//assign this event to that driver too
	$query = "SELECT `dpw_user_id` FROM `pickup_event` WHERE `pickup_date` IS NULL AND `can_id` = {$can_id}";
	$dpw_user_id = $db->get_one($query);

	if($dpw_user_id)
	{
		$reserve_date = "CURRENT_DATE()";
		$reserve_time = "CURRENT_TIME()";
	}
	else
		$dpw_user_id = $reserve_date = $reserve_time = "NULL";

	$query = <<<EOS
INSERT INTO `pickup_event`
(`can_id`, `bag_count`, `pdp_user_id`, `bag_date`, `bag_time`, `dpw_user_id`, `reserve_date`, `reserve_time`, `notes`)
VALUES
({$can_id}, {$bag_count}, {$user_id}, CURRENT_DATE(), CURRENT_TIME(), {$dpw_user_id}, {$reserve_date}, {$reserve_time}, '{$notes}')
EOS;
	$db->query($query);

	$event_id = $db->get_one("SELECT LAST_INSERT_ID");

	return $event_id;
}

function testing($display) {
	echo "<PRE>"; print_r($display); echo "</PRE>";
	exit;
}

function update_can($can_id, $type_id, $latitude, $longitude) {
	//update all attributes of a can
	$db = new db();

	$query = <<<EOS
UPDATE `can`
SET `type_id` = {$type_id}, `latitude` = {$latitude}, `longitude` = {$longitude}
WHERE `can_id` = {$can_id}
EOS;
	$db->query($query);

	return TRUE;
}

?>
