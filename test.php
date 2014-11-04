<?php

/*
	Try this sample code to try DKIM and/or Domain Keys signature headers in your e-mails.
	Uncomment the mail() lines to actually test the sending of e-mail.
	Note that Domain Keys is currently NOT usable with PHP's mail() function (see the class file for more information).
	So Domain Keys is disabled by default, enable it in the options if you need it.
*/

//view error
ini_set("display_errors", "1");
error_reporting(E_ALL);

//define('MAILHEADER_EOL', "\r\n");
define('MAILHEADER_EOL', "\n");

// use this the project

require_once('mail-signature.class.php');
require_once('mail-signature.config.php');

// YOUR E-MAIL
$to = MAIL_TEST_EMAIL;
if ($to == 'admin@example.com') echo '<br>Edit MAIL_TEST_EMAIL in mail-signature.config.php<br>';

$subject = 'My subject - Test';

$headers =
'MIME-Version: 1.0'.MAILHEADER_EOL.
'From: "Sender" <sender@example.com>'.MAILHEADER_EOL.
'Content-type: text/html; charset=utf8';

$message =
	'<html>'.MAILHEADER_EOL.
		'<header></header>'.MAILHEADER_EOL.
		'<body>'.MAILHEADER_EOL.
			'Hello, this a DKIM test e-mail'.MAILHEADER_EOL.
		'</body>'.MAILHEADER_EOL.
	'</html>';
	
	

// 1) YOU USUALLY DID :
mail($to, $subject.'1', $message, $headers);
echo '<br>1:-<br>';

// 1a) NOW YOU WILL DO (after setting up the config file and your DNS records) :
// don't Make sure linefeeds are in CRLF format - it is essential for signing
get_signed_headers_mod($to, $subject, $message, $headers);
mail($to, $subject, $message, $headers);//Body and headers alredy modifited
echo '<br>1a:+<br>';

// 2) NOW YOU WILL DO (after setting up the config file and your DNS records) :
// don't Make sure linefeeds are in CRLF format - it is essential for signing

$signature = new mail_signature(
	MAIL_RSA_PRIV,
	MAIL_RSA_PASSPHRASE,
	MAIL_DOMAIN,
	MAIL_SELECTOR
);

$signed_headers = $signature -> get_signed_headers_mod($to, $subject.'2', $message, $headers);//Body and headers alredy modifited
mail($to, $subject.'2', $message, $headers);
echo '<br>2:'.$signed_headers.'<br>';

// 3) NOW YOU WILL DO (after setting up the config file and your DNS records) :

// Make sure linefeeds are in CRLF format - it is essential for signing
$message = preg_replace('/(?<!\r)\n/', "\r\n", $message);
$headers = preg_replace('/(?<!\r)\n/', "\r\n", $headers);

$signature = new mail_signature(
	MAIL_RSA_PRIV,
	MAIL_RSA_PASSPHRASE,
	MAIL_DOMAIN,
	MAIL_SELECTOR
);
$signed_headers = $signature -> get_signed_headers($to, $subject.'3', $message, $headers);//$message and $headers modification before in line current-20

mail($to, $subject.'3', $message, $signed_headers.$headers);//add hand result get_signed_headers
echo '<br>3:'.$signed_headers.'<br>';


// 4) OR USE OPTIONS TO ADD SOME FLAVOR :

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
$signed_headers = $signature -> get_signed_headers($to, $subject.'4', $message, $headers);//$message and $headers modification before in line current-20

mail($to, $subject.'4', $message, $signed_headers.$headers);//add hand result get_signed_headers
echo '<br>4:'.$signed_headers.'<br>';

?>
