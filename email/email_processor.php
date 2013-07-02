<?php
// An email sent to webservice@wycliffe-services.net will call this file when you edit
// /home/mailboxes/wycliffe-services.net/webservice@wycliffe-services.net/.courier to be like this:
// | /usr/bin/php5 /var/www/email/email_processor.php

$handle = fopen('php://stdin', 'r');
while(!feof($handle)) {
    $buffer .= fgets($handle);
}
fclose($handle);
echo $buffer;
?>