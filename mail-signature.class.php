<?php

/**
 * php-mail-signature
 * 
 * https://github.com/louisameline/php-mail-signature
 * Author:	Louis Ameline - 04/2012
 * 
 * 
 * This stand-alone DKIM class is based on the work made on PHP-MAILER (see license below).
 * The differences are :
 * - it is a standalone class
 * - it supports Domain Keys header
 * - it supports UTF-8
 * - it will let you choose the headers you want to base the signature on
 * - it will let you choose between simple and relaxed body canonicalization
 * - it will let you choose between RSA/SHA-1 and RSA/SHA-256 hashing.
 * 
 * If the class fails to sign the e-mail, the returned DKIM header will be empty and the mail
 * will still be sent, just unsigned. A php warning is thrown for logging.
 * 
 * NOTE: you will NOT be able to use Domain Keys with PHP's mail() function, since it does
 * not allow to prepend the DK header before the To and Subject ones. DKIM is ok with that,
 * but Domain Keys is not. If you still want Domain Keys, you will have to manage to send
 * your mail straight to your MTA without the mail() function.
 * 
 * Successfully tested against Gmail, Yahoo Mail, Live.com, appmaildev.com
 * Hope it helps and saves you plenty of time. Let me know if you find issues.
 * 
 * For more info, you should read :
 * @link http://www.ietf.org/rfc/rfc4871.txt
 * @link https://www.ietf.org/rfc/rfc8301.txt
 * @link http://www.zytrax.com/books/dns/ch9/dkim.html
 * 
 * @link https://github.com/louisameline/php-mail-signature
 * @author Louis Ameline
 * @version 1.2
 */

/*
 * Original PHPMailer CC info :
 * .---------------------------------------------------------------------------.
 * |  Software: PHPMailer - PHP email class                                    |
 * |   Version: 5.2.1                                                          |
 * |      Site: https://code.google.com/a/apache-extras.org/p/phpmailer/       |
 * | ------------------------------------------------------------------------- |
 * |     Admin: Jim Jagielski (project admininistrator)                        |
 * |   Authors: Andy Prevost (codeworxtech) codeworxtech@users.sourceforge.net |
 * |          : Marcus Bointon (coolbru) coolbru@users.sourceforge.net         |
 * |          : Jim Jagielski (jimjag) jimjag@gmail.com                        |
 * |   Founder: Brent R. Matzelle (original founder)                           |
 * | Copyright (c) 2010-2012, Jim Jagielski. All Rights Reserved.              |
 * | Copyright (c) 2004-2009, Andy Prevost. All Rights Reserved.               |
 * | Copyright (c) 2001-2003, Brent R. Matzelle                                |
 * | ------------------------------------------------------------------------- |
 * |   License: Distributed under the Lesser General Public License (LGPL)     |
 * |            http://www.gnu.org/copyleft/lesser.html                        |
 * | This program is distributed in the hope that it will be useful - WITHOUT  |
 * | ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or     |
 * | FITNESS FOR A PARTICULAR PURPOSE.                                         |
 * '---------------------------------------------------------------------------'
 */

// You can delete this function if you have PHP >= 5.3.0
if (!function_exists('array_replace_recursive'))
{
    function recurse($array, $array1)
    {
      foreach ($array1 as $key => $value)
      {
        // create new key in $array, if it is empty or not an array
        if (!isset($array[$key]) || (isset($array[$key]) && !is_array($array[$key])))
        {
          $array[$key] = array();
        }
 
        // overwrite the value in the base array
        if (is_array($value))
        {
          $value = recurse($array[$key], $value);
        }
        $array[$key] = $value;
      }
      return $array;
    }
	
	function array_replace_recursive($array, $array1)
	{
		// handle the arguments, merge one by one
		$args = func_get_args();
		$array = $args[0];
		if (!is_array($array))
		{
		  return $array;
		}
		for ($i = 1; $i < count($args); $i++)
		{
		  if (is_array($args[$i]))
		  {
			$array = recurse($array, $args[$i]);
		  }
		}
		return $array;
	}
}


class mail_signature {

	private $private_key;
	private $domain;
	private $selector;
	private $options;
	private $canonicalized_headers_relaxed;
	
