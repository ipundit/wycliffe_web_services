<?php 
require_once 'util.php';
require_once 'classes/Participant.php';
define('_EVENTS_TIMEOUT_', 300);

class Events
{
	public static function main(&$msg) {
		$arr = empty($_POST) ? $_GET : $_POST;
		$row = Events::validateInput($arr, $msg);
		if ($msg != '') { return; }

		if (Events::processReport($row, $msg)) { return; }
		if (Events::createNewAccount($row, $msg)) { return; }
		Events::createNewEvent($row, $msg);
	}

	private static function validateInput($data, &$msg) {
		if (!isset($data['simulate'])) { $data['simulate'] = 0; }

		$filters = array(
		  "fromEmail"=>FILTER_VALIDATE_EMAIL,
		  "firstName"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "name"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "eventName"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "forwardingEmail"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "clientName"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "clientEmail"=>FILTER_VALIDATE_EMAIL,
		  "userName"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "password"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "report"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "simulate"=>array('filter'=>FILTER_VALIDATE_INT, 'options'=>array("min_range"=>0, "max_range"=>1)),
		);
		$row = filter_var_array($data, $filters);

		foreach ($row as &$value) {
			$value = trim($value);
		}
		return $row;
	}
	
	private static function processReport($row, &$msg) {
		switch ($row['report']) {
		case '':
			return false;
		case 'download':
		case 'email':
		case 'invitation':
		case 'logistics':
		case 'upload':
			$report = $row['report'];
			break;
		default:
			$msg = 'invalid report';
			return true;
		}
		
		if ($row['eventName'] == '') {
			$msg = 'eventName is missing';
			return true;
		}
		$eventName = $row['eventName'];
		
		if ($row['userName'] == '') {
			$msg = 'userName is missing';
			return true;
		}
		$userName = $row['userName'];
		
		if ($row['password'] == '') {
			$msg = 'password is missing';
			return true;
		}
		$password = $row['password'];
		
		$participant = new Participant($row['userName'], $row['password'], $msg);
		if ($msg != '') { return true; }
		
		$path = $participant->reportCSV($msg);
		if ($path === false) { return true;	}

		switch ($report) {
		case 'download':
			if ($row['simulate'] == 1) {
				$msg = file_get_contents($path);
			} else {
				header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename='.basename($path));
				header('Content-Transfer-Encoding: binary');
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . filesize($path));
				ob_clean();
				flush();
				readfile($path);
			}
			break;
		case 'email':
			if ($row['fromEmail'] == '') {
				$msg = 'invalid fromEmail';
				return true;
			}
			
			$files = array();
			$files['mailing_list.csv'] = $path;
			$body = <<<BODY
Attached is the latest participant list for the <b>$eventName</b>. Reply to this email with an updated <b>mailing_list.csv</b> to overwrite the server copy.<br>
<br>
Event name: $eventName<br>
User name: $userName<br>
Password: $password<br>
report: upload<br>
BODY;
			util::sendEmail($msg, '', "events@wycliffe-services.net", $row["fromEmail"], "Re: Get the latest participant list for $eventName", 
							$body, '', '', '', $files, $row['simulate']);
			break;
		case 'upload':
			
		case 'invitation':
		case 'logistics':
		}
		
