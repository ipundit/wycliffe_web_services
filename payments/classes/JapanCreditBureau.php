<?php
require_once 'classes/User.php';

class JapanCreditBureau
{
        public function makePurchase($user, $purchase, $msg) {

                require_once 'classes/Organization.php';

                $po = new Organization($purchase->po());

$isTesting = 1;

                if (isTesting) {
                        $url = "https://beta-jcbacqapi.pvbcard.com/WSJCBAcqAPI.asmx";
                } else {
                        $url = "https://jcbacqapi.pvbcard.com/WSJCBAcqAPI.asmx";
                }

                $XPost = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                <soap:Envelope xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\">
                  <soap:Body>
                    <SaleTransaction xmlns=\"http://WSJCBAcq.pvbcard.com/\">
                      <pCommandString>" . this->makeCommandString($user, $purchase, $po, $isTesting) . "</pCommandString>
                    </SaleTransaction>
                  </soap:Body>
                </soap:Envelope>";

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_HEADER, 0); // Don<92>t return the header, just the html
                curl_setopt($ch, CURLOPT_URL, $url); // set url to post to
                curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
                curl_setopt($ch, CURLOPT_TIMEOUT, 40); // times out after 40s
                curl_setopt($ch, CURLOPT_POSTFIELDS, $XPost); // add POST fields
                curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . "/cacert.crt");
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('SOAPAction: http://WSJCBAcq.pvbcard.com/SaleTransaction'));

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
                
				$responseString = $vals[$index['SaleTransactionResult'][0]]['value']
				
				$fields = this->shred($responseString);
				
				if ($fields["FIELD=39"] == "00") {
						$msg = "All well!";
                        return true;					
				} else {
						$msg = "Error : " . $fields["FIELD=39"];
						return false;
				}
        }

        private function makeCommandString($user, $purchase, $po, $isTest) {

            return "FIELD=2&VALUE="  . $purchase->creditCard() . ";" .
            	   "FIELD=4&VALUE="  . this->formatAmount($purchase->amount()) . ";" .
            	   "FIELD=14&VALUE=" . this->formatExpiry($purchase->month(), $purchase->year()) . ";" .
            	   "FIELD=41&VALUE=" . $po->terminal_id($isTest) . ";" .     
            	   "FIELD=42&VALUE=" . $po->merchant_id($isTest);          	              	   
        }
        
        private function formatAmount($amount) {
        	
        	$pos = strpos($amount, ".");
        	
        	if ($pos === FALSE) {
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
        	
        	$fieldValuePairs = explode($responseString, ";");
        	
        	foreach ($fieldValuePairs as &$fieldValuePair) {
        		$splitFieldAndValue = explode($fieldValuePair, "&");
        		
        		$shreddings[$splitFieldAndValue[0]] = $splitFieldAndValue[1];
        	}
        	
        	return $shreddings;
        }          
}
?>
