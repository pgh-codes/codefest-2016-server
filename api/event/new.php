<?php
require_once('../../db/db.php');

header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');

switch($_SERVER['REQUEST_METHOD']) {
  case POST:
    $req_json = file_get_contents('php://input');

    if($req_json == "" || $req_json == null) {
      print(json_encode(array("success" => false, "error" => "No data received")));
      break;
    } else {
      $req = json_decode($req_json, true);
    }
    if(!isset($req['canId'],$req['bags'])) {
      print(json_encode(array("success" => false, "error" => "Incomplete data received")));
      break;
    }
    session_start();
    if(isset($_SESSION['info']) && $_SESSION['info']['user_id'] > 0 ) {
      $new_event = start_event($req['canId'], $req['bags'], $_SESSION['info']['user_id'], $req['note']);
      print(json_encode(array("success" => "true", "error" => "", "eventId" => $new_event)));
      break;
    } else {
      print(json_encode(array("success" => "false", "error" => "Invalid Credentials")));
    }
    break;
  default:
    print(json_encode(array("success" => false, "error" => "Unsupported request method")));
    break;
}
 ?>
