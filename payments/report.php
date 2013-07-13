<?php
require_once 'classes/Donation.php';

$donation = new Donation();

$data = empty($_POST) ? $_GET : $_POST;
echo $donation->reportCSV($data);
?>