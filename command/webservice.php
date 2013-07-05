<?php
require_once 'classes/CommandProcessor.php';

$processor = new CommandProcessor();
$processor->process($msg);
echo $msg;
?>