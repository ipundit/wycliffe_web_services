<?php 
require_once 'classes/WebserviceWorker.php';
define("IGNORE", "__IGNORE__");
define('MAX_NUM_WORKERS', 1000);

class WebserviceForeman {
	private $workers;
	private $variables;
	private $mh;
	private $lastId;
	private $simulate;
	
	public function __construct($simulate) {
		$this->workers = array();
		$this->variables = array();
		$this->simulate = $simulate;
		$this->mh = curl_multi_init();
	}
    
	public function run($url, $params, $expects, $result, $lineCount, $runAllProcesses, &$msg) {
		foreach ($this->variables as $key => $value) {
			foreach ($params as &$paramValue) {
				$paramValue = str_replace($key, $value, $paramValue);
			}
			$expects = str_replace($key, $value, $expects);
		}

		$worker = new WebserviceWorker($url, $params, $expects, $result, $lineCount);
		$this->lastId = $worker->id();
		$this->workers[$this->lastId] = $worker;
		curl_multi_add_handle($this->mh, $this->lastId);
		
		if (!$this->simulate) {
			$running = 0;
			if (count($this->workers) >= MAX_NUM_WORKERS) {
				if (!$this->runImpl(false, $msg)) { return false; }
			} else {
				curl_multi_exec($this->mh, $running);
			}
		}
		
		if ($runAllProcesses) {	return $this->runImpl(true, $msg); }
		return $this->isBlockingCall($result) ? $this->runImpl(false, $msg) : true;
	}

	private function isBlockingCall($result) {
		return $result != IGNORE;
	}
	
	private function runImpl($runAllProcesses, &$msg) {
		if ($this->simulate) { 
			$msg = 'regression tests passed';
			return true;
		}
		$ok = true;

		$this->full_curl_multi_exec($still_running); // start requests
		do { // "wait for completion"-loop
			curl_multi_select($this->mh); // non-busy wait for state change
			$this->full_curl_multi_exec($still_running); // get new state
			while ($info = curl_multi_info_read($this->mh)) {
				$id = $info['handle'];
				if ($ok) {
					$str = curl_multi_getcontent($id);
					$worker = $this->workers[$id];
					
					if ($worker->result() != IGNORE) {
						$this->variables[$worker->result()] = $str;
						if (!$runAllProcesses) { $still_running = false; }
					}

					$ok = $worker->processReturn($str, $temp);
					if (!$ok || $id == $this->lastId) { $msg = $temp; }
				}
				unset($this->workers[$id]);
				curl_multi_remove_handle($this->mh, $id);
			}
		} while ($still_running);
        return $ok;
	}
	
	function full_curl_multi_exec(&$still_running) {
		do {
			$rv = curl_multi_exec($this->mh, $still_running);
		} while ($rv == CURLM_CALL_MULTI_PERFORM);
		return $rv;
	} 
  
	function __destruct() {
		curl_multi_close($this->mh);
	}	
}
?>