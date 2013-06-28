<?php 
require_once 'classes/Email.php';
require_once 'classes/Organization.php';
require_once 'classes/Purchase.php';
require_once 'classes/Record.php';

class User extends Record
{
	public function __construct() {
		$columns = array(
		    "email"=>"text",
	    	"name"=>"text",
			"country"=>"text",
			"state"=>"text",
			"city"=>"text",
			"address"=>"text",
			"address2"=>"text",
			"postalCode"=>"text",
			"phone"=>"text",
		);
		Record::__construct("user", $columns, "email");
	}

	public function makePurchase($data, &$msg) {
		$filters = array(
		  "email"=>FILTER_VALIDATE_EMAIL,
		  "name"=>FILTER_SANITIZE_STRING,
		  "country"=>FILTER_SANITIZE_STRING,
		  "state"=>FILTER_SANITIZE_STRING,
		  "city"=>FILTER_SANITIZE_STRING,
		  "address"=>FILTER_SANITIZE_STRING,
		  "address2"=>FILTER_SANITIZE_STRING,
		  "postalCode"=>FILTER_SANITIZE_STRING,
		  "phone"=>FILTER_SANITIZE_STRING,
		  "po"=>FILTER_SANITIZE_STRING,
		);
		$row = filter_var_array($data, $filters);

		if (!$this->containsColumns($row, "email,name,country,state,city,address,address2,postalCode,phone,po")) {
			$msg = "Invalid input";
			return false;
		}
		Record::initialize($row, false);
		
		$purchase = new Purchase();
		if (!$purchase->readFromData($data)) {
			$msg = "Invalid purchase input";
			return false;
		}

		if (!$this->makePurchaseImpl($purchase, $msg)) { return false; }
		$this->emailPurchaseReceipt($row["po"], $purchase->amount(), $msg);
			
		return true;
	}

	private function makePurchaseImpl($purchase, &$msg) {
		if (!$purchase->makePurchase($this, $msg)) { 
			if (substr($msg, 0, 23) == 'Could not resolve host:') {
				$msg = "Could not connect";
			}
			return false;
		}
		return true;
	}

	private function emailPurchaseReceipt($key, $amount, $orderNumber) {
		$PO = new Organization($key);

		// fixme: localize this
		$body = "Thank you for donating to " . $PO->name() . ".<h1>Your donation information</h1>
		Name: " . $this->name() .
		"<br />Email: " . $this->emailAddress() .
		"<br />Donation tracking number: " . $orderNumber .
		"<br />Amount: $" . $amount . " " . $PO->currency() .
		"<br />Date: " . date('F j, Y g:i a');

		$body = $body . "<h1>Your contact information</h1>
		Phone number: " . $this->phone() .
		"<br />Address: " . $this->address() .
		"<br />Address line 2: " . $this->address2() .
		"<br />Postal code: " . $this->postalCode() .
		"<br />City: " . $this->city() .
		"<br />State: " . $this->state() .
		"<br />Country: " . $this->country();
				
		$this->email($this->name(), $this->emailAddress(), $PO->notify_emails(), $PO->name() . " donation receipt", $body);
	}
	
	private function email($name, $email, $bcc, $subject, $body) {
		$signature = 'Regards,\nWycliffe payment services';
		$mail = new Email();
		$mail->send("no-reply@wycliffe-services.net", $name, $email, $bcc, $subject, $body, $signature);
	}

	public function name() {
		return $this->row['name'];
	}
	public function emailAddress() {
		return $this->row['email'];
	}
	public function address() {
		return $this->row['address'];
	}
	public function address2() {
		return isset($this->row['address2']) ? $this->row['address2'] : '';
	}
	public function city() {
		return $this->row['city'];
	}
	public function state() {
		return $this->row['state'];
	}
	public function postalCode() {
		return $this->row['postalCode'];
	}
	public function country() {
		return $this->row['country'];
	}
	public function phone() {
		return $this->row['phone'];
	}
}
?>