<?php 
require_once 'classes/WebserviceWorker.php';

class WebserviceForeman {
	private $workers;
	private $variables;
	private $mh;

	public function __construct() {
		$this->workers = array();
		$this->variables = array();
		$this->mh = curl_multi_init();
	}
    
	public function schedule($url, $params, $expects, $result, $lineCount) {
		foreach ($this->variables as $key => $value) {
			foreach ($params as &$paramValue) {
				$paramValue = str_replace($key, $value, $paramValue);
			}
			$expects = str_replace($key, $value, $expects);
		}

		$worker = new WebserviceWorker($url, $params, $expects, $result, $lineCount);
		$this->workers[] = $worker;
		curl_multi_add_handle($this->mh, $worker->id());
		
		$running = 0;
		curl_multi_exec($this->mh, $running);
	}

	// Can only be called once, then you have to construct a new WebserviceForeman
	public function run(&$msg) {
		$running = 0;
		do {
			$status = curl_multi_exec($this->mh, $running);
		} while ($status === CURLM_CALL_MULTI_PERFORM || $running);

		$ok = true;
		foreach ($this->workers as $worker) {
			$str = curl_multi_getcontent($worker->id());
			if ($ok) {
				if ($worker->result() != IGNORE) { $this->variables[$worker->result()] = $str; }
				
				$ok = $worker->processReturn($str, $temp);
				if (!$ok) { $msg = $temp; }
			}
			curl_multi_remove_handle($this->mh, $worker->id());
		}
		
		if ($ok) { $msg = $temp; }
        return $ok;
	}
	
	function __destruct() {
		curl_multi_close($this->mh);
	}	
}
?>