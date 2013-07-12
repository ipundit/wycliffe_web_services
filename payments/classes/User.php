<?php 
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
		if (!isset($data['simulate'])) { $data['simulate'] = 0; }

		$filters = array(
		  "email"=>FILTER_VALIDATE_EMAIL,
		  "name"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "country"=>FILTER_SANITIZE_STRING,
		  "state"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "city"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "address"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "address2"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "postalCode"=>FILTER_SANITIZE_STRING,
		  "phone"=>FILTER_SANITIZE_STRING,
		  "simulate"=>array('filter'=>FILTER_VALIDATE_INT, 'options'=>array("min_range"=>0, "max_range"=>1)),
		);
		$row = filter_var_array($data, $filters);

		$msg = $this->containsColumns($row, "email,name,country,state,city,address,address2,postalCode,phone,simulate");
		if ($msg != '') {
			$msg = "Invalid input for " . $msg;
			return false;
		}
		$simulate = $row['simulate'] == 1;
		Record::initialize($row, false);
		
		$purchase = new Purchase();
		if (!$purchase->readFromData($data, $msg)) {
			$msg = "Invalid purchase input for " . $msg;
			return false;
		}

		$org = new Organization();
		if (!$org->readFromData($data, $msg)) {
			$msg = "Invalid organization input for " . $msg;
			return false;
		}
		
		if (!$this->makePurchaseImpl($org, $purchase, $simulate, $msg)) { return false; }
		return $this->emailPurchaseReceipt($org, $purchase->amount(), $msg, $simulate, $msg);
	}

	private function makePurchaseImpl($org, $purchase, $simulate, &$msg) {
		if (!$purchase->makePurchase($org, $this, $simulate, $msg)) { 
			if (substr($msg, 0, 23) == 'Could not resolve host:') {
				$msg = "Could not connect";
			}
			return false;
		}
		return true;
	}

	private function emailPurchaseReceipt($org, $amount, $orderNumber, $simulate, &$msg) {
		// fixme: localize this
		$body = array(
			"Dear " . $this->name(),
			'',
			'Thank you for donating to ' . $org->name() . '.<h1>Your donation information</h1>Name: ' . $this->name(),
			'Email: ' . $this->emailAddress(),
			'Donation tracking number: ' . $orderNumber,
			'Amount: $' . $amount . ' ' . $org->currency(),
			'Date: ' . date('F j, Y g:i a') . ' UTC<h1>Your contact information</h1>Phone number: ' . $this->phone(),
			'Address: ' . $this->address(),
			'Address line 2: ' . $this->address2(),
			'Postal code: ' . $this->postalCode(),
		);

		if ($this->city() != '--') { $body[] = "City: " . $this->city(); }
		if ($this->state() != '--') { $body[] = "State: " . $this->state(); }
		
		$body[] = 'Country: ' . $this->country();
		$body[] = '';
		$body[] = 'Regards,';
		$body[] = 'Wycliffe payment services';
		$body = implode(PHP_EOL, $body);
		
		$subject = $org->test() ? "TESTING: " : "";
		$subject = $subject . $org->name() . " donation receipt";

		$to = $this->name() . " <" . $this->emailAddress() .">";
		
		$retValue = '';
		$temp = util::sendEmail($retValue, '', "no-reply@wycliffe-services.net", $to, $subject, $body, '', 
								$org->notify_emails(), '', array(), $simulate);
		if ($simulate) { 
			$msg = $retValue; // Even if sending the email fails in production, let the user know that credit card charge passed
			return false;
		}
		return $temp;
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