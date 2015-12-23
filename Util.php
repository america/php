<?php

class Util {
  private static $fp;
  private static $con;

  public static function getDBInfo() {
    return $dbInfo = parse_ini_file("user_info.ini");

  }

  public static function open_Log() {

    $app_name = basename($_SERVER['PHP_SELF']);
    $logFile = dirname(__FILE__) . "/" . $app_name . ".log";
    if (!(self::$fp = fopen("$logFile", 'a'))) {
      return;
    }
    //return $fp;
  }

  public static function close_Log() {
    if(self::$fp != null) {
      fclose(self::$fp);
    }
  }

  public static function write_Log($msg) {

    fprintf(self::$fp, date('Y:m:d H:i:s')."\n");
    fprintf(self::$fp, "$msg"."\n");
  }

  public static function getTweetLists() {

    self::$con = self::getDbConnection();

    $sql = "SELECT CONTENTS
      FROM tweet_list";

    $query = self::$con->prepare($sql);
    $query->execute();

    $rows = $query->fetchAll();

    return $rows;
  }

  public static function getDbConnection() {
    try {

      $dbInfo = self::getDBInfo();

      $dsn = $dbInfo['dsn'];
      $user = $dbInfo['db_user'];
      $password = $dbInfo['db_password'];

      self::$con = new PDO($dsn, $user, $password);

      return self::$con;
    } catch ( PDOException $e) {
      print($e->getMessage()."\n");

      self::write_Log("Caught Exception -> ".$e);
    } catch ( Exception $e) {
      print($e->getMessage()."\n");

      self::write_Log("Caught Exception -> ".$e);
    } finally {

    }
  }
}
?>
