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
			"oneBedRoom"=>"text",
			"twoBedRoom"=>"text",
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
			"lang"=>"text",
			"cc"=>"text",
			"notes"=>"text",
			"passkey"=>"text",
		);
		Record::__construct($userName, $columns, "id", 'events', $userName, $password, $msg);
	}
	
	public function reportCSV($dir, $includePasskey, &$msg) {
		$exception = $includePasskey ? array() : array('passkey');
		$columns = $this->columns($exception);
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
	
	public function overwriteDatabase($eventShortName, $str, $simulate, &$msg) {
		$rows = util::parseCSV($str);
		$header = array_shift($rows);
		if (!$this->validateHeader($header, $msg)) { return false; }
		
		if ($simulate == 1) { return true; }

		$this->delete('*');
		
		$columns = Record::columns(array('passkey'));
		foreach ($rows as $data) {
			if (count($data) == 0 || implode('', $data) == '') { continue; }
			$row = array();
			
			$index = 0;
			foreach ($columns as $column) {
				$row[$column] = $data[$index++];
			}
			
			if ($row['id'] == '') { $row['id'] = rand(1, 65535); }
			$row['passkey'] = Participant::encryptPasskey($msg, $eventShortName, $row['id']);
			if ($msg != '') { return false; }
			
			Record::initialize($row, false);
			Record::serialize();
		}		
		
		return true;
	}
	
	private function validateHeader($header, &$msg) {
		$expectedHeader = '$' . implode(",$", Record::columns());
		$header = implode(",", $header);
		if ($header != $expectedHeader) { $header.= ',$passkey'; }
		if ($header != $expectedHeader) {
			$msg = 'invalid file';
			return false;
		}
		return true;
	}
	
	// These functions are used by management.php
	public static function getParticipants($eventName, $password) {
		$msg = '';
		$participant = new Participant($eventName, $password, $msg);
		if ($msg != '') { return false; }
	
		$retValue = array();
		$res = $participant->selectAll('id,firstName,lastName,passkey');
		if ($res->numRows() >= 1) {
			while (($row = $res->fetchRow())) {
				$retValue[$row['id'] . '_' . $row['passkey']] = $row['firstname'] . ' ' . $row['lastname'];
			}
		}
		asort($retValue);
		return $retValue;
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

		$participant = new Participant($params['eventName'], $params['password'], $msg);
		if ($msg != '') { return false; }

		if ($params['id'] == '0' && $params['passkey'] == '') {
			if ($params['doUpdate'] != 1) {
				foreach ($params as $key => $value) {
					if ($key != strtolower($key)) {
						$params[strtolower($key)] = $value;
						unset($params[$key]);
					}
				}
				$params['iscoming'] = 2;
				$params['needvisa'] = 0;
				$msg = $params;
				return true;
			}
			
			$params['id'] = $participant->nextId();
			$params['passkey'] = Participant::encryptPasskey($msg, $params['eventName'], $params['id']);
			if ($msg != '') { return false; }
		} else if (!$participant->hasId($params['id'])) {
			$msg = 'id not found';
			return false;
		}

		if ($params['doUpdate'] == 1 && false === $participant->update($params, $msg)) { return false; }
		
		$row = $participant->getEventRegistration($params['id'], $msg);
		if ($row === false) { return false; }
		
		$msg = $row;
		return true;
	}
	
	public function hasId($id) {
		return $this->inDatabase('id', $id);
	}
	
	private function update($params, &$msg) {
		$simulate = $params['simulate'];
		if ($simulate == 1) { return true; }

		if (isset($params['arrivaltime']) && $params['arrivaltime'] != '') { $params['arrivaltime'] .= ":00"; }
		if (isset($params['departuretime']) && $params['departuretime'] != '') { $params['departuretime'] .= ":00"; }
		
		Record::initialize($params, false);
		$msg = Record::serialize();
		return $msg == '';
	}
	
	private function getUserTemplate() {
		$res = $this->getParticipant(-1);
		return $res->fetchRow();
	}
	
	private function getParticipant($id) {
		return Record::select($this->columns(array('tags','oneBedRoom','twoBedRoom','cc')), 'id=?', $id);
	}
	
	private function getEventRegistration($id, &$msg) {
		$res = $this->getParticipant($id);
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
		if (!isset($data['lang'])) { $data['lang'] = "en"; }
		if (!isset($data['simulate'])) { $data['simulate'] = 0; }

		$filters = array(
		  "id"=>FILTER_VALIDATE_INT,
		  "eventName"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "passkey"=>FILTER_UNSAFE_RAW,
		  "isComing"=>array('filter'=>FILTER_VALIDATE_INT, 'options'=>array("min_range"=>0, "max_range"=>2)),
		  "arrivalFlightNumber"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "arrivalDate"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "arrivalTime"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "departureFlightNumber"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "departureDate"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "departureTime"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "honorific"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "firstName"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "lastName"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "organization"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "title"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "email"=>FILTER_VALIDATE_EMAIL,
		  "phone"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "passportNumber"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "passportExpiryDate"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "passportCountry"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "passportName"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "lang"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "notes"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "simulate"=>array('filter'=>FILTER_VALIDATE_INT, 'options'=>array("min_range"=>0, "max_range"=>1)),
		  "password"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "doUpdate"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		);
		$row = filter_var_array($data, $filters);

		if ($row['doUpdate'] != '1') { $row['doUpdate'] = 0; }

		if ($row['id'] == '0' && $row['passkey'] == '' && $row['password'] !== '') { return $row; }
		if ($row['id'] == '') {
			$msg = 'invalid id';
			return false;
		}
		if (strlen($row['lang']) != 2) {
			$msg = 'invalid lang';
			return false;
		}
		if (strtolower($row['lang']) != $row['lang']) {
			$msg = 'invalid lang';
			return false;
		}
		if ($row['simulate'] == '' && $row['simulate'] !== 0) {
			$msg = 'invalid simulate';
			return false;
		}
		
		if ($row['password'] == '') { $row['password'] = Participant::encryptPasskey($msg, $row['eventName'], $row['id'], false, $row['passkey']); }
		if (false === $row['password']) { return false; }
		
		foreach ($filters as $key => $value) {
			if (isset($row[$key])) {
				$row[$key] = trim($row[$key]);
				if ($row[$key] == '' && isset($data[$key]) && $data[$key] != '') {
					$msg = 'invalid ' . $key;
					return false;
				}
			} else {
				unset($row[$key]);
			}
		}
		
		foreach (array('arrivalDate', 'departureDate', 'passportExpiryDate') as $theDate) {
			if (!isset($row[$theDate]) || $row[$theDate] == '') { continue; }
			if (preg_match('/^20\d\d\-\d?\d\-\d?\d$/', $row[$theDate])) {
				$arr = explode('-', $row[$theDate]);
				if (checkdate($arr[1], $arr[2], $arr[0])) { continue; }
			}
			$msg = 'invalid ' . $theDate;
			return false;
		}
		
		foreach (array('arrivalTime', 'departureTime') as $theTime) {
			if (!isset($row[$theTime]) || $row[$theTime] == '') { continue; }
			if (preg_match('/^\d?\d:\d\d$/', $row[$theTime])) {
				$arr = explode(':', $row[$theTime]);
				if (Participant::checktime($arr[0], $arr[1])) { continue; }
			}
			$msg = 'invalid ' . $theTime;
			return false;
		}

		return $row;
	}
	
	private static function checktime($hour, $minute) {
		$hour = ltrim($hour, '0');
		$minute = ltrim($minute, '0');
		return ($hour > -1 && $hour < 24 && $minute > -1 && $minute < 60);
	}
	
	private static function encryptPasskey(&$msg, $eventShortName, $salt, $encrypt = true, $passkey = '') {
		if ($eventShortName == '' || strpos($eventShortName, '..') !== false) {
			$msg = 'invalid eventName';
			return false;
		}
		$constantsPath = "/var/www/event/$eventShortName/classes/DatabaseConstants.php";
		if (!file_exists($constantsPath)) {
			$msg = 'invalid eventName';
			return false;
		}

		require_once $constantsPath;
		$temp = md5(EVENT_PASSWORD . $salt);

		if ($encrypt) { return $temp; }
		
		if ($passkey != $temp) {
			$msg = 'invalid passkey';
			return false;
		}
		return EVENT_PASSWORD;
	}
}
?>