<?php
require_once('../db/db.php');

header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');

switch ($_SERVER['REQUEST_METHOD']) {
  case 'GET':
    $cans = get_cans();
    $resp = array();
    foreach($cans as $can) {
      $resp[] = array("id" => $can['can_id'], "type" => $can['type_id'], "lat" => $can['latitude'], "lng" => $can['longitude']);
    }
    print(json_encode(array("cans" => $resp), JSON_NUMERIC_CHECK));
    break;
  case 'POST':
    $req = file_get_contents('php://input');

    if($req == "" || $req == null) {
      print(json_encode(array("success" => false, "error" => "No data received", "canId" => "")));
      break;
    }

    $can_info = json_decode($req, true);

    if($_REQUEST['canId'] != "") {
      $can_id = $_REQUEST['canId'];
      update_can($can_id, $can_info['type'], $can_info['lat'], $can_info['lng']);
    } else {
      $can_id = add_can($can_info['type'], $can_info['lat'], $can_info['lng']);
    }
    print(json_encode(array("success" => true, "error" => "", "canId" => $can_id)));
    break;
}

?>
