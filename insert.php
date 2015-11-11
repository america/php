<?php
  function getDBInfo() {
    return $dbInfo = parse_ini_file("user_info.ini");
  }

  $dbInfo = getDBInfo();

  $dsn = $dbInfo['dsn'];
  $user = $dbInfo['user'];
  $password = $dbInfo['password'];

  $con = new PDO($dsn, $user, $password);

  $list="list.dat";

  $filelist = file("$list", FILE_USE_INCLUDE_PATH);

  try {

  foreach($filelist as $value) {
    //print($value);
    $sql = "insert into tweet_list(CONTENTS) values('". $value. "')";

    $query = $con->prepare($sql);
    $query->execute();
  }
  } catch(PDOException $e) {
    print($e->getMessage());
  }


?>
