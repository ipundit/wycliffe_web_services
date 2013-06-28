<?php
require_once 'classes/User.php';

class JapanCreditBureau
{
	public function makePurchase($user, $purchase, $msg) {
		$msg = "Stubbed out JapanCreditBureau class.";
		return true;
		
		// test
		
		// fixme2: Implement this according to JCB interface
		
		require_once 'classes/Organization.php';
		
		$po = new Organization($purchase->po());
		
$isTesting = 1;
		if (isTesting) {
			$url = "https://dev.psigate.com:7989/Messenger/XMLMessenger";
		} else {
			$url = "https://secure.psigate.com:7934/Messenger/XMLMessenger";
		}

		$XPost = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
			<Order><StoreID>$po->store_id($isTesting)</StoreID><Passphrase>$po->pass_phrase($isTesting)</Passphrase>
			<Subtotal>".$purchase->amount()."</Subtotal><PaymentType>CC</PaymentType>
			<CardAction>0</CardAction>
			<CardNumber>".$purchase->creditCard()."</CardNumber>
			<CardExpMonth>".$purchase->month()."</CardExpMonth>
			<CardExpYear>".$purchase->year()."</CardExpYear>
			<Bname>".$user->name()."</Bname>
			<Baddress1>".$user->address()."</Baddress1>
			<Baddress2>".$user->address2()."</Baddress2>
			<Bcity>".$user->city()."</Bcity>
			<Bprovince>".$user->state()."</Bprovince>
			<Bpostalcode>".$user->postalCode()."</Bpostalcode>
			<Bcountry>".$user->country()."</Bcountry>
			<CustomerIP>".$_SERVER['REMOTE_ADDR']."</CustomerIP>
			<Phone>".$user->phoneNumber()."</Phone>
			<Email>".$user->emailAddress()."</Email>
			<Item>
				<ItemID>Donation</ItemID>
				<ItemDescription>Donation through website</ItemDescription>
				<ItemQty>1</ItemQty>
				<ItemPrice>".$purchase->amount()."</ItemPrice>
				<Option>
					<Project>".$purchase->project()."</Project>
				</Option>
			</Item>";
		$XPost = $XPost . "</Order>";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0); // Don’t return the header, just the html
		curl_setopt($ch, CURLOPT_URL, $url); // set url to post to
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
		curl_setopt($ch, CURLOPT_TIMEOUT, 40); // times out after 40s
		curl_setopt($ch, CURLOPT_POSTFIELDS, $XPost); // add POST fields
		curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . "/cacert.crt");

		$result = curl_exec($ch); // run the whole process
		if (curl_errno($ch)) {
			$msg = curl_error($ch);
			return false;
		} else {
			curl_close($ch);
		}

		$xml_parser = xml_parser_create();
		xml_parser_set_option($xml_parser,XML_OPTION_CASE_FOLDING,0);
		xml_parser_set_option($xml_parser,XML_OPTION_SKIP_WHITE,1);
		xml_parse_into_struct($xml_parser, $result, $vals, $index);
		xml_parser_free($xml_parser);

		switch ($vals[$index['Approved'][0]]['value']) {
		case "APPROVED":
			$msg = $vals[$index['OrderID'][0]]['value'];
			return true;
		case "DECLINED":
			$msg = "Your credit card was declined";
			return false;
		default:
			$msg = $vals[$index['ErrMsg'][0]]['value'];
			return false;
		}
	}
}
?>