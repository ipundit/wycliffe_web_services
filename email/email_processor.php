<?php
/*****************************************************************************************************
 * An email sent to webservice@wycliffe-services.net will call this file when you edit               *
 * /home/mailboxes/wycliffe-services.net/webservice@wycliffe-services.net/.courier to be like this:  *
 * | /usr/bin/php5 /var/www/email/email_processor.php                                                *
 *****************************************************************************************************/
require_once('classes/EmailProcessor.php');

if (!EmailProcessor::readFromData($message, $error)) { 
	echo $error;
	return;
}
if (!EmailProcessor::processMessage($message, $error)) { 
	echo $error;
	return;
}
echo 'ok';


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


function deleteAttachments($attachments) {
	foreach ($attachments as $value) {
		try {
			if (file_exists($value)) { unlink($value); }
		} catch (Exception $ignore) {}
	}
}



function simulate() {
	$simulate = isset($_GET['simulate']) ? $_GET['simulate'] : (isset($_POST['simulate']) ? $_POST['simulate'] : 0);
	$simulate = filter_var($simulate, FILTER_VALIDATE_INT, array('filter'=>FILTER_VALIDATE_INT, 'options'=>array("min_range"=>0, "max_range"=>1)));
	return $simulate == 1;
}


?>