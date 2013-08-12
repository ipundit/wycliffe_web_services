<?php 
require_once 'util.php';
require_once 'classes/akismet.class.php';
require_once 'classes/EmailProcessor.php';
define("_EMAIL_ASCII_TAB_", 9);
define('_EMAIL_TIMEOUT_', 300);

class Email
{
	private static $currentLineNumber = 0;
	private static $currentLine = array();
	private static $baseDir;
	private static $lastBody = '';
	
	public static function sendFromPost(&$msg) {
		set_time_limit(_EMAIL_TIMEOUT_);
		$baseDir = util::saveAllFiles();
		Email::$baseDir = $baseDir;
		
		try {
			$retValue = Email::sendFromPostImpl($baseDir, $msg);
			EmailProcessor::notifyEmailProcessingComplete($msg);
		} catch (Exception $ignore) {}
		util::delTree($baseDir);
		return $retValue;
	}
	
	private static function sendFromPostImpl($baseDir, &$msg) {
		ini_set('display_errors', '0'); 
		register_shutdown_function('Email::shutdown'); 

		$dryRun = '';
		$msg = '';
		
		$row = Email::validateInput($_POST, $msg);
		if ($msg != '') { return false;	}

		$files = Email::getPathToAttachments($baseDir);
		$numSent = 1;
		if ($row["to"] == '') {
			$lines = Email::fillTemplateFromCSV($row, $msg);
			if ($msg != '') { return false; }

			if ($row['simulate'] == 1) {
				$msg = trim(preg_replace('/\s+/', ' ', print_r($lines, true)));
//$msg = '<pre>' . print_r($lines, true) . '</pre>';
				return false;
			}

			foreach ($lines as $lineNumber => &$line) {
				self::$currentLineNumber = $lineNumber;
				self::$currentLine = $line;
			
				if (!util::sendEmail($msg, $line['fromName'], $line['from'], $line['to'], $line['subject'], 
									 $line['body'], $line['cc'], $line['bcc'], $line['replyTo'], $files,
									 $line['simulate'])) {
					if ($line['simulate'] == 1) { return false;	}
					
					Email::sendErrorMessage($msg);
					return false;
				}
				if ($line['simulate'] == 2) {
					$line = $msg;
					$msg = '';
					continue;
				}
			}
			if ($row['simulate'] == 2) {
				$dryRun = implode('<hr />', $lines);
			}
			$numSent = count($lines);
		} else {
			if (!util::sendEmail($msg, $row["fromName"], $row["from"], $row["to"], $row['subject'], 
										$row['body'], $row['cc'], $row['bcc'], $row['replyTo'], $files, $row['simulate'])) {
				return false;
			}
			if ($row['simulate'] == 2) {
				$dryRun = $msg;
				$msg = '';
			}
		}

		if ($row['simulate'] == 2) {
			$dryRun = Email::dryRunHTML($dryRun);
			if (FALSE  === file_put_contents('./dryRun.html', implode(PHP_EOL, $dryRun))) { 
				$msg = 'could not write to dryRun file';
				return false;
			}
			$msg = $numSent . ' emails simulated; see <a href="' . util::absURL('dryRun.html') . '">dry run page</a>';
		} else {
			$msg = $numSent;
		}
		return true;
	}

	private static function dryRunHTML($body) {
		return array(
		'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
		'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">',
		'<head>',
		'<title>Dry run output</title>',
		'<style type="text/css">div { display: inline-block; } .col1 { width: 100px; } .col2 { display:inline; } </style>',
		'</head>',
		'<body>',
		$body,
		'<form action="clearDryRunPage.php" method="post">',
		'<p><button type="submit">Clear this page so no one else can see your dry run results</button></p>',
		'</form>',
		'</body>',
		'</html>',
		);
	}
	
