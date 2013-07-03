<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<title>Wycliffe Web Services command processor</title>
<style type="text/css">
h1 { margin: 0 0 5px 0; }
label { display: inline-block; }
#radioButtons { float: left; }

button {
	float: left;
	height: 83px;
	margin: 0 0 0 20px;
	padding: 6px 22px;
	border-width: 2px;
	font-weight: bold;
	font-size: 23px;
	width: 428px;
}

#url { width: 296px; }
#file { width: 338px; }

@-moz-document url-prefix() { /* firefox */
	#url { width: 292px; }
	.form-select { width: 82px; }
}
@media screen and (-webkit-min-device-pixel-ratio:0) { /* chrome */
	#url { width: 286px; }
	#file { width: 328px; }
	button { width: 438px; }
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
	width: 800px;
	height: 500px;
}

.error {
	color: red;
}
label.error {
	display: block;
	text-align: left;
	font-weight: bold;
}
</style>

<script language='JavaScript' type='text/javascript' src='../jquery-1.10.0.min.js'></script>
<script language='JavaScript' type='text/javascript' src='../jquery.validate.min.js'></script>
<script language='JavaScript' type='text/javascript' src='../additional-methods.min.js'></script>
<script language='JavaScript' type='text/javascript'>
	// Need to get translations of validation messages
	var translationMappings = {
		<?php echo generateMapping("Please enter a valid URL to a .csv file"); ?>,
		<?php echo generateMapping("Please choose a .csv file"); ?>
	};
	
	function translate(englishString) {
		return translationMappings[englishString];
	}
</script>
<script language='JavaScript' type='text/javascript' src='index.js'></script>
</head>
<body>

<h1><?php echo t("Run web service commands from"); ?>:</h1>
<div class="error" id="error"></div>
<form id="theForm" action="#" method="post">
<div>
	<div id="radioButtons">
	<input type="radio" name="src" id="choiceFile" checked /><input type="file" name="file" id="file" size="40" /><br />
	<input type="radio" name="src" id="choiceService" /><label for="choiceService">http://wycliffe-services.net/</label>
		<select name="service" id="service" class="form-select">
			<?php
				foreach (getOptions() as $option) {
					echo '<option value="' . $option .'">' . $option . '</option>';
				}
			?>
		</select>
		<label for="choiceService">/tests/*.csv</label><br />
	<input type="radio" name="src" id="choiceText" /><label for="choiceText"><?php echo t('Copy and paste a .csv file'); ?>:</label>
	</div>
	<button type="button"><?php echo t("Submit"); ?><div id="spinner"></div></button>
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

function t($englishText) {
	// Do nothing stub for localization.  Implement this if necessary in the future
//	global $bundle;
//	return $bundle->translate($englishText);
	return $englishText;
}

// Used for mapping client side error messages.
function generateMapping($englishText) {
	return '"' . $englishText . '" : "' . t($englishText) . '"';
}
?>
