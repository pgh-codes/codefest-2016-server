<?php
require_once('../db/db.php');

switch ($_SERVER['REQUEST_METHOD']) {
  case 'GET':
    print_r(get_cans());
    break;
}

?>
