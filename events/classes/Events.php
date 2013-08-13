<?php 
require_once 'util.php';
define("_EMAIL_ASCII_TAB_", 9);
define('_EVENTS_TIMEOUT_', 300);

class Events
{
	public static function main(&$msg) {
		$row = Events::validateInput($_POST, $msg);
		if ($msg != '') { return; }

		if (Events::createNewEvent($row, $msg)) { return; }
	}

	private static function validateInput($data, &$msg) {
		if (!isset($data['simulate'])) { $data['simulate'] = 0; }

		$filters = array(
		  "fromEmail"=>FILTER_SANITIZE_EMAIL,
		  "name"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "eventName"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "forwardingEmail"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "simulate"=>array('filter'=>FILTER_VALIDATE_INT, 'options'=>array("min_range"=>0, "max_range"=>1)),
		);
		$row = filter_var_array($data, $filters);

		foreach ($row as &$value) {
			$value = trim($value);
		}
		return $row;
	}
	
	private static function createNewEvent($row, &$msg) {
		if ($row['eventName'] == '') { return false; }

		if ($row['name'] == '') {
			$msg = 'invalid name';
			return true;
		}
		if ($row['fromEmail'] == '') {
			$msg = 'invalid fromEmail';
			return true;
		}
		
		$name = $row['name'];
		$fromEmail = $row['fromEmail'];
		$eventName = $row['eventName'];
		$forwardingEmail = $row['forwardingEmail'];
		
		$body = <<<BODY
We received an events account creation request.<br>
<br>
<b>Name:</b> $name<br>
<b>Email:</b> $fromEmail<br>
<b>Event name:</b> $eventName<br>
<b>Forwarding email:</b> $forwardingEmail<br>
<br>
1. Run /home/sysadmin/add_events_account.sh event_name_with_no_spaces_less_than_17_chars<br>
2. Manually create the email account, possibly forwarding it in courier
3. Send <a href="mailto:events@wycliffe-services.net?subject=Created Wycliffe Web Services events account&body=Name: $name%0D%0AEmail: $fromEmail%0D%0AEvent name: $eventName%0D%0AForwarding email: $forwardingEmail%0D%0AMySQL user name: %0D%0AMySQL password:">configuration email</a> to user
BODY;
		util::sendEmail($msg, "", "no-reply@wycliffe-services.net", "developer_support@wycliffe-services.net", 
			"Events account creation request", $body, '', '', '', array(), $row['simulate']);

		$body = <<<BODY
Dear $name,<br>
<br>
Your request to create an events account for <b>$eventName</b> has been received and will be processed shortly.
BODY;
		util::sendEmail($msg, "", "no-reply@wycliffe-services.net", $fromEmail, 
			"Received events account creation request", $body, '', '', '', array(), $row['simulate']);
		
		$msg = 'ok';
		return true;
	}
}
?>
