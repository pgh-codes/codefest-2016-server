<?php
require_once('../../db/db.php');

header('Content-type: application/json');

switch($_SERVER['REQUEST_METHOD']) {
  case GET:
    session_start();
    if (!isset($_SESSION['info'])) {
      print(json_encode(array("success" => false, "error" => "not logged in")));
      break;
    } else {
      unset($_SESSION['info']);
      print(json_encode(array("success" => true, "error" => "")));
      break;
    }
  default:
    print(json_encode(array("success" => false, "error" => "Unsupported request method")));
    break;
}
 ?>
