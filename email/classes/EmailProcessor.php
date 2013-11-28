<?php 
require_once 'util.php';
require_once 'classes/Email.php';
define('DUMP_TO_DRY_RUN', false);
define('_EMAIL_PROCESSOR_TIMEOUT_', 30);

class EmailProcessor
{
	public static function readFromData(&$message, &$error) {
		if (!EmailProcessor::receivedEmail($buffer, $error)) { return false; }
		if (DUMP_TO_DRY_RUN) {
			file_put_contents('./dryRun.html', print_r($buffer, true));
			echo "Dumped to dryRun.html";
			return false;
		}
		return EmailProcessor::parse($buffer, $message, $error, EmailProcessor::simulate() == 1);
	}
	
	public static function parse($buffer, &$message, &$error, $simulateSpamCheck = false) {
		$mail = mailparse_msg_create();
		mailparse_msg_parse($mail, $buffer);
		$struct = mailparse_msg_get_structure($mail);

		if (!EmailProcessor::initHeaders($mail, $struct, $message, $error)) { return false; }

		EmailProcessor::initBody($mail, $struct, $buffer, $message);
		$body = $message['html'] == '' ? $message['body'] : $message['html'];
		
		
		$email = '';
		$name = EmailProcessor::splitNameEmail($message['from'], $email);
		if (Email::isSpam($name, $email, $body, $simulateSpamCheck)) {
			$error = "spam email discarded";
			return false;
		}

		return EmailProcessor::initAttachments($mail, $struct, $buffer, $message, $error);
	}
	
	private static function splitNameEmail($name, &$email) {
		$startIndex = strpos($name, '<');
		if ($startIndex === false) {
			$email = $name;
			return $name;
		}

		$endIndex = strpos($name, '>', $startIndex);
		if ($endIndex === false) { 
			$email = $name;
			return $name;
		}
		
		$email = substr($name, $startIndex + 1, $endIndex - $startIndex - 1);
		return rtrim(substr($name, 0, $startIndex - 1));
	}

	public static function processMessage($to, $message, &$error, $deleteAttachments = false) {
		try {
			set_time_limit(_EMAIL_PROCESSOR_TIMEOUT_);
			$retValue = EmailProcessor::processMessageImpl($to, $message, $error, $deleteAttachments);
		} catch (Exception $e) {}
		if ($deleteAttachments) { EmailProcessor::deleteAttachments($message['attachments']); }
		
		return $retValue;
	}
	
	private static function processMessageImpl($to, $message, &$error, $deleteAttachments = false) {
		if (EmailProcessor::simulate() == 3) {
			if ($deleteAttachments) { EmailProcessor::deleteAttachments($message['attachments']); };
			$error = trim(preg_replace('/\s+/', ' ', print_r($message, true)));
			return false;
		}
		
		if ($to == '') { $to = $message['to']; }
		$templateName = EmailProcessor::getTemplateName($to, $error);
		if ($templateName === false) { return false; }
		
		$template = EmailProcessor::readTemplate($templateName, $error);
		if ($template === false) { return false; }

		EmailProcessor::setDerivedVariables($message);
		// fixme: write regression tests for functions from here onwards
		if (!EmailProcessor::parseTemplate($template, $error)) { return false; }
		if ($template['url'] === false) { // should only happen for emails sent to help@wycliffe-services.net
			$parseError = false;
		} else {
			$parseError = true;
			
			if (EmailProcessor::parseEmail($message, $template, $params, $error)) {
				$params['emailProcessor'] = EmailProcessor::serialize($template, $templateName, $message, '');
				$ch = util::curl_init($template['url'], $params, true);
				$result = curl_exec($ch);
				return true;
			}
		}
		return EmailProcessor::sendDefaultForm($template, $templateName, $message, $error, $parseError, EmailProcessor::simulate());
	}

	private static function serialize(&$template, &$templateName, &$message, $str) {
		$tokenizer = "\036";
		if ($str == '') {
			return serialize($template) . $tokenizer . $templateName . $tokenizer . serialize($message);
		}
		$arr = explode($tokenizer, $str);
		$template = unserialize($arr[0]);
		$templateName = $arr[1];
		$message = unserialize($arr[2]);
	}
	
