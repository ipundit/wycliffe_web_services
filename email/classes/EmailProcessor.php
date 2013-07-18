<?php 
require_once 'util.php';
require_once 'classes/Email.php';

class EmailProcessor
{
	public static function readFromData(&$message) {
		if (!EmailProcessor::receivedEmail($buffer)) {
			$message = buffer;
			return false;
		}
		return EmailProcessor::parse($buffer, $message, EmailProcessor::simulate());
	}
	public static function parse($buffer, &$message, $simulateSpamCheck = false) {
		$mail = mailparse_msg_create();
		mailparse_msg_parse($mail, $buffer);
		$struct = mailparse_msg_get_structure($mail);

		if (!EmailProcessor::initHeaders($mail, $struct, $message)) { return false; }

		EmailProcessor::initBody($mail, $struct, $buffer, $message);
		$body = $message['html'] == '' ? $message['body'] : $message['html'];
		if (Email::isSpam($message['from'], $message['from'], $body, $simulateSpamCheck)) {
			$message = "spam email discarded";
			return false;
		}

		if (!EmailProcessor::initAttachments($mail, $struct, $buffer, $message)) { return false; }
		return true;
	}
	
	private static function receivedEmail(&$buffer) {
		if (!EmailProcessor::getFilePath($buffer)) { return false; }
		
		if ($buffer == '') { 
			$handle = fopen('php://stdin', 'r');
			while(!feof($handle)) {
				$buffer .= fgets($handle);
			}
			fclose($handle);
		} else {
			$buffer = file_get_contents($buffer);
		}
		return true;
	}
	private static function getFilePath(&$path) {
		$fileName = isset($_GET['testFile']) ? $_GET['testFile'] : (isset($_POST['testFile']) ? $_POST['testFile'] : '');
		if ($fileName == '') { return true; }
		
		$fileName = filter_var($fileName, FILTER_SANITIZE_STRING, array('flags'=>FILTER_FLAG_NO_ENCODE_QUOTES));
		if ($fileName == '') {
			$path = 'invalid fileName';
			return false;
		}
		
		$path = '/var/www/email/tests/' . $fileName;
		if (file_exists($path)) { return true; }

		$path = $path . ' does not exist';
		return false;
	}
	
	private static function initHeaders($mail, &$struct, &$message) {
		$info = EmailProcessor::getInfo($mail, array_shift($struct));
		if (EmailProcessor::isBounceMessage($info)) {
			$message = "bounce or spam email discarded";
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
	private static function initAttachments($mail, &$struct, $buffer, &$message) {
		$message['attachments'] = array();

		while (!empty($struct)) {
			$info = EmailProcessor::getInfo($mail, array_shift($struct));
			
			if ($info['content-type'] == 'message/rfc822') {
				$key = $struct[0];
				
				$temp = EmailProcessor::getInfo($mail, array_shift($struct));
				$info['disposition-filename'] = $temp['headers']['subject'] . '.eml';
				EmailProcessor::processAttachment($buffer, $info, $message);
				
				if (count($struct) == 0) { return; }
				while (util::startsWith($struct[0], $key)) {
					array_shift($struct);
				}
				continue;
			}
			if ($info['content-disposition'] == 'attachment') {
				EmailProcessor::processAttachment($buffer, $info, $message);
				continue;
			}
			$message = 'Unexpected info: ' . print_r($info, true);
			return false;
		}
		return true;
	}

	private static function getData($buffer, $info) {
		return substr($buffer, $info['starting-pos-body'], $info['ending-pos-body'] - $info['starting-pos-body'] + 1);
	}

	private static function getInfo($mail, $struct) {
		$section = mailparse_msg_get_part($mail, $struct);
		return mailparse_msg_get_part_data($section);
	}

	private static function processAttachment($buffer, $info, &$message) {
		$newPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $info['disposition-filename'];
		$message['attachments'][] = $newPath;
		if ($info['transfer-encoding'] == 'base64') { 
			file_put_contents($newPath, base64_decode(EmailProcessor::getData($buffer, $info)));
		} else {
			file_put_contents($newPath, EmailProcessor::getData($buffer, $info));
		}
	}

	private static function isBounceMessage($info) {
		if (!isset($info['headers']['from'])) { return true; }
		if (util::startsWith($info['headers']['from'], 'postmaster')) { return true; }
		if (isset($info['content-report-type']) && $info['content-report-type'] == 'delivery-status') { return true; }
		return false;
	}
	
	private static function simulate() {
		$simulate = isset($_GET['simulate']) ? $_GET['simulate'] : (isset($_POST['simulate']) ? $_POST['simulate'] : 0);
		$simulate = filter_var($simulate, FILTER_VALIDATE_INT, array('filter'=>FILTER_VALIDATE_INT, 'options'=>array("min_range"=>0, "max_range"=>1)));
		return $simulate == 1;
	}
}
?>
