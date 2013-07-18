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
if (!EmailProcessor::processMessage($message, $error, true)) { 
	echo $error;
	return;
}
echo 'ok';
?>