	public static function shutdown() { // has to be public for register_shutdown_function to work
		$err = error_get_last();
		util::delTree(Email::$baseDir);

		if ($err == null) { return; }
		if ($err['message'] == "Non-static method PEAR::isError() should not be called statically") { return; } // work-around known PEAR bug

		if (connection_aborted()) {
			Email::sendErrorMessage('you pressed the stop button');
			return;
		}

		if ($err['message'] == 'Maximum execution time of ' . _EMAIL_TIMEOUT_ . ' seconds exceeded') {
			$msg = 'timed out; you are only allowed to use ' . _EMAIL_TIMEOUT_ . ' seconds of server time per call';
			Email::sendErrorMessage($msg);
			echo $msg;
			return;
		}
		echo '<b>Fatal error:</b> ' . $err['message'] . ' in <b>' . $err['file'] . '</b> on line <b>' . $err['line'] .'</b>';
	}

	private static function sendErrorMessage($msg) {
		$ignore = '';

		if (self::$currentLine['to'] == 'spam') {
			$body = "Timed out on checking if email is spam on <b>line " . self::$currentLineNumber . "</b> of the mailing list file. You are only allowed to use " . _EMAIL_TIMEOUT_ . " seconds of server time. Contact developer_support@wycliffe-services.net to fix this problem, with the number of emails you are trying to send.";
			$subject = "Checking for spam timed out";
		} else {
			$body = 'Sending email failed on <b>line ' . self::$currentLineNumber . 
					'</b> of the mailing list file with message: <b>' . $msg . '</b>. Restart sending emails from line ' . 
					self::$currentLineNumber . ' onwards.';
			$subject = 'Email to ' . self::$currentLine['to'] . ' failed with subject: ' .	self::$currentLine['subject'];
		}
		
		util::sendEmail($ignore, 'Wycliffe Web Services mailier', 'no-reply@wycliffe-services.net', 
						self::$currentLine['from'], $subject, $body);
	}
	
	private static function fillTemplateFromCSV($row, &$msg) {
		unset($row['to']);
		$csvLines = util::parseCSV(file_get_contents($_FILES["to"]['tmp_name']));
		unset($_FILES["to"]);
		
		$variablesLookup = array();
		if (!Email::parseLines($csvLines, $row['startRow'], $row['maxRows'], $variablesLookup, $msg)) { return false; }

		$filters = Email::getTags($row['tags']);
		foreach ($row as $key => &$value) {
			$value = array('hasVariable'=>strpos($value, '$') !== false, 'template'=>$value);
		}

		$lines = array();
		foreach ($variablesLookup as $lineCount => $variableLookup) {
			$email = $variableLookup['$email'];
			if ($email == '') { continue; }

			if (!Email::filteredByTags($filters, Email::getTags($variableLookup['$tags']))) { continue; }
			
			$lines[$lineCount]['to'] = $email;
			foreach ($row as $key => &$value) {
				$lines[$lineCount][$key] = $value['template'];
			}

			foreach ($row as $key => &$value) {
				if ($value['hasVariable']) {
					foreach ($variableLookup as $variableKey => $variableValue) {
						$lines[$lineCount][$key] = str_replace($variableKey, $variableValue, $lines[$lineCount][$key]);
					}
				}
			}

			self::$currentLineNumber = $lineCount;
			$lines[$lineCount] = Email::validateInput($lines[$lineCount], $msg);
			if ($msg != '') { 
				$msg = 'mailing list line ' . $lineCount . ': ' . $msg;
				return false;
			}
		}
		return $lines;
	}
	
	private static function filteredByTags($filters, $tags) {
		if (empty($filters)) { return true; }
		if (empty($tags)) { return false; }

		foreach ($filters as $filter) {
			foreach ($tags as $tag) {
				if ($filter == $tag) { return true; }
			}
		}
		return false;
	}
	
	private static function getTags($tags) {	
		$tags = explode(',', $tags);
		foreach($tags as $key => $value) {
			$tags[$key] = trim($tags[$key]);
			if ($tags[$key] == '') { unset($tags[$key]); }
		}
		return $tags;
	}
	
