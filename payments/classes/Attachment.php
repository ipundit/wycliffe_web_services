<?php 
require_once 'classes/Record.php';

class Attachment extends Record
{
	public function __construct() {
		$columns = array(
			"id"=>"integer",
			"task_id"=>"integer",
	    	"title"=>"text",
		);
		Record::__construct("attachment", $columns, "id");
	}

	public function create($task_id, $title) {
		$row = array('task_id'=>$task_id, 'title'=>$title);
		Record::initialize($row, false);
	}

}
?>
