<?php
require_once 'Mail.php';
require_once 'Mail/mime.php';
define("_parseCSV_ASCII_TAB", 9);

class util {
	static public function curl_init($url, $params, $isAsync = false) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, false);     // Don't return the header, just the html
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);  // Follow the redirects (needed for mod_rewrite)
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // return into a variable
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_CAINFO, "/etc/ssl/certs/mozilla.pem"); // http://davidwalsh.name/php-ssl-curl-error
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

		if ($isAsync) {
			curl_setopt($ch, CURLOPT_TIMEOUT, 1);          // superfast timeout to go into async
			curl_setopt($ch, CURLOPT_FRESH_CONNECT, true); // Don't used a cached connection handle
		} else {
			curl_setopt($ch, CURLOPT_TIMEOUT, 40); // times out after 40s, use 0 for indefinite waiting
		}
		return $ch;
	}
	
	static public function renameTempfile($paramName, $baseDir) {
		$oldPath = $_FILES[$paramName]['tmp_name'];
		$newPath = $baseDir . $_FILES[$paramName]['name'];
		move_uploaded_file($oldPath, $newPath);
		$_FILES[$paramName]['tmp_name'] = $newPath;
	}
	public static function saveAllFiles() {
		$baseDir = util::createTempDir();
		foreach ($_FILES as $key => $value) {
			util::renameTempfile($key, $baseDir);
		}
		return $baseDir;
	}
	public static function createTempDir() {
		$dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . util::randomString();
		if (!file_exists($dir)) { mkdir($dir, 0777, true); }
		return $dir . DIRECTORY_SEPARATOR;
	}
	private static function randomString($length = 5) {
		return substr(str_shuffle(MD5(microtime())), 0, $length);
	}
	public static function delTree($dir) {
		if (!file_exists($dir)) { return true; }
		if (!is_dir($dir) || is_link($dir)) { return unlink($dir); }
		foreach (scandir($dir) as $item) {
			if ($item == '.' || $item == '..') continue;
			if (!util::delTree($dir . "/" . $item)) {
				chmod($dir . "/" . $item, 0777);
				if (!util::delTree($dir . "/" . $item)) return false;
			};
		}
		return rmdir($dir);
	}
	
	static public function generateCSV($rows) {
		foreach ($rows as &$columns) {
			foreach ($columns as &$column) {
				if (strpos($column, ',') !== false || strpos($column, '"') !== false) {
					$column = '"' . str_replace('"', '""', $column) . '"';
				}
			}
			$columns = implode(',', $columns);
		}
		return implode(PHP_EOL, $rows);
	}
	
	static public function parseCSV($data, $delimiter = '\t', $enclosure = '"', $newline = "\n") {
		$data = str_replace("\r\n", "\n", $data);
		
		$pos = $last_pos = -1;
		$end = strlen($data);
		$row = 0;
		$quote_open = false;
		$trim_quote = false;

		$replace_char = $delimiter == '\t' ? chr(_parseCSV_ASCII_TAB) : $delimiter;
		$return = array();

		// Create a continuous loop
		for ($i = -1;; ++$i){
			++$pos;
			// Get the positions
			$comma_pos = strpos($data, $delimiter, $pos);
			$quote_pos = strpos($data, $enclosure, $pos);
			$newline_pos = strpos($data, $newline, $pos);

			do {
				// Which one comes first?
				$pos = min(($comma_pos === false) ? $end : $comma_pos, ($quote_pos === false) ? $end : $quote_pos, ($newline_pos === false) ? $end : $newline_pos);

				// Cache it
				$char = (isset($data[$pos])) ? $data[$pos] : null;
				if ($char == $enclosure) { // ignore double quotes
					if (isset($data[$pos + 1]) && $data[$pos + 1] == $enclosure) {
						$quote_pos = strpos($data, $enclosure, $pos + 2);
						continue;
					}
				}
				break;
			} while (true);
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
						$index = strpos($return[$row], $enclosure . $replace_char);
						if ($index === false) {
							$return[$row] = substr(trim($return[$row]), 0, -1);
						} else {
							$return[$row] = substr($return[$row], 0, $index) . substr($return[$row], $index + 1);
						}
					}
				} else {
					$return[$row] = '';
				}
				
				// And we're done
				if ($done) { break; }

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
	
	public static function absURL($fileName) {
		$path = $_SERVER['SCRIPT_NAME'];
		util::removeAfter($path, '/', false);
		return 'http://www.wycliffe-services.net' . $path . '/' . $fileName;
	}

	static public function removeAfter(&$str, $postFix, $fromFront = true) {
		$pos = $fromFront ? strpos($str, $postFix) : strrpos($str, $postFix);
		if ($pos === false) { return false; }
		if ($pos == 0) {
			$str = '';
			return true;
		}
		$str = rtrim(substr($str, 0, $pos));
		return true;
	}
	
	static public function removeBefore(&$str, $prefix, $fromFront = true) {
		$pos = $fromFront ? strpos($str, $prefix) : strrpos($str, $prefix);
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
	
	static public function sendEmail(&$msg, $fromName, $from, $to, $subject, $body, $cc = '', $bcc = '', $replyTo = '', $attachments = array(), $simulate = 0) {
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

		if ($simulate == 1) {
			$msg = trim(preg_replace('/\s+/', ' ', print_r($headers, true) . $body));
			if (count($attachments) > 0) { $msg = $msg . ' Number of attachments: ' . count($attachments); }
			return false;
		} else if ($simulate == 2) {
			foreach ($headers as $key => &$value) {
				$value = '<div><div class="col1">' . $key . ':</div><div class="col2">' . htmlentities($value, ENT_NOQUOTES) . '</div></div>';
			}
			$headers[] = '<br />';
			$headers[] = $body;
			$msg = implode('', $headers);
			return true;
		}
				
        $mime = new Mail_mime('');
//        $mime->setTXTBody($body); // only support sending html emails
        $mime->setHTMLBody('<html><body>'.$body.'</body></html>');

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
		
		openlog('util::sendEmail', LOG_NDELAY, LOG_MAIL);
		syslog(LOG_NOTICE, "From $from to $to $subject");
		closelog();
		return true;
	}
	
	public static function isJaarsEmail($email) {
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { return false; }

		$arr = explode('@', $email);
		if (count($arr) != 2) { return false; }
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
	
	public static function dump($variable, $die = true) {
		$str = '<pre>' . print_r($variable, true) . '</pre>';
		if ($die) {	die($str); }
		echo $str;
	}
}
?>