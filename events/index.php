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
	// $row['eventName']
	// $row['userName']
	// $row['password'] - how do I get this securely?  From userName, read password from classes/events_constants.php u+rx.  shell script can append to this file
	// $row['id']
	// $row['email']
	// $row['phone'], etc.
?>

<h1>Registration for <?php echo($row->eventName) ?></h1>
<form id="theForm" action="#" method="post">
<div id="errorAnchor" class="error"></div>
<fieldset>
<legend>Arrival / departure information</legend>
<div class="row" id="comingRow">
<div class="column rowLabel">
	<label>Coming?</label>
</div>
<div class="column">
	<input type="radio" id="comingYes" value="1" /> <label for="comingYes">Yes</label>
</div>
<div class="column">
	<input type="radio" id="comingNo" value="0" /> <label for="comingNo">No</label>
</div>
<div class="column">
	<input type="radio" id="comingUnsure" value="2" /> <label for="comingUnsure">Unsure</label>
</div>
</div>
<div class="row">
<div class="column rowLabel">
	<label>Arrival</label>
</div>
<div class="column">
	<input type="text" id="arrivalFlightNumber" class="flightTextField" value="<?php echo($row->arrivalflightnumber) ?>" />
	<label class="verticalLabel">Flight number</label>
</div>
<div class="column">
	<input type="text" id="arrivalDate" class="flightTextField" value="<?php echo($row->arrivaldate) ?>" />
	<label class="verticalLabel">Date mm/dd</label>
</div>
<div class="column">
	<input type="text" id="arrivalTime" class="flightTextField" value="<?php echo($row->arrivaltime) ?>" />
	<label class="verticalLabel">24 hour time hh:mm</label>
</div>
</div>
<div class="row">
<div class="column rowLabel">
	<label>Departure</label>
</div>
<div class="column">
	<input type="text" id="departureFlightNumber" class="flightTextField" value="<?php echo($row->departureflightnumber) ?>" />
	<label class="verticalLabel">Flight number</label>
</div>
<div class="column">
	<input type="text" id="arrivalDate" class="flightTextField" value="<?php echo($row->departuredate) ?>" />
	<label class="verticalLabel">Date mm/dd</label>
</div>
<div class="column">
	<input type="text" id="departureTime" class="flightTextField" value="<?php echo($row->departuretime) ?>" />
	<label class="verticalLabel">24 hour time hh:mm</label>
</div>
</div>
</fieldset>
<fieldset>
<legend>Name tag and Contact Information</legend>
<div class="row">
<div class="column rowLabel">
	<label>Name</label>
</div>
<div class="column">
	<input type="text" id="honorific" value="<?php echo($row->honorific) ?>" />
	<label class="verticalLabel">eg) Dr. Rev. Pdt.</label>
</div>
<div class="column">
	<input type="text" id="firstName" class="nameTextField" value="<?php echo($row->firstname) ?>" />
	<label class="verticalLabel">First name</label>
</div>
<div class="column">
	<input type="text" id="lastName" class="nameTextField" value="<?php echo($row->lastname) ?>" />
	<label class="verticalLabel">Last name</label>
</div>
</div>
<div class="row">
<div class="column rowLabel">
	<label>Work</label>
</div>
<div class="column">
	<input type="text" id="organization" class="contactTextField" value="<?php echo($row->organization) ?>" />
	<label class="verticalLabel">Organization</label>
</div>
<div class="column">
	<input type="text" id="title" class="contactTextField" value="<?php echo($row->title) ?>" />
	<label class="verticalLabel">Title</label>
</div>
</div>
<div class="row">
<div class="column rowLabel">
	<label>Contact</label>
</div>
<div class="column">
	<input type="text" id="email" class="contactTextField" value="<?php echo($row->email) ?>" />
	<label class="verticalLabel">Email</label>
</div>
<div class="column">
	<input type="text" id="phone" class="contactTextField" value="<?php echo($row->phone) ?>" />
	<label class="verticalLabel">Cell phone number</label>
</div>
</div>
</fieldset>
<fieldset>
<legend>Passport Information for visa invitation letter</legend>
<div class="row">
<div class="column rowLabel">
	<label>Passport</label>
</div>
<div class="column">
	<input type="text" id="passportNumber" class="flightTextField" value="<?php echo($row->passportnumber) ?>" />
	<label class="verticalLabel">Number</label>
</div>
<div class="column">
	<input type="text" id="passportExpiryDate" class="flightTextField" value="<?php echo($row->passportexpirydate) ?>" />
	<label class="verticalLabel">Expiry date yyyy/mm/dd</label>
</div>
<div class="column">
	<input type="text" id="country" class="flightTextField" value="<?php echo($row->country) ?>" />
	<label class="verticalLabel">Issuing country</label>
</div>
</div>
<div class="row">
<div class="column rowLabel">
	<label>Name</label>
</div>
<div class="column">
	<input type="text" id="passportName" value="<?php echo($row->passportname) ?>" />
	<label class="verticalLabel">Your name as it appears in your passport</label>
</div>
</div>
</fieldset>
<fieldset>
<legend>Notes and special instructions</legend>
<textarea id="notes"><?php echo($row->notes) ?></textarea>
</fieldset>

<input type="hidden" id="id" value="<?php echo($row->id) ?>" />
<button type="submit"><?php echo t("Submit"); ?><div id="spinner"></div></button>
</form>
<?php	util::dump($row); ?>

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
		$ch = util::curl_init("https://wycliffe-services.net/events/webservice_participant.php", $params);
		$result = curl_exec($ch);
	} else {
		$result = '{"error":"invalid id"}';
	}
	$result = json_decode($result);
	$result->eventName = EVENT_USERNAME;
	$result->passportname = 'fixme';
	$result->arrivaldate = 'fixme';
	$result->arrivaltime = 'fixme';
	$result->departuredate = 'fixme';
	$result->departuretime = 'fixme';
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
