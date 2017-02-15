<?php
if (is_uploaded_file($_FILES["upfile"]["tmp_name"])) {
  if (move_uploaded_file($_FILES["upfile"]["tmp_name"], "/Applications/MAMP/htdocs/p_free/data/" . $_FILES["upfile"]["name"])) {
    chmod("/Applications/MAMP/htdocs/p_free/data/" . $_FILES["upfile"]["name"], 0644);
	// APIキー
	$api_key = "api key";

	// 画像へのパス
	$image_path = "/Applications/MAMP/htdocs/p_free/data/" . $_FILES["upfile"]["name"] ;
	//$image_path = "/Applications/MAMP/htdocs/p_free/data/cat.jpg";

	// リクエスト用のJSONを作成
	$json = json_encode( array(
		"requests" => array(
			array(
				"image" => array(
					"content" => base64_encode( file_get_contents( $image_path ) ) ,
				) ,
				"features" => array(
						"type" => "LABEL_DETECTION" ,
						"maxResults" => 5 ,
				) ,
			) ,
		) ,
	) ) ;

	// リクエストを実行
	$curl = curl_init() ;
	curl_setopt( $curl, CURLOPT_URL, "https://vision.googleapis.com/v1/images:annotate?key=" . $api_key ) ;
	curl_setopt( $curl, CURLOPT_HEADER, true ) ;
	curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, "POST" ) ;
	curl_setopt( $curl, CURLOPT_HTTPHEADER, array( "Content-Type: application/json" ) ) ;
	curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false ) ;
	curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true ) ;
	if( isset($referer) && !empty($referer) ) curl_setopt( $curl, CURLOPT_REFERER, $referer ) ;
	curl_setopt( $curl, CURLOPT_TIMEOUT, 15 ) ;
	curl_setopt( $curl, CURLOPT_POSTFIELDS, $json ) ;
	$res1 = curl_exec( $curl ) ;
	$res2 = curl_getinfo( $curl ) ;
	curl_close( $curl ) ;

	// 取得したデータ
	$json = substr( $res1, $res2["header_size"] ) ;	　// 取得したJSON
	//print($json);
	$table = json_decode($json, true);

	$words = array();
	for($i=0; $i<5; $i++){
		array_push($words, $table['responses'][0]['labelAnnotations'][$i]['description']);
	}

	//youtube data api
	$baseurl = "https://www.googleapis.com/youtube/v3/search?part=snippet";

	$en_name = "- (";
	// 検索したいキーワード
	$en_name .= $words[0];
	for($i=1; $i<5; $i++){
		$en_name .= '|'. $words[$i];
	}
	$en_name .= ")";

	// 検索クエリ　テキストをエンコードする
	$prm_q = urlencode($en_name);

	// youtube data api API KEY
	$prm_key = "YouTube Data API KEY";

	$prm_max = 5;// 最大件数

	// 指定したタイプのJSONを取得
	$prm_type = "video";
	$url = "$baseurl&q=$prm_q&key=$prm_key&maxResults=$prm_max&type=$prm_type";
	// jsonを取得
	$json = file_get_contents($url);

	// PHPオブジェクトに変換
	$youtube = json_decode($json);

	// 5件目までのvideoID,タイトル,詳細,サムネイルを取得
	$video_id = array();
	$title = array();
	$description = array();
	$thumbnail = array();

	for($i=0; $i<$prm_max; $i++){
		array_push($video_id, $youtube->items[$i]->id->videoId);
		array_push($title, $youtube->items[$i]->snippet->title);
		array_push($description, $youtube->items[$i]->snippet->description);
		array_push($thumbnail, $youtube->items[$i]->snippet->thumbnails->medium->url);
	}

	$params = "?";

	for($i=0; $i<5; $i++){
		if($i == 0){
			$params .= "id".$i ."=". $video_id[$i];
		}else{
			$params .= "&" ."id". $i . "=". $video_id[$i];
		}
		$params .= "&" . "title". $i . "=". $title[$i];
		$params .= "&" . "description". $i . "=". $description[$i];
		$params .= "&" . "thumbnail". $i . "=". $thumbnail[$i];
	}

	$url = 'http://localhost/p_free2/index.html'.$params;
	header("Location: {$url}");
	exit;
	}
}

?>
