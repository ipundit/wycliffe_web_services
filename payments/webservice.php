<?php
require_once 'classes/User.php';

$data = (array) json_decode(file_get_contents('php://input'));
$user = new User();
if ($user->makePurchase($data, $msg)) { $msg = 'ok'; }
echo '{"status":"' . $msg , '"}';
?>