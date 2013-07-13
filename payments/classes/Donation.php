<?php 
require_once 'classes/Record.php';

class Donation extends Record
{
	public function __construct() {}
	
	public function recordDonation($org, $user, $purchase, &$msg) {
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
	
	public function reportCSV($startDate, $endDate) {
		$headers = array('name','email','phone','country','state','city','saddress','address2',
					   'postalCode', 'purchaseId', 'amount', 'project', 'date');

		// fixme: setup WHERE clause for $startDate $endDate in yyyy-mm-dd format
		$where = '';
		$rows = Record::getRows($headers, $where);
		
		$retValue = array(implode('\t', headers));
		foreach ($rows as $row) {
			$csvRow = array();
			foreach ($headers as $header) {
				$csvRow[] = $row[$header];
			}
			$retValue[] = implode('\t', $csvRow);
		}
		return implode('\n', $retValue);
	}
}
?>