	public static function notifyEmailProcessingComplete($error) {
		$str = isset($_POST['emailProcessor']) ? $_POST['emailProcessor'] : '';
		if ($str == '') { return true; }
		
		$template = '';
		$templateName = '';
		$message = array();
		EmailProcessor::serialize($template, $templateName, $message, $str);
		if (!filter_var($templateName, FILTER_SANITIZE_STRING, array('flags'=>FILTER_FLAG_NO_ENCODE_QUOTES))) {
			$error = "Invalid templateName";
			return false;
		}
		return EmailProcessor::sendDefaultForm($template, $templateName, $message, $error, false, 0);
	}
		
	private static function sendDefaultForm($template, $templateName, $message, &$error, $parseError, $simulate) {
		if ($error == '') {
			EmailProcessor::fillInTemplate($template['body'], $message);
			if (!EmailProcessor::extractParams($template['body'], $message, $template['params'], $error) && $error != '') { return false; }
			
			$subject = $template['title'];
			$body = $template['body'];
		} else {
			$subject = 'Re: ' . $message['subject'];
			
			if ($parseError) {
				$body = 'We found an error in your form and could not execute your request. Please reply to this email to correct the following error: <b>' . $error . '</b>';
			} elseif (is_numeric($error)) {
				$body = 'Your request was processed successfully; number of emails sent: ' . $error;
				EmailProcessor::log("sendDefaultForm", $body);
			} else {
				$body = 'There was an error while running your request: <b>' . $error . '</b>';
				EmailProcessor::log("sendDefaultForm", $error);
			}
			$body .= '.<br><br>Regards,<br>Wycliffe Web Services<br><hr>';

			if ($message['body'] == '') {
				$body = preg_replace('/^.*?<body.*?>/s', $body, $message['html']);
				$body = preg_replace('/<\/body>.*/s', '', $body);
			} else {
				$body .= $message['body'];
			}
		}
		$recipient = $message['reply-to'] == '' ? $message['from'] : $message['reply-to'];
		return util::sendEmail($error, $templateName, $templateName . '@wycliffe-services.net', $recipient, $subject, $body, '', '', '', array(), array(), $simulate);
	}
	
	private static function log($functionName, $message) {
		openlog("EmailProcessor::$functionName", LOG_NDELAY, LOG_MAIL);
		syslog(LOG_NOTICE, "$message");
		closelog();
	}

	private static function parseEmail($message, $template, &$params, &$error) {
		$body = $message['body'] == '' ? $message['html'] : $message['body'];
		$params = $template['params'];

		return EmailProcessor::extractParams($body, $message, $params, $error);
	}
	
