<?php
require_once('../db/db.php');

header('Content-type: application/json');

switch ($_SERVER['REQUEST_METHOD']) {
  case 'GET':
    $cans = get_cans();
    $resp = array();
    foreach($cans as $can) {
      $resp[] = array("id" => $can['can_id'], "type" => $can['type_id'], "lat" => $can['latitude'], "lng" => $can['longitude']);
    }
    print(json_encode(array("cans" => $resp), JSON_NUMERIC_CHECK));
    break;
}

?>
