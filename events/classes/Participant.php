<?php 
require_once 'Record.php';

class Participant extends Record
{
	public function __construct($userName, $password, &$msg) {
		$columns = array(
		    "id"=>"integer",
		    "tag"=>"text",
		    "honorific"=>"text",
			"firstName"=>"text",
			"lastName"=>"text",
			"email"=>"text",
			"phone"=>"text",
			"organization"=>"text",
			"title"=>"text",
			"isComing"=>"boolean",
			"room"=>"text",
			"needVisa"=>"boolean",
			"country"=>"text",
			"passportNumber"=>"text",
			"passportExpiryDate"=>"date",
			"arrivalTime"=>"timestamp",
			"arrivalFlightNumber"=>"text",
			"departureTime"=>"timestamp",
			"departureFlightNumber"=>"text",
			"notes"=>"text",
		);
		Record::__construct($userName, $columns, "id", 'events', $userName, $password, $msg);
	}
		
	public function reportCSV(&$msg) {
		$columns = $this->columns();
		$res = $this->selectAll($columns);

		$asciiTab = chr(9);

		foreach ($columns as &$column) {
			$column = '$' . $column;
		}
		$retValue = array(implode($asciiTab, $columns));
		
        while (($row = $res->fetchRow())) {
			$retValue[] = implode($asciiTab, $row);
        }
		$retValue = implode(PHP_EOL, $retValue);

		$path = util::createTempDir() . 'mailing_list.csv';
		if (FALSE  === file_put_contents($path, $retValue)) {
			$msg = 'could not write file';
			return false;
		}
		return $path;
	}
}
?>