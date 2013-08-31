<?php 
require_once 'Record.php';
require_once 'util.php';

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
		
	public function reportCSV($dir, &$msg) {
		$columns = $this->columns();
		$res = $this->selectAll($columns);

		foreach ($columns as &$column) {
			$column = '$' . $column;
		}
		$retValue = array($columns);
		
        while (($row = $res->fetchRow())) {
			$retValue[] = $row;
        }
		$retValue = util::generateCSV($retValue);
		
		$path = $dir . 'mailing_list.csv';
		if (FALSE  === file_put_contents($path, $retValue)) {
			$msg = 'could not write file';
			return false;
		}
		return $path;
	}
	
	public function overwriteDatabase($str, $simulate, &$msg) {
		$rows = util::parseCSV($str);
		$header = array_shift($rows);
		if (!$this->validateHeader($header, $msg)) { return false; }
		
		if ($simulate == 1) { return true; }

		$this->delete('*');
		
		$columns = Record::columns();
		foreach ($rows as $data) {
			$row = array();
			
			$index = 0;
			foreach ($columns as $column) {
				$row[$column] = $data[$index++];
			}
			Record::initialize($row, false);
			Record::serialize();
		}		
		
		return true;
	}
	
	private function validateHeader($header, &$msg) {
		$expectedHeader = '$' . implode(",$", Record::columns());
		if (implode(",", $header) != $expectedHeader) {
			$msg = 'invalid file';
			return false;
		}
		return true;
	}
}
?>