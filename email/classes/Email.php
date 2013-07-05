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
		
		if ($row['simulate'] == 1) {
			$msg = "ok";
			return true;
		}

		if (isset($row["to"])) {
			
			return true;
		}
		return true;
	}
	
	private static function validateInput(&$msg) {
		$filters = array(
		  "to"=>FILTER_UNSAFE_RAW,
		  "fromName"=>FILTER_SANITIZE_STRING,
		  "fromEmail"=>FILTER_SANITIZE_STRING,
		  "replyTo"=>FILTER_SANITIZE_EMAIL,
		  "subject"=>FILTER_SANITIZE_STRING,
		  "cc"=>FILTER_UNSAFE_RAW,
		  "bcc"=>FILTER_UNSAFE_RAW,
		  "body"=>FILTER_SANITIZE_STRING,
		  "attachment"=>FILTER_SANITIZE_STRING,
		  "template"=>FILTER_SANITIZE_STRING,
		  "mailingList"=>FILTER_SANITIZE_STRING,
		  "simulate"=>FILTER_VALIDATE_INT,
		);
		$row = filter_var_array($_POST, $filters);

		if (isset($row["to"])) {
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
		} else {
			if (!isset($_FILES["mailingList"])) {
				$msg = "Either to parameter must be set or mailingList file must uploaded";
				return false;
			}
			if (!isset($_FILES["template"])) {
				$msg = "template file must be uploaded if mailingList uploaded";
				return false;
			}
		}
		
		if (isset($row["fromEmail"]) && !in_array($row['fromEmail'], array(
			'events@wycliffe-services.net', 'help@wycliffe-services.net',
			'mailer@wycliffe-services.net', 'no-reply@wycliffe-services.net',
			'webservice@wycliffe-services.net'))) {
				$msg = "invalid fromEmail";
				return false;
		} else {
			$row["fromEmail"] = "no_reply@wycliffe-services.net";
		}

		if (isset($row["cc"]) && !Email::validateEmailList($row["cc"])) {
			$msg = "invalid cc";
			return false;
		}

		if (isset($row["bcc"]) && !Email::validateEmailList($row["bcc"])) {
			$msg = "invalid bcc";
			return false;
		}
		
		return $row;
	}
	
	private static function send($from, $name, $email, $bcc, $subject, $body, $signature = '') {
		$to = $name . " <" . $email .">";

        $headers = array(
			'From' => $from,
			'To'   => $to,
			'Bcc'  => $bcc,
			'Subject' => $subject,
		);
		if ($signature != '') { $signature = '\n\n' . $signature; }
		$body = "Dear " . $name . ',\n\n' . $body . $signature;

        $mime = new Mail_mime('');
        $mime->setTXTBody($body);
        $mime->setHTMLBody('<html><body>'.str_replace('\n', '<br />', $body).'</body></html>');
        $body = $mime->get();

        $headers = $mime->headers($headers);
		$params['sendmail_path'] = '/usr/lib/sendmail';

        $mail =& Mail::factory('sendmail', $params);
		$mail->send($to, $headers, $body);
	}

	private static function validateEmailList($str) {
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
