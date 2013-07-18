<?php 
require_once 'util.php';
require_once 'classes/Email.php';

class EmailProcessor
{
	public static function readFromData(&$message, &$error) {
		if (!EmailProcessor::receivedEmail($buffer, $error)) { return false; }
		return EmailProcessor::parse($buffer, $message, $error, EmailProcessor::simulate());
	}
	
	public static function parse($buffer, &$message, &$error, $simulateSpamCheck = false) {
		$mail = mailparse_msg_create();
		mailparse_msg_parse($mail, $buffer);
		$struct = mailparse_msg_get_structure($mail);

		if (!EmailProcessor::initHeaders($mail, $struct, $message, $error)) { return false; }

		EmailProcessor::initBody($mail, $struct, $buffer, $message);
		$body = $message['html'] == '' ? $message['body'] : $message['html'];
		if (Email::isSpam($message['from'], $message['from'], $body, $simulateSpamCheck)) {
			$error = "spam email discarded";
			return false;
		}

		if (!EmailProcessor::initAttachments($mail, $struct, $buffer, $message, $error)) { return false; }
		return true;
	}

	public static function processMessage($message, &$error, $deleteAttachments = false) {
		if (EmailProcessor::simulate()) {
			if ($deleteAttachments) { EmailProcessor::deleteAttachments($message['attachments']); };
			echo trim(preg_replace('/\s+/', ' ', print_r($message, true)));
			return;
		}

		try {
			/*
				$handler = EmailResponder::factory($message['to']);
				$handler->process($message);

				events@wycliffe-services.net
				help@wycliffe-services.net
				webservice@wycliffe-services.net

				1) If email not recognized or is blank, send template.  Need template read from file
				2) If template, convert to web service call and handle it from there.  Need body parser and mapping to web service call

				file_put_contents('/var/www/email/output.html', '<pre>' .print_r($message, true) . '</pre>');
			*/
		} catch (Exception $e) {
		
		}
		
		if ($deleteAttachments) { EmailProcessor::deleteAttachments($message['attachments']); }
		echo '<pre>' .print_r($message, true) . '</pre>';
	}

	private static function receivedEmail(&$buffer, &$error) {
		if (!EmailProcessor::getFilePath($path, $error)) { return false; }
		
		if ($path == '') { 
			$buffer = '';
			$handle = fopen('php://stdin', 'r');
			while(!feof($handle)) {
				$buffer .= fgets($handle);
			}
			fclose($handle);
		} else {
			$buffer = file_get_contents($path);
		}
		return true;
	}
	private static function getFilePath(&$path, &$error) {
		$fileName = isset($_GET['testFile']) ? $_GET['testFile'] : (isset($_POST['testFile']) ? $_POST['testFile'] : '');
		if ($fileName == '') { return true; }
		
		$fileName = filter_var($fileName, FILTER_SANITIZE_STRING, array('flags'=>FILTER_FLAG_NO_ENCODE_QUOTES));
		if ($fileName == '') {
			$error = 'invalid fileName';
			return false;
		}
		
		$path = '/var/www/email/tests/' . $fileName;
		if (file_exists($path)) { return true; }

		$error = $path . ' does not exist';
		$path = '';
		return false;
	}
	
	private static function initHeaders($mail, &$struct, &$message, &$error) {
		$info = EmailProcessor::getInfo($mail, array_shift($struct));
		
		if (!isset($info['headers']['from'])) {
			$error = "malformed email";
			return false;
		}
		if (EmailProcessor::isBounceMessage($info)) {
			$error = "bounce email discarded";
			return false;
		}

		$fields = 'from,to,cc,reply-to,subject,date';
		foreach (explode(',', $fields) as $field) {
			$message[$field] = isset($info['headers'][$field]) ? $info['headers'][$field] : '';
		}
		return true;
	}
	private static function initBody($mail, &$struct, $buffer, &$message) {
		$message['body'] = '';
		$message['html'] = '';

		while (!empty($struct)) {
			$info = EmailProcessor::getInfo($mail, $struct[0]);
			switch ($info['content-type']) {
			case 'multipart/alternative':
				break;
			case 'text/plain':
				$message['body'] = EmailProcessor::getData($buffer, $info);
				break;
			case 'text/html':
				$message['html'] = EmailProcessor::getData($buffer, $info);
				break;
			default:
				return;
			}
			array_shift($struct);
		}
	}
	private static function initAttachments($mail, &$struct, $buffer, &$message, &$error) {
		$message['attachments'] = array();
		$baseDir = util::createTempDir();

		while (!empty($struct)) {
			$info = EmailProcessor::getInfo($mail, array_shift($struct));
			
			if ($info['content-type'] == 'message/rfc822') {
				$key = $struct[0];
				
				$temp = EmailProcessor::getInfo($mail, array_shift($struct));
				$info['disposition-filename'] = $temp['headers']['subject'] . '.eml';
				EmailProcessor::processAttachment($buffer, $info, $baseDir, $message);
				
				if (count($struct) == 0) { return; }
				while (util::startsWith($struct[0], $key)) {
					array_shift($struct);
				}
				continue;
			}
			if ($info['content-disposition'] == 'attachment') {
				EmailProcessor::processAttachment($buffer, $info, $baseDir, $message);
				continue;
			}
			$error = 'Unexpected info: ' . print_r($info, true);
			util::deltree($baseDir);
			return false;
		}
		
		if (count($message['attachments']) == 0) { rmdir($baseDir); }
		return true;
	}

	private static function getData($buffer, $info) {
		return substr($buffer, $info['starting-pos-body'], $info['ending-pos-body'] - $info['starting-pos-body'] + 1);
	}

	private static function getInfo($mail, $struct) {
		$section = mailparse_msg_get_part($mail, $struct);
		return mailparse_msg_get_part_data($section);
	}

	private static function processAttachment($buffer, $info, $baseDir, &$message) {
		$newPath = $baseDir . $info['disposition-filename'];
		$message['attachments'][] = $newPath;
		if ($info['transfer-encoding'] == 'base64') { 
			file_put_contents($newPath, base64_decode(EmailProcessor::getData($buffer, $info)));
		} else {
			file_put_contents($newPath, EmailProcessor::getData($buffer, $info));
		}
	}

	private static function isBounceMessage($info) {
		if (util::startsWith($info['headers']['from'], 'postmaster')) { return true; }
		if (isset($info['content-report-type']) && $info['content-report-type'] == 'delivery-status') { return true; }
		return false;
	}
	
	private static function simulate() {
		$simulate = isset($_GET['simulate']) ? $_GET['simulate'] : (isset($_POST['simulate']) ? $_POST['simulate'] : 0);
		$simulate = filter_var($simulate, FILTER_VALIDATE_INT, array('filter'=>FILTER_VALIDATE_INT, 'options'=>array("min_range"=>0, "max_range"=>1)));
		return $simulate == 1;
	}
	
	private static function deleteAttachments($attachments) {
		if (count($attachments) == 0) { return; }
		
		$arr = explode(DIRECTORY_SEPARATOR, $attachments[0]);
		array_pop($arr);
		util::delTree(implode(DIRECTORY_SEPARATOR, $arr));
	}
}
?>