<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<title>Wycliffe Web Services email tester</title>
<style type="text/css">
.radio {
	margin: 3px 3px 3px 0;
}

h1 {
	margin: 0 0 5px 0;
	font-size: 25px;
}

label { display: inline-block; }
#error { color: red; }

.attachment { width: 263px; }

.row {
	clear: both;
	padding-top: 10px;
}
.header {
	float: left;
	width: 61px;
	text-align: right;
	margin-right: 5px;
}
.cell {
	float: left;
}
.rightCell {
	margin: 2px 0 0 15px;
}

#fromNameJAARS, #fromNameWWS {
	width: 127px;
}
#fromEmailJAARS {
	width: 417px;
}
#toEmailText {
	width: 579px;
}
#fromReplyToWWS, #tags {
	width: 212px;
}
#body {
	width: 603px;
	height: 500px;
}
@-moz-document url-prefix() { /* firefox */
	#fromNameJAARS, #fromNameWWS { width: 132px; }
	#fromEmailJAARS { width: 416px; }
	#toEmailText { width: 585px; }
	#body { width: 605px; }
}
@media screen and (-webkit-min-device-pixel-ratio:0) { /* chrome */
	#toEmailText { width: 585px; }
	#fromNameJAARS, #fromNameWWS { width: 155px; }
	#fromEmailJAARS { width: 395px; }
}

#startMaxRow {
	display: inline-block;
	float: right;
}
.inlineLabel {
	margin-left: 15px;
}
#startRow, #maxRows {
	width: 27px;
}
#cc, #bcc, #subject {
	width: 605px;
}

button {
	height: 43px;
	margin: -3px 0 0 0;
	border-width: 2px;
	font-weight: bold;
	font-size: 23px;
	width: 282px;
}
#spinner {
    background-image: url("../spinner.gif");
    background-repeat: no-repeat;
    display: none;
    height: 16px;
    margin: 4px 0 0 7px;
    width: 16px;
}
#attachmentsDiv {
	width: 282px;
}
</style>

<script language='JavaScript' type='text/javascript' src='../jquery-1.10.0.min.js'></script>
<script language='JavaScript' type='text/javascript' src='../jquery.validate.min.js'></script>
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

<div id="error"></div>
<form id="theForm" action="#" method="post">
<div class="row">
	<div class="header"><?php echo t("From") ?>:</div>
	<div class="cell">
		<input type="radio" class="radio" name="choiceFrom" id="choiceJAARS" checked /> <input type="text" id="fromNameJAARS" /> <label for="choiceJAARS">&lt;</label> <input type="text" id="fromEmailJAARS" /> <label for="choiceJAARS">&gt;</label><br />
		<input type="radio" class="radio" name="choiceFrom" id="choiceWWS" /> <input type="text" id="fromNameWWS" /> <label for="choiceWWS"><?php echo t("via Wycliffe Web Services"); ?> &lt;</label> <select id="fromEmailWWS" class="form-select">
			<?php
				foreach (getOptions() as $option) {
					echo '<option value="' . $option .'">' . $option . '</option>';
				}
			?>
		</select> <label for="choiceWWS">@wycliffe-services.net &gt;</label>
	</div>
	<div class="cell rightCell">
		<br />
		<div class="header"><label for="choiceWWS"><?php echo t("Reply-to") ?>:</label></div>
		<div class="cell"><input type="text" id="fromReplyToWWS" /></div>
	</div>
</div>
<div class="row">
	<div class="header"><?php echo t("To") ?>:</div>
	<div class="cell">
		<input type="radio" class="radio" name="choiceTo" id="choiceEmail" checked /> <input type="text" id="toEmailText" /><br />
		<input type="radio" class="radio" name="choiceTo" id="choiceFile" /> <input type="file" id="toEmailFile" size="40" />
			<div id="startMaxRow"><label for="choiceFile"><?php echo t("Start row") ?>:</label> <input type="text" id="startRow" /> <label for="choiceFile" class="inlineLabel"><?php echo t("Max rows") ?>:</label> <input type="text" id="maxRows" /></div>
	</div>
	<div class="cell rightCell">
		<br />
		<div class="header"><label for="choiceFile">Tags:</label></div>
		<div class="cell"><input type="text" id="tags" /></div>
	</div>
</div>
<div class="row">
	<div class="header"><?php echo t("Cc") ?>:</div>
	<div class="cell"><input type="text" id="cc" /></div>
	<div class="cell rightCell">
		<input type="checkbox" id="simulate" /> <label for="simulate"><?php echo t("Simulate") ?></label>
	</div>
</div>
<div class="row">
	<div class="header"><?php echo t("Bcc"); ?>:</div>
	<div class="cell"><input type="text" id="bcc" /></div>
	<div class="cell rightCell">
		<button type="button"><?php echo t("Submit"); ?><div id="spinner"></div></button>
	</div>
</div>
<div class="row">
	<div class="header"><?php echo t("Subject"); ?>:</div>
	<div class="cell"><input type="text" id="subject" /></div>
</div>
<div class="row">
	<div class="header"><?php echo t("Body"); ?>:</div>
	<div class="cell"><textarea id="body"></textarea></div>
	
	<div class="cell rightCell" id="attachmentsDiv">
		<h1><?php echo t("Attachments"); ?>:</h1>
		1. <input type="file" id="file1" class="attachment" /><br />
		2. <input type="file" id="file2" class="attachment" /><br />
		3. <input type="file" id="file3" class="attachment" /><br />
		4. <input type="file" id="file4" class="attachment" /><br />
		5. <input type="file" id="file5" class="attachment" /><br />
		6. <input type="file" id="file6" class="attachment" /><br />
		7. <input type="file" id="file7" class="attachment" /><br />
		8. <input type="file" id="file8" class="attachment" /><br />
		9. <input type="file" id="file9" class="attachment" />
	</div>
</div>
</form>
</body>
</html>

<?php
function getOptions() {
	require_once('util.php');
	require_once('classes/Email.php');
	$arr = Email::wycliffeServicesEmails();
	
	foreach ($arr as &$value) {
		util::removeAfter($value, '@');
	}
	return $arr;
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
