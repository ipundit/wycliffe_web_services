<?php 
require_once 'util.php';
require_once 'classes/Participant.php';
define('_EVENTS_TIMEOUT_', 300);

class Events
{
	public static function main(&$msg) {
		$tempDir = util::saveAllFiles();
		try {
			Events::mainImpl($tempDir, $msg);
		} catch (Exception $e) {}
		util::deltree($tempDir);
	}
	
	private static function mainImpl($tempDir, &$msg) {
		$arr = empty($_POST) ? $_GET : $_POST;
		$row = Events::validateInput($arr, $msg);
		if ($msg != '') { return; }

		if (Events::processReport($tempDir, $row, $msg)) { return; }
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
	
	private static function processReport($tempDir, $row, &$msg) {
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
		
		$path = $participant->reportCSV($tempDir, $msg);
		if ($path === false) { return true;	}

		switch ($report) {
		case 'download':
			Events::processDownload($path, $row['simulate'], $msg);
			break;
		case 'email':
			if ($row['fromEmail'] == '') {
				$msg = 'invalid fromEmail';
				break;
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
			$str = Events::readFile($msg);

			if ($str === false) { break; }
			
			if ($participant->overwriteDatabase($str, $row['simulate'], $msg)) {
				if ($row['simulate'] == 1) {
					Events::processDownload($path, $row['simulate'], $msg);
				} else {
					$msg = 'ok';
				}
				
				if ($row['fromEmail'] != '' && $row['name'] != '') {
					$body = <<<BODY
Dear {$row['name']},<br>
<br>
Your mailing list upload for <b>$eventName</b> completed with this message: <b>$msg</b>
BODY;
					util::sendEmail($msg, "", "no-reply@wycliffe-services.net", $row['fromEmail'], 
						"Mailing list upload completed for " . $eventName, $body, '', '', '', array(), $row['simulate']);				
				}
			}
			break;
		case 'invitation':
			$name = $row['clientName'];
			$fromEmail = $row['clientEmail'];
			$body = <<<BODY
Dear $name,<br>
<br>
This mail merge program will send a personalized invitation to each person in your mailing list.  Reply to this email and attach the <b>mailing_list.csv</b> participant list you created earlier. Then fill out the below template and click send. Text that starts with a $ will be replaced by the corresponding value for each person in mailing_list.csv.<br>
<br>
<b>Your name:</b> $name<br>
<b>Your email:</b> $fromEmail<br>
<b>Subject:</b> Invitation to $eventName<br>
<br>
<b>Body->:</b><br>
Dear \$honorific \$firstName,<br>
<br>
I would like to personally invite you to the <b>$eventName</b>.  It will be in [city] from [start date] to [end date].  If you can make it, please click <a href="https://wycliffe-services.net/event/$userName/">here</a> to confirm your attendance.  It will bring you to a website where you can enter your registration information.<br>
<br>
Regards,<br>
$name<br>
<b>->Body:</b> # everything between <b>Body</b> will be counted as the body of your email
<h4>Filling out the rest of the form is optional.</h4>
<b>Simulate:</b> 0 # 0 to actually send the email, or 1 to run through all the checks but not actually send the email. simulate = 2 will output the email(s) to http://www.wycliffe-services.net/email/dryRun.html instead of emailing them out. This allows you to check the mass mailing before sending it out (recommended).
<h4>Other receipients</h4>
<b>Cc:</b> # A comma separated list of emails<br>
<b>Bcc:</b> # A comma separated list of emails<br>
<b>Reply-to:</b> # An email address where you want replies to your email to go to.  Otherwise, they will go to $fromEmail
<h4>Mailing list filter settings</h4>
<b>Tags:</b> # Tags will be compared to \$tags in the mailing list.  If there is a match, the email will be sent. If tags is not set, then all rows will be sent.<br>
<b>Starting row:</b> 1 # Email processing will start on the Starting row you specify here.  Useful for long mailing scripts that may have timed out in the middle of processing, so you can start mailing again from Starting row.  Rows start from 1, so specifying 1 here will mean processing the whole mailing list file.<br>
<b>Maximum number of rows to process:</b> 0 # Email processing will process a maximum number of rows to process. 0 means that there is no limit to the number of rows that will be processed, and the mailing will only quit if there is an error or the script times out.
BODY;
			$files = array();
			$files['mailing_list.csv'] = $path;
			util::sendEmail($msg, "", "email@wycliffe-services.net", $fromEmail, 
				"Invitation email template for " . $eventName, $body, '', '', '', array(), $row['simulate']);
			if ($msg == '') { $msg = 'ok'; }
			break;
		case 'logistics':
			$msg = 'ok';
		}
		
		return true;
	}

	private static function processDownload($path, $simulate, &$msg) {
		if ($simulate == 1) {
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
	}
	
	private static function readFile(&$msg) {
		if (!array_key_exists('file', $_FILES)) {
			$msg = 'missing file';
			return false;
		}
		$file = $_FILES['file'];
		
		if (!util::endsWith($file['name'], '.csv') || !filter_var($file['name'], FILTER_SANITIZE_STRING, array('flags'=>FILTER_FLAG_NO_ENCODE_QUOTES))) {
			$msg = "invalid file name";
			return false;
		}
		if (!filter_var($file['tmp_name'], FILTER_SANITIZE_STRING, array('flags'=>FILTER_FLAG_NO_ENCODE_QUOTES))) {
			$msg = "Invalid file path";
			return false;
		}	
		
		return file_get_contents($file['tmp_name']);
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
		$clientEmail = $row['clientEmail'];
		$userName = $row['userName'];
		$password = $row['password'];
		
		$body = <<<BODY
Dear $clientName,<br>
<br>
Your Wycliffe Web Services events account for the <b>$eventName</b> has been created. You can now:<br>
<br>
1. <a href="http://wycliffe-services.net/events/webservice.php?eventName=$eventName&userName=$userName&password=$password&report=download">Download</a> the latest participant list. You can click this link at any time to get a real-time report of who has confirmed their attendance for your event. Alternatively, you can have the report <a href="mailto:events@wycliffe-services.net?subject=Get the latest participant list for $eventName&body=Just click send to get the latest participant list.%0D%0A%0D%0AEvent name: $eventName%0D%0AUser name: $userName%0D%0APassword: $password%0D%0Areport: email">emailed</a> to you.<br>
2. Update the participant tracking list, and then <a href="http://wycliffe-services.net/events/management.php?eventName=$eventName&userName=$userName&password=$password&clientName=$clientName&clientEmail=$clientEmail">upload it to the server</a> or <a href="mailto:events@wycliffe-services.net?subject=Update participant list for $eventName&body=Attach mailing_list.csv to this email and click send. Warning: Your existing participant list database on the server will be overwritten with the contents of mailing_list.csv, so make sure that it is based on the latest server version.%0D%0A%0D%0AYour name: $clientName%0D%0AEvent name: $eventName%0D%0AUser name: $userName%0D%0APassword: $password%0D%0Areport: upload%0D%0A">email</a> it.<br>
3. <a href="mailto:events@wycliffe-services.net?subject=Get the invitation email template&body=Just click send to get the invitation email template.%0D%0A%0D%0AYour name: $clientName%0D%0AEvent name: $eventName%0D%0AUser name: $userName%0D%0APassword: $password%0D%0Areport: invitation">Send</a> out the invitation email.<br>
4. <a href="mailto:events@wycliffe-services.net?subject=Get the logistics email template&body=Just click send to get the logistics email template.%0D%0A%0D%0AYour name: $clientName%0D%0AEvent name: $eventName%0D%0AUser name: $userName%0D%0APassword: $password%0D%0Areport: logistics">Send</a> out the logistics email.
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
