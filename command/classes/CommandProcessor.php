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
		$src = isset($_POST['src']) ? trim($_POST['src']) : '';
		$simulate = isset($_POST['simulate']) ? trim($_POST['simulate']) == 1 : false;

		if (count($_FILES) > 0) {
			if (array_key_exists('src', $_FILES)) {
				if ($src != '') {
					$msg = 'cannot have more than one src of commands';
					return false;
				}
				$commandFile = $_FILES['src'];
				return CommandProcessor::processFile($commandFile['name'], $commandFile['tmp_name'], $simulate, $msg);
			}
		}

		if ($src == '') {
			$msg = 'src parameter must be set';
			return false;
		}

		if (preg_match('/\t/', $src)) {
			return CommandProcessor::processCommands($src, $simulate, $msg);
		}

		$dir = '/var/www/' . $src . '/tests/';
		if (!file_exists($dir)) {
			$msg = 'web service ' . $src . ' does not exist';
			return false;
		}
		return CommandProcessor::processService($dir, $simulate, $msg);
	}

	static private function processService($path, $simulate, &$msg) {
		$dir = new DirectoryIterator($path);
		foreach ($dir as $fileInfo) {
			if ($fileInfo->isFile()) {
				$file = $fileInfo->getFilename();
				
				if (preg_match('/^_file[1-4]_.+$/', $file)) {
					$newFile = substr($file, 7); // remove 7 char _file1_ prefix
					$newPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $newFile;
					copy($path . $file, $newPath);
					$_FILES[substr($file, 0, 6)] = array('name' => $newFile, 'tmp_name' => $newPath);
				}
			}
		}
		
		foreach ($dir as $fileInfo) {
			if ($fileInfo->isFile()) {
				$file = $fileInfo->getFilename();
				
				if (util::endsWith($file, '.csv') && !preg_match('/^_file[1-4]_.+$/', $file)) {
					if (!CommandProcessor::processFile($file, $path . $file, $simulate, $msg)) { return false; }
				}
			}
		}
	
		if ($simulate) { $msg = 'ok'; }
		return true;
	}

	static private function processFile($name, $path, $simulate, &$msg) {
		if (!filter_var($name, FILTER_SANITIZE_STRING, array('flags'=>FILTER_FLAG_NO_ENCODE_QUOTES))) {
			$msg = "Invalid file name";
			return false;
		}	
		if (!filter_var($path, FILTER_SANITIZE_STRING, array('flags'=>FILTER_FLAG_NO_ENCODE_QUOTES))) {
			$msg = "Invalid file path";
			return false;
		}	
		
		if (CommandProcessor::processCommands(file_get_contents($path), $simulate, $msg)) { return true; }
		$msg = $name . ': ' . $msg;
		return false;
	}

	static private function processCommands($str, $simulate, &$msg) {
		$fileNames = array('_file1','_file2','_file3','_file4');
		foreach ($_FILES as $key => $value) {
			if (in_array($key, $fileNames)) {
				util::renameTempFile($key);
			} else {
				unset($_FILES[$key]);
			}
		}

		$str = str_replace("\r\n", "\n", $str);
		$lines = CommandProcessor::parseCSV($str);
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
			util::removeAfter($line, '#');
			
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

	static private function startsWithURL($str) {
		return preg_match("/^URL\t/", $str);
	}

	static function parseCSV($data, $delimiter = '\t', $enclosure = '"', $newline = "\n"){
		$pos = $last_pos = -1;
		$end = strlen($data);
		$row = 0;
		$quote_open = false;
		$trim_quote = false;

		$replace_char = $delimiter == '\t' ? chr(ASCII_TAB) : $delimiter;
		$return = array();

		// Create a continuous loop
		for ($i = -1;; ++$i){
			++$pos;
			// Get the positions
			$comma_pos = strpos($data, $delimiter, $pos);
			$quote_pos = strpos($data, $enclosure, $pos);
			$newline_pos = strpos($data, $newline, $pos);

			// Which one comes first?
			$pos = min(($comma_pos === false) ? $end : $comma_pos, ($quote_pos === false) ? $end : $quote_pos, ($newline_pos === false) ? $end : $newline_pos);

			// Cache it
			$char = (isset($data[$pos])) ? $data[$pos] : null;
			$done = ($pos == $end);

			// It it a special character?
			if ($done || $char == $delimiter || $char == $newline){
				// Ignore it as we're still in a quote
				if ($quote_open && !$done){
					continue;
				}

				$length = $pos - ++$last_pos;

				// Get all the contents of this column
				if ($length > 0) {
					$return[$row] = substr($data, $last_pos, $length);
					$return[$row] = str_replace($enclosure . $enclosure, $enclosure, $return[$row]); // Remove double quotes
					$return[$row] = str_replace($replace_char . $enclosure, $replace_char, $return[$row]); // Remove starting quote

					if ($trim_quote) { // Remove trailing quote
						$return[$row] = substr(trim($return[$row]), 0, -1);
					}
				} else {
					$return[$row] = '';
				}
				
				// And we're done
				if ($done) {
					break;
				}

				// Save the last position
				$last_pos = $pos;

				// Next row?
				if ($char == $newline) {
					++$row;
				}

				$trim_quote = false;
			}
			// Our quote?
			else if ($char == $enclosure) {
				// Toggle it
				if ($quote_open == false){
					// It's an opening quote
					$quote_open = true;
					$trim_quote = false;

					// Trim this opening quote?
					if ($last_pos + 1 == $pos){
						++$last_pos;
					}
				}
				else {
					// It's a closing quote
					$quote_open = false;

					// Trim the last quote?
					$trim_quote = true;
				}
			}
		}
		return $return;
	}
}
?>