	public function __construct($private_key, $passphrase, $domain, $selector, $options = array()){
		
		// prepare the resource
		$this -> private_key = openssl_get_privatekey($private_key, $passphrase);
		$this -> domain = $domain;
		$this -> selector = $selector;
		
		/*
		 * This function will not let you ask for the simple header canonicalization because
		 * it would require more code, it would not be more secure and mails would yet be
		 * more likely to be rejected : no point in that
		 */
		$default_options = array(
			'use_dkim' => true,
			// disabled by default, see why at the top of this file
			'use_domainKeys' => false,
			/*
			 * Allowed user, defaults is "@<MAIL_DKIM_DOMAIN>", meaning anybody in the
			 * MAIL_DKIM_DOMAIN domain. Ex: 'admin@mydomain.tld'. You'll never have to use
			 * this unless you do not control the "From" value in the e-mails you send.
			 */
			'identity' => null,
			// "relaxed" is recommended over "simple" for better chances of success
			'dkim_body_canonicalization' => 'relaxed',
			// "nofws" is recommended over "simple" for better chances of success
			'dk_canonicalization' => 'nofws',

			/*
			 * Specify whether DKIM signatures should be signed with SHA-256 (recommended)
			 * or the older, weaker SHA-1 (historic).  This variable takes either the value
			 * "sha1" or "sha256", as those are the only two hashes supported by the current
			 * version of DKIM.
			 * 
			 * RFC 8301 says that "rsa-sha1 MUST NOT be used for signing or verifying."
			 * It is very highly recommended that you keep this set to "sha256".
			 * 
			 * This only affects DKIM signing.  DomainKeys, historic in its own right,
			 * only supported SHA-1.
			 */
			'dkim_hash' => 'sha256',

			/*
			 * The default list of headers types you want to base the signature on. The
			 * types here (in the default options) are to be put in lower case, but the
			 * types in $options can have capital letters. If one or more of the headers
			 * specified are not found in the $headers given to the function, they will
			 * just not be used.
			 * If you supply a new list, it will replace the default one
			 */
			'signed_headers' => array(
				'mime-version',
				'from',
				'to',
				'subject',
				'reply-to'
			)
		);
		
		if(isset($options['signed_headers'])){
		
			// lower case fields
			foreach($options['signed_headers'] as $key => $value){
				$options['signed_headers'][$key] = strtolower($value);
			}
			
			// delete the default fields if a custom list is provided, not merge
			$default_options['signed_headers'] = array();
		}
		
		// PHP >= 5.3.0.
		if(function_exists('array_replace_recursive')){
			$this -> options = array_replace_recursive($default_options, $options);
		}
		else {
			trigger_error(sprintf('Your PHP version is lower than 5.3.0, please get the "array_replace_recursive" function on the following page (it is in the first comment) and declare it before using this class : http://php.net/manual/fr/function.array-replace-recursive.php'), E_USER_WARNING);
		}
	}
	
	/**
	 * This function returns an array of relaxed canonicalized headers (lowercases the
	 * header type and cleans the new lines/spaces according to the RFC requirements).
	 * only headers required for signature (specified by $options) will be returned
	 * the result is an array of the type : array(headerType => fullHeader [, ...]),
	 * e.g. array('mime-version' => 'mime-version:1.0')
	 */
	private function _dkim_canonicalize_headers_relaxed($sHeaders){
		
		$aHeaders = array();
		
		// a header value which is spread over several lines must be 1-lined
		$sHeaders = preg_replace("/\n\s+/", " ", $sHeaders);
		
		$lines = explode("\r\n", $sHeaders);
		
		foreach($lines as $key => $line){
			
			// delete multiple WSP
			$line = preg_replace("/\s+/", ' ', $line);
			
			if(!empty($line)){
			
				// header type to lowercase and delete WSP which are not part of the
				// header value
				$line = explode(':', $line, 2);
				
				$header_type = trim(strtolower($line[0]));
				$header_value = trim($line[1]);
				
				if(		in_array($header_type, $this -> options['signed_headers'])
					or	$header_type == 'dkim-signature'
				){
					
					$aHeaders[$header_type] = $header_type.':'.$header_value;
				}
			}
		}
		
		return $aHeaders;
	}
	
	/**
	 * Apply RFC 4871 requirements before body signature. Do not modify
	 */
	private function _dkim_canonicalize_body_simple($body){
		
		/*
		 * Unlike other libraries, we do not convert all \n in the body to \r\n here
		 * because the RFC does not specify to do it here. However it should be done
		 * anyway since MTA may modify them and we recommend you do this on the mail
		 * body before calling this DKIM class - or signature could fail.
		 */
		
		// remove multiple trailing CRLF
		while(mb_substr($body, mb_strlen($body, 'UTF-8')-4, 4, 'UTF-8') == "\r\n\r\n"){
			$body = mb_substr($body, 0, mb_strlen($body, 'UTF-8')-2, 'UTF-8');
		}
		
		// must end with CRLF anyway
		if(mb_substr($body, mb_strlen($body, 'UTF-8')-2, 2, 'UTF-8') != "\r\n"){
			$body .= "\r\n";
		}
		
		return $body;
	}
	
