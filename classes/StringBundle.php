<?php 
require_once 'Record.php';

class StringBundle extends Record
{
	private $strings;
	
	public function __construct($language) {
		$columns = array(
		    "en"=>"text",
		    $language =>"text"
		);

		Record::__construct("string_bundle", $columns, "en");
		
		$res = Record::selectAll(array_keys($columns));
		if ($res->numRows() < 1) { throw new Exception($key . " is not a String Bundle."); }
		
		$this->strings = array();
        while (($row = $res->fetchRow())) {
            $this->strings[$row["en"]] = $row[strtolower($language)];
        }
	}

	public function translate($text) {
		return $this->strings[$text];
	}
	
	public function generateMapping() {
		$arr = array();
		foreach ($this->strings as $key => $value) {
			$arr[] = '"' . $key . '" : "' . $value . '"';
		}
		return implode(',', $arr);
	}
}
?>