	private static function extractURL(&$params, &$error) {
		if (!isset($params['url'])) { return false; }
		$url = $params['url'][0];
		if (!filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)) {
			$error = 'url not valid in template';
			return false;
		}
		unset($params['url']);
		return $url;
	}

	private static function extractParams($body, $message, &$params, &$error) {
		$retValue = true;
		$bodyVars = EmailProcessor::parseBody($body, $error);
		
		if ($bodyVars === false) { return false; }
		$attachments = $message['attachments'];
		
		foreach ($params as $key => &$value) {
			if ($value[0] == 'attachment') {
				if (empty($attachments)) { // get attachment
					unset($params[$key]);
					continue;
				} else {
					$value = '@' . array_shift($attachments);
					continue;
				}
			} else if (util::removeBefore($value[0], 'email_')) {
				$value = $message[$value[0]];
				continue;
			} else if (util::removeBefore($value[0], 'attachment_')) {
				$gotAttachment = false;
				foreach ($attachments as $key => $attachment) {
					$temp = $attachment;
					util::removeBefore($temp, '/', false);

					if ($value[0] == $temp) {
						$value = '@' . $attachment;
						unset($attachments[$key]);
						$gotAttachment = true;
						break;
					}
				}
				if (!$gotAttachment) {
					// $error = 'You must attach ' . $value; // Just resend the default template without an error message
					$value = 'NOT_FOUND';
					$retValue = false;
				}
				continue;
			} else if (isset($bodyVars[$value[0]])) {
				$value[0] = $bodyVars[$value[0]];
			} else {
				$value[0] = '';
			}
			$value = EmailProcessor::processMetaCommand($value, $error);
			if ($value === false) { return false; }
		}
		return $retValue;
	}

	private static function processMetaCommand($value, &$error) {
		if ($value[2] == 1 && $value[0] == '') { return false; }
		if ($value[1] == '') { return EmailProcessor::trimStartEndTags($value[0], 'br'); }
		
		if (util::removeBefore($value[1], 'trim_')) {
			$retValue = EmailProcessor::trimStartEndTags($value[0], '(br|p|div)');
			switch ($value[1]) {
			case 'all_tags':
				$retValue = filter_var($retValue, FILTER_SANITIZE_STRING, array('flags'=>FILTER_FLAG_NO_ENCODE_QUOTES));
			case 'whitespace':
				break;
			default:
				$retValue =  EmailProcessor::trimStartEndTags($retValue, $value[1]);
			}
			return $retValue;
		}
		$error = $value[1] . ' is not a recognized command';
		return false;
	}

	private static function trimStartEndTags($str, $tagName) {
		$retValue = preg_replace('/(<\/?' . $tagName . '>\n?)+$/', '', $str);
		return preg_replace('/^(<' . $tagName . '.*?>\n?)+/', '', $retValue);
	}
	
	private static function setDerivedVariables(&$message) {
		if (preg_match('/^(.*) <(.+@.+\..+)>$/', $message['from'], $matches) == 1) {
			$message['fromName'] = $matches[1];
			$message['fromEmail'] = $matches[2];

			$names = explode(' ', $message['fromName'], 2);
			$message['firstName'] = $names[0];
		} else {
			$message['fromEmail'] = $message['from'];
			
			$names = explode('@', $message['fromEmail'], 2);
			$message['fromName'] = $names[0];
			$message['firstName'] = $names[0];
		}
	}
	
	private static function fillInTemplate(&$str, $message) {
		$pos = -1;
		do {
			if (preg_match('/.*?\$(\w+).*/s', $str, $matches, PREG_OFFSET_CAPTURE, $pos + 1) != 1) { return; }
			
			$variable = $matches[1][0];
			$pos = $matches[1][1] - 1;
			$afterVariable = $pos + strlen($variable) + 1;
			
			if ($pos > 0 && substr($str, $pos - 1, 1) == '$') {
				$str = substr($str, 0, $pos) . $variable . substr($str, $afterVariable);
				$pos++;
				continue;
			}

			if (isset($message[$variable])) {
				$variable = $message[$variable];
				$str = substr($str, 0, $pos) . $variable . substr($str, $afterVariable);
			}
			
		} while (true);
	}
	
	private static function parseBody($body, &$error) {
		$body = str_replace("\r\n", "\n", $body);
		$body = preg_replace('/(<\/?blockquote.*?>\n?)+/s', '', $body);
		$body = preg_replace('/(\n+ +)/s', ' ', $body);

		$body = preg_replace('/<h4>/s', '<br><h4>', $body);
		$body = preg_replace('/<\/h4>/s', '</h4><br>', $body);
		$body = str_replace("<br>", "<br>\n", $body);
		
		$retValue = array();
		
		$inMultiLineTag = false;
		$lines = explode(PHP_EOL, $body);
		for ($i = 0; $i < count($lines); $i++) { 
			if (preg_match('/^.+?(:)[ <]/', $lines[$i], $matches, PREG_OFFSET_CAPTURE) != 1) { continue; }

			util::removeAfter($lines[$i], '#');
			$colon = $matches[1][1];
			if ($colon >= strlen($lines[$i])) { continue; }
			
			$key = trim(substr($lines[$i], 0, $colon));
			if (strpos($key, ':') > 0) { continue; }
			if (util::startsWith($key, '<')) { util::removeBefore($key, '>'); }
			
			$value = substr($lines[$i], $colon + 1);
			if (util::startsWith($value, '</')) { util::removeBefore($value, '>'); }
			
			$prefix = '';
			if (util::endsWith($key, '->')) {
				$prefix = '->';
			} else if (util::endsWith($key, '-&gt;')) {
				$prefix = '-&gt;';
			}
			if ($prefix == '') {
				if (util::startsWith($value, '<')) {
					while (!util::endsWith($value, '>')) {
						$value .= ' ' . $lines[++$i];
					}
				}
			} else {
				$inMultiLineTag = true;
				$key = substr($key, 0, strlen($key) - strlen($prefix));
				
				$prefix = $prefix . $key;
				for ($i = $i + 1; $i < count($lines); $i++) {
					if (strpos($lines[$i], $prefix) !== false) {
						$inMultiLineTag = false;
						break;
					} else {
						$value.= PHP_EOL . ltrim($lines[$i]);
					}
				}
			}
			
			if ($inMultiLineTag) {
				$error = $key . ' does not have an ending tag';
				return false;
			}
			
			if (isset($retValue[$key])) { 
				$error = $key . ' can only be set in the form once';
				return false;
			}
			$retValue[$key] = trim($value);
		}
		
		return $retValue;
	}
	
	private static function parseTemplate(&$template, &$error) {
		if (!EmailProcessor::extractMeta($template, $title, $params, $error)) { return false; }

		$url = EmailProcessor::extractURL($params, $error);
		$body = EmailProcessor::extractBody($template, $error);
		if ($body === false) { return false; }
		
		$template = array('title'=>$title, 'url'=>$url, 'params'=>$params, 'body'=>$body);
		return true;
	}
	private static function extractMeta($template, &$title, &$params, &$error) {
		$head = EmailProcessor::extractTag($template, 'head', $error);
		if ($head === false) { return false; }

		$title = EmailProcessor::extractTag($head, 'title',  $error);
		if ($title === false) { return false; }
		
		$params = array();
		$offset = 0;
		
		while (preg_match('/.+?meta name="(.+?)" content="(.+?)"( command="(.+?)")?( required="(.+?)")?.*/', $head, $matches, PREG_OFFSET_CAPTURE, $offset) == 1) {
			$command = isset($matches[4]) ? $matches[4][0] : '';
			$required = isset($matches[6]) ? $matches[6][0] : '';
			$params[$matches[1][0]] = array($matches[2][0], $command, $required);
			$offset = $matches[2][1];
		}
		return true;
	}
	private static function extractBody($template, &$error) {
		return EmailProcessor::extractTag($template, 'body', $error);
	}
	private static function extractTag($template, $tag, &$error) {
		$startTag = '<' . $tag . '>';
		$endTag = '</' . $tag . '>';
		
		$startIndex = strpos($template, $startTag);
		if ($startIndex === false) { 
			$error = 'could not find opening ' . $tag . ' tag';
			return false;
		}
		$startIndex += strlen($startTag);

		$endIndex = strpos($template, $endTag, $startIndex);
		if ($endIndex === false) { 
			$error = 'could not find closing ' . $tag . ' tag';
			return false;
		}
		return substr($template, $startIndex, $endIndex - $startIndex);
	}

	private static function getTemplateName($to, &$error) {
		util::removeBefore($to, "<");
	
		if (!util::removeAfter($to, '@wycliffe-services.net')) {
			$error = "invalid wycliffe-services.net domain";
			return false;
		}
		
		if (strpos($to, '/') !== false || strpos($to, '..') !== false) {
			$error = "invalid wycliffe-services.net address";
			return false;
		}
		return $to;
	}
	
	private static function readTemplate($templateName, &$error) {
		$path = $templateName == 'help' ? './help_template.html' : '../' . $templateName . '/email_template.html';
		if (!file_exists($path)) { 
			$error = "template does not exist";
			return false;
		}
		return file_get_contents($path);
	}
	
	private static function receivedEmail(&$buffer, &$error) {
		if (!EmailProcessor::getFilePath($path, $error)) { return false; }

		if ($path == '') { 
			$buffer = '';
			$handle = fopen('php://stdin', 'r');
			while(!feof($handle)) {
				$buffer .= fgets($handle);
			}
			fclose($handle);
		} else {
			$buffer = file_get_contents($path);
		}
		return true;
	}
	private static function getFilePath(&$path, &$error) {
		$fileName = isset($_GET['testFile']) ? $_GET['testFile'] : (isset($_POST['testFile']) ? $_POST['testFile'] : '');
		if ($fileName == '') { return true; }
		
		$fileName = filter_var($fileName, FILTER_SANITIZE_STRING, array('flags'=>FILTER_FLAG_NO_ENCODE_QUOTES));
		if ($fileName == '') {
			$error = 'invalid fileName';
			return false;
		}
		
		$path = './tests/' . $fileName;
		if (file_exists($path)) { return true; }

		$error = util::absURL('tests/' . $fileName) . ' does not exist';
		$path = '';
		return false;
	}
	
	private static function initHeaders($mail, &$struct, &$message, &$error) {
		$info = EmailProcessor::getInfo($mail, $struct[0]);

		if (!isset($info['headers']['from'])) {
			$error = "malformed email";
			return false;
		}
		if (EmailProcessor::isBounceMessage($info)) {
			$error = "bounce email discarded";
			return false;
		}

		$fields = 'from,to,cc,reply-to,subject,date';
		foreach (explode(',', $fields) as $field) {
			$message[$field] = isset($info['headers'][$field]) ? $info['headers'][$field] : '';
		}

		switch ($info['content-type']) {
		case 'text/plain':
		case 'text/html':
			break;
		default:
			array_shift($struct);
		}
		return true;
	}
	private static function initBody($mail, &$struct, $buffer, &$message) {
		$message['body'] = '';
		$message['html'] = '';

		while (!empty($struct)) {
			$info = EmailProcessor::getInfo($mail, $struct[0]);
			switch ($info['content-type']) {
			case 'multipart/alternative':
				break;
			case 'text/plain':
				$message['body'] = EmailProcessor::getData($buffer, $info);
				break;
			case 'text/html':
				$message['html'] = EmailProcessor::getData($buffer, $info);
				break;
			default:
				return;
			}
			array_shift($struct);
		}
	}
	private static function initAttachments($mail, &$struct, $buffer, &$message, &$error) {
		$message['attachments'] = array();
		$baseDir = util::createTempDir();

		while (!empty($struct)) {
			$info = EmailProcessor::getInfo($mail, array_shift($struct));
			
			if ($info['content-type'] == 'message/rfc822') {
				$key = $struct[0];
				
				$temp = EmailProcessor::getInfo($mail, array_shift($struct));
				$info['disposition-filename'] = $temp['headers']['subject'] . '.eml';
				EmailProcessor::processAttachment($buffer, $info, $baseDir, $message);
				
				if (count($struct) == 0) { return; }
				while (util::startsWith($struct[0], $key)) {
					array_shift($struct);
				}
				continue;
			}
			if ($info['content-disposition'] == 'attachment') {
				EmailProcessor::processAttachment($buffer, $info, $baseDir, $message);
				continue;
			}
			$error = 'Unexpected info: ' . print_r($info, true);
			util::deltree($baseDir);
			return false;
		}
		
		if (count($message['attachments']) == 0) { rmdir($baseDir); }
		return true;
	}

	private static function getData($buffer, $info) {
		return substr($buffer, $info['starting-pos-body'], $info['ending-pos-body'] - $info['starting-pos-body'] + 1);
	}

	private static function getInfo($mail, $struct) {
		$section = mailparse_msg_get_part($mail, $struct);
		return mailparse_msg_get_part_data($section);
	}

	private static function processAttachment($buffer, $info, $baseDir, &$message) {
		$newPath = $baseDir . $info['disposition-filename'];
		$message['attachments'][] = $newPath;
		if ($info['transfer-encoding'] == 'base64') { 
			file_put_contents($newPath, base64_decode(EmailProcessor::getData($buffer, $info)));
		} else {
			file_put_contents($newPath, EmailProcessor::getData($buffer, $info));
		}
	}

	private static function isBounceMessage($info) {
		if (util::startsWith($info['headers']['from'], 'postmaster')) { return true; }
		if (isset($info['content-report-type']) && $info['content-report-type'] == 'delivery-status') { return true; }
		return false;
	}
	
	private static function simulate() {
		$simulate = isset($_GET['simulate']) ? $_GET['simulate'] : (isset($_POST['simulate']) ? $_POST['simulate'] : 0);
		return filter_var($simulate, FILTER_VALIDATE_INT, array('filter'=>FILTER_VALIDATE_INT, 'options'=>array("min_range"=>0, "max_range"=>3)));
	}
	
	private static function deleteAttachments($attachments) {
		if (count($attachments) == 0) { return; }
		
		$arr = explode(DIRECTORY_SEPARATOR, $attachments[0]);
		array_pop($arr);
		util::delTree(implode(DIRECTORY_SEPARATOR, $arr));
	}
}
?>