	private static function parseLines($lines, $startRow, $maxRows, &$variablesLookup, &$msg) {
		$isFirstLine = true;
		$keys = '';
		$i = 1;

		foreach ($lines as $line) {
			util::removeAfter($line, '# ');
			
			$columns = explode(chr(_EMAIL_ASCII_TAB_), $line);
			if ($isFirstLine) {
				if (!Email::getKeys($columns, $keys, $msg)) { return false; }
				$isFirstLine = false;
				$i++;
				continue;
			}

			if ($line == '' || $i < $startRow) { $i++; continue; }
			if ($maxRows != 0 && $startRow + $maxRows <= $i) { break; }
			$variablesLookup[$i] = array('$email' => '');
			
			$j = 0;
			foreach ($columns as $column) {
				if (util::startsWith($keys[$j], '$')) {
					$variablesLookup[$i][$keys[$j++]] = $column;
				}
			}
			for (; $j < count($keys); $j++) {
				if (util::startsWith($keys[$j], '$')) {
					$variablesLookup[$i][$keys[$j]] = '';
				}
			}
			$i++;
		}
		return true;
	}

	private static function getKeys($columns, &$keys, &$msg) {
		$isOk = false;
		foreach ($columns as $column) {
			if ($column == '$email') {
				$isOk = true;
				break;
			}
		}
		if (!$isOk) {
			$msg = 'first line of to file must have $email column';
			return false;
		}
		$keys = $columns;
		return true;
	}
	
	private static function getPathToAttachments($baseDir) {
		$retValue = array();
		
		foreach ($_FILES as $key => $value) {
			if (preg_match('/^attach[1-9]$/', $key)) {
				util::renameTempfile($key, $baseDir);
				$retValue[$key] = $_FILES[$key]['tmp_name'];
			}
		}
		return $retValue;
	}
	
	private static function validateInput($arr, &$msg) {
		if (!isset($arr['startRow'])) { $arr['startRow'] = 1; }
		if (!isset($arr['maxRows'])) { $arr['maxRows'] = 0; }
		if (!isset($arr['simulate'])) { $arr['simulate'] = 0; }

		$filters = array(
		  "to"=>FILTER_UNSAFE_RAW,
		  "from"=>FILTER_UNSAFE_RAW,
		  "fromName"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "replyTo"=>FILTER_SANITIZE_EMAIL,
		  "subject"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "cc"=>FILTER_UNSAFE_RAW,
		  "bcc"=>FILTER_UNSAFE_RAW,
		  "tags"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "startRow"=>array('filter'=>FILTER_VALIDATE_INT, 'options'=>array("min_range"=>1)),
		  "maxRows"=>array('filter'=>FILTER_VALIDATE_INT, 'options'=>array("min_range"=>0)),
		  "simulate"=>array('filter'=>FILTER_VALIDATE_INT, 'options'=>array("min_range"=>0, "max_range"=>2)),
		  "body"=>FILTER_UNSAFE_RAW,
		);

		$row = filter_var_array($arr, $filters);
		foreach ($row as &$value) {
			$value = trim($value);
		}
		
		if ($row["to"] == '') {
			if (!isset($_FILES["to"])) {
				$msg = "to parameter must be set";
				return false;
			}
			if ($row['startRow'] == '') {
				$msg = 'startRow must be an integer greater than or equal to 1';
				return false;
			}
			if ($row['maxRows'] == '') {
				$msg = 'maxRows must be an integer greater than or equal to 0';
				return false;
			}
		} else {
			if (isset($_FILES["to"])) {
				$msg = "cannot have more than one source of to";
				return false;
			}
			if (!Email::validateEmailList($row["to"])) {
				$msg = "invalid to";
				return false;
			}
			
			unset($row['tags']);
			unset($row['startRow']);
			unset($row['maxRows']);
		}
		if ($row["from"] == '') {
			$row["from"] = "no-reply@wycliffe-services.net";
		} else if (!in_array($row['from'], Email::wycliffeServicesEmails()) && !util::isJaarsEmail($row['from'])) {
			if (!isset($_FILES["to"]) || strpos($row['from'], '$') === false) {
				$msg = "invalid from email";
				return false;
			}
		}

		if (!Email::validateEmailList($row["cc"])) {
			$msg = "invalid cc";
			return false;
		}

		if (!Email::validateEmailList($row["bcc"])) {
			$msg = "invalid bcc";
			return false;
		}
		
		if (!Email::validateEmailList($row["replyTo"])) {
			$msg = "invalid replyTo";
			return false;
		}
		
		if (Email::isInjectionAttack($row["subject"])) {
			$msg = "invalid subject";
			return false;
		}

		if ($row['simulate'] == '') {
			$msg = 'simulate must be 0 or 1 or 2';
			return false;
		}
		
		if (Email::isSpam($row['fromName'], $row['from'], $row['body'], $row['simulate'] == 1)) {
			$msg = "spam detected";
			return false;
		}

		$footer = 'Sent via <a href="http://www.wycliffe-services.net">Wycliffe Web Services</a>. Forward this email to spam@wycliffe-services.net if you think you received this email in error.</span>';
		if (!util::endsWith($row['body'], $footer)) {
			$row['body'] .= '<br><br><span style="color:#BBBBBB">--<br>' . $footer;
		}
		return $row;
	}
	
