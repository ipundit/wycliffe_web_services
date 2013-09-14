<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<title>Event name event registration</title>
<style type="text/css">
fieldset { 
	width: 550px;
	padding: 10px 4px 10px 10px;
}
legend {
	font-weight: bold;
	font-size: 18px;
}

.row { height: 44px; }
#comingRow { height: 30px; } 
.column { float: left; }
.rowLabel {
	width: 64px;
	text-align: right;
	margin-right: 10px;
}
.verticalLabel {
	display: block;
	color: #0000AA;
	display: block;
	font-size: 12px;
	text-align: center;
}

.flightTextField { width: 152px; }
#honorific { width: 80px; }
.nameTextField { width: 188px; }
.contactTextField { width: 230px; }
#passportName { width: 464px; }

textarea {
	height: 70px;
	width: 536px;
}

button {
	height: 43px;
	margin-top: 7px;
	border-width: 2px;
	font-weight: bold;
	font-size: 23px;
	width: 570px;
}

@-moz-document url-prefix() {
	fieldset { padding: 10px; }
	#passportName { width: 468px; }
	.contactTextField { width: 231px; }
	textarea { width: 542px; }
	button { width: 575px; }
}
@media screen and (-webkit-min-device-pixel-ratio:0) { /* Chrome */
	fieldset { 
		width: 545px;
		padding: 10px;
	}
	button { width: 571px; }
}

#spinner {
    background-image: url("../../spinner.gif");
    background-repeat: no-repeat;
    display: none;
    height: 16px;
    margin: 4px 0 0 7px;
    width: 16px;
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

<script language='JavaScript' type='text/javascript' src='../../jquery-1.10.2.min.js'></script>
<script language='JavaScript' type='text/javascript' src='../../jquery.validate.min.js'></script>
<script language='JavaScript' type='text/javascript' src='index.js'></script>
<script language='JavaScript' type='text/javascript'>
<?php
	require_once 'translation.php';
	echo 'index_js_init({' . configureForLang(500) . '})';
?>
</script>
</head>
<body>
<?php
	$row = readFromDatabase();
	if ($row === false) { return; }
?>

<h1>Registration for <?php echo($row->eventName) ?></h1>
<form id="theForm" action="#" method="post">
<div id="errorAnchor" class="error"></div>
<fieldset>
<legend>Arrival / Departure Information</legend>
<div class="row" id="comingRow">
<div class="column rowLabel">
	<label>Coming?</label>
</div>
<div class="column">
	<input type="radio" id="comingYes" name="isComing" value="1" <?php if ($row->iscoming == 1) { echo("checked"); } ?> /> <label for="comingYes">Yes</label>
</div>
<div class="column">
	<input type="radio" id="comingNo" name="isComing" value="0" <?php if ($row->iscoming == 0) { echo("checked"); } ?> /> <label for="comingNo">No</label>
</div>
<div class="column">
	<input type="radio" id="comingUnsure" name="isComing" value="2" <?php if ($row->iscoming == 2) { echo("checked"); } ?> /> <label for="comingUnsure">Unsure</label>
</div>
</div>
<div class="row">
<div class="column rowLabel">
	<label>Arrival</label>
</div>
<div class="column">
	<input type="text" id="arrivalFlightNumber" name="arrivalFlightNumber" class="flightTextField" value="<?php echo($row->arrivalflightnumber) ?>" />
	<label class="verticalLabel">Flight number</label>
</div>
<div class="column">
	<input type="text" id="arrivalDate" name="arrivalDate" class="flightTextField" value="<?php echo($row->arrivaldate) ?>" />
	<label class="verticalLabel">Date yyyy-mm-dd</label>
</div>
<div class="column">
	<input type="text" id="arrivalTime" name="arrivalTime" class="flightTextField" value="<?php echo($row->arrivaltime) ?>" />
	<label class="verticalLabel">24 hour time hh:mm</label>
</div>
</div>
<div class="row">
<div class="column rowLabel">
	<label>Departure</label>
</div>
<div class="column">
	<input type="text" id="departureFlightNumber" name="departureFlightNumber" class="flightTextField" value="<?php echo($row->departureflightnumber) ?>" />
	<label class="verticalLabel">Flight number</label>
</div>
<div class="column">
	<input type="text" id="departureDate" name="departureDate" class="flightTextField" value="<?php echo($row->departuredate) ?>" />
	<label class="verticalLabel">Date yyyy-mm-dd</label>
