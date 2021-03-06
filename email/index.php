﻿<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
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
.error { color: red; }
label.error {
	display: block;
	text-align: left;
	font-weight: bold;
}

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

#WWSDiv {
	float: right;
}
#fromName {
	width: 127px;
}
#fromEmailJAARS {
	width: 417px;
}
#fromEmailWWS {
	width: 92px;
}
#to {
	width: 579px;
}
#toEmailFile {
	width: 190px;
}
#replyTo, #tags {
	width: 212px;
}
.radioSimulate {
	margin-left: 13px;
}
#choiceSend {
	margin-left: 4px;
}
#body {
	width: 603px;
	height: 500px;
}
@-moz-document url-prefix() { /* firefox */
	#fromName { width: 132px; }
	#fromEmailJAARS { width: 416px; }
	#fromEmailWWS { width: 96px; }
	#to { width: 585px; }
	#choiceSend { margin-left: 15px; }
	#body { width: 605px; }
}
@media screen and (-webkit-min-device-pixel-ratio:0) { /* chrome */
	#fromName { width: 155px; }
	#fromEmailJAARS { width: 395px; }
	#fromEmailWWS { width: 94px; }
	#to { width: 585px; }
	#choiceSend { margin-left: 26px; }
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

<script language='JavaScript' type='text/javascript' src='../jquery-1.10.2.min.js'></script>
<script language='JavaScript' type='text/javascript' src='../jquery.validate.min.js'></script>
<script language='JavaScript' type='text/javascript' src='index.js'></script>
<script language='JavaScript' type='text/javascript'>
<?php 
	require_once 'translation.php';
	echo 'index_js_init({' . configureForLang(200) . '})';
?>
</script>
</head>
<body>

<form id="theForm" action="#" method="post">
<div id="errorAnchor" class="error"></div>
<div class="row">
	<div class="header"><?php echo t("From") ?>:</div>
	<div class="cell">
		<input type="text" id="fromName" name="fromName" /> <input type="radio" class="radio" name="choiceFrom" id="choiceJAARS" checked /> <label for="choiceJAARS">&lt;</label> <input type="text" name="fromEmailJAARS" id="fromEmailJAARS" /> <label for="choiceJAARS">&gt;</label><br />
		<div id="WWSDiv"><input type="radio" class="radio" name="choiceFrom" id="choiceWWS" /> <label for="choiceWWS"><?php echo t("via Wycliffe Web Services"); ?> &lt;</label> <select name="fromEmailWWS" id="fromEmailWWS" class="form-select">
			<?php
				foreach (getOptions() as $option) {
					echo '<option value="' . $option .'">' . $option . '</option>';
				}
			?>
		</select> <label for="choiceWWS">@wycliffe-services.net &gt;</label></div>
	</div>
	<div class="cell rightCell">
		<br />
		<div class="header"><label for="choiceWWS"><?php echo t("Reply-to") ?>:</label></div>
		<div class="cell"><input type="text" name="replyTo" id="replyTo" /></div>
	</div>
</div>
<div class="row">
	<div class="header"><?php echo t("To") ?>:</div>
	<div class="cell">
		<input type="radio" class="radio" name="choiceTo" id="choiceEmail" checked /> <input type="text" name="to" id="to" /><br />
		<input type="radio" class="radio" name="choiceTo" id="choiceFile" /> <input type="file" name="toEmailFile" id="toEmailFile" size="40" /> <a href="mailing_list.csv">mailing list template</a>
			<div id="startMaxRow"><label for="choiceFile"><?php echo t("Start row") ?>:</label> <input type="text" name="startRow" id="startRow" /> <label for="choiceFile" class="inlineLabel"><?php echo t("Max rows") ?>:</label> <input type="text" name="maxRows" id="maxRows" /></div>
	</div>
	<div class="cell rightCell">
		<br />
		<div class="header"><label for="choiceFile"><?php echo t("Tags"); ?>:</label></div>
		<div class="cell"><input type="text" name="tags" id="tags" /></div>
	</div>
</div>
<div class="row">
	<div class="header"><?php echo t("Cc") ?>:</div>
	<div class="cell"><input type="text" name="cc" id="cc" /></div>
	<div class="cell rightCell">
		<input type="radio" class="radioSimulate" name="choiceSimulate" id="choiceSend" value="0" checked /><label for="choiceSend"> <?php echo t("Send") ?></label>
		<input type="radio" class="radioSimulate" name="choiceSimulate" id="choiceSimulate" value="2" /><label for="choiceSimulate"> <?php echo t("Simulate") ?></label>
		<input type="radio" class="radioSimulate" name="choiceSimulate" id="choiceRegression" value="1" /><label for="choiceRegression"> <?php echo t("Regression test") ?></label>
	</div>
</div>
<div class="row">
	<div class="header"><?php echo t("Bcc"); ?>:</div>
	<div class="cell"><input type="text" name="bcc" id="bcc" /></div>
	<div class="cell rightCell">
		<button type="submit"><?php echo t("Submit"); ?><div id="spinner"></div></button>
	</div>
</div>
<div class="row">
	<div class="header"><?php echo t("Subject"); ?>:</div>
	<div class="cell"><input type="text" name="subject" id="subject" /></div>
</div>
<div class="row">
	<div class="header"><?php echo t("Body"); ?>:</div>
	<div class="cell"><textarea name="body" id="body"></textarea></div>
	
	<div class="cell rightCell" id="attachmentsDiv">
		<h1><?php echo t("Attachments"); ?>:</h1>
		1. <input type="file" name="file1" id="file1" class="attachment" /><br />
		2. <input type="file" name="file2" id="file2" class="attachment" /><br />
		3. <input type="file" name="file3" id="file3" class="attachment" /><br />
		4. <input type="file" name="file4" id="file4" class="attachment" /><br />
		5. <input type="file" name="file5" id="file5" class="attachment" /><br />
		6. <input type="file" name="file6" id="file6" class="attachment" /><br />
		7. <input type="file" name="file7" id="file7" class="attachment" /><br />
		8. <input type="file" name="file8" id="file8" class="attachment" /><br />
		9. <input type="file" name="file9" id="file9" class="attachment" />
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
?>
