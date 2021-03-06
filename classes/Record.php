<?php 
require_once 'MDB2.php';
require_once 'util.php';
require_once 'DatabaseConstants.php';

class Record
{
	private $db;
	private $databaseName;
	private $tableName;
	private $keyField;
	private $columns;
	protected $row;
	private $isChanged;
	
	protected function __construct($tableName, $columns, $keyField, $database = '', $userName = '', $password = '', &$msg = '') {
		if ($database == '') { $database = DATABASE; }
		if ($userName == '') { $userName = USERNAME; }
		if ($password == '') { $password = PASSWORD; }
		
		$dsn = array(
		    'phptype'  => 'mysqli',
		    'hostspec' => 'localhost',
		    'username' => $userName, // These constants are read from the DatabaseConstants.php 
		    'password' => $password, // in the root of your workspace directory. Only the owner
		    'database' => $database, // of that directory (ie: you) can read this file and therefore
		); // read/write to your database. The exception is the production MySQL user - its
		   // DatabaseConstants.php is public domain, but has only read privileges for the 
		   // production database.
		$options = array('debug' => 2);
		
		$this->db = \MDB2::singleton($dsn, $options);
		if (\PEAR::isError($this->db)) { die($this->db->getMessage()); }
		$this->db->setFetchMode(MDB2_FETCHMODE_ASSOC);

		mysqli_report(MYSQLI_REPORT_STRICT);
		try {
			$this->db->setCharset('utf8');
		} catch (Exception $e) {
			if (util::startsWith($e->getMessage(), "Access denied")) {
				$msg = "Invalid username/password";
				return;
			}
			$msg = $e->getMessage();
			return;
		}

		$this->databaseName = $dsn['database'];
		$this->tableName = $tableName;

		$this->columns = $columns;
		$this->keyField = $keyField;
		$this->isChanged = false;
	}
	
	public function delete($keyField) {
		$sql = "DELETE FROM " . $this->databaseName . '.' . $this->tableName;
		if ($keyField != '*') { $sql .= ' WHERE ' . $this->keyField . '=' . $this->db->quote($keyField); } 
		 
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

		if (PEAR::isError($res)) { die($res->getUserInfo()); }
		return $res;
	}
	
	protected function selectAll($columns, $where = '') {
		if (is_array($columns)) { $columns = "`" . implode("`,`", $columns) . "`"; }
		if ($where != '') { $where = ' WHERE ' . $where; }
		
		$statement = $this->db->prepare('SELECT ' . $columns . ' FROM ' . $this->databaseName . '.' . $this->tableName . $where, MDB2_PREPARE_RESULT);
		if (PEAR::isError($statement)) { die($statement->getUserInfo()); }

		$res = $statement->execute();
		$statement->free();

		if (PEAR::isError($res)) { die($res->getUserInfo()); }
		return $res;
	}

	protected function getRows($columns, $where) {
		$retValue = array();

		$res = $this->selectAll($columns, $where);
		if ($res->numRows() < 1) { return $retValue; }

        while ($row = $res->fetchRow()) {
            $retValue[] = $row;
        }
		return $retValue;
	}
	
	protected function inDatabase($key, $value) {
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
			if (!array_key_exists($column, $row) || ($row[$column] !== 0 & $row[$column] == '')) { return $column; }
		}
		return '';
	}

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
			if (!isset($setClauses)) { return ''; }
			
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

	protected function columns($excludes = array()) {
		$columns = $this->columns;
		foreach ($excludes as $value) {
			unset($columns[$value]);
		}
		
		$retValue = array();
		foreach ($columns as $key => $value) {
			$retValue[] = $key;
		}
		return $retValue;
	}

/*
	Keep database connection open across a request
 	function __destruct() {
 		$this->db->disconnect();
 	}
*/
}
?>
