<?php 
require_once 'util.php';
require_once 'classes/Record.php';
require_once 'classes/ChinaUnionPay.php';
require_once 'classes/JapanCreditBureau.php';
require_once 'classes/MerchantAccounts.php';

class Purchase extends Record
{
	public function __construct() {
		$columns = array(
		    "purchaseId"=>"text",
		    "amount"=>"float",
			"cardName"=>"text",
			"creditCard"=>"text",
			"month"=>"integer",
			"year"=>"integer",
			"project"=>"text",
		);
		Record::__construct("purchase", $columns, "purchaseId");
	}

	public function readFromData($data, &$msg) {
		$filters = array(
		  "amount"=>FILTER_VALIDATE_FLOAT,
		  "cardName"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		  "creditCard"=>FILTER_SANITIZE_STRING,
		  "month"=>FILTER_VALIDATE_INT,
		  "year"=>FILTER_VALIDATE_INT,
		  "project"=>array('filter'=>FILTER_SANITIZE_STRING, 'flags'=>FILTER_FLAG_NO_ENCODE_QUOTES),
		);
		$row = filter_var_array($data, $filters);

		$msg = $this->containsColumns($row, "amount,cardName,creditCard,month,year");
		if ($msg != '') { return false; }
		if (!isset($row['project'])) { $row["project"] = ''; }
		$row["purchaseId"] = '';
		
		if (!$this->isValidCreditCard($row['creditCard'])) { $msg = "creditCard"; return false; }
		if (!preg_match("/^01|02|03|04|05|06|07|08|09|10|11|12$/i", $row['month'])) { $msg = "month"; return false; }
		if (!preg_match("/^20[1-9][0-9]$/i", $row['year'])) { $msg = "year"; return false; }
		
		Record::initialize($row, false);
		return true;
	}

	public function makePurchase($org, $user, $simulate, &$msg) {
		if ($this->isPayPal()) {
			$paymentProcessor = new PayPal();
		} else {
			switch (substr($this->row['creditCard'], 0, 1)) {
			case 3:
				$paymentProcessor = new JapanCreditBureau();
				break;
			case 4:
			case 5:
				$paymentProcessor = new MerchantAccounts();
				break;
			case 6:
				$paymentProcessor = new ChinaUnionPay();
				break;
			default:
				$msg = "Only Visa, Mastercard, JCB and China Union Pay cards are accepted.";
				return false;
			}
		}

		if (!$paymentProcessor->makePurchase($org, $user, $this, $simulate, $msg)) {
			// fixme: localize this
			$msg = "Your credit card was declined: " . $msg;
			return false;
		}
		$this->row['purchaseId'] = $msg;
		return true;
	}
	
	private function isValidCreditCard(&$cc) {
		// Strip any non-digits (useful for credit card numbers with spaces and hyphens)
		$cc = preg_replace('/\D/', '', $cc);
		$cc = str_replace(" ", "", $cc);
		
	    $cards = array(
	        "visa" => "4[0-9]{12}(?:[0-9]{3})?",
	        "mastercard" => "5[1-5][0-9]{14}",
			"jcb" => "(?:2131|1800|35\d{3})\d{11}",
			"cup" => "62[0-9]{14}[0-9]?[0-9]?[0-9]?",
	    );
	    $matches = array();

	    $result = preg_match("/^" . $cards["cup"] . "$/", $cc, $matches);
	    if ($result > 0) { return true; }

	    $pattern = "/^(?:".implode("|", $cards).")$/";
	    $result = preg_match($pattern, $cc, $matches);
		
	    if ($result > 0) { return $this->luhn_check($cc); }
	    return false;
	}
	private function luhn_check($number) {
		// Set the string length and parity
		$number_length=strlen($number);
		$parity=$number_length % 2;
		
		// Loop through each digit and do the maths
		$total=0;
		for ($i=0; $i<$number_length; $i++) {
			$digit=$number[$i];
			// Multiply alternate digits by two
			if ($i % 2 == $parity) {
				$digit*=2;
				// If the sum is two digits, add them together (in effect)
				if ($digit > 9) {
					$digit-=9;
				}
			}
			// Total up the digits
			$total+=$digit;
		}
		
		// If the total mod 10 equals 0, the number is valid
		return ($total % 10 == 0);
	}

	private function isPayPal() {
		return false;
	}
	public function purchaseId() {
		return $this->row['purchaseId'];
	}
	public function cardName() {
		return $this->row['cardName'];
	}
	public function amount() {
		return $this->row['amount'];
	}
	public function creditCard() {
		return $this->row['creditCard'];
	}
	public function month() {
		return $this->row['month'];
	}
	public function year() {
		return substr($this->row['year'], 2);
	}
	public function project() {
		return $this->row['project'];
	}
}
?>
