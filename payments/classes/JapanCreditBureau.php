<?php
require_once 'classes/User.php';

class JapanCreditBureau
{
	public function makePurchase($org, $user, $purchase, $donationId, $simulate, &$msg) {
		if ($simulate) {
			if ($purchase->creditCard() == '3541599999094304') {
				$msg = $this->declineMessage('05');
				return false;
			}
			$msg = 1234;
			return true;
		}

		require_once 'classes/Organization.php';
		
		if ($org->test()) {
			$url = "https://beta-jcbacqapi.pvbcard.com/WSJCBAcqAPI.asmx?wsdl";
		} else {
			$url = "https://jcbacqapi.pvbcard.com/WSJCBAcqAPI.asmx?wsdl";
		}

		$client = new SoapClient($url,
			array("trace" => 1, // enable trace to view what is happening
			"exceptions" => 0,  // disable exceptions
			"cache_wsdl" => 0));
		$response = $client->SaleTransaction(array ("pCommandString" => $this->makeCommandString($org, $user, $purchase)));
		$fields = $this->shred($response->SaleTransactionResult);

		if ($fields["39"] == "00") {
			$msg = $fields["998"]; // The order number
			return true;					
		} else {
			$msg = $this->declineMessage($fields["39"]);
			return false;
		}
	}

	private function makeCommandString($org, $user, $purchase) {
		return "FIELD=2&VALUE=" . $purchase->creditCard() . ";" .
		"FIELD=4&VALUE="  . $this->formatAmount($purchase->amount()) . ";" .
		"FIELD=14&VALUE=" . $this->formatExpiry($purchase->month(), $purchase->year()) . ";" .
		"FIELD=41&VALUE=" . $org->terminal_id() . ";" .     
		"FIELD=42&VALUE=" . $org->merchant_id();          	              	   
	}

	private function formatAmount($amount) {
		$decimalPlace = strpos($amount, ".");

		if ($decimalPlace === FALSE) {
			return $amount . "00";
		} else {
			return str_replace(".", "", $amount);
		}
		return $amount;
	}

	private function formatExpiry($month, $year) {
		if (strlen($month) === 1) {
			return "0" . $month . $year;         		
		} else {
			return $month . $year;        		
		}
	}

	private function shred($responseString) {
		$shreddings = array();

		$fieldValuePairs = explode(";", $responseString);

		foreach ($fieldValuePairs as &$fieldValuePair) {
			$splitFieldAndValue = explode("&", $fieldValuePair);
			$shreddings[$this->removePrefix('FIELD=', $splitFieldAndValue[0])] = $this->removePrefix('VALUE=', $splitFieldAndValue[1]);
		}
		return $shreddings;
	}      

	private function removePrefix($prefix, $str) {
		return preg_replace('/^' . $prefix . '/', '', $str);    
	}
	
	private function declineMessage($index) {
		$messages = array(
			"00" => "Transaction was successful",
			"01" => "Refer to issuer",
			"03" => "Invalid merchant",
			"04" => "Pickup card",
			"05" => "Transaction was rejected",
			"06" => "Error",
			"07" => "Fake card",
			"09" => "Request in progress",
			"12" => "Invalid transaction",
			"13" => "Invalid amount",
			"14" => "Invalid card number",
			"15" => "Invalid issuer",
			"20" => "Invalid response",
			"30" => "Format error",
			"31" => "Unsupported bank",
			"33" => "Expired card",
			"34" => "Suspended card for fraud",
			"36" => "Restricted card",
			"40" => "Function unsupported",
			"41" => "Lost card",
			"42" => "No account",
			"43" => "Card reported as stolen",
			"44" => "Insufficient funds",
			"54" => "Expired date error",
			"55" => "Incorrect PIN",
			"56" => "No card record",
			"57" => "Transaction denied to cardholder",
			"58" => "Transaction denied to terminal",
			"59" => "Suspected fradulent transaction",
			"61" => "Exceed withdrawal limits",
			"62" => "Restricted card",
			"63" => "Security violation",
			"65" => "Exceed withdrawal frequency limits",
			"75" => "PIN tried exceeded",
			"76" => "Incorrect reversal",
			"77" => "Lost or stolen card",
			"78" => "Merchant is in blacklist",
			"79" => "Account status is false",
			"87" => "Incorred passport",
			"88" => "Incorrect date of birth",
			"89" => "Not approved",
			"90" => "Cutoff in progress",
			"91" => "Issuer or JCB switch down",
			"92" => "Institution unavailable",
			"94" => "Duplicate transaction",
			"96" => "System error",
		);
		return $messages[$index];
	}
}
?>
