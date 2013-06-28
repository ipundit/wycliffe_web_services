<?php 
require_once 'Mail.php';
require_once 'Mail/mime.php';

class Email
{
	public function send($from, $name, $email, $bcc, $subject, $body, $signature = '') {
		$to = $name . " <" . $email .">";

        $headers = array(
			'From' => $from,
			'To'   => $to,
			'Bcc'  => $bcc,
			'Subject' => $subject,
		);
		if ($signature != '') { $signature = '\n\n' . $signature; }
		$body = "Dear " . $name . ',\n\n' . $body . $signature;

        $mime = new Mail_mime('');
        $mime->setTXTBody($body);
        $mime->setHTMLBody('<html><body>'.str_replace('\n', '<br />', $body).'</body></html>');
        $body = $mime->get();

        $headers = $mime->headers($headers);
		$params['sendmail_path'] = '/usr/lib/sendmail';

        $mail =& Mail::factory('sendmail', $params);
		$mail->send($to, $headers, $body);
	}
}
?>
