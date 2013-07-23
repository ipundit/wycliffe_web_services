<?php
/*****************************************************************************************************
 * An email sent to webservice@wycliffe-services.net will call this file when you edit               *
 * /home/mailboxes/wycliffe-services.net/webservice@wycliffe-services.net/.courier to be like this:  *
 * | /usr/bin/php5 /var/www/email/email_processor.php webservice@wycliffe-services.net               *
 *****************************************************************************************************/

chdir ('/var/www/email/');
require_once('classes/EmailProcessor.php');

if (!EmailProcessor::readFromData($message, $error)) { 
	echo $error;
	return;
}

$to = isset($argv[1]) ? $argv[1] : '';
if (!EmailProcessor::processMessage($to, $message, $error, true)) { 
	echo $error;
	return;
}
echo 'ok';
?>