<?php 
require_once 'classes/Record.php';
require_once 'classes/Organization.php';

class Donation extends Record
{
	public function __construct() {
		$columns = array(
		    "id"=>"integer",
		    "email"=>"text",
		    "name"=>"text",
			"country"=>"text",
			"state"=>"text",
			"city"=>"text",
			"address"=>"text",
			"address2"=>"text",
			"postalCode"=>"text",
			"phone"=>"text",
			"purchaseId"=>"text",
			"amount"=>"float",
			"project"=>"text",
			"org"=>"text",
			"createTimestamp"=>"timestamp",
		);
		Record::__construct("donation", $columns, "id");
	}
	
	public function recordDonation($org, $user, $purchase, &$msg) {
		$row = array(
			'email' => $user->emailAddress(),
			'name' => $user->name(),
			'country' => $user->country(),
			'state' => $user->state(),
			'city' => $user->city(),
			'address' => $user->address(),
			'address2' => $user->address2(),
			'postalCode' => $user->postalCode(),
			'phone' => $user->phone(),
			'purchaseId' => $purchase->purchaseId(),
			'amount' => $purchase->amount(),
			'project' => $purchase->project(),
			'org' => $org,
		);
		Record::initialize($row, false);

		$msg = $this->serialize();
		return $msg == '';
	}
	
	public function reportCSV($data) {
		$msg = '';
		$org = new Organization();
		if (!$org->readFromData($data, $msg)) { return 'invalid ' . $msg; }
		
		$filters = array(
		  "startDate"=>FILTER_SANITIZE_STRING,
		  "endDate"=>FILTER_SANITIZE_STRING,
		);
		$row = filter_var_array($data, $filters);
		
		foreach($row as $key => $value) {
			$value = trim($value);
			if ($value != '' && date('Y-m-d', strtotime($value)) != $value) { return 'invalid ' . $key; }
		}
		
		$report = $this->generateReport($org->org(), $row['startDate'], $row['endDate']);
		$file = tempnam(sys_get_temp_dir(), 'reportCSV') . '.csv';
		if (FALSE  === file_put_contents($file, $report)) { return 'could not write file'; }

		$msg = '';
		if (!$this->emailReport($file, $org->notify_emails(),
			'Credit card donation report startDate=' . $row['startDate'] . ' endDate=' . $row['endDate'], $msg)) {
			return $msg;
		}
		return 'Your report has been sent to the ' . $org->name() . ' notification email address.';
	}
	
	private function emailReport($file, $to, $subject, &$msg) {
		$params = array(
			'to' => $to,
			'subject' => $subject,
			'attach1' => '@' . $file,
		);
		$ch = util::curl_init('https://wycliffe-services.net/email/webservice.php', $params);
		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			$msg = curl_error($ch);
			$retValue = false;
		} else {
			curl_close($ch);
			$retValue = true;
		}

		unlink($file);
		return $retValue;
	}
	
	private function generateReport($org, $startDate, $endDate) {
		$asciiTab = chr(9);
		
		$headers = array('name','email','phone','country','state','city','address','address2',
					     'postalCode','purchaseId','amount','project','date');
		$retValue = array(implode($asciiTab, $headers));
		$headers[count($headers) - 1] = 'createTimestamp';

		$where = '`org` = "' . $org . '"';
		
		if ($startDate != '' || $endDate != '') {
			if ($startDate == '') { $startDate = '2000-01-01'; }
			if ($endDate == '') { $endDate = date('Y-m-d'); }
			$where .= " AND `createTimestamp` between '" . $startDate . "' and DATE_ADD('" . $endDate . "',INTERVAL 1 DAY)";
		}
		$rows = Record::getRows($headers, $where);

		foreach ($headers as &$header) {
			$header = strtolower ($header);
		}

		foreach ($rows as $row) {
			$csvRow = array();
			
			for ($i = 0; $i < count($headers); $i++) {
				$csvRow[] = $row[$headers[$i]];
			}
			$retValue[] = implode($asciiTab, $csvRow);
		}
		return implode(PHP_EOL, $retValue);
	}
}
?>