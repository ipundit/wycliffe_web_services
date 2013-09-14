<?php 
require_once 'Record.php';
require_once 'util.php';

class Participant extends Record
{
	public function __construct($userName, $password, &$msg) {
		$columns = array(
		    "id"=>"integer",
		    "tags"=>"text",
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
	
	// The rest of these functions are used by index.php
	public static function main(&$msg) {
		$tempDir = util::saveAllFiles();
		try {
			Participant::mainImpl($tempDir, $msg);
		} catch (Exception $e) {}
		util::deltree($tempDir);
	}
	private static function mainImpl($tempDir, &$msg) {
		$params = Participant::validateInput(empty($_POST) ? $_GET : $_POST, $msg);
		if ($msg != '') { return; }

		if (file_exists('classes/DatabaseConstants.php')) {
			require_once 'classes/DatabaseConstants.php';
		} else if ($params['simulate'] == 1) {
			require_once '/var/www/event/TestEvent/classes/DatabaseConstants.php';
		} else {
			$msg = 'regression test must use simulate = 1';
			return;
		}
	
		$participant = new Participant(EVENT_USERNAME, EVENT_PASSWORD, $msg);
		if ($msg != '') {
			echo $msg;
			return;
		}
		
		$row = $participant->getEventRegistration($params['id'], $msg);
		if ($row === false) { return;}
		
		$msg = json_encode($row);
	}
			
	public function getEventRegistration($id, &$msg) {
		$res = Record::select('*', 'id=?', $id);
		if ($res->numRows() != 1) {
			$msg = 'id not found';
			return;
		}
		return $res->fetchRow();
	}
	private static function validateInput($data, &$msg) {
		if (!isset($data['simulate'])) { $data['simulate'] = 0; }

		$filters = array(
		  "id"=>FILTER_VALIDATE_INT,
		  "simulate"=>array('filter'=>FILTER_VALIDATE_INT, 'options'=>array("min_range"=>0, "max_range"=>1)),
		);
		$row = filter_var_array($data, $filters);

		foreach ($filters as $key => $value) {
			if (isset($row[$key])) {
				$row[$key] = trim($row[$key]);
				if ($row[$key] != '') { continue; }
			}
			$msg = 'invalid ' . $key;
		}
		return $row;
	}
}
?>