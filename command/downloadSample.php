<?php

if (isset($_POST['service'])) {
	$service = $_POST['service'];
} else if (isset($_GET['service'])) {
	$service = $_GET['service'];
} else {
	echo 'service not set';
	exit;
}

if (isset($_POST['simulate'])) {
	$simulate = $_POST['simulate'];
} else if (isset($_GET['simulate'])) {
	$simulate = $_GET['simulate'];
} else {
	$simulate = 0;
}
$simulate = $simulate == 1;

$service = filter_var($service, FILTER_SANITIZE_STRING, array('flags'=>FILTER_FLAG_NO_ENCODE_QUOTES));
if ($service !== false && !preg_match('/^.*[\.|\/]/', $service)) {
	$file = '/var/www/' . $service . '/tests/sample.csv';
	if (file_exists($file)) {
		if ($simulate) {
			echo trim(preg_replace('/(\s+|")/', ' ', file_get_contents($file)));
			exit;
		}

		$fp = fopen($file, 'rb');
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename='.basename($file));
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($file));
		ob_clean();
		flush();
		readfile($file);
		exit;
	}
}
echo 'No sample file found';
?>