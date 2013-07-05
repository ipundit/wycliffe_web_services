<?php
require_once 'classes/Email.php';

$msg = '';
Email::sendFromPost($msg);
echo $msg;
?>