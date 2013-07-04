<?php
require_once 'classes/WebserviceForeman.php';

if (!process($msg)) {
	// {status:<fileName>: line <CSV line number>: Expect_<name>, got <value>}
	echo '{"status":"' . $msg , '"}';
	return;
}
echo $msg;

function process(&$msg) {
	switch (count($_FILES)) {
	case 0:
		break;
	case 1:
		$file = $_FILES['file'];
		break;
	default:
		$msg = 'Only one .csv file is supported';
		return;
	}
	
	$filters = array(
	  "service"=>FILTER_SANITIZE_STRING,
	  "commands"=>FILTER_UNSAFE_RAW,
	);
	$row = filter_input_array(INPUT_POST, $filters);
	if (isset($row)) {
		foreach ($row as $key => $value) {
			if ($value == '') { unset($row[$key]); }
		}
	}
	
	switch (count($row)) {
	case 0:
		if (!isset($file)) {
			$msg = 'No parameters passed';
			return;
		}
		return processFile($file['name'], $file['tmp_name'], $msg);
	case 1:
		if (isset($file)) {
			$msg = 'Send a file or service | commands';
			return false;
		}
		if (isset($row['service'])) {
			$dir = '/var/www/' . $row['service'] . '/tests/';
			if (!file_exists($dir)) {
				$msg = 'Web service "' . $row['service'] . '" does not exist';
				return false;
			}
			return processDirectory($dir, $msg);
		} else if (isset($row['commands'])) {
			return processCommands($row['commands'], $msg);
		} else {
			$msg = 'Unrecognized parameter';
			return false;
		}
	default:
		$msg = 'Send one of service | commands';
		return false;
	}
}

function processDirectory($path, &$msg) {
	$dir = new DirectoryIterator($path);
	foreach ($dir as $fileinfo) {
		if ($fileinfo->isFile()) {
			$file = $fileinfo->getFilename();
			
			if (endsWith($file, '.csv')) {
				if (!processFile($file, $path . $file, $msg)) { return false; }
			}
		}
	}
	return true;
}

function processFile($name, $path, &$msg) {
	if (!filter_var($name, FILTER_SANITIZE_STRING)) {
		$msg = "Invalid file name";
		return false;
	}	
	if (!filter_var($path, FILTER_SANITIZE_STRING)) {
		$msg = "Invalid file path";
		return false;
	}	
	
	if (processCommands(file_get_contents($path), $msg)) { return true; }
	$msg = $name . ': ' . $msg;
	return false;
}

function processCommands($str, &$msg) {
	$str = str_replace("\r\n", "\n", $str);
	$lines = explode("\n", $str);

	define("START", 0);
	define("PARAMS", 1);
	define("EXPECTS", 2);
	define("RESULT", 3);
	$state = START;

	$lineCount = 0;
	$expectsLineCount = 0;
	$url = '';
	$params = array();

	define("IGNORE", "__IGNORE__");
	$expects = IGNORE;
	$result = IGNORE;
	$asciiTab = 9;
	$foreman = new WebserviceForeman();
	
	foreach ($lines as $line) {
		$lineCount++;
		removeAfter($line, '#');
		$line = trim($line);
		
		if ($line == '') { continue; }
		if ($state == START && !startsWithURL($line)) {
			$msg = "line " . $lineCount . ": must start with URL<tab>";
			return false;
		}
		if (startsWithURL($line)) {
			if ($state != START) { 
				$foreman->schedule($url, $params, $expects, $result, $expectsLineCount);
				if ($result != IGNORE && !$foreman->run(true, $msg)) { return false; }
			}

			$url = rtrim(substr($line, 4)); // 4 = length of URL\t
			removeAfter($url, $asciiTab);
			
			$params = array();
			$expects = IGNORE;
			$result = IGNORE;
			$state = PARAMS;
			continue;
		}

		$arr = explode(chr($asciiTab), $line);
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
		$params[$arr[0]] = $arr[1];
	}

	$foreman->schedule($url, $params, $expects, $result, $expectsLineCount);
	return $foreman->run(false, $msg);
}

function startsWithURL($str) {
	return preg_match("/^URL\t/", $str);
}
function removeAfter(&$str, $postFix) {
	$index = strpos($str, $postFix);
	if ($index === false) { return false; }
	if ($index == 0) {
		$str = '';
		return true;
	}
	$str = rtrim(substr($str, 0, $index - 1));
	return true;
}
function endsWith($haystack, $needle) {
    $length = strlen($needle);
    if ($length == 0) { return true; }
    return (substr($haystack, -$length) === $needle);
}
?>