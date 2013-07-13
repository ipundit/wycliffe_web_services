<?php 
require_once 'Record.php';

class Organization extends Record
{
	private $test;
	
	public function __construct() {}
	
	public function readFromData($data, &$msg, $useDefaultOrg = false) {
		$filters = array(
		  "org"=>FILTER_SANITIZE_STRING,
		  "test"=>FILTER_VALIDATE_INT,
		);
		$row = filter_var_array($data, $filters);

		$temp = $this->containsColumns($row, "org");
		if (!$useDefaultOrg && $temp != '') { $msg = $temp; return false; }
		if (!isset($row["org"])) { $row["org"] = "wycliffe_singapore"; };

		$columns = array(
		    "org"=>"text",
		    "name"=>"text",
			"country"=>"text",
			"currency"=>"text",
			"redirect_url"=>"text",
			"notify_emails"=>"text",
			"merchant_id"=>"integer",
			"terminal_id"=>"integer",
			"merchant_id_test"=>"integer",
			"terminal_id_test"=>"integer",
			"production_store_id"=>"text",
			"testing_store_id"=>"text",
			"production_pass_phrase"=>"text",
			"testing_pass_phrase"=>"text",			
		);
		Record::__construct("organization", $columns, "org");
		
		$res = Record::select(array_keys($columns), "`org`=?", $row["org"]);
		if ($res->numRows() != 1) { $msg = $row["org"] . " is not an Organization."; return false; }
		Record::initialize($res->fetchRow(), true);

		$this->test = isset($row['test']) ? $row['test'] == 1 : 0;
		return true;
	}

	public function test() {
		return $this->test;
	}
	public function org() {
		return $this->row['org'];
	}
	public function name() {
		return $this->row['name'];
	}
	public function country() {
		return $this->row['country'];
	}
	public function currency() {
		return $this->row['currency'];
	}
	public function redirect_url() {
		return $this->row['redirect_url'];
	}
	public function notify_emails() {
		return $this->row['notify_emails'];
	}
	public function merchant_id() {
		return $this->test() ? $this->row['merchant_id_test'] : $this->row['merchant_id'];
	}
	public function terminal_id() {
		return $this->test() ? $this->row['terminal_id_test'] : $this->row['terminal_id'];
	}
	public function store_id() {
		return $this->test() ? $this->row['testing_store_id'] : $this->row['production_store_id'];
	}
	public function pass_phrase() {
		return $this->test() ? $this->row['testing_pass_phrase'] : $this->row['production_pass_phrase'];
	}
}
?>
