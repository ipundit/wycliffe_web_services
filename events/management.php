<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<title>Wycliffe Web Services event event registration</title>
<link rel="stylesheet" href="/jquery-ui/jquery-ui.css" />
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
#spinner, #spinnerInvitation, #spinnerLogistics {
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
<script language='JavaScript' type='text/javascript' src='../jquery-ui/jquery-ui.min.js'></script>

<script language='JavaScript' type='text/javascript' src='management.js'></script>
<script language='JavaScript' type='text/javascript'>
<?php
	require_once 'translation.php';
	echo 'index_js_init({' . configureForLang(300) . '});';
	$eventName = isset($_GET['eventName']) ? $_GET['eventName'] : '';
	$userName = isset($_GET['userName']) ? $_GET['userName'] : '';
	$password = isset($_GET['password']) ? $_GET['password'] : '';
	$queryString = "eventName=".urlencode($eventName)."&amp;userName=".urlencode($userName).'&amp;password='.$password;
	$fromEmail = isset($_GET['fromEmail']) ? $_GET['fromEmail'] : '';
	$name = isset($_GET['name']) ? $_GET['name'] : '';
	echo 'var participants = ' . readFromDatabase($userName, $password) . ';';
?>

$(function() {
	$("#participants").autocomplete({
		source: participants,
		focus: function( event, ui ) {
            $("#participants").val(ui.item.label);
            return false;  
        },
		select: function( event, ui ) {
			event.preventDefault();
			alert(ui.item.value);
		}
	});
});

</script>
</head>
<body>
<h1><?php echo t("Logistics menu for") . ' ' . $eventName ?></h1>
<form id="theForm" action="#" method="post">
<div id="errorAnchor" class="error"></div>
You can:
<ol>
  <li><a href="<?php echo "webservice.php?report=download&amp;" . $queryString ?>">Download the current participant list</a> to get a real time report of who has confirmed their attendance for your event</li>
  <li>Update <a href="<?php echo "webservice.php?report=download&amp;" . $queryString ?>">that list</a> in any spreadsheet program that can open .csv files, like Excel or LibreOffice Calc</li>
  <li>Upload your updated list to the server to replace its contents<input type="file" name="file" id="file" /><button type="submit" id="upload"><?php echo t("Upload"); ?><div id="spinner"></div></button></li>
  <li><a href="javascript:void(0)" id="invitation">Send out the invitation email</a> to the participants in that list<div id="spinnerInvitation"></div></li>
  <li><a href="javascript:void(0)" id="logistics">Send out the logistics email</a> to the participants in that list<div id="spinnerLogistics"></div></li>
  <li><label for="participants">Update a participant: </label><input id="participants"></li>
</ol>
<input type="hidden" id="eventName" value="<?php echo $eventName; ?>" />
<input type="hidden" id="userName" value="<?php echo $userName ?>" />
<input type="hidden" id="password" value="<?php echo $password ?>" />
<input type="hidden" id="report" value="upload" />
<input type="hidden" id="fromEmail" value="<?php echo $fromEmail ?>" />
<input type="hidden" id="name" value="<?php echo $name ?>" />
</form>
<body>
</body>
</html>

<?php
function readFromDatabase($userName, $password) {
	require_once('util.php');
	require_once('classes/Participant.php');
	
	$participants = Participant::getParticipants($userName, $password);
	if ($participants === false) { return ''; }
	
	$arr = array();
	foreach ($participants as $key => $value) {
		$arr[] = $value . '", value: "' . $key;
	}
	return '[{label: "' . implode('"},{label: "', $arr) . '"}]';
}
?>
