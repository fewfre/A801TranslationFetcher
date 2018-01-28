<?php
# Return format
$format = "html";
if(isset($_GET["format"])) {
	switch($_GET["format"]) {
		case "json":
		case "text":
			$format = $_GET["format"];
			break;
	}
}

# Select the game
$game = "transformice";
if(isset($_GET["game"])) {
	switch($_GET["game"]) {
		case "transformice":
		case "tfm":
			$game = "transformice";
			break;
		case "deadmaze":
		case "dm":
			$game = "deadmaze";
			break;
		default:
			sendError(400, "No recognized game '{$_GET["game"]}'", $format);
			break;
	}
}

# Select the language
$language = isset($_GET["lang"]) ? clean($_GET["lang"]) : "en";

/********
* Build url
*********/
switch($game) {
	case "transformice":
		$filename = "tfz_$language";
		$url = "http://transformice.com/langues/$filename";
		break;
	case "deadmaze":
		$filename = "deadmeat_$language";
		$url = "http://transformice.com/langues/$filename";
		break;
}

/********
* Check if cache data exists and is up-to-date
*********/
$local_file = "i18n/$filename.$format";
if(file_exists($local_file) && ($localDate = filemtime($local_file)) >= externalLastModified($url)) {
	// If cached file is good
	header('Last-Fetched-From-Game: '.gmdate('D, d M Y H:i:s ', $localDate) . 'GMT');
	$data = file_get_contents($local_file);
} else {
	// If file needs to be fetched
	/********
	* Get Data
	*********/
	try {
		$i = 3;
		do {
			if($i < 3) usleep(500000);
			$i--;
			$data = externalFetch($url);
			$data = gzuncompress($data);
		} while (!$data && $i > 0);
		if(!$data) {
			sendError(400, "No lang file found for '$game': $language", $format);
		}
	}
	catch (Exception $e) {
		sendError(400, "No lang file found for '$game': $language", $format);
	}
	// $data = utf8_decode($data);
	// iconv("UTF-8", "CP1252", $data);
	// echo mb_detect_encoding($str, "auto")." --- ";
	// $data = htmlspecialchars($data);
	
	/********
	* Return Data
	*********/
	switch($format) {
		case "json":
			$data = charset_decode_utf_8($data);
			$split_char = "&#164;";
			// $split_char = "\xa4";
			$data = rtrim($data, $split_char);
			$data = explode($split_char, $data);
			// json_encode can't handle UTF-8 characters https://stackoverflow.com/q/6771938/1411473
			// mb_internal_encoding('UTF-8');
			// $data=array_map('utf8_encode',$data);
			
			// Convert array to json
			$data = json_encode($data);
			break;
		case "text":
			// Just use the default data
			// $data = charset_decode_utf_8($data);
			break;
		case "html":
		default:
			$data = charset_decode_utf_8($data);
			$data = "<html><body>$data</body></html>";
			break;
	}
	if(!is_dir("i18n")) { mkdir("i18n"); }
	file_put_contents($local_file, $data);
}

# Set appropriate headers
switch($format) {
	case "json":
		header('Content-type: application/json charset=utf-8');
		break;
	case "text":
		header('Content-Type: text/plain charset=utf-8');
		break;
	case "html":
		header('Content-Type: text/html charset=utf-8');
		break;
}
echo $data;

function externalFetch($url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSLVERSION,3);
	$data = curl_exec ($ch);
	$error = curl_error($ch);
	curl_close ($ch);
	
	return $data;
}

function externalLastModified($url) {
	$h = get_headers($url, 1);
	if (!($h || strstr($h[0], '200') === false)) {
		return new \DateTime($h['Last-Modified']);//php 5.3
	}
	return null;
}

// http://php.net/manual/en/function.utf8-decode.php#116671
function charset_decode_utf_8 ($string) {
    /* Only do the slow convert if there are 8-bit characters */
    /* avoid using 0xA0 (\240) in ereg ranges. RH73 does not like that */
    if (!preg_match("/[\200-\237]/", $string)
     && !preg_match("/[\241-\377]/", $string)
    ) {
        return $string;
    }

    // decode three byte unicode characters
    $string = preg_replace("/([\340-\357])([\200-\277])([\200-\277])/e",
        "'&#'.((ord('\\1')-224)*4096 + (ord('\\2')-128)*64 + (ord('\\3')-128)).';'",
        $string
    );

    // decode two byte unicode characters
    $string = preg_replace("/([\300-\337])([\200-\277])/e",
        "'&#'.((ord('\\1')-192)*64+(ord('\\2')-128)).';'",
        $string
    );

    return $string;
}

function clean($str){
	$re = "/([0-9a-zA-Z])/u";
	preg_match_all($re, $str, $matches);
	return isset($matches[0]) ? implode($matches[0]) : '';
}

function sendError($code, $msg, $format) {
	header("HTTP/1.0 $code $msg");
	// switch($format) {
	// 	case "json":
	// 		header('Content-type: application/json');
	// 		echo json_encode(array( "error" => true, "error_msg" => $msg ));
	// 		break;
	// 	case "text":
	// 	default:
			echo $msg;
	// 		break;
	// }
	http_response_code($code);
	exit;
}
