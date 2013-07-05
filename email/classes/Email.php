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
		
		if ($row["to"] == '') {
		} else {
			if (!Email::send($msg, $row["fromName"], $row["sender"], $row["to"], $row['subject'], 
			$row['body'], $row['cc'], $row['bcc'], $row['replyTo'], $_FILES, $row['simulate'] == 1)) {
				return false;
			}
		}

		$msg = "ok";
		return true;
	}
	
	private static function validateInput(&$msg) {
		$filters = array(
		  "to"=>FILTER_UNSAFE_RAW,
		  "sender"=>FILTER_SANITIZE_EMAIL,
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
			if (!isset($_FILES["mailingList"])) {
				$msg = "Either to parameter must be set or mailingList file must uploaded";
				return false;
			}
			if (!isset($_FILES["template"])) {
				$msg = "template file must be uploaded if mailingList uploaded";
				return false;
			}
		} else {
			if (isset($_FILES["mailingList"])) {
				$msg = "mailingList cannot be set if to is set";
				return false;
			}
			if (isset($_FILES["template"])) {
				$msg = "template cannot be set if to is set";
				return false;
			}
			if (!Email::validateEmailList($row["to"])) {
				$msg = "invalid to";
				return false;
			}
		}

		if ($row["sender"] == '') {
			$row["sender"] = "no_reply@wycliffe-services.net";
		} else if (!in_array($row['sender'], array(
			'events@wycliffe-services.net', 'help@wycliffe-services.net',
			'mailer@wycliffe-services.net', 'no-reply@wycliffe-services.net',
			'webservice@wycliffe-services.net'))) {
			$msg = "invalid sender";
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
	
	private static function send(&$msg, $fromName, $sender, $to, $subject, $body, $cc = '', $bcc = '', $replyTo = '', $attach = '', $simulate) {
		if ($replyTo != '') {
			$replyTo = $fromName == '' ? $replyTo : $fromName . ' <' . $replyTo . '>';
		}
		
		$headers = array(
			'Sender' => $sender,
			'From' => $fromName == '' ? $sender : $fromName . ' via Wycliffe Web Services <' . $sender . '>',
			'To'   => $to,
			'Cc'   => $cc,
			'Bcc'  => $bcc,
			'Reply-To' => $replyTo,
			'Return-Path' => $replyTo,
			'Subject' => $subject,
		);
		if ($simulate) {
			$msg = trim(preg_replace('/\s+/', ' ', print_r($headers, true)));
			return false;
		}
				
        $mime = new Mail_mime('');
        $mime->setTXTBody($body);
        $mime->setHTMLBody('<html><body>'.str_replace('\n', '<br />', $body).'</body></html>');
        $body = $mime->get();

        $headers = $mime->headers($headers);
		$params['sendmail_path'] = '/usr/lib/sendmail';

        $mail =& Mail::factory('sendmail', $params);
		return $mail->send($to, $headers, $body);
	}

	private static function validateEmailList($str) {
		if ($str == '') { return true; }
		foreach (explode(",", $str) as $email) {
			if (util::removeBefore($email, "<")) {
				if (!util::removeAfter($email, ">")) { return false; }
			}
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { return false; }
		}
		return true;
	}
}
?>
