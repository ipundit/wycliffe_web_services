<?php
require_once 'util.php';

class WebserviceWorker {
	private $expects;
	private $mResult;
	private $lineCount;
	private $mId;
	
	public function __construct($url, $params, $expects, $result, $lineCount) {
		$this->expects = $expects;
		$this->mResult = $result;
		$this->lineCount = $lineCount;
		$this->mId = util::curl_init($url, $params);
	}
	
	public function result() {
		return $this->mResult;
	}
	public function id() {
		return $this->mId;
	}

	public function processReturn($str, &$retValue) {
		if ($this->expects != IGNORE && $this->expects != $str) {
			$retValue = 'line ' . $this->lineCount . ': failed EXPECTS<br />' . PHP_EOL . $this->expects . PHP_EOL . ' <br />GOT<br /> '. PHP_EOL . $str;
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