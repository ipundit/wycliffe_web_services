<?php
require_once 'util.php';
require_once 'Record.php';
require_once 'classes/Organization.php';
require_once 'classes/Purchase.php';
require_once 'translation.php';

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

	public function makePurchase($data, $onSecureServer, &$msg) {
		if (!isset($data['address2'])) { $data['address2'] = ''; }
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

		$msg = $this->containsColumns($row, "email,name,country,state,city,address,postalCode,phone,simulate");
		if ($msg != '') {
			$msg = "Invalid input for " . $msg;
			return false;
		}
		$simulate = 1; // fixme: hardcode simulate until production server is ready
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

		if (!$purchase->makePurchase($org, $this, $onSecureServer, $simulate == 1, $msg)) { 
			if (substr($msg, 0, 23) == 'Could not resolve host:') {	$msg = "Could not connect";	}
			return false;
		}

		if ($onSecureServer) { 
			require_once 'classes/Donation.php';
			$donation = new Donation();
			
			if ($donation->recordDonation($org->org(), $this, $purchase, $msg)) {
				$msg = $purchase->purchaseId();
				return true;
			}
			return false;
		}
		
		$simulate = 0; // fixme: remove when email testing passes
		return $this->emailPurchaseReceipt($org, $purchase->amount(), $msg, $simulate, $msg);
	}

	private function emailPurchaseReceipt($org, $amount, $orderNumber, $simulate, &$msg) {
		// fixme: localize this
		require_once 'translation.php';
		configureForLang();
		$body = array(
			t("Dear") . " " . $this->name(),
			'',
			t('Thank you for donating to') . ' ' . $org->name() . '.<h1>' . t('Your donation information') . '</h1>' . t('Name:') . ' ' . $this->name(),
			t('Email:') . ' ' . $this->emailAddress(),
			t('Donation tracking number:') . ' ' . $orderNumber,
			t('Amount: $') . $amount . ' ' . $org->currency(),
			t('Date:') . ' ' . date('F j, Y g:i a') . ' UTC<h1>' . t('Your contact information') . '</h1>' . t('Phone number:') . ' ' . $this->phone(),
			t('Address:') . ' ' . $this->address(),
			t('Address line 2:') . ' ' . $this->address2(),
			t('Postal code:') . ' ' . $this->postalCode(),
		);

		if ($this->city() != '--') { $body[] = t("City:") . ' ' . $this->city(); }
		if ($this->state() != '--') { $body[] = t("State:") . ' ' . $this->state(); }
		
		$body[] = t('Country:') . ' ' . $this->country();
		$body[] = '';
		$body[] = t('Regards,');
		$body[] = t('Wycliffe payment services');
		$body = implode('<br />', $body);
		
		$subject = $org->test() ? "TESTING: " : "";
		$subject = $subject . $org->name() . " " . t("donation receipt");

		$to = $this->name() . " <" . $this->emailAddress() .">";
		
		$retValue = '';
		$temp = util::sendEmail($retValue, '', "no-reply@wycliffe-services.net", $to, $subject, $body, '', 
								$org->notify_emails(), '', array(), array(), $simulate);
		if ($simulate == 1) { 
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