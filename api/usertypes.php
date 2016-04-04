<?php
require_once('../db/db.php');
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');


switch($_SERVER['REQUEST_METHOD']) {
  case GET:
    if(is_numeric($_GET['type'])) {
      $usertypes = get_user_type($_GET['type']);
    } else {
      $usertypes = get_user_types();
    }
    $resp = array();
    foreach($usertypes as $usertype) {
      $resp[] = array("type" => $usertype['type_id'], "name" => $usertype['name']);
    }

    print(json_encode(array("usertypes" => $resp), JSON_NUMERIC_CHECK));
    break;

  default:
    print(json_encode(array("success" => false, "error" => "Unsupported request method")));
    break;

}
 ?>
