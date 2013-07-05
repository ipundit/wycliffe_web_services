<?php 
require_once 'MDB2.php';
require_once 'util.php';
require_once '../DatabaseConstants.php';

class Record
{
	private $db;
	private $databaseName;
	private $tableName;
	private $keyField;
	private $columns;
	protected $row;
	private $isChanged;
	
	protected function __construct($tableName, $columns, $keyField) {
		$dsn = array(
		    'phptype'  => 'mysqli',
		    'hostspec' => 'localhost',
		    'username' => USERNAME, // These constants are read from the DatabaseConstants.php 
		    'password' => PASSWORD, // in the root of your workspace directory. Only the owner
		    'database' => DATABASE, // of that directory (ie: you) can read this file and therefore
		); // read/write to your database. The exception is the production MySQL user - its
		   // DatabaseConstants.php is public domain, but has only read privileges for the 
		   // production database.
		$options = array('debug' => 2);
		
		$this->db = \MDB2::singleton($dsn, $options);
		if (\PEAR::isError($this->db)) { die($this->db->getMessage()); }
		
		$this->db->setFetchMode(MDB2_FETCHMODE_ASSOC);
		$this->db->setCharset('utf8');

		$this->databaseName = $dsn['database'];
		$this->tableName = $tableName;

		$this->columns = $columns;
		$this->keyField = $keyField;
		$this->isChanged = false;
	}
	
	public function delete($keyField) {
		$sql = "DELETE FROM " . $this->databaseName . '.' . $this->tableName . ' WHERE ' . $this->keyField . '=' . $this->db->quote($keyField);
		$affected = $this->db->exec($sql);
		if (\PEAR::isError($affected)) {
		    die($affected->getMessage());
		}
	}
	
	protected function initialize($row, $rowFromDatabase) {
		$this->row = $row;
		$this->isChanged = !$rowFromDatabase;
	}
	
	protected function select($columns, $where, $values, $orderByForTop = '', $groupBy = '') {
		$types = explode("?", trim(str_replace('`', '', $where)));
		unset($types[sizeof($types) - 1]);

		for ($i = 0; $i < sizeof($types); $i++) {
			$types[$i] = rtrim($types[$i]);
			$types[$i] = trim(substr($types[$i], 0, -1));
			util::removeBefore($types[$i], 'AND');
			$pos = strpos($types[$i], " ");
			
			if ($pos !== false) { $types[$i] = ltrim(substr($types[$i], $pos)); }
			$types[$i] = $this->columns[$types[$i]];
		}

		$orderByForTop = ($orderByForTop == '') ? '' : ' ORDER BY ' . $orderByForTop . ' DESC LIMIT 1';
		$groupBy = ($groupBy == '') ? '' : ' GROUP BY ' . $groupBy;
		
		if (is_array($columns)) { $columns = "`" . implode("`,`", $columns) . "`"; }

		$statement = $this->db->prepare('SELECT ' . $columns . ' FROM ' . $this->databaseName . '.' . $this->tableName . ' WHERE ' . $where . $orderByForTop . $groupBy, $types, MDB2_PREPARE_RESULT);
		$res = $statement->execute($values);
		$statement->free();

		if (\PEAR::isError($res)) { die($res->getMessage()); }
		return $res;
	}
	
	protected function selectAll($columns) {
		
		if (is_array($columns)) { $columns = "`" . implode("`,`", $columns) . "`"; }

		$statement = $this->db->prepare('SELECT ' . $columns . ' FROM ' . $this->databaseName . '.' . $this->tableName, MDB2_PREPARE_RESULT);
		$res = $statement->execute();
		$statement->free();

		if (\PEAR::isError($res)) { die($res->getMessage()); }
		return $res;
	}

	private function inDatabase($key, $value) {
		return $this->select($key, $key . "=?", $value)->numRows() >= 1;
	}
	
	protected function set($key, $value) {
		if ($this->row[$key] == $value) { return false; }
		$this->row[$key] = $value;
		$this->isChanged = true;
		return true;
	}

	protected function nextId($columnName = '') {
		if ($columnName == '') { $columnName = $this->keyField; }
		$statement = $this->db->prepare('SELECT Max(' . $columnName . ') As maxid FROM ' . $this->databaseName . '.' . $this->tableName, null, MDB2_PREPARE_RESULT);
		$res = $statement->execute();
		$statement->free();
		if (\PEAR::isError($res)) { die($res->getMessage()); }
		$row = $res->fetchRow();
		return $row['maxid'] + 1;
	}
	
	protected function containsColumns($row, $columns) {
		foreach (explode(",", $columns) as $column) {
			if (!isset($row[$column])) { return false; }
		}
		return true;
	}

/*
	We shouldn't store any donor or payment information in a centralized database - instead, let each PO do that for privacy reasons

	public function serialize($key = '') {
		if (!$this->isChanged) { return ''; }

		if ($key == '') { $key = $this->keyField; }
		$i = 0;
		
		if (isset($this->row[$key]) && $this->inDatabase($key, $this->row[$key])) {
			foreach ($this->columns as $column => $type) {
				if (isset($this->row[$column])) {
					if ($column == $key) {
						$keyFieldType = $type;
					} else if ($column != 'createtimestamp') {
						$setClauses[$i] = $column . " = ?";
						$values[$i] = $this->row[$column];
						$types[$i] = $type;
					    $i++;
					}
				}
			}
			
			$statement = $this->db->prepare('UPDATE ' . $this->databaseName . '.' . $this->tableName . ' SET ' . implode(", ", $setClauses) . ' WHERE ' . $key . " = " . 
				$this->db->quote($this->row[$key], $keyFieldType), $types, MDB2_PREPARE_RESULT);
		} else {
			foreach ($this->columns as $column => $type) {
				if (isset($this->row[$column])) {
					$value = $this->row[$column];
					
					$columns[$i] = $column;
					$values[$i] = $value;
					$questions[$i] = '?';
					$types[$i] = $type;
				    $i++;
				}
			}
			$statement = $this->db->prepare('INSERT INTO ' . $this->databaseName . '.' . $this->tableName . ' (' . implode(", ", $columns) . ') VALUES (' . implode(", ", $questions) . ')', $types, MDB2_PREPARE_RESULT);
		}

		$res = $statement->execute($values);
		$statement->free();
		if (\PEAR::isError($res)) { return $res->getMessage(); }
		$this->isChanged = false;
		return '';
	}
*/

/*
	Keep database connection open across a request
 	function __destruct() {
 		$this->db->disconnect();
 	}
*/
}
?>
