<?php
function savejson($names){
	$json = json_encode(array_values($names));
	$fp = fopen("names.json", "w");
	fwrite($fp, $json);
	fclose($fp);
}

function savejsonexceptions($names){
	$json = json_encode($names);
	$fp = fopen("exceptions.json", "w");
	fwrite($fp, $json);
	fclose($fp);
}

function uploadimage($file){
	$client_id = IMGUR_CLIENT_ID;
	$image = implode('', file($file));
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://api.imgur.com/3/image.json');
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Client-ID ' . $client_id));
	curl_setopt($ch, CURLOPT_POSTFIELDS, array('image' => base64_encode($image)));
	$reply = curl_exec($ch);
	curl_close($ch);
	$reply = json_decode($reply);
	if ($reply->data->link != ""){
		if (REMOVE_IMAGE == "yes"){
			unlink($file);
		}
		return $reply->data->link;
	}else{
		return false;
	}
}

function getimage(){
	$url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']); //Script URL
	$data = json_decode(send("http://api.rest7.com/v1/html_to_image.php?url=".$url."/table.php&format=png"));
	if (@$data->success !== 1){
		return false;
	}else{
		$image = send($data->file);
		if (!is_dir('images')) {
			mkdir('images', 0775, true);
		}
		$name = "images/".date('YmdHis').'.png';
		file_put_contents($name, $image);
		return $name;
	}
}

function sendTwitter($string,$image){
	include("codebird.php");
	\Codebird\Codebird::setConsumerKey(API_KEY, API_SECRET_KEY);
	$cb = \Codebird\Codebird::getInstance();
	$cb->setToken(ACCESS_TOKEN, ACCESS_TOKEN_SECRET);
	if ($image != ""){
		$reply = $cb->media_upload([
			'media' => $image
		]);
		$media_id = $reply->media_id_string;
		$params = [
			'media_ids' => $media_id,
			'status' => $string
		];
	}
	$reply = $cb->statuses_update($params);
	if ($reply->created_at != ""){
		return true;
	}else{
		return false;
	}
}

function send($url){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
	$output = curl_exec($ch);
	curl_close($ch);
	return $output;
}

function sendTelegram($string,$image,$options=array("")){
	$url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
	if (IMGUR_CLIENT_ID != ""){
		$image_url = uploadimage($image);
	}else{
		$image_url = $url.'/'.$image;
	}
	if ($image_url != ""){
		$string = '<a href="'.$image_url.'">✨</a> '.$string;
		$defaults = ['parse_mode' => 'html'];
		$options = ['disable_notification' => true];
		$params = array_merge($defaults, $options);
		$url = 'https://api.telegram.org/bot' . TELEGRAM_TOKEN. '/sendMessage?text='.urlencode($string).'&chat_id='.CHANNEL_ID.'&'.http_build_query($params);
		return send($url);
	}else{
		return false;
	}
}
?>