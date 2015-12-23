<?php

require_once("twitter_bot.php");
require_once("Util.php");

$bot = new Ramdom_Bot();
$bot->main();

class Ramdom_Bot {

  function main() {

    $con;
    $user;
    $consumer_key;
    $consumer_secret;
    $access_token;
    $access_token_secret;

    try {

      Util::open_Log();

      $con = Util::getDbConnection();

      $sql = "SELECT user, consumer_key, consumer_secret, access_token, access_token_secret
        FROM twitter_users
        WHERE No = 0";

      $query = $con->prepare($sql);
      $query->execute();

      foreach($query->fetchAll() as $row) {
        $user =  $row['user'];
        $consumer_key = $row['consumer_key'];
        $consumer_secret = $row['consumer_secret'];
        $access_token =  $row['access_token'];
        $access_token_secret = $row['access_token_secret'];
      }

      $Bot = new Twitter_Bot($user,$consumer_key,$consumer_secret,$access_token,$access_token_secret);

      $list = Util::getTweetLists();

      if( shuffle($list) ){
        $messages = $list[0];
      }
      // ツイート
      $result_code = $Bot->Post($messages[0]);

    } catch ( PDOException $e) {
      print($e->getMessage()."\n");

      Util::write_Log("Caught Exception -> ".$e);
    } catch ( Exception $e) {
      print($e->getMessage()."\n");

      Util::write_Log("Caught Exception -> ".$e);
    } finally {
      Util::close_Log();
    }
  }
}
