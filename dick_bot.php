<?php
// 先ほどのtwitter_bot.phpを読み込む。パスはあなたが置いた適切な場所に変更してください
// CRONで動かす場合は、ここをサーバールートからのフルパスにする
require_once("twitter_bot.php");
require_once("Util.php");

$con;
$user;
$consumer_key;
$consumer_secret;
$access_token;
$access_token_secret;


$app_name = "dick_bot";

$logFile = dirname(__FILE__) . "/" . $app_name . ".log";

if (!($fp = fopen("$logFile", 'a'))) {
      return;
}

try {

  $dbInfo = getDBInfo();

  $dsn = $dbInfo['dsn'];
  $user = $dbInfo['user'];
  $password = $dbInfo['password'];

  $con = new PDO($dsn, $user, $password);

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

} catch (Exception $e) {
  print("Caught Exception -> $e\n");
  print("Message -> $e.getMessage()\n");

} finally {

}

// オブジェクト生成
$Bot = new Twitter_Bot($user,$consumer_key,$consumer_secret,$access_token,$access_token_secret);

$sid = null;
$since_id = null;

$min = date("i");
//print("$min\n");

  try {

    // データファイルを空にする
    $Bot->Clear_data("Tweet");

    // 以下通常のTL取得
    $since_id = $Bot->Get_data("Since"); // 最後に取得した呟きのID
    $Bot->Clear_data("Since");
    //print_r($since_id);
    //$timeline = $Bot->Get_TL("home_timeline", $since_id, 300); // タイムラインの取得
    //$timeline = $Bot->Get_TL("user_timeline", $since_id, 300); // タイムラインの取得
    $timeline = $Bot->Get_TL("mentions_timeline", $since_id); // タイムラインの取得

    foreach($timeline as $status){
      $tx = null;
      // $sid = $status->id; // 呟きのID。int型
      //if($since_id == null) {
      //  $since_id = $status->id_str;
      //  print_r("$since_id");
      //}
      $sid = $status->id_str; // 呟きのID。string型
      //print("sid -> $sid\n");

      // $uid = $status->user->id; // ユーザーナンバー。int型
      $uid = $status->user->id_str; // ユーザーナンバーstring型
      $screen_name = $status->user->screen_name; // ユーザーID
      $name = $status->user->name; // ユーザー名
      // 呟き内容。余分なスペースを消して、半角カナを全角カナに、全角英数を半角英数に変換
      $text = mb_convert_kana(trim($status->text),"rnKHV","utf-8");

      $createdAt = $status->created_at;

      $creatTime = strtotime($createdAt);
      $Bot->Save_data("tweet_contents", "$sid", "$uid", "$screen_name", "$text", 0, date('Y:m:d H:i:s', $creatTime));
      //$Bot->Save_data("Tweet","\n\n");

      // Botが自分自身の呟き、RT、QTに反応しないようにする
      //if($screen_name == $user || preg_match("/(R|Q)T( |:)/",$text)){
      if($name == $user){
        print("screen_name -> $screen_name\n");
        print("user -> $user\n");
        print("$text\n");
        //print("abc\n");
        continue;
      }
      if(stristr($text,"@".$user)){ // Bot宛のリプライ
        print("get Mention!\n");
        //print("created_at -> $createdAt\n");
        //print("now -> ".date( "Y/m/d (D) H:i:s", time())."\n");
        //print("$text\n");
        if(preg_match("/おは(よ)?(う|ー|～|〜)/",$text)){
          $tx = "おはようございます、".$name."さん";
        }
        elseif(preg_match("/こんにち(は|わ)/",$text)){
          $tx = "こんにちは";
        }
        elseif(preg_match("/こんばん(は|わ)/",$text)){
          $tx = "こんばんは";
        }
        //elseif(preg_match("/(可愛|かわい)い/",$text)){
        //  $tx = "あら、ありがとうございます";
        //}
        elseif(!preg_match("/[一-龠ぁ-んァ-ヴー！？]/u",$text)){
          $tx = "ん？ 日本語じゃないわね。何処の言葉かしら？";
        }
        else {
          continue;
        }
      } elseif(!strstr($text,"@")){ // リプライでない普通の呟き
      //  if(preg_match("/風邪(引|ひ)いた/",$text)){
      //    $tx = $name."さん大丈夫？ お大事にね";
      //  }
        // $tx = Rrt(array("～","～",・・・,"～")); とすればランダムに台詞を一つ取り出します
      //  elseif(preg_match("/眠い|ねむい|ねむたい/",$text)){
      //    $tx = Rrt(array("無理しないでね","適度に休憩してね"));
      //  }
      //  else {
      //    continue;
      //  }
      } else { // フォロワー同士のリプライなど。通常はこういったものに反応されると嫌がられるかも
        continue;
      }
      // $txが空でないのならPOST
      if($tx){
        print("Post:$tx\n");
        //$Bot->Post("@".$screen_name." ".$tx,$sid);
      }
      print("\n");
    }

    $sql = "SELECT CONTENTS
            FROM tweet_list";

    $query = $con->prepare($sql);
    $query->execute();

    $rows = $query->fetchAll();

    //print_r($rows);
    if( shuffle($rows) ){
      $message = $rows[0];
      //print_r($message[0]);
      //print($message[0]);
    }
    // ツイート
    //$Bot->Post($message[0], $sid);

  } catch ( PDOException $e) {
    print($e->getMessage()."\n");

    writeLog($fp, "Caught Exception -> ".$e);
    continue;
  } catch ( Exception $e) {
    print($e->getMessage()."\n");

    writeLog($fp, "Caught Exception -> ".$e);
    continue;
  } finally {
    // 最後に呟きのIDを保存して終わり
    //$Bot->End($sid);

    fclose($fp);
  }

function writeLog($fp, $msg) {

    fprintf($fp, date('Y:m:d H:i:s')."\n");
    fprintf($fp, "$msg"."\n");
}
?>
