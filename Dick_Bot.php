<?php
// 先ほどのtwitter_bot.phpを読み込む。パスはあなたが置いた適切な場所に変更してください
// CRONで動かす場合は、ここをサーバールートからのフルパスにする
require_once("twitter_bot.php");
require_once("Util.php");

$dick_bot = new Dick_Bot();
$dick_bot->main();

class Dick_Bot {

  function main() {
    $con;
    $user;
    $consumer_key;
    $consumer_secret;
    $access_token;
    $access_token_secret;

    Util::open_Log();

    try {

      $dbInfo = Util::getDBInfo();

      $dsn = $dbInfo['dsn'];
      $user = $dbInfo['db_user'];
      $password = $dbInfo['db_password'];

      $con = new PDO($dsn, $user, $password);

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

    } catch (Exception $e) {
      print("Caught Exception -> $e\n");

      Util::write_Log("Caught Exception -> ".$e);
    } finally {

    }

    // オブジェクト生成
    $Bot = new Twitter_Bot($user,
                           $consumer_key,
                           $consumer_secret,
                           $access_token,
                           $access_token_secret);

    $sid = null;
    $since_id = null;

    $min = date("i");
    //print("$min\n");

    try {

      // 以下通常のTL取得
      $since_id = $Bot->getLastTweetSid(); // 最後に取得した呟きのID
      print("last id -> \n");
      print_r($since_id);

      // 通常のタイムラインの取得
      //$timeline = $Bot->Get_TL("home_timeline", $since_id, 300);
      // ユーザータイムラインの取得
      //$timeline = $Bot->Get_TL("user_timeline", $since_id, 300);
      // mentionのタイムラインの取得
      $timeline = $Bot->Get_TL("mentions_timeline", $since_id);

      if($timeline) {
        foreach($timeline as $status){
          $tx = null;
          $sid = $status->id; // 呟きのID。int型
          if($since_id == null) {
            $since_id = $status->id_str;
          }
          $sid = $status->id_str; // 呟きのID。string型

          // $uid = $status->user->id; // ユーザーナンバー。int型
          $uid = $status->user->id_str; // ユーザーナンバーstring型
          $screen_name = $status->user->screen_name; // ユーザーID
          $name = $status->user->name; // ユーザー名

          // 呟き内容。余分なスペースを消して、半角カナを全角カナに、
          // 全角英数を半角英数に変換
          $text = mb_convert_kana(trim($status->text),"rnKHV","utf-8");

          $createdAt = $status->created_at;

          $creatTime = strtotime($createdAt);
          // 自分宛てのリプライをDBに登録する
          $Bot->Save_data("tweet_contents",
                          "$sid",
                          "$uid",
                          "$screen_name",
                          "$text",
                          0,
                          date('Y:m:d H:i:s', $creatTime));
        }
      }

      $this->autoReply($Bot);

    } catch ( PDOException $e) {
      print($e->getMessage()."\n");

      Util::write_Log("Caught Exception -> ".$e);
      //continue;
    } catch ( Exception $e) {
      print($e->getMessage()."\n");

      Util::write_Log("Caught Exception -> ".$e);
      //continue;
    } finally {

      Util::close_Log();
    }
  }

  function autoReply($Bot) {

    // リプライをしていないツイートを取得する
    $noReplied = $Bot->getNoReplied();

    if($noReplied == null) {
      print("All Tweets is replied\n");
      return;
    }

    foreach($noReplied as $row){
      //print_r($rows);
      //print($rows['screen_name']);
      $screen_name = $row['screen_name'];
      $sid = $row['sid'];

      $list = getTweetLists($con);

      if( shuffle($list) ){
        $messages = $list[0];
      }
      print("sid -> ".$sid."\n");
      // ツイート
      $result_code = $Bot->Post("@".$screen_name." ".$messages[0], $sid);

      if($result_code == 0) {
        $Bot->updateReplyStatus($sid);
      }
    }
  }

  function getTweetLists($con) {

    $sql = "SELECT
                   contents
            FROM
                   tweet_list";

    $query = $con->prepare($sql);
    $query->execute();

    $rows = $query->fetchAll();

  }
}
