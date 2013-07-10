<?php
require_once 'Mail.php';
require_once 'Mail/mime.php';

class util {
	static public function curl_init($url, $params) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, false);     // Don't return the header, just the html
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
		curl_setopt($ch, CURLOPT_TIMEOUT, 40);       // times out after 40s
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_CAINFO, "/etc/ssl/certs/mozilla.pem"); // http://davidwalsh.name/php-ssl-curl-error
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		return $ch;
	}
	
	static public function renameTempfile($paramName) {
		$oldPath = $_FILES[$paramName]['tmp_name'];

		$arr = explode(DIRECTORY_SEPARATOR, $oldPath);
		$arr[sizeof($arr) - 1] = $_FILES[$paramName]['name'];
		$newPath = implode(DIRECTORY_SEPARATOR, $arr);

		move_uploaded_file($oldPath, $newPath);
		$_FILES[$paramName]['tmp_name'] = $newPath;
	}
	
	static public function parseCSV($data, $delimiter = '\t', $enclosure = '"', $newline = "\n") {
		$data = str_replace("\r\n", "\n", $data);
		
		$pos = $last_pos = -1;
		$end = strlen($data);
		$row = 0;
		$quote_open = false;
		$trim_quote = false;

		$replace_char = $delimiter == '\t' ? chr(ASCII_TAB) : $delimiter;
		$return = array();

		// Create a continuous loop
		for ($i = -1;; ++$i){
			++$pos;
			// Get the positions
			$comma_pos = strpos($data, $delimiter, $pos);
			$quote_pos = strpos($data, $enclosure, $pos);
			$newline_pos = strpos($data, $newline, $pos);

			// Which one comes first?
			$pos = min(($comma_pos === false) ? $end : $comma_pos, ($quote_pos === false) ? $end : $quote_pos, ($newline_pos === false) ? $end : $newline_pos);

			// Cache it
			$char = (isset($data[$pos])) ? $data[$pos] : null;
			$done = ($pos == $end);

			// It it a special character?
			if ($done || $char == $delimiter || $char == $newline){
				// Ignore it as we're still in a quote
				if ($quote_open && !$done){
					continue;
				}

				$length = $pos - ++$last_pos;

				// Get all the contents of this column
				if ($length > 0) {
					$return[$row] = substr($data, $last_pos, $length);
					$return[$row] = str_replace($enclosure . $enclosure, $enclosure, $return[$row]); // Remove double quotes
					$return[$row] = str_replace($replace_char . $enclosure, $replace_char, $return[$row]); // Remove starting quote

					if ($trim_quote) { // Remove trailing quote
						$return[$row] = substr(trim($return[$row]), 0, -1);
					}
				} else {
					$return[$row] = '';
				}
				
				// And we're done
				if ($done) {
					break;
				}

				// Save the last position
				$last_pos = $pos;

				// Next row?
				if ($char == $newline) {
					++$row;
				}

				$trim_quote = false;
			}
			// Our quote?
			else if ($char == $enclosure) {
				// Toggle it
				if ($quote_open == false){
					// It's an opening quote
					$quote_open = true;
					$trim_quote = false;

					// Trim this opening quote?
					if ($last_pos + 1 == $pos){
						++$last_pos;
					}
				}
				else {
					// It's a closing quote
					$quote_open = false;

					// Trim the last quote?
					$trim_quote = true;
				}
			}
		}
		return $return;
	}
	
	static public function removeAfter(&$str, $postFix) {
		$index = strpos($str, $postFix);
		if ($index === false) { return false; }
		if ($index == 0) {
			$str = '';
			return true;
		}
		$str = rtrim(substr($str, 0, $index - 1));
		return true;
	}
	
	static public function removeBefore(&$str, $prefix) {
		$pos = strpos($str, $prefix);
		if ($pos === false) { return false; }
		
		$str = ltrim(substr($str, $pos + strlen($prefix)));
		return true;
	}

	static public function startsWith($haystack, $needle) {
		$length = strlen($needle);
		if ($length == 0) { return true; }
		return (substr($haystack, 0, $length) === $needle);
	}
	
	static public function endsWith($haystack, $needle) {
		$length = strlen($needle);
		if ($length == 0) { return true; }
		return (substr($haystack, -$length) === $needle);
	}
	
	static public function sendEmail(&$msg, $fromName, $from, $to, $subject, $body, $cc = '', $bcc = '', $replyTo = '', $attachments = array(), $simulate) {
		if (util::isJaarsEmail($from)) {
			$sender = 'wycliffe-services-smtp@wycliffe.net';
			if ($replyTo != '') {
				$msg = "replyTo not supported for Jaars emails";
				return false;
			}
			$returnPath = $from;
		} else {
			$sender = $from;
			$returnPath = $replyTo;
			if ($replyTo != '' && $fromName != '') {
				$replyTo = $fromName . ' <' . $replyTo . '>';
			}
			if ($fromName != '') { $fromName .= ' via Wycliffe Web Services'; }
		}
	
		$headers = array(
			'Sender' => $sender,
			'From' => $fromName == '' ? $from : $fromName . ' <' . $from . '>',
			'To'   => $to,
			'Cc'   => $cc,
			'Reply-To' => $replyTo,
			'Return-Path' => $returnPath, // SMTP gives 501 error if this field is set to $fromName <$replyTo>
			'Subject' => $subject,
		);
		foreach ($headers as $key => $value) {
			if ($value == '') { unset($headers[$key]); }
		}

		if ($simulate) {
			$msg = trim(preg_replace('/\s+/', ' ', print_r($headers, true)));
			if (count($attachments) > 0) { $msg = $msg . ' Number of attachments: ' . count($attachments); }
			return false;
		}
				
        $mime = new Mail_mime('');
        $mime->setTXTBody($body);
        $mime->setHTMLBody('<html><body>'.str_replace(PHP_EOL, '<br />', $body).'</body></html>');

		foreach ($attachments as $file) {
			$mime->addAttachment($file);
		}

        $body = $mime->get();
        $headers = $mime->headers($headers);

		$mail = util::getFactory($from);
		
		if ($cc != '') { $to = $to . ', ' . $cc; }
		if ($bcc != '') { $to = $to . ', ' . $bcc; }
		$mail = $mail->send($to, $headers, $body);
		
		if (PEAR::isError($mail)) {
			$msg = $mail->getMessage();
			return false;
		}
		return true;
	}
	public static function isJaarsEmail($email) {
		$arr = explode('@', $email, 2);
		return in_array($arr[1], util::jaarsDomains());
	}	

	private static function getFactory($from) {
		if (preg_match('/.+@[' . implode(util::jaarsDomains(), '|') . ']/', $from)) {
			require_once('email_constants.php');
			$server = 'smtp';
			$params = array(
				'host' => 'mail.jaars.org',
				'port' => 587, // Jaars supports STARTTLS for encrypted connections
				'auth' => true,
				'username' => JAARS_USERNAME,
				'password' => JAARS_PASSWORD,
			);
		} else {
			$server = 'sendmail';
			$params['sendmail_path'] = '/usr/lib/sendmail';
		}
		return Mail::factory($server, $params);
	}
	
	private static function jaarsDomains() {
		return array('sil.org', 'wycliffe.net', 'wycliffe.org', 'jaars.org', 'kastanet.org');
	}
}
?>