<?php 
require_once 'Record.php';

class StringBundle extends Record
{
	private $strings;
	
	public function __construct($language, $startRow, $endRow) {
		$columns = array(
		    "en"=>"text",
		    $language =>"text"
		);

		Record::__construct("string_bundle", $columns, "en");
		
		if ($startRow == -1 && $endRow == -1) {
			$where = '';
		} else {
			if ($startRow == -1) { $startRow = 0; }
			if ($endRow == -1) { $endRow = $startRow + 99; }
			$where = "`id` between " . $startRow . " and " . $endRow;
		}
		
		$res = Record::selectAll(array_keys($columns), $where);
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
