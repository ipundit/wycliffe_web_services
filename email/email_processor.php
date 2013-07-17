<?php
/*****************************************************************************************************
 * An email sent to webservice@wycliffe-services.net will call this file when you edit               *
 * /home/mailboxes/wycliffe-services.net/webservice@wycliffe-services.net/.courier to be like this:  *
 * | /usr/bin/php5 /var/www/email/email_processor.php                                                *
 *****************************************************************************************************/
require_once('classes/EmailParser.php');

$buffer = '';
if (!receivedEmail($buffer)) {
	echo $buffer;
	return;
}

$message = EmailParser::parse($buffer);
if ($message === false) { return; }

if (simulate()) {
	deleteAttachments($message['attachments']);
	echo trim(preg_replace('/\s+/', ' ', print_r($message, true)));
	return;
}

deleteAttachments($message['attachments']);
echo '<pre>' .print_r($message, true) . '</pre>';

/*
$handler = EmailResponder::factory($message['to']);
$handler->process($message);

events@wycliffe-services.net
help@wycliffe-services.net
webservice@wycliffe-services.net

1) If email not recognized or is blank, send template.  Need template
2) If template, convert to web service call and handle it from there.  Need body parser and mapping to web service call

file_put_contents('/var/www/email/output.html', '<pre>' .print_r($message, true) . '</pre>');
*/


function deleteAttachments($attachments) {
	foreach ($attachments as $value) {
		try {
			if (file_exists($value)) { unlink($value); }
		} catch (Exception $ignore) {}
	}
}

function receivedEmail(&$buffer) {
	if (!getFilePath($buffer)) { return false; }
	
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

function simulate() {
	$simulate = isset($_GET['simulate']) ? $_GET['simulate'] : (isset($_POST['simulate']) ? $_POST['simulate'] : 0);
	$simulate = filter_var($simulate, FILTER_VALIDATE_INT, array('filter'=>FILTER_VALIDATE_INT, 'options'=>array("min_range"=>0, "max_range"=>1)));
	return $simulate == 1;
}

function getFilePath(&$path) {
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
?>