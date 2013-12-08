<?php

/*
	Try this sample code to try DKIM and/or Domain Keys signature headers in your e-mails.
	Uncomment the mail() lines to actually test the sending of e-mail.
	Note that Domain Keys is currently NOT usable with PHP's mail() function (see the class file for more information).
	So Domain Keys is disabled by default, enable it in the options if you need it.
*/


// YOUR E-MAIL
$to = 'test@example.com';

$subject = 'My subject';

$headers =
'MIME-Version: 1.0
From: "Sender" <sender@example.com>
Content-type: text/html; charset=utf8';

$message =
	'<html>
		<header></header>
		<body>
			Hello, this a DKIM test e-mail
		</body>
	</html>';
	
	

// 1) YOU USUALLY DID :
// mail($to, $subject, $message, $headers);



// 2) NOW YOU WILL DO (after setting up the config file and your DNS records) :

// Make sure linefeeds are in CRLF format - it is essential for signing
$message = preg_replace('/(?<!\r)\n/', "\r\n", $message);
$headers = preg_replace('/(?<!\r)\n/', "\r\n", $headers);

require_once('mail-signature.class.php');
require_once('mail-signature.config.php');

$signature = new mail_signature(
	MAIL_RSA_PRIV,
	MAIL_RSA_PASSPHRASE,
	MAIL_DOMAIN,
	MAIL_SELECTOR
);
$signed_headers = $signature -> get_signed_headers($to, $subject, $message, $headers);

// mail($to, $subject, $message, $signed_headers.$headers);



// 3) OR USE OPTIONS TO ADD SOME FLAVOR :

$message = preg_replace('/(?<!\r)\n/', "\r\n", $message);
$headers = preg_replace('/(?<!\r)\n/', "\r\n", $headers);

$options = array(
	'use_dkim' => false,
	'use_domainKeys' => true,
	'identity' => MAIL_IDENTITY,
	// if you prefer simple canonicalization (though the default "relaxed" is recommended)
	'dkim_body_canonicalization' => 'simple',
	'dk_canonicalization' => 'nofws',
	// if you want to sign the mail on a different list of headers than the default one (see class constructor). Case-insensitive.
	'signature_headers' => array(
		'message-Id',
		'Content-type',
		'To',
		'subject'
	)
);

require_once('mail-signature.class.php');
require_once('mail-signature.config.php');

$signature = new mail_signature(
	MAIL_RSA_PRIV,
	MAIL_RSA_PASSPHRASE,
	MAIL_DOMAIN,
	MAIL_SELECTOR,
	$options
);
$signed_headers = $signature -> get_signed_headers($to, $subject, $message, $headers);

// mail($to, $subject, $message, $signed_headers.$headers);

?>