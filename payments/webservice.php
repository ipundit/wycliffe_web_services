<?php
require_once 'classes/User.php';

$user = new User();
if ($user->makePurchase($_POST, false, $msg)) { $msg = 'ok' . $msg; }
echo $msg;
?>