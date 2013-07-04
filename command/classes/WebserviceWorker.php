<?php 
class WebserviceWorker {
	private $expects;
	private $mResult;
	private $lineCount;
	private $mId;
	
	public function __construct($url, $params, $expects, $result, $lineCount) {
		$this->expects = $expects;
		$this->mResult = $result;
		$this->lineCount = $lineCount;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, false);     // Don't return the header, just the html
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
		curl_setopt($ch, CURLOPT_TIMEOUT, 40);       // times out after 40s
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_CAINFO, "/etc/ssl/certs/mozilla.pem"); // http://davidwalsh.name/php-ssl-curl-error
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		$this->mId = $ch;
	}
	
	public function result() {
		return $this->mResult;
	}
	public function id() {
		return $this->mId;
	}

	public function processReturn($str, &$retValue) {
		if ($this->expects != IGNORE && $this->expects != $str) {
			$retValue = 'line ' . $this->lineCount . ': failed EXPECTS ' . $str;
			return false;
		}
		$retValue = $str;
		return true;
	}
	
	function __destruct() {
		curl_close($this->mId);
	}	
}
?>