</div>
<div class="column">
	<input type="text" id="departureTime" name="departureTime" class="flightTextField" value="<?php echo($row->departuretime) ?>" />
	<label class="verticalLabel">24 hour time hh:mm</label>
</div>
</div>
</fieldset>
<fieldset>
<legend>Name Tag and Contact Information</legend>
<div class="row">
<div class="column rowLabel">
	<label>Name</label>
</div>
<div class="column">
	<input type="text" id="honorific" name="honorific" value="<?php echo($row->honorific) ?>" />
	<label class="verticalLabel">eg) Dr. Rev. Pdt.</label>
</div>
<div class="column">
	<input type="text" id="firstName" name="firstName" class="nameTextField" value="<?php echo($row->firstname) ?>" />
	<label class="verticalLabel">First name</label>
</div>
<div class="column">
	<input type="text" id="lastName" name="lastName" class="nameTextField" value="<?php echo($row->lastname) ?>" />
	<label class="verticalLabel">Last name</label>
</div>
</div>
<div class="row">
<div class="column rowLabel">
	<label>Work</label>
</div>
<div class="column">
	<input type="text" id="organization" name="organization" class="contactTextField" value="<?php echo($row->organization) ?>" />
	<label class="verticalLabel">Organization</label>
</div>
<div class="column">
	<input type="text" id="title" name="title" class="contactTextField" value="<?php echo($row->title) ?>" />
	<label class="verticalLabel">Title</label>
</div>
</div>
<div class="row">
<div class="column rowLabel">
	<label>Contact</label>
</div>
<div class="column">
	<input type="text" id="email" name="email" class="contactTextField" value="<?php echo($row->email) ?>" />
	<label class="verticalLabel">Email</label>
</div>
<div class="column">
	<input type="text" id="phone" name="phone" class="contactTextField" value="<?php echo($row->phone) ?>" />
	<label class="verticalLabel">Cell phone number</label>
</div>
</div>
</fieldset>
<?php
$str = <<<STR
<fieldset>
<legend>Passport Information for Visa Invitation Letter</legend>
<div class="row">
<div class="column rowLabel">
	<label>Passport</label>
</div>
<div class="column">
	<input type="text" id="passportNumber" name="passportNumber" class="flightTextField" value="$row->passportnumber" />
	<label class="verticalLabel">Number</label>
</div>
<div class="column">
	<input type="text" id="passportExpiryDate" name="passportExpiryDate" class="flightTextField" value="$row->passportexpirydate" />
	<label class="verticalLabel">Expiry date yyyy-mm-dd</label>
</div>
<div class="column">
	<input type="text" id="passportCountry" name="passportCountry" class="flightTextField" value="$row->passportcountry" />
	<label class="verticalLabel">Issuing country</label>
</div>
</div>
<div class="row">
<div class="column rowLabel">
	<label>Name</label>
</div>
<div class="column">
	<input type="text" id="passportName" name="passportName" value="$row->passportname" />
	<label class="verticalLabel">Your name as it appears in your passport</label>
</div>
</div>
</fieldset>
STR;
if ($row->needvisa) { echo $str; }
?>
<fieldset>
<legend>Notes and Special Instructions</legend>
<textarea id="notes" id="name"><?php echo($row->notes) ?></textarea>
</fieldset>

<input type="hidden" id="id" name="id" value="<?php echo($row->id) ?>" />
<button type="submit"><?php echo t("Submit"); ?><div id="spinner"></div></button>
</form>
</body>
</html>

<?php
function readFromDatabase() {
	require_once 'util.php';
	
	if (isset($_GET['id'])) {
		require_once 'classes/DatabaseConstants.php';
		$params = array(
			'id' => $_GET['id'],
			'userName' => EVENT_USERNAME,
			'password' => EVENT_PASSWORD,
		);
		if (isset($_GET['isComing'])) { $params['isComing'] = $_GET['isComing']; }
		$ch = util::curl_init("https://wycliffe-services.net/events/webservice_participant.php", $params);
		$result = curl_exec($ch);
	} else {
		$result = '{"error":"invalid id"}';
	}
	$result = json_decode($result);
	$result->eventName = EVENT_USERNAME;
	foreach ($result as &$value) {
		$value = str_replace('"', "&quot;", $value);
	}

	if ($result->error == 'ok') { return $result; }
	if ($result->error == "invalid id" || $result->error == 'id not found') {
		echo t("Sorry, we cannot find your event registration. Please contact your event coordinator to get a new invitation email.");
	} else {
		echo $result->error;
	}
	return false;
}
?>
