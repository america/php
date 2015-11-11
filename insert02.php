<?php
  function getDBInfo() {
    return $dbInfo = parse_ini_file("user_info.ini");
  }

  $dbInfo = getDBInfo();

  $dsn = $dbInfo['dsn'];
  $user = $dbInfo['user'];
  $password = $dbInfo['password'];

  $con = new PDO($dsn, $user, $password);


  //print($argv[1]."\n");
  try {

    $sql = "insert into tweet_list(CONTENTS) values('". $argv[1]. "')";

    $query = $con->prepare($sql);
    $query->execute();
  } catch(Exception $e) {
    print($e->getMessage());
  }


?>
