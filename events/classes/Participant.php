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
			"isComing"=>"integer",
			"room"=>"text",
			"needVisa"=>"boolean",
			"passportName"=>"text",
			"passportCountry"=>"text",
			"passportNumber"=>"text",
			"passportExpiryDate"=>"date",
			"arrivalDate"=>"date",
			"arrivalTime"=>"time",
			"arrivalFlightNumber"=>"text",
			"departureDate"=>"date",
			"departureTime"=>"time",
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
			util::removeAfter($row['arrivaltime'], ":", false);
			util::removeAfter($row['departuretime'], ":", false);
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
			if (Participant::mainImpl($tempDir, $msg)) {
				$msg['error'] = 'ok';
				$retValue = $msg;
			} else {
				$retValue['error'] = $msg;
			}
			$msg = json_encode($retValue);
		} catch (Exception $e) {}
		util::deltree($tempDir);
	}
	private static function mainImpl($tempDir, &$msg) {
		$params = Participant::validateInput(empty($_POST) ? $_GET : $_POST, $msg);
		if ($msg != '') { return false; }

		$participant = new Participant($params['userName'], $params['password'], $msg);
		if ($msg != '') { return false; }
		
		if (false === $participant->update($params, $msg)) { return false; }
		
		$row = $participant->getEventRegistration($params['id'], $msg);
		if ($row === false) { return false;}
		
		$msg = $row;
		return true;
	}
	
	private function update($params, &$msg) {
		$simulate = $params['simulate'];
		if ($simulate == 1) { return true; }
		
		Record::initialize($params, false);
		$msg = Record::serialize();
		return $msg == '';
	}
	
	private function getEventRegistration($id, &$msg) {
		$res = Record::select($this->columns(array('tags','room')), 'id=?', $id);
		
		if ($res->numRows() != 1) {
			$msg = 'id not found';
			return false;
		}
		$retValue = $res->fetchRow();

		util::removeAfter($retValue['arrivaltime'], ":", false);
		util::removeAfter($retValue['departuretime'], ":", false);
		return $retValue;
	}
	private static function validateInput($data, &$msg) {
		if (!isset($data['simulate'])) { $data['simulate'] = 0; }

		$filters = array(
		  "id"=>FILTER_VALIDATE_INT,
		  "userName"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "password"=>FILTER_UNSAFE_RAW,
		  "isComing"=>array('filter'=>FILTER_VALIDATE_INT, 'options'=>array("min_range"=>0, "max_range"=>2)),
		  "simulate"=>array('filter'=>FILTER_VALIDATE_INT, 'options'=>array("min_range"=>0, "max_range"=>1)),
		);
		$row = filter_var_array($data, $filters);

		foreach ($filters as $key => $value) {
			if (isset($row[$key])) {
				$row[$key] = trim($row[$key]);
				if ($row[$key] != '') { continue; }
			} else if ($key == "isComing") { continue; }
			$msg = 'invalid ' . $key;
		}
		return $row;
	}
}
?>