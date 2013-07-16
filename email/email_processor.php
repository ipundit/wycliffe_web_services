<?php
// An email sent to webservice@wycliffe-services.net will call this file when you edit
// /home/mailboxes/wycliffe-services.net/webservice@wycliffe-services.net/.courier to be like this:
// | /usr/bin/php5 /var/www/email/email_processor.php
require_once('util.php');

$buffer = receivedEmail();
$mail = mailparse_msg_create();
mailparse_msg_parse($mail, $buffer);
$struct = mailparse_msg_get_structure($mail);

if (!initHeaders($mail, $struct, $message)) { return; }
initBody($mail, $struct, $buffer, $message);
initAttachments($mail, $struct, $buffer, $message);

file_put_contents('/var/www/email/output.html', '<pre>' .print_r($message, true) . '</pre>');
deleteAttachments($message['attachments']);
echo '<pre>' .print_r($message, true) . '</pre><hr />';

function initHeaders($mail, &$struct, &$message) {
	$info = getInfo($mail, array_shift($struct));
	if (isBounceMessage($info) || isSpam($info)) { return false; }

	$fields = 'from,to,cc,reply-to,subject,date';
	foreach (explode(',', $fields) as $field) {
		$message[$field] = isset($info['headers'][$field]) ? $info['headers'][$field] : '';
	}
	return $message;
}

function initBody($mail, &$struct, $buffer, &$message) {
	$message['body'] = '';
	$message['html'] = '';

	while (!empty($struct)) {
		$info = getInfo($mail, $struct[0]);
		switch ($info['content-type']) {
		case 'multipart/alternative':
			break;
		case 'text/plain':
			$message['body'] = getData($buffer, $info);
			break;
		case 'text/html':
			$message['html'] = getData($buffer, $info);
			break;
		default:
			return;
		}
		array_shift($struct);
	}
}	

function initAttachments($mail, &$struct, $buffer, &$message) {
	$message['attachments'] = array();

	while (!empty($struct)) {
		$info = getInfo($mail, array_shift($struct));
		
		if ($info['content-type'] == 'message/rfc822') {
			$key = $struct[0];
			
			$temp = getInfo($mail, array_shift($struct));
			$info['disposition-filename'] = $temp['headers']['subject'] . '.eml';
			processAttachment($buffer, $info, $message);
			
			if (count($struct) == 0) { return; }
			while (util::startsWith($struct[0], $key)) {
				array_shift($struct);
			}
			continue;
		}
		if ($info['content-disposition'] == 'attachment') {
			processAttachment($buffer, $info, $message);
			continue;
		}
		die('Unexpected info: ' . print_r($info, true));
	}
}

function getData($buffer, $info) {
	return substr($buffer, $info['starting-pos-body'], $info['ending-pos-body'] - $info['starting-pos-body'] + 1);
}

function getInfo($mail, $struct) {
	$section = mailparse_msg_get_part($mail, $struct);
	return mailparse_msg_get_part_data($section);
}

function deleteAttachments($attachments) {
	foreach ($attachments as $value) {
		try {
			if (file_exists($value)) { unlink($value); }
		} catch (Exception $ignore) {}
	}
}

function processAttachment($buffer, $info, &$message) {
	$newPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $info['disposition-filename'];
	$message['attachments'][] = $newPath;
	if ($info['transfer-encoding'] == 'base64') { 
		file_put_contents($newPath, base64_decode(getData($buffer, $info)));
	} else {
		file_put_contents($newPath, getData($buffer, $info));
	}
}

function isBounceMessage($info) {
	if (util::startsWith($info['headers']['from'], 'postmaster')) { return true; }
	if (isset($info['content-report-type']) && $info['content-report-type'] == 'delivery-status') { return true; }
	return false;
}
function isSpam($info) {
	return false;
}

function receivedEmail() {
	$buffer = '';
	
	$handle = fopen('php://stdin', 'r');
//	$handle = fopen('/var/www/email/input.html.forwardedinline', 'r');
	while(!feof($handle)) {
		$buffer .= fgets($handle);
	}
	fclose($handle);

	define("DUMP_OUTPUT", false);
	if (DUMP_OUTPUT) {
		file_put_contents('/var/www/email/output.html', '<pre>' .print_r($buffer, true) . '</pre>');
		echo '<pre>' .print_r($buffer, true) . '</pre><hr />';
		exit();
	}

	return $buffer;
}
?>