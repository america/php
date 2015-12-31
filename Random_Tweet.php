<?php

require_once("twitter_bot.php");
require_once("Util.php");

define('TRY_MAX', 10);

$bot = new Ramdom_Bot();
$bot->main($argv[1]);

class Ramdom_Bot {

  function main($option) {

    $con;
    $user;
    $consumer_key;
    $consumer_secret;
    $access_token;
    $access_token_secret;

    try {

      Util::open_Log();

      $con = Util::getDbConnection();

      $sql = "SELECT user,
                     consumer_key,
                     consumer_secret,
                     access_token,
                     access_token_secret
              FROM
                     twitter_users
              WHERE
                     No = 0";

      $query = $con->prepare($sql);
      $query->execute();

      foreach($query->fetchAll() as $row) {
        $user =  $row['user'];
        $consumer_key = $row['consumer_key'];
        $consumer_secret = $row['consumer_secret'];
        $access_token =  $row['access_token'];
        $access_token_secret = $row['access_token_secret'];
      }

      $Bot = new Twitter_Bot($user,
                             $consumer_key,
                             $consumer_secret,
                             $access_token,
                             $access_token_secret);

      $list = Util::getTweetLists();

      // latest
      if(strcmp($option, 'last') === 0) {
        $max = count($list);
        $messages = $list[$max - 1];
      // random
      } else {
        if( shuffle($list) ){
          $messages = $list[0];
        }
      }

      for($i = 0; $i < TRY_MAX; $i++) {
        // ツイート
        $result_code = $Bot->Post($messages[0]);
        if($result_code == 0) {
          break;
        }
      }
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
