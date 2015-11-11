<?php

function getDBInfo() {
  return $dbInfo = parse_ini_file("user_info.ini");
}
?>
