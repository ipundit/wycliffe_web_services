<?php 
require_once 'Mail.php';
require_once 'Mail/mime.php';
require_once 'util.php';

class Email
{
	public static function sendFromPost(&$msg) {
		$msg = '';
		$row = Email::validateInput($msg);
		if ($msg != '') { return false;	}
		
		$files = Email::getPathToAttachments();
		
		if ($row["to"] == '') {
		} else {
			if (!Email::send($msg, $row["fromName"], $row["from"], $row["to"], $row['subject'], 
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
			'webservice@wycliffe-services.net')) && !Email::isJaarsEmail($row['from'])) {
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
	
	private static function send(&$msg, $fromName, $from, $to, $subject, $body, $cc = '', $bcc = '', $replyTo = '', $attachments = array(), $simulate) {
		if (Email::isJaarsEmail($from)) {
			$sender = 'wycliffe-services-smtp@wycliffe.net';
			if ($replyTo != '') {
				$msg = "replyTo not supported for Jaars emails";
				return false;
			}
			$returnPath = $from;
		} else {
			$sender = $from;
			$returnPath = $replyTo;
			if ($replyTo != '' && $fromName != '') {
				$replyTo = $fromName . ' <' . $replyTo . '>';
			}
			if ($fromName != '') { $fromName .= ' via Wycliffe Web Services'; }
		}
		
		$headers = array(
			'Sender' => $sender,
			'From' => $fromName == '' ? $from : $fromName . ' <' . $from . '>',
			'To'   => $to,
			'Cc'   => $cc,
			'Reply-To' => $replyTo,
			'Return-Path' => $returnPath, // SMTP gives 501 error if this field is set to $fromName <$replyTo>
			'Subject' => $subject,
		);
		foreach ($headers as $key => $value) {
			if ($value == '') { unset($headers[$key]); }
		}

		if ($simulate) {
			$msg = trim(preg_replace('/\s+/', ' ', print_r($headers, true)));
			if (count($attachments) > 0) { $msg = $msg . ' Number of attachments: ' . count($attachments); }
			return false;
		}
				
        $mime = new Mail_mime('');
        $mime->setTXTBody($body);
        $mime->setHTMLBody('<html><body>'.str_replace('\n', '<br />', $body).'</body></html>');

		foreach ($attachments as $file) {
			$mime->addAttachment($file);
		}

        $body = $mime->get();
        $headers = $mime->headers($headers);

		$mail = Email::getFactory($from);
		
		if ($cc != '') { $to = $to . ', ' . $cc; }
		if ($bcc != '') { $to = $to . ', ' . $bcc; }
		$mail = $mail->send($to, $headers, $body);
		
		if (PEAR::isError($mail)) {
			$msg = $mail->getMessage();
			return false;
		}
		return true;
	}

	private static function getFactory($from) {
		
		if (preg_match('/.+@[' . implode(Email::jaarsDomains(), '|') . ']/', $from)) {
			require_once('email_constants.php');
			$server = 'smtp';
			$params = array(
				'host' => 'mail.jaars.org',
				'auth' => true,
				'username' => JAARS_USERNAME,
				'password' => JAARS_PASSWORD,
			);
		} else {
			$server = 'sendmail';
			$params['sendmail_path'] = '/usr/lib/sendmail';
		}
		return Mail::factory($server, $params);
	}
	
	private static function jaarsDomains() {
		return array('sil.org', 'wycliffe.net', 'wycliffe.org', 'jaars.org', 'kastanet.org');
	}

	private static function isJaarsEmail($email) {
		$arr = explode('@', $email, 2);
		return in_array($arr[1], Email::jaarsDomains());
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
