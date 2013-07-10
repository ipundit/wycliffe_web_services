<?php 
require_once 'util.php';
define("_EMAIL_ASCII_TAB_", 9);

class Email
{
	public static function sendFromPost(&$msg) {
		$msg = '';
		$row = Email::validateInput($_POST, $msg);
		if ($msg != '') { return false;	}
		
		$files = Email::getPathToAttachments();
		if ($row["to"] == '') {
			$lines = Email::fillTemplateFromCSV($row, $msg);
			if ($msg != '') { return false; }

			if ($row['simulate'] == 1) {
				$msg = trim(preg_replace('/\s+/', ' ', print_r($lines, true)));
				return false;
			}
			
			foreach ($lines as $line) {
				if (!util::sendEmail($msg, $line["fromName"], $line["from"], $line['$email'], $line['subject'], 
									 $line['body'], $line['cc'], $line['bcc'], $line['replyTo'], $files,
									 $line['simulate'] == 1)) {
					if ($line['simulate'] == 1) {
						return false;
					}
					util::sendEmail($msg, 'Wycliffe Web Services mailier', 'no-reply@wycliffe-services.net', 
									$line["from"], 'This email failed: ' . $line['subject'], 
									'Sending email to ' . $line['$email'] . ' failed with message:<br />' . $msg);
					$msg = '';
				}
			}
		} else {
			if (!util::sendEmail($msg, $row["fromName"], $row["from"], $row["to"], $row['subject'], 
								 $row['body'], $row['cc'], $row['bcc'], $row['replyTo'], $files, $row['simulate'] == 1)) {
				return false;
			}
		}

		$msg = "ok";
		return true;
	}

	private static function fillTemplateFromCSV($row, &$msg) {
		unset($row['to']);
		$csvLines = util::parseCSV(file_get_contents($_FILES["to"]['tmp_name']));
		unset($_FILES["to"]);
		
		$variablesLookup = array();
		
		if (!Email::parseLines($csvLines, $variablesLookup, $msg)) { return false; }
		
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
	
	private static function parseLines($lines, &$variablesLookup, &$msg) {
		$isFirstLine = true;
		$keys = '';
		$i = 1;

		foreach ($lines as $line) {
			util::removeAfter($line, '#');
			
			$columns = explode(chr(_EMAIL_ASCII_TAB_), $line);
			if ($isFirstLine) {
				if (!Email::getKeys($columns, $keys, $msg)) { return false; }
				$isFirstLine = false;
				$i++;
				continue;
			}

			$variablesLookup[$i] = array('$email' => '');
			if ($line == '') { $i++; continue; }
			
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
	
	private static function getPathToAttachments() {
		$retValue = array();
		
		foreach ($_FILES as $key => $value) {
			if (preg_match('/^attach[1-9]$/', $key)) {
				util::renameTempfile($key);
				$retValue[$key] = $_FILES[$key]['tmp_name'];
			}
		}
		return $retValue;
	}
	
	private static function validateInput($arr, &$msg) {
		$filters = array(
		  "to"=>FILTER_UNSAFE_RAW,
		  "from"=>FILTER_UNSAFE_RAW,
		  "fromName"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "replyTo"=>FILTER_SANITIZE_EMAIL,
		  "subject"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "cc"=>FILTER_UNSAFE_RAW,
		  "bcc"=>FILTER_UNSAFE_RAW,
		  "body"=>FILTER_UNSAFE_RAW,
		  "tags"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "simulate"=>FILTER_VALIDATE_INT,
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
		} else {
			if (isset($_FILES["to"])) {
				$msg = "cannot have more than one source of to";
				return false;
			}
			if (!Email::validateEmailList($row["to"])) {
				$msg = "invalid to";
				return false;
			}
		}

		if ($row["from"] == '') {
			$row["from"] = "no-reply@wycliffe-services.net";
		} else if (!in_array($row['from'], array(
				'events@wycliffe-services.net', 'help@wycliffe-services.net',
				'mailer@wycliffe-services.net', 'no-reply@wycliffe-services.net',
				'webservice@wycliffe-services.net')) && !util::isJaarsEmail($row['from'])) {
			if (!isset($_FILES["to"]) || strpos($row['from'], '$') === false) {
				$msg = "invalid from";
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
		
		return $row;
	}
	
	private static function validateEmailList($str) {
		if ($str == '') { return true; }
		foreach (explode(",", $str) as $email) {
			$email = trim($email);
			if (util::removeBefore($email, "<")) {
				if (!util::removeAfter($email, ">")) { return false; }
			}
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { return false; }
		}
		return true;
	}
}
?>
