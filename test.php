<?php
// curlリソースの作成
$curl = curl_init();

// ユーザ名
//$username = "@chapi2082";
$username = "america";

// パスワード
$password = "Uxhztuio1049";

// 発言する文字列を設定する
// プログラムの文字コードがUTF-8の場合はこのまま
$status = "日本語のテスト";
// プログラムの文字コードがSJISの場合はUTF-8に変換
//$status = mb_convert_encoding($status, "UTF-8", "SJIS");

// 発言用のURLを設定(TwitterのAPIドキュメントを参照)
$url = "https://api.twitter.com/1/statuses/update.json";

// POSTフィールドを作成
$postData = array("status" => $status);

// 認証情報を設定
$authData = "$username:$password";

// curlに各種パラメータを設定す
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($curl, CURLOPT_USERPWD, $authData);
curl_setopt($curl, CURLOPT_POST, TRUE);
curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
curl_setopt($curl, CURLOPT_HEADER, FALSE);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($curl, CURLOPT_HTTPHEADER, array("Expect:"));

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

if($http_code == "200" && !empth($response)) {
  print "ok\n";
} else {
  print "ng\n";
  print curl_error($curl);
}

curl_close($curl);
?>
