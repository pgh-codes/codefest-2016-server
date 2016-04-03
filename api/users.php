<?php
require_once('../db/db.php');
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');


switch($_SERVER['REQUEST_METHOD']) {
  case GET:
    if(is_numeric($_GET['userId'])) {
      $users = get_user($_GET['userId']);
    } else {
      $users = get_users();
    }
    $resp = array();
    foreach($users as $user) {
      $resp[] = array("username" => $user['firstname'] . " " . $user['lastname'], "userid" => $user['user_id'], "type" => $user['type_id']);
    }

    print(json_encode(array("users" => $resp), JSON_NUMERIC_CHECK));
    break;
  default:
    print(json_encode(array("success" => false, "error" => "Unsupported request method")));
    break;

}
 ?>