	/**
	  * Apply RFC 4871 requirements before body signature. Do not modify
	  */
	private function _dkim_canonicalize_body_relaxed($body){
		
		$lines = explode("\r\n", $body);
		
		foreach($lines as $key => $value){
			
			// ignore WSP at the end of lines
			$value = rtrim($value);
			
			// ignore multiple WSP inside the line
			$lines[$key] = preg_replace('/\s+/', ' ', $value);
		}
		
		$body = implode("\r\n", $lines);
		
		// ignore empty lines at the end
		$body = $this -> _dkim_canonicalize_body_simple($body);
		
		return $body;
	}
	
	/**
	 * Apply RFC 4870 requirements before body signature. Do not modify
	 */
	private function _dk_canonicalize_simple($body, $sHeaders){
		
		/*
		 * Note : the RFC assumes all lines end with CRLF, and we assume you already
		 * took care of that before calling the class
		 */
		
		// keep only headers wich are in the signature headers
		$aHeaders = explode("\r\n", $sHeaders);
		foreach($aHeaders as $key => $line){
			
			if(!empty($aHeaders)){
			
				// make sure this line is the line of a new header and not the
				// continuation of another one
				$c = substr($line, 0, 1);
				$is_signed_header = true;
				
				// new header
				if(!in_array($c, array("\r", "\n", "\t", ' '))){
				
					$h = explode(':', $line);
					$header_type = strtolower(trim($h[0]));
					
					// keep only signature headers
					if(in_array($header_type, $this -> options['signed_headers'])){
						$is_signed_header = true;
					}
					else {
						unset($aHeaders[$key]);
						$is_signed_header = false;
					}
				}
				// continuated header
				else {
					// do not keep if it belongs to an unwanted header
					if($is_signed_header == false){
						unset($aHeaders[$key]);
					}
				}
			}
			else {
				unset($aHeaders[$key]);
			}
		}
		$sHeaders = implode("\r\n", $aHeaders);
		
		$mail = $sHeaders."\r\n\r\n".$body."\r\n";
		
		// remove all trailing CRLF
		while(mb_substr($body, mb_strlen($mail, 'UTF-8')-4, 4, 'UTF-8') == "\r\n\r\n"){
			$mail = mb_substr($mail, 0, mb_strlen($mail, 'UTF-8')-2, 'UTF-8');
		}
		
		return $mail;
	}
	
	/**
	  * Apply RFC 4870 requirements before body signature. Do not modify
	  */
	private function _dk_canonicalize_nofws($body, $sHeaders){
		
		// HEADERS
		// a header value which is spread over several lines must be 1-lined
		$sHeaders = preg_replace("/\r\n\s+/", " ", $sHeaders);
		
		$aHeaders = explode("\r\n", $sHeaders);
		
		foreach($aHeaders as $key => $line){
			
			if(!empty($line)){
			
				$h = explode(':', $line);
				$header_type = strtolower(trim($h[0]));
				
				// keep only signature headers
				if(in_array($header_type, $this -> options['signed_headers'])){
					
					// delete all WSP in each line
					$aHeaders[$key] = preg_replace("/\s/", '', $line);
				}
				else {
					unset($aHeaders[$key]);
				}
			}
			else {
				unset($aHeaders[$key]);
			}
		}
		$sHeaders = implode("\r\n", $aHeaders);
		
		// BODY
		// delete all WSP in each body line
		$body_lines = explode("\r\n", $body);
		
		foreach($body_lines as $key => $line){
			$body_lines[$key] = preg_replace("/\s/", '', $line);
		}
		
		$body = rtrim(implode("\r\n", $body_lines))."\r\n";
		
		return $sHeaders."\r\n\r\n".$body;
	}
	
