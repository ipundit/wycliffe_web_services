<?php 
require_once 'util.php';

class EmailParser
{
	public static function parse($buffer) {
		$mail = mailparse_msg_create();
		mailparse_msg_parse($mail, $buffer);
		$struct = mailparse_msg_get_structure($mail);

		$message = '';
		if (!EmailParser::initHeaders($mail, $struct, $message)) { return false; }
		EmailParser::initBody($mail, $struct, $buffer, $message);
		EmailParser::initAttachments($mail, $struct, $buffer, $message);
		return $message;
	}
	
	private static function initHeaders($mail, &$struct, &$message) {
		$info = EmailParser::getInfo($mail, array_shift($struct));
		if (EmailParser::isBounceMessage($info) || EmailParser::isSpam($info)) { return false; }

		$fields = 'from,to,cc,reply-to,subject,date';
		foreach (explode(',', $fields) as $field) {
			$message[$field] = isset($info['headers'][$field]) ? $info['headers'][$field] : '';
		}
		return $message;
	}

	private static function initBody($mail, &$struct, $buffer, &$message) {
		$message['body'] = '';
		$message['html'] = '';

		while (!empty($struct)) {
			$info = EmailParser::getInfo($mail, $struct[0]);
			switch ($info['content-type']) {
			case 'multipart/alternative':
				break;
			case 'text/plain':
				$message['body'] = EmailParser::getData($buffer, $info);
				break;
			case 'text/html':
				$message['html'] = EmailParser::getData($buffer, $info);
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
			$info = EmailParser::getInfo($mail, array_shift($struct));
			
			if ($info['content-type'] == 'message/rfc822') {
				$key = $struct[0];
				
				$temp = EmailParser::getInfo($mail, array_shift($struct));
				$info['disposition-filename'] = $temp['headers']['subject'] . '.eml';
				EmailParser::processAttachment($buffer, $info, $message);
				
				if (count($struct) == 0) { return; }
				while (util::startsWith($struct[0], $key)) {
					array_shift($struct);
				}
				continue;
			}
			if ($info['content-disposition'] == 'attachment') {
				EmailParser::processAttachment($buffer, $info, $message);
				continue;
			}
			die('Unexpected info: ' . print_r($info, true));
		}
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
			file_put_contents($newPath, base64_decode(EmailParser::getData($buffer, $info)));
		} else {
			file_put_contents($newPath, EmailParser::getData($buffer, $info));
		}
	}

	private static function isBounceMessage($info) {
		if (!isset($info['headers']['from'])) { return true; }
		if (util::startsWith($info['headers']['from'], 'postmaster')) { return true; }
		if (isset($info['content-report-type']) && $info['content-report-type'] == 'delivery-status') { return true; }
		return false;
	}
	private static function isSpam($info) {
		return false;
	}
}
?>
