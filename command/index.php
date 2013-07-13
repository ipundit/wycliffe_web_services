<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<title>Wycliffe Web Services command processor</title>
<style type="text/css">
h1 {
	margin: 0 0 5px 0;
	font-size: 25px;
}

label { display: inline-block; }
.error { color: red; }
label.error {
	display: block;
	text-align: left;
	font-weight: bold;
}

#radioButtons { float: left; }
#uploadFiles { 
	float: left;
	margin-left: 10px;
}

#commandFile { width: 338px; }
@-moz-document url-prefix() { /* firefox */
	.form-select { width: 82px; }
}
@media screen and (-webkit-min-device-pixel-ratio:0) { /* chrome */
	#commandFile { width: 328px; }
}

.attachment { width: 180px; }
.rightFileLabel { margin-left: 5px; }

button {
	height: 43px;
	margin: 6px 0 0 0;
	padding: 6px 22px;
	border-width: 2px;
	font-weight: bold;
	font-size: 23px;
	width: 448px;
}
#spinner {
    background-image: url("../spinner.gif");
    background-repeat: no-repeat;
    display: none;
    height: 16px;
    margin: 4px 0 0 7px;
    width: 16px;
}

#text {
	display: block;
	clear: both;
	width: 815px;
	height: 500px;
}
</style>

<script language='JavaScript' type='text/javascript' src='../jquery-1.10.2.min.js'></script>
<script language='JavaScript' type='text/javascript' src='../jquery.validate.min.js'></script>
<script language='JavaScript' type='text/javascript' src='index.js'></script>
<script language='JavaScript' type='text/javascript'>
<?php 
	$bundle = configureForLang();
	echo 'index_js_init({' . $bundle->generateMapping() . '})';
?>
</script>
</head>
<body>
<form id="theForm" action="#" method="post">
<div id="radioButtons">
	<h1><?php echo t("Run web service commands from"); ?>:</h1>
	<input type="radio" name="choice" id="choiceFile" checked /><input type="file" name="commandFile" id="commandFile" size="40" /><br />
	<input type="radio" name="choice" id="choiceService" /><label for="choiceService">http://wycliffe-services.net/</label>
	<select name="service" id="service" class="form-select">
		<?php
			foreach (getOptions() as $option) {
				echo '<option value="' . $option .'">' . $option . '</option>';
			}
		?>
	</select>
	<label for="choiceService">/tests/*.csv</label><br />
	<input type="radio" name="choice" id="choiceText" /><label for="choiceText"><?php echo t('Copy and paste a .csv file'); ?>:</label>
	<div id="errorAnchor" class="error"></div>
</div>
<div id="uploadFiles">
	<h1><?php echo t("And upload files"); ?>:</h1>
	<label>_file1: </label><input type="file" name="file1" id="file1" class="attachment" /><label class="rightFileLabel">_file3: </label><input type="file" name="file3" id="file3" class="attachment" /><br />
	<label>_file2: </label><input type="file" name="file2" id="file2" class="attachment" /><label class="rightFileLabel">_file4: </label><input type="file" name="file4" id="file4" class="attachment" /><br />
	<button type="submit"><?php echo t("Submit"); ?><div id="spinner"></div></button>
</div>
<textarea id="text"></textarea>
</form>
</body>
</html>

<?php
function getOptions() {
	$retValue = array();
	
	$dir = new DirectoryIterator('/var/www/');
	foreach ($dir as $fileinfo) {
		if ($fileinfo->isDir() && !$fileinfo->isDot()) {
			$subdir = $fileinfo->getFilename();
			if (file_exists('/var/www/' . $subdir . '/tests/')) { array_push($retValue, $subdir); }
		}
	}
	asort($retValue);
	return $retValue;
}
function configureForLang() {
	require_once 'StringBundle.php';
	$lang = isset($_GET["lang"]) ? filter_var($_GET["lang"], FILTER_SANITIZE_STRING) : "en";
	return new StringBundle($lang);
}
function t($englishText) {
	global $bundle;
	return $bundle->translate($englishText);
}
?>