		util::deltree(dirname($path));
		return true;
	}
		
	private static function createNewAccount($row, &$msg) {
		if ($row['clientName'] == '') { return false; }

		if ($row['eventName'] == '') {
			$msg = 'eventName is missing';
			return true;
		}
		if ($row['clientEmail'] == '') {
			$msg = 'invalid client email';
			return true;
		}
		if ($row['userName'] == '') {
			$msg = 'userName is missing';
			return true;
		}
		if ($row['password'] == '') {
			$msg = 'password is missing';
			return true;
		}
		
		$eventName = $row['eventName'];
		$clientName = $row['clientName'];
		$userName = $row['userName'];
		$password = $row['password'];
		
		$body = <<<BODY
Dear $clientName,<br>
<br>
Your Wycliffe Web Services events account for the <b>$eventName</b> has been created. You can now:<br>
<br>
1. <a href="http://wycliffe-services.net/events/webservice.php?eventName=$eventName&userName=$userName&password=$password&report=download">Download</a> the latest participant list. You can click this link at any time to get a real-time report of who has confirmed their attendance for your event. Alternatively, you can have the report <a href="mailto:events@wycliffe-services.net?subject=Get the latest participant list for $eventName&body=Just click send to get the latest participant list.%0D%0A%0D%0AEvent name: $eventName%0D%0AUser name: $userName%0D%0APassword: $password%0D%0Areport: email">emailed</a> to you.<br>
2. Update the participant tracking list, and then <a href="http://wycliffe-services.net/events/management.php?eventName=$eventName&userName=$userName&password=$password">upload it to the server</a> or <a href="mailto:events@wycliffe-services.net?subject=Update participant list for $eventName&body=Attach mailing_list.csv to this email and click send. Warning: Your existing participant list database on the server will be overwritten with the contents of mailing_list.csv, so make sure that it is based on the latest server version. However, the old contents of mailing_list.csv will be emailed to you, in case you need to revert.%0D%0A%0D%0AEvent name: $eventName%0D%0AUser name: $userName%0D%0APassword: $password%0D%0Areport: upload">email</a> it.<br>
3. <a href="mailto:events@wycliffe-services.net?subject=Get the invitation email template&body=Just click send to get the invitation email template.%0D%0A%0D%0AEvent name: $eventName%0D%0AUser name: $userName%0D%0APassword: $password%0D%0Areport: invitation">Send</a> out the invitation email.<br>
4. <a href="mailto:events@wycliffe-services.net?subject=Get the logistics email template&body=Just click send to get the logistics email template.%0D%0A%0D%0AEvent name: $eventName%0D%0AUser name: $userName%0D%0APassword: $password%0D%0Areport: logistics">Send</a> out the logistics email.
BODY;
		util::sendEmail($err1, "", "events@wycliffe-services.net", $row['clientEmail'], 
			"Logistics menu for " . $row['eventName'], $body, '', '', '', array(), $row['simulate']);
		
		util::sendEmail($err2, "", "no-reply@wycliffe-services.net", 'developer_support@wycliffe-services.net', 
			"Logistics menu email sent for " . $row['eventName'], 'Email sent to user successfully', '', '', '', array(), $row['simulate']);

		$msg = $err1 . $err2;
		if ($msg == '') { $msg = 'ok'; }
		return true;
	}
	
	private static function createNewEvent($row, &$msg) {
		if ($row['eventName'] == '') { 
			$msg = 'invalid eventName';
			return false;
		}
		if ($row['name'] == '') {
			$msg = 'invalid name';
			return false;
		}
		if ($row['fromEmail'] == '') {
			$msg = 'invalid fromEmail';
			return false;
		}
		
		$name = $row['name'];
		$fromEmail = $row['fromEmail'];
		$eventName = trim($row['eventName']);
		$forwardingEmail = $row['forwardingEmail'];
		
		$createdEmail = strtolower(str_replace(' ', '_', $eventName)) . '@wycliffe-services.net';
		$shortName = Events::generateAcronym($eventName);
		
		$body = <<<BODY
We received an events account creation request.<br>
<br>
<b>Client name:</b> $name<br>
<b>Client email:</b> $fromEmail<br>
<b>Event name:</b> $eventName<br>
<br>
1. Run /home/sysadmin/add_events_account.sh $shortName $name $fromEmail<br>
2. Send <a href="mailto:events@wycliffe-services.net?subject=Created Wycliffe Web Services events account&body=Client name: $name%0D%0AClient email: $fromEmail%0D%0AEvent name: $eventName%0D%0AUser name: $shortName%0D%0APassword: ">configuration email</a> to user
BODY;
		util::sendEmail($err1, "", "no-reply@wycliffe-services.net", "developer_support@wycliffe-services.net", 
			"Events account creation request", $body, '', '', '', array(), $row['simulate']);

		$body = <<<BODY
Dear $name,<br>
<br>
Your request to create an events account for <b>$eventName</b> has been received and will be processed shortly.
BODY;
		util::sendEmail($err2, "", "no-reply@wycliffe-services.net", $fromEmail, 
			"Received events account creation request", $body, '', '', '', array(), $row['simulate']);
		
		$msg = $err1 . $err2;
		if ($msg == '') { $msg = 'ok'; }
		return true;
	}

	private static function generateAcronym($str) {
		$arr = explode(' ', $str);
		foreach($arr as &$value) {
			if (!is_numeric($value)) {
				$value = substr($value, 0, 1);
			}
		}
		return implode('', $arr);
	}
}
?>
