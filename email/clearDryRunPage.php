<?php
	require_once('util.php');
	
	$dryRun = array(
	'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
	'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">',
	'<head>',
	'<title>Dry run output</title>',
	'</head>',
	'<body>',
	'<h1>Dry run page cleared</h1>',
	'</body>',
	'</html>',
	);
	file_put_contents('./dryRun.html', $dryRun);
	header('Location: ' . util::absURL('dryRun.html'));
?>