	/**
	 * The function will return no DKIM header (no signature) if there is a failure,
	 * so the mail will still be sent in the default unsigned way
	 * it is highly recommended that all linefeeds in the $body and $headers you submit
	 * are in the CRLF (\r\n) format !! Otherwise signature may fail with some MTAs
	 */
	private function _get_dkim_header($body){	
		$body =
			($this -> options['dkim_body_canonicalization'] == 'simple') ?
			$this -> _dkim_canonicalize_body_simple($body) :
			$this -> _dkim_canonicalize_body_relaxed($body);
		
		// Base64 of packed binary hash of body
		$bh = rtrim(chunk_split(base64_encode(pack("H*", hash($this->options['dkim_hash'],$body))), 64, "\r\n\t"));
		$i_part =
			($this -> options['identity'] == null) ?
			'' :
			' i='.$this -> options['identity'].';'."\r\n\t";
		
		$dkim_header =
			'DKIM-Signature: '.
				'v=1;'."\r\n\t".
				'a=rsa-' . $this -> options['dkim_hash'] . ';'."\r\n\t".
				'q=dns/txt;'."\r\n\t".
				's='.$this -> selector.';'."\r\n\t".
				't='.time().';'."\r\n\t".
				'c=relaxed/'.$this -> options['dkim_body_canonicalization'].';'."\r\n\t".
				'h='.implode(':', array_keys($this -> canonicalized_headers_relaxed)).';'."\r\n\t".
				'd='.$this -> domain.';'."\r\n\t".
				$i_part.
				'bh='.$bh.';'."\r\n\t".
				'b=';
		
		// now for the signature we need the canonicalized version of the $dkim_header
		// we've just made
		$canonicalized_dkim_header = $this -> _dkim_canonicalize_headers_relaxed($dkim_header);
		
		// we sign the canonicalized signature headers
		$to_be_signed = implode("\r\n", $this -> canonicalized_headers_relaxed)."\r\n".$canonicalized_dkim_header['dkim-signature'];
		
		// $signature is sent by reference in this function
		$signature = '';
		$signing_algorithm = null;

		if ($this->options['dkim_hash'] === 'sha256') {
			$signing_algorithm = OPENSSL_ALGO_SHA256;
		} else if ($this->options['dkim_hash'] === 'sha1') {
			$signing_algorithm = OPENSSL_ALGO_SHA1;
		} else {
			die('Unsupported dkim_hash value "' . $this->options['dkim_hash'] . '" -- DKIM only supports sha256 and sha1.');
		}
		if(openssl_sign($to_be_signed, $signature, $this -> private_key, $signing_algorithm)){
			$dkim_header .= rtrim(chunk_split(base64_encode($signature), 64, "\r\n\t"))."\r\n";
		}
		else {
			trigger_error(sprintf('Could not sign e-mail with DKIM : %s', $to_be_signed), E_USER_WARNING);
			$dkim_header = '';
		}
		
		return $dkim_header;
	}
	
	private function _get_dk_header($body, $sHeaders){
	
		// Creating DomainKey-Signature
		$domainkeys_header =
			'DomainKey-Signature: '.
				'a=rsa-sha1; '."\r\n\t".
				'c='.$this -> options['dk_canonicalization'].'; '."\r\n\t".
				'd='.$this -> domain.'; '."\r\n\t".
				's='.$this -> selector.'; '."\r\n\t".
				'h='.implode(':', array_keys($this -> canonicalized_headers_relaxed)).'; '."\r\n\t".
				'b=';
		
		// we signed the canonicalized signature headers + the canonicalized body
		$to_be_signed =
			($this-> options['dk_canonicalization'] == 'simple') ?
			$this -> _dk_canonicalize_simple($body, $sHeaders) :
			$this -> _dk_canonicalize_nofws($body, $sHeaders);
		
		$signature = '';

		if(openssl_sign($to_be_signed, $signature, $this -> private_key, OPENSSL_ALGO_SHA1)){
			
			$domainkeys_header .= rtrim(chunk_split(base64_encode($signature), 64, "\r\n\t"))."\r\n";
		}
		else {
			$domainkeys_header = '';
		}
		
		return $domainkeys_header;
	}
	
	/**
	 * You may leave $to and $subject empty if the corresponding headers are already
	 * in $headers
	 */
	public function get_signed_headers($to, $subject, $body, $headers){
		
		$signed_headers = '';
		
		if(!empty($to) or !empty($subject)){
			
			/*
			 * To and Subject are not supposed to be present in $headers if you
			 * use the php mail() function, because it takes care of that itself in
			 * parameters for security reasons, so we reconstruct them here for the
			 * signature only
			 */
			$headers .=
				(mb_substr($headers, mb_strlen($headers, 'UTF-8')-2, 2, 'UTF-8') == "\r\n") ?
				'' :
				"\r\n";
			
			if(!empty($to)) $headers .= 'To: '.$to."\r\n";
			if(!empty($subject)) $headers .= 'Subject: '.$subject."\r\n";
		}
		
		// get the clean version of headers used for signature
		$this -> canonicalized_headers_relaxed = $this -> _dkim_canonicalize_headers_relaxed($headers);
		
		if(!empty($this -> canonicalized_headers_relaxed)){
			
			// Domain Keys must be the first header, it is an RFC (stupid) requirement
			if($this -> options['use_domainKeys'] == true){
				$signed_headers .= $this -> _get_dk_header($body, $headers);
			}
			
			if($this -> options['use_dkim'] == true){
				$signed_headers .= $this -> _get_dkim_header($body);
			}
		}
		else {
			trigger_error('No headers found to sign the e-mail with !', E_USER_WARNING);
		}
		
		return $signed_headers;
	}
}
