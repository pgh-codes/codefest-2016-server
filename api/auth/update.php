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

    session_start();
    if(login($req['userid'], $req['password'])) {
      if(set_password($req['userid'], $req['newPassword'])) {
        print(json_encode(array("success" => "true", "error" => "")));
      } else {
        print(json_encode(array("success" => "false", "error" => "something went wrong updating password, please check the log")));
      }
    } else {
      print(json_encode(array("success" => "false", "error" => "authentication failed")));
    }
    break;
  default:
    print(json_encode(array("success" => false, "error" => "Unsupported request method")));
    break;
}
 ?>
