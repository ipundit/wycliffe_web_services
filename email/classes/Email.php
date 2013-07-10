<?php 
require_once 'util.php';

class Email
{
	public static function sendFromPost(&$msg) {
		$msg = '';
		$row = Email::validateInput($msg);
		if ($msg != '') { return false;	}
		
		$files = Email::getPathToAttachments();
		
		if ($row["to"] == '') {
			$lines = util::parseCSV(file_get_contents($_FILES["to"]['tmp_name']));
		} else {
			if (!util::sendEmail($msg, $row["fromName"], $row["from"], $row["to"], $row['subject'], 
			$row['body'], $row['cc'], $row['bcc'], $row['replyTo'], $files, $row['simulate'] == 1)) {
				return false;
			}
		}

		$msg = "ok";
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
	
	private static function validateInput(&$msg) {
		$filters = array(
		  "to"=>FILTER_UNSAFE_RAW,
		  "from"=>FILTER_SANITIZE_EMAIL,
		  "fromName"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "replyTo"=>FILTER_SANITIZE_EMAIL,
		  "subject"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "cc"=>FILTER_UNSAFE_RAW,
		  "bcc"=>FILTER_UNSAFE_RAW,
		  "body"=>FILTER_UNSAFE_RAW,
		  "simulate"=>FILTER_VALIDATE_INT,
		);
		$row = filter_var_array($_POST, $filters);

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
			$row["from"] = "no_reply@wycliffe-services.net";
		} else if (!in_array($row['from'], array(
			'events@wycliffe-services.net', 'help@wycliffe-services.net',
			'mailer@wycliffe-services.net', 'no-reply@wycliffe-services.net',
			'webservice@wycliffe-services.net')) && !util::isJaarsEmail($row['from'])) {
			$msg = "invalid from";
			return false;
		}

		$row['subject'] = urldecode($row['subject']);
		
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
