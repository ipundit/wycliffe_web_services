<?php
/*****************************************************************************************************
 * An email sent to webservice@wycliffe-services.net will call this file when you edit               *
 * /home/mailboxes/wycliffe-services.net/webservice@wycliffe-services.net/.courier to be like this:  *
 * | /usr/bin/php5 /var/www/email/email_processor.php                                                *
 *****************************************************************************************************/
require_once('classes/EmailParser.php');

$message = EmailParser::parse(receivedEmail());
file_put_contents('/var/www/email/output.html', '<pre>' .print_r($message, true) . '</pre>');
deleteAttachments($message['attachments']);
echo '<pre>' .print_r($message, true) . '</pre><hr />';

function deleteAttachments($attachments) {
	foreach ($attachments as $value) {
		try {
			if (file_exists($value)) { unlink($value); }
		} catch (Exception $ignore) {}
	}
}

function receivedEmail() {
	$buffer = '';

	$path = getFilePath();
	if ($path == '') { 
		$handle = fopen('php://stdin', 'r');
		while(!feof($handle)) {
			$buffer .= fgets($handle);
		}
		fclose($handle);
	} else {
		$buffer = file_get_contents($path);
	}
	return $buffer;
}

function getFilePath() {
	$fileName = isset($_GET['testFile']) ? $_GET['testFile'] : (isset($_POST['testFile']) ? $_POST['testFile'] : '');
	$fileName = filter_var($fileName, FILTER_SANITIZE_STRING, array('flags'=>FILTER_FLAG_NO_ENCODE_QUOTES));
	
	if ($fileName != '') {
		$path = '/var/www/email/tests/' . $fileName;
		if (file_exists($path)) { return $path; }
	}
	return '';
}
?>