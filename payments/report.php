<?php
require_once 'classes/Donation.php';

$donation = new Donation();
echo $donation->reportCSV($_GET);
?>