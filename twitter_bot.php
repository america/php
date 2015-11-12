<?php
/*
 *  twitter_bot.php by 栃尾トメ
 *    SDN Project - http://www.sdn-project.net/
 */

// twitteroauth.phpを読み込む。パスはあなたが置いた適切な場所に変更してください。フルパスの方がいいかも。
//require_once("twitteroauth.php");
require_once("twitteroauth/autoload.php");
require_once("Util.php");
use Abraham\TwitterOAuth\TwitterOAuth;

date_default_timezone_set('UTC');


class Twitter_Bot{
  var $user;
  var $TO;
  var $times;

  function Twitter_Bot($usr,$consumer_key,$consumer_secret,$oauth_token,$oauth_token_secret){
    $this->user = $usr;
    $this->TO = new TwitterOAuth($consumer_key,$consumer_secret,$oauth_token,$oauth_token_secret);
    $this->times = array_sum(explode(" ",microtime()));
  }
  function Request($url,$method = "POST",$opt = array()){
    //$req = $this->TO->OAuthRequest("https://api.twitter.com/1.1/".$url,$method,$opt);
    if($method == "post") {
      $req = $this->TO->$method($url, array('status' => $opt));
    } else if($method == "get") {
      $req = $this->TO->$method($url, $opt);
    }
    if($req){
      $result = $req;
    } else {
      $result = null;
    }
    return $result;
  }
 // データを読み込む。ここをSQLiteやMySQLなどにデータを保存するように書き換えてもいいかもしれない。
 function Get_data($type){
   $dat = $this->user."_".$type.".dat";
   if(!file_exists($dat)){
     touch($dat);
     chmod($dat,0666);
     return null;
   }
   return file($dat);
 }

 // DBにデータを書き込む。
 function Save_data($tableName, $sid, $uid, $screen_name, $tweet, $reply, $createdAt){

   try {
     $dbInfo = getDBInfo();
     $con = new PDO($dbInfo['dsn'], $dbInfo['user'], $dbInfo['password']);
     $sql = "insert into ". $tableName. "(sid, uid, screen_name, tweet, reply, create_time) values('".$sid."', '".$uid."', '".$screen_name."', '".$tweet."', '".$reply."', '".$createdAt. "')";
     print("sql -> ". $sql. "\n");
     $query = $con->prepare($sql);
     $result = $query->execute();
     if(!$result) {
       //print("Execute Failure!\n");
       //print("sql -> ".$sql."\n");
       throw new PDOException("Excecute Failure! sql -> ".$sql."\n");
     }
   }catch(PDOException $e) {
     //print($e->getMessage()."\n");
     print("ha\n\n");
     throw $e;
   }
 }
 // Clear data file
 function Clear_data($type){
   $dat = $this->user."_".$type.".dat";
   if(!file_exists($dat)){
     touch($dat);
     chmod($dat,0666);
   }
   $fdat = fopen($dat,"w");
   flock($fdat,LOCK_EX);
   ftruncate($fdat, 0);
   flock($fdat,LOCK_UN);
   fclose($fdat);
 }
  // ツイートをPOSTする。$statusには発言内容。
  // $repは相手にリプライする場合にリプライ元のツイートのIDを指定する。リプライ元のツイートとリプライする相手のユーザー名が一致しないといけない。
  function Post($status,$rep = null) {
    $opt = array("status"=>$status);
    if($rep){
      $opt['in_reply_to_status_id'] = $rep;
    }
    //$req = $this->Request("statuses/update.json","POST",$opt);
    $req = $this->Request("statuses/update","post",$opt);
    if(!$req){
      die('Post(): $req is NULL');
    }
    //$code = $req->Code;
    $responceCode = $this->TO->getLastHttpCode();
    //$result = json_decode($req->Body);

    print $status;

    if(($responceCode == "200" || $responceCode == "304") && $rep){
      print("tweet OK\n");
      $this->Save_data("Since",$rep);
      return;
    } else {
      print("tweet NG\n");
    }

    //if($result->error){
    //  die($code.", ".$result->error);
    //}
  }
  // タイムラインなどを取得する。$typeにはhome_timeline、mentions_timelineなど。詳しくはAPI仕様書を。
  // $sidはツイートのID。$sidで指定したツイートのIDより後のツイートを取得するようにさせる。
  // $countは一度にツイートをどれだけ取得するか。最大200。
  function Get_TL($type,$sid = null,$count = 30){
    try {

      $opt = array("count"=>$count);

      if($sid){
        $opt['since_id'] = $sid;
      }
      //$req = $this->Request("statuses/".$type.".json","GET",$opt);
      $req = $this->Request("statuses/".$type,"get",$opt);
      if($req){
        $http_code = $this->TO->getLastHttpCode();

        //if($req->Code != "200"){
        if($http_code != "200"){
          //die("Error: ".$http_code);
          throw new Exception("http_code -> $http_code");
        }
        //$result = json_decode($req->Body);
      } else {
        die('Get_TL(): $req is NULL');
      }
      //if($result->error){
      //  die($result->error);
      //}
      //return array_reverse($result);

    } catch( Exception $e) {
      print("Caught Exception!\n");
      print($e->getMessage()."\n");
      throw $e;
    }
    return $req;
  }
  // フォロー・リムーブする。$uidはフォロー、リムーブしたいユーザーナンバー又はユーザー名。$flgは「true」ならフォロー、「false」ならリムーブ。
  // 返り値は「ok」、「already」、「error」の3種類。「ok」は正常にフォロー、リムーブが完了。「already」は既にそのユーザーをフォロー、リムーブしている。「error」はTwitter側の何かしらのエラー。
  function Follow($uid,$flg = true){
    $result = "ok";
    $key = ctype_digit($uid)?"user_id":"screen_name";
    $opt = array($key=>$uid);
    if($flg){
      $opt['follow'] = "true";
    }
    $req = $this->Request("friendships/".($flg?"create":"destroy").".json",$flg?"POST":"DELETE",$opt);
    if($req){
      if($req->Code != "200"){
        $result = "error";
      }
      $result = json_decode($req->Body);
      if($result->error){
        $result = "already";
      }
    } else {
      $result = "error";
    }
    return $result;
  }
  // ツイートをお気に入りに追加する。$sidはお気に入りに追加したいツイートのID。
  function Favorite($sid){
    $req = $this->Request("favorites/create.json","POST",array("id"=>$sid,"include_entities"=>"false"));
    if(!$req){
      die('Favorite(): $req is NULL');
    }
    if($req->Code != "200"){
      die("Error: ".$req->Code);
    }
  }
  // ツイートを消す。$sidは消したいツイートのID。自分のツイート以外はエラーになります。
  function Delete($sid){
    $req = $this->Request("statuses/destroy/".$sid.".json","POST");
    if($req){
      if($req->Code != "200"){
        die("Error: ".$req->Code);
      }
      $result = json_decode($req->Body);
      if($result->error){
        die($result->error);
      }
    } else {die('Delete(): $req is NULL');}
  }
  // ツイートをRTする。$sidはRTしたいツイートのID。
  function RT($sid){
    $req = $this->Request("statuses/retweet/".$sid.".json","POST");
    if($req){
      if($req->Code != "200"){
        die("Error: ".$req->Code);
      }
      $result = json_decode($req->Body);
      if($result->error){
        die($result->error);
      }
    } else {
      die('RT(): $req is NULL');
    }
  }
  // DMを送る。$uidはDMを送りたいユーザーナンバー又はユーザー名。$textは本文。
  function DM($uid,$text){
    $key = ctype_digit($uid)?"user_id":"screen_name";
    $req = $this->Request("direct_messages/new.json","POST",array($key=>$uid,"text"=>$text));
    if($req){
      if($req->Code != "200"){
        die("Error: ".$req->Code);
      }
      $result = json_decode($req->Body);
      if($result->error){
        die($result->error);
      }
    } else {
      die('DM(): $req is NULL');
    }
  }
  // 終わりの処理
  function End($sid){
    $this->Save_data("Since",$sid);
    echo "Normal termination: ".sprintf("%0.4f",array_sum(explode(" ",microtime())) - $this->times)." sec, ".date("H:i:s");
  }
}
// 配列$arrからランダムに一つ取り出す
function Rrt($arr){
  if(!is_array($arr)){
    return $arr;
  }
  $rand = array_rand($arr,1);
  return $arr[$rand];
}
?>
