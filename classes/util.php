<?php
class util {
	static public function curl_init($url, $params) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, false);     // Don't return the header, just the html
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
		curl_setopt($ch, CURLOPT_TIMEOUT, 40);       // times out after 40s
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_CAINFO, "/etc/ssl/certs/mozilla.pem"); // http://davidwalsh.name/php-ssl-curl-error
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		return $ch;
	}
	
	static public function renameTempfile($paramName) {
		$oldPath = $_FILES[$paramName]['tmp_name'];

		$arr = explode(DIRECTORY_SEPARATOR, $oldPath);
		$arr[sizeof($arr) - 1] = $_FILES[$paramName]['name'];
		$newPath = implode(DIRECTORY_SEPARATOR, $arr);

		move_uploaded_file($oldPath, $newPath);
		$_FILES[$paramName]['tmp_name'] = $newPath;
	}
	
	static public function removeAfter(&$str, $postFix) {
		$index = strpos($str, $postFix);
		if ($index === false) { return false; }
		if ($index == 0) {
			$str = '';
			return true;
		}
		$str = rtrim(substr($str, 0, $index - 1));
		return true;
	}
	
	static public function removeBefore(&$str, $prefix) {
		$pos = strpos($str, $prefix);
		if ($pos === false) { return false; }
		
		$str = ltrim(substr($str, $pos + strlen($prefix)));
		return true;
	}

	static public function startsWith($haystack, $needle) {
		$length = strlen($needle);
		if ($length == 0) { return true; }
		return (substr($haystack, 0, $length) === $needle);
	}
	
	static public function endsWith($haystack, $needle) {
		$length = strlen($needle);
		if ($length == 0) { return true; }
		return (substr($haystack, -$length) === $needle);
	}
}
?>