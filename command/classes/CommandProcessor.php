<?php
require_once 'util.php';
require_once 'classes/WebserviceForeman.php';

define("START", 0);
define("PARAMS", 1);
define("EXPECTS", 2);
define("RESULT", 3);
define("IGNORE", "__IGNORE__");
define("ASCII_TAB", 9);

class CommandProcessor {
	static public function process(&$msg) {
		$baseDir = util::saveAllFiles();
		try {
			$retValue = CommandProcessor::processImpl($baseDir, $msg);
		} catch (Exception $ignore) {}
		util::delTree($baseDir);
		return $retValue;
	}
	
	static private function processImpl($baseDir, &$msg) {
		try {
			$src = isset($_POST['src']) ? trim($_POST['src']) : '';
			$simulate = isset($_POST['simulate']) ? trim($_POST['simulate']) == 1 : false;

			if (count($_FILES) > 0) {
				if (array_key_exists('src', $_FILES)) {
					if ($src != '') {
						$msg = 'cannot have more than one src of commands';
						return false;
					}
					$commandFile = $_FILES['src'];
					return CommandProcessor::processFile($commandFile['name'], $commandFile['tmp_name'], $baseDir, $simulate, $msg);
				}
			}

			if ($src == '') {
				$msg = 'src parameter must be set';
				return false;
			}

			if (preg_match('/\t/', $src)) {
				return CommandProcessor::processCommands($src, $baseDir, $simulate, $msg);
			}

			$dir = '../' . $src . '/tests/';
			if (!file_exists($dir)) {
				$msg = 'web service ' . $src . ' does not exist';
				return false;
			}
			return CommandProcessor::processService($dir, $baseDir, $simulate, $msg);
		} catch (Exception $e) {
			$retValue = false;
			$msg = "exception caught";
		}
	}

	static private function processService($path, $baseDir, $simulate, &$msg) {
		$retValue = true;
		
		$dir = new DirectoryIterator($path);
		foreach ($dir as $fileInfo) {
			if ($fileInfo->isFile()) {
				$file = $fileInfo->getFilename();
				
				if (preg_match('/^_file[1-4]_.+$/', $file)) {
					$newFile = substr($file, 7); // remove 7 char _file1_ prefix
					$newPath = $baseDir . $newFile;
					copy($path . $file, $newPath);
					$_FILES[substr($file, 0, 6)] = array('name' => $newFile, 'tmp_name' => $newPath);
				}
			}
		}
		
		foreach ($dir as $fileInfo) {
			if ($fileInfo->isFile()) {
				$file = $fileInfo->getFilename();
				
				if (util::endsWith($file, '.csv') && !preg_match('/^_file[1-4]_.+$/', $file)) {
					$retValue = CommandProcessor::processFile($file, $path . $file, $baseDir, $simulate, $msg);
					if (!$retValue) { break; }
				}
			}
		}
		if ($retValue) { $msg = 'regression tests passed'; }
	
		return $retValue;
	}

	static private function processFile($name, $path, $baseDir, $simulate, &$msg) {
		if (!filter_var($name, FILTER_SANITIZE_STRING, array('flags'=>FILTER_FLAG_NO_ENCODE_QUOTES))) {
			$msg = "Invalid file name";
			return false;
		}	
		if (!filter_var($path, FILTER_SANITIZE_STRING, array('flags'=>FILTER_FLAG_NO_ENCODE_QUOTES))) {
			$msg = "Invalid file path";
			return false;
		}	
		
		if (CommandProcessor::processCommands(file_get_contents($path), $baseDir, $simulate, $msg)) { return true; }
		$msg = $name . ': ' . $msg;
		return false;
	}

	static private function processCommands($str, $baseDir, $simulate, &$msg) {
		$fileNames = array('_file1','_file2','_file3','_file4');
		foreach ($_FILES as $key => $value) {
			if (in_array($key, $fileNames)) {
				util::renameTempFile($key, $baseDir);
			} else {
				unset($_FILES[$key]);
			}
		}

		$str = CommandProcessor::escapeDoubleQuotes($str);
		$lines = util::parseCSV($str);
		$state = START;

		$lineCount = 0;
		$expectsLineCount = 0;
		$url = '';
		$params = array();

		$expects = IGNORE;
		$result = IGNORE;
		$foreman = new WebserviceForeman($simulate);

		foreach ($lines as $line) {
			$lineCount++;
			util::removeAfter($line, '# ');
			
			$line = trim($line);
			
			if ($line == '') { continue; }
			if ($state == START && !CommandProcessor::startsWithURL($line)) {
				$msg = "line " . $lineCount . ": must start with URL";
				return false;
			}
			if (CommandProcessor::startsWithURL($line)) {
				if ($state != START) { 
					$foreman->schedule($url, $params, $expects, $result, $expectsLineCount);
					if ($result != IGNORE && !$foreman->run(true, $msg)) { return false; }
				}

				$url = rtrim(substr($line, 4)); // 4 = length of URL\t
				util::removeAfter($url, ASCII_TAB);
				
				$params = array();
				$expects = IGNORE;
				$result = IGNORE;
				$state = PARAMS;
				continue;
			}

			$arr = explode(chr(ASCII_TAB), $line, 2);
			if (!preg_match('/^[\w|\d]+$/', $arr[0])) {
				$msg = "line " . $lineCount . ': ' . $arr[0] . ' is an invalid parameter';
				return false;
			}
			if (count($arr) == 1) { $arr[1] = ''; }
			
			if ($arr[0] == "EXPECTS") {
				if ($state == RESULT) {
					$msg = "line " . $lineCount . ': Cannot have EXPECTS after RESULT';
					return false;
				}
				$state = EXPECTS;
				$expects = $arr[1];
				
				$expectsLineCount = $lineCount;
				continue;
			}
			if ($arr[0] == "RESULT") {
				if (substr($arr[1], 0, 1) != '$') {
					$msg = "line " . $lineCount . ': RESULT must start with a $';
					return false;
				}
				$state = RESULT;
				$result = $arr[1];
				continue;
			}
			
			// Supposed to be in PARAMS state
			if ($state == EXPECTS) {
				$msg = "line " . $lineCount . ': Cannot have parameters after EXPECTS';
				return false;
			}
			if ($state == RESULT) {
				$msg = "line " . $lineCount . ': Cannot have parameters after RESULT';
				return false;
			}

			if (array_key_exists($arr[0], $params)) {
				$msg = "line " . $lineCount . ': ' . $arr[0] . ' already exists';
				return false;
			}
			
			if (in_array($arr[1], $fileNames)) {
				if (array_key_exists($arr[1], $_FILES)) {
					$arr[1] = '@' . $_FILES[$arr[1]]['tmp_name'];
				} else {
					$msg = "line " . $lineCount . ': ' . $arr[1] . ' was not uploaded';
					return false;
				}
			}
			$params[$arr[0]] = $arr[1];
		}
		
		$foreman->schedule($url, $params, $expects, $result, $expectsLineCount);
		return $foreman->run(false, $msg);
	}

	static private function escapeDoubleQuotes($str) {
		$end = strlen($str);
		$pos = -1;
		$inMultiLine = false;
		do {
			$pos = strpos($str, '"', $pos + 1);
			if ($pos === false) { break; }
			if ($pos > 0 && $str[$pos - 1] == chr(ASCII_TAB)) {
				if ($inMultiLine) {	return "invalid str: inMultiLine already"; }
				$inMultiLine = true;
				continue;
			}
			if ($inMultiLine) {
				if ($str[$pos + 1] == '"') {
					$pos++;
				} else {
					$inMultiLine = false;
				}
				continue;
			}
			$str = substr($str, 0, $pos) . '"' . substr($str, $pos);
			$pos++;
			$end++;
		} while ($pos < $end);
		return $str;
	}
	
	static private function startsWithURL($str) {
		return preg_match("/^URL\t/", $str);
	}
}
?>