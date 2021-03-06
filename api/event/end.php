<?php
require_once('../../db/db.php');

header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');

switch ($_SERVER['REQUEST_METHOD']) {
  case 'GET':
    if(isset($_GET['canId']) && is_numeric($_GET['canId'])) {
      session_start();
      if(isset($_SESSION['info'])) {
        if(($_SESSION['info']['type_id'] % 2) == 0) {
          end_event($_GET['canId']);
          print(json_encode(array("success" => "true", "error" => "")));
          break;
        } else {
          print(json_encode(array("success" => "false", "error" => "Improper user type")));
          break;
        }
      } else {
        print(json_encode(array("success" => "false", "error" => "Not logged in")));
        break;
      }
    } else {
      print(json_encode(array("success" => "false", "error" => "Invalid request parameters")));
      break;
    }
  default:
    print(json_encode(array("success" => false, "error" => "Unsupported request method")));
    break;

  }
