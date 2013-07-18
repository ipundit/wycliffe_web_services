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
		$str = preg_replace_callback('/\/tmp\/(\w+)\//', function($match) { return '/tmp/random_string/'; }, $str); // to make email_processor.csv regression test pass
		if ($this->expects != IGNORE && $this->expects != $str) {
			$retValue = 'line ' . $this->lineCount . ': failed EXPECTS<br />' . PHP_EOL . $this->expects . PHP_EOL . ' <h3>GOT</h3> '. PHP_EOL . $str;
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