<?php

$data = parseData($data, $game, $language);
$data = <<<HTML
<table class='result-table'>
	<thead>
		<tr>
			<th>Unique Key</th>
			<th>Translation</th>
		</tr>
	</thead>
	<tbody>
		$data
	</tbody>
</table>
HTML;

function parseData($data, $game, $language) {
	// let tLines = pData;
	$data = preg_replace('/¤$/', '', $data); // Replace extra one at end
	$lines = explode("¤", $data);
	// let tLines = pData.split("&#164;");
	
	$html = "";
	foreach($lines as $line) {
		list($key, $message) = splitOnce($line, "=");
		$message = highlightSyntaxAll($message);
		$message = "<pre>$message</pre>";
		$hashTag = $key;
		$href = getShareUrl($game, $language, $key);
		// Extra row before it is needed for some CSS styling
		$html .= "<tr id='$hashTag' class='permalink-target'></tr>"
			."<tr><th><a class='permalink' href='$href' onclick='onPermaClick(\'$key\'); return false;'>#</a><div class='overflow'>$key</div></th><td>$message</td></tr>";
	}
	return $html;
}

function getShareUrl($game, $lang, $id) {
	$params = [];
	if($id) { $params[] = "id=$id"; }
	if($game) { $params[] = "g=$game"; }
	if($lang) { $params[] = "l=$lang"; }
	$link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	return $link . (count($params) > 0 ? "?"+implode("&", $params) : "");
}
	
function highlightSyntaxAll($str) {
	$str = highlightHTML($str);
	$str = highlightStringSubstitution($str);
	$str = highlightSex($str);
	$str = highlightSpeaker($str);
	$str = highlightDialogBreak($str);
	return $str;
}

function highlightHTML($str) {
	// let tTitle = htmlEscape(pString);
	// return `<span title='${tTitle}'>${highlightHTMLRecurrsive(pString)}</span>`;
	return "<!-- $str -->".highlightHTMLRecurrsive($str);
	// return pString.replace(/</g, "&lt;").replace(/>/g, "&gt;");
}
function highlightHTMLRecurrsive($str) {
	$regex = '/<(\w+)(?:\s*(.*?))>((?:.|\n)*?)((?:<\/\1>|$))/';
	preg_match_all($regex, $str, $matches);
	if($matches) {
		list($allMatched, $tag, $attribs, $content, $end) = $matches;
		// console.log($matches);
		$content = highlightHTMLRecurrsive($content);
		$formattedCode = null;
		switch($tag) {
			case "img":
				$attribs = str_replace("http:", "https:", $attribs);
				$formattedCode = "<img $attribs title='".htmlEscape($attribs)."' />$content";
				break;
			case "font":
				if(strpos($attribs, "color=") !== false) {
					$content = `<font $attribs>$content</font>`;
				}
				// Fall through
			default:
				$attribs = $attribs ? " <span class='attr'>$attribs</span>" : "";
				$end = $end ? "<span class='tag'>&lt;/$tag&gt</span>" : "";
				$formattedCode = "<span class='tag'>&lt;$tag$attribs&gt;</span>$content$end";
				break;
		}
		$str = str_replace($allMatched, $formattedCode, $str);
		// $str = pString.replace($allMatched, $formattedCode);
	}
	return $str;
}

function highlightStringSubstitution($str) {
	return preg_replace('/(\%\d)/', "<span class='subst'>$1</span>", $str);
}

function highlightSex($str) {
	return preg_replace('/\((.*?)\|(.*?)\)/', "<span class='sex'>(<span class='m'>$1</span>|<span class='f'>$2</span>)</span>", $str);
}

function highlightSpeaker($str) {
	return preg_replace('/(^#[A-Za-z_]*:)/', "<span class='speaker'>$1</span>", $str);
}

function highlightDialogBreak($str) {
	return preg_replace('/(_P_)/', "<span class='d-break'>$1</span>", $str);
}

function htmlEscape($str) {
	$str = preg_replace('/</', "&lt;", $str);
	$str = preg_replace('/>/', "&gt;", $str);
	$str = preg_replace('/"/', "&#34;", $str);
	$str = preg_replace('/\'/', "&#39;", $str);
	return $str;
}

// Only split first instance of something in a string. ex: "a-b-c-d" split on "-" would return "a" and "b-c-d"
function splitOnce($str, $separator) {
	return explode($separator, $str, 2);
	// $splitIndex = strpos($str, $separator);
	// return [ substr($str, 0, $splitIndex), substr($str, $splitIndex+count($separator)) ];
}