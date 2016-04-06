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
  case POST:
    $req_json = file_get_contents('php://input');

    if($req_json == "" || $req_json == null) {
      print(json_encode(array("success" => false, "error" => "No data received")));
      break;
    } else {
      $req = json_decode($req_json, true);
    }
    if(!isset($req['firstname'],$req['lastname'],$req['type'])) {
      print(json_encode(array("success" => false, "error" => "Incomplete data received")));
      break;
    }
    session_start();
    if(isset($_SESSION['info'])) {
      $cur_user = get_user($_SESSION['info']);
      if($cur_user['type_id'] >= 2) {
        $new_password = add_user($req['firstname'], $req['lastname'], $req['type']);
        print(json_encode(array("success" => "true", "error" => "", "newPassword" => $new_password)));
        break;
      }
    }
  case DELETE:
    if(isset($_REQUEST['userId']) && is_numeric($_REQUEST['userId'])) {
      session_start();
      if(isset($_SESSION['info'])) {
        $cur_user = get_user($_SESSION['info']);
        if($cur_user['type_id'] >= 2) {
          remove_user($_REQUEST['userId']);
          print(json_encode(array("success" => "true", "error" => "", "userid" => $_REQUEST['userId'])));
          break;
        } else {
          print(json_encode(array("success" => "false", "error" => "Insufficient privileges")));
          break;
        }
      } else {
        print(json_encode(array("success" => "false", "error" => "Not logged in")));
        break;
      }
    } else {
      print(json_encode(array("success" => "false", "error" => "userId parameter not specified")));
      break;
    }
  default:
    print(json_encode(array("success" => false, "error" => "Unsupported request method")));
    break;

}
 ?>
