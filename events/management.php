<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<title>Wycliffe Web Services event event registration</title>
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

#file { margin-left: 10px; }

button {
	margin-left: 10px;
}
#spinner {
    background-image: url("../spinner.gif");
    background-repeat: no-repeat;
    display: none;
    height: 16px;
    margin: 4px 0 0 7px;
    width: 16px;
}
</style>

<script language='JavaScript' type='text/javascript' src='../jquery-1.10.2.min.js'></script>
<script language='JavaScript' type='text/javascript' src='../jquery.validate.min.js'></script>
<script language='JavaScript' type='text/javascript' src='management.js'></script>
<script language='JavaScript' type='text/javascript'>
<?php
	require_once 'translation.php';
	echo 'index_js_init({' . configureForLang(300) . '})';
	$eventName = isset($_GET['eventName']) ? $_GET['eventName'] : '';
	$userName = isset($_GET['userName']) ? $_GET['userName'] : '';
	$password = isset($_GET['password']) ? $_GET['password'] : '';
?>
</script>
</head>
<body>
<h1><?php echo t("Logistics menu for") . ' ' . $eventName ?></h1>
<form id="theForm" action="#" method="post">
<div id="errorAnchor" class="error"></div>
Tasks:
<ol>
  <li><a href="<?php echo "webservice.php?report=download&eventName=".$eventName."&userName=".$userName.'&password='.$password ?>" id="downloadLink">Download the current participant list</a> to get a real time report of who has confirmed their attendance for your event</li>
  <li>Update that list in any spreadsheet program that can open .csv files, like Excel or LibreOffice Calc</li>
  <li>Upload that updated list to the server to replace its contents<input type="file" name="file" id="file" /><button type="submit" id="upload"><?php echo t("Upload"); ?><div id="spinner"></div></button></li>
  <li><a href="">Send out the invitation email</a> to those participants</li>
  <li><a href="">Send out the logistics email</a> to those participants</li>
</ol>
<input type="hidden" id="eventName" value="<?php echo $eventName; ?>" />
<input type="hidden" id="userName" value="<?php echo $_GET['userName'] ?>" />
<input type="hidden" id="password" value="<?php echo $_GET['password'] ?>" />
</form>
</body>
</html>

<?php
function readFromDatabase() {
	require_once('util.php');
	require_once('classes/Events.php');
	$arr = array();
	
	foreach ($arr as &$value) {
		util::removeAfter($value, '@');
	}
	return $arr;
}
?>