	public static function isSpam($fromName, $fromEmail, $body, $simulate = false, $userAgent = 'Wycliffe Web Services/1.0 | email/1.0',
			$referrer = 'http://wycliffe-services.net/email/webservice.php') {
		if ($simulate) { return $fromName == 'Free Viagra'; } // Just put in one case for failure
		
		self::$currentLine = array(
			'from' => $fromEmail,
			'to' => 'spam',
			'subject' => 'spam',
		);

		$spamChecker = new Akismet('http://wycliffe-services.net/email/webservice.php', '9b41b2cabb36', 
			array(
				'author'     => $fromName,
				'email'      => $fromEmail,
				'website'    => 'http://wycliffe-services.net/',
				'body'       => $body,
				'user_agent' => $userAgent,
				'referrer'   => $referrer,
			));
		if ($spamChecker->errorsExist()) { return false; } // silently skip spam checking then
		if (Email::similarToLastBody($body)) { return false; } // optimization: if body hasn't really changed between emails - assume this one is not a spam email either
		
		return $spamChecker->isSpam();
	}
	
	private static function similarToLastBody($body) {
		$retValue = false;
		if (Email::$lastBody != '') {
			$percent = 0;
			similar_text($body, Email::$lastBody, $percent);
			$retValue = $percent >= .95;
			$retValue = true;
		}
		Email::$lastBody = $body;
		return $retValue;
	}
	
	public static function wycliffeServicesEmails() {
		$retValue = array('no-reply@wycliffe-services.net', 'help@wycliffe-services.net');
		$dir = new DirectoryIterator('../');
		foreach ($dir as $fileinfo) {
			if ($fileinfo->isDir() && !$fileinfo->isDot()) {
				$subdir = $fileinfo->getFilename();
				if (file_exists('../' . $subdir . '/email_template.html')) { array_push($retValue, $subdir . '@wycliffe-services.net'); }
			}
		}
		asort($retValue);
		return $retValue;
	}
	
	private static function validateEmailList($str) {
		if ($str == '') { return true; }
		if (Email::isInjectionAttack($str)) { return false; }
		
		foreach (explode(",", $str) as $email) {
			$email = trim($email);
			if (util::removeBefore($email, "<")) {
				if (!util::removeAfter($email, ">")) { return false; }
			}
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { return false; }
		}
		return true;
	}
	static private function isInjectionAttack($str) {
		if (Email::containsNewLines($str)) { return true; }
		return Email::headerBlacklist($str);
	}
	static private function containsNewLines($str) {
		return preg_match("/(%0A|%0D|\\n+|\\r+)/i", $str) != 0;
	}
	static private function headerBlacklist($str) {
		$strs = array("content-type:","mime-version:","multipart\/mixed","Content-Transfer-Encoding:","bcc:","cc:","to:");
		$str = strtolower($str);
		
		foreach($strs as $bad_string) {
			if (preg_match("/.*" . $bad_string . ".*/", $str)) { return true; }
		}
		return false;
	}
}
?>
