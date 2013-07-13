<?php
$bundle = '';
function configureForLang($startIndex = -1, $endIndex = -1) {
	require_once 'StringBundle.php';
	$lang = isset($_GET["lang"]) ? filter_var($_GET["lang"], FILTER_SANITIZE_STRING) : "en";
	
	global $bundle;
	$bundle = new StringBundle($lang, $startIndex, $endIndex);
	return $bundle->generateMapping();
}
function t($englishText) {
	global $bundle;
	return $bundle->translate($englishText);
}
?>