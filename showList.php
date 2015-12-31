<?php
  function getDBInfo() {
    return $dbInfo = parse_ini_file("user_info.ini");
  }

  $dbInfo = getDBInfo();

  $dsn = $dbInfo['dsn'];
  $user = $dbInfo['user'];
  $password = $dbInfo['password'];

  $con = new PDO($dsn, $user, $password);

  try {

    $sql = "select * from tweet_list";

    $query = $con->prepare($sql);
    $query->execute();

    foreach($query->fetchAll() as $row) {
      $no = $row['NO'];
      $content = $row['CONTENTS'];

      print("----------"."\n");
      print("No:"."\n");
      print($no."\n");
      print("Content:"."\n");
      print($content."\n");
      print("----------"."\n");

    }

  } catch(Exception $e) {
    print($e->getMessage()."\n");
  }
?>
