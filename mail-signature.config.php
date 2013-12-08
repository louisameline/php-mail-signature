<?php

// DKIM is used to sign e-mails. If you change your RSA key, apply modifications to the DNS DKIM record of the mailing (sub)domain too !
// Disclaimer : the php openssl extension can be buggy with Windows, try with Linux first

// To generate a new private key with Linux :
// openssl genrsa -des3 -out private.pem 1024
// Then get the public key
// openssl rsa -in private.pem -out public.pem -outform PEM -pubout

// Edit with your own info :

define('MAIL_RSA_PASSPHRASE', 'myPassPhrase');

define('MAIL_RSA_PRIV',
'-----BEGIN RSA PRIVATE KEY-----
Proc-Type: 4,ENCRYPTED
DEK-Info: DES-EDE3-CBC,CBE310933536C953

lAu0Y811LGmYb6C2u2hcbgo3uO3J9oElEgYK05SJ9mZp15Zrino6E36CL32uUDmN
sXAeq34wzEk7nZipoNtR9ULgiv7hZNXwn7AF0dmgCy3aJl3ZMY9aTvUy/VZmnK8z
rSL1YjZeJj4wMr5YBS61bV/6nrGuo7uwNp4mE9Lau654fbPi+hwOh5pA3KZ9VrT6
mFhb85gryQCPfiLX/AqETCrldDdSkVa1yHbejwUke4B3Wzy+BjF0llkUKSfI4eO4
a78ToylYK8YZ0rVt0MaRUyjjUKipEawvYT6pTLr8h3+uVvWCw5cRy/8Qb8aybHoF
Oia1UCzSPDimOrrBCy5ValqgXc7tYKCBR02ZYseBRNKj+7079hCJPjW6RB5IeJgD
Z6vwutsZtim016ABraEBvy0mzsfvhifg1pwKqQGaKqio1VfSCOEmxcsK66uPF5ra
uNELXf7Y5WQsbcFmKR4imZ7MLDIl48xunBV5Xtd7bYYqa84DDIqhhsIyo1loE7JP
z/xrYp5msP4gmF8iurNbm2S9h1kiusLDvxxMKFpRV/ept+/ijXAkq7JklLDsDAeO
WNQZd1Aqm4uMSnlM+fKgwG79OjQ8sWHKkl1bf15DD65tQK/EIGanI8bQ+XMzJ7Fq
99RxSYBUhbhRnX+rKGJKviTiSqjhOmXAlhKLNU2dzOLcxS5bal6n5nubtePLpY9x
0H4C2af0LcjMrXlgpol1BVydmsqm6cE81mQ8nSr9tEG5BFpv3wnckMt1nTbGJQzd
SeCFoyzWFQ/y0spBxnrrbPe3OtRTYN+RMTSzqek4Lq9hDuTucE6ZkA==
-----END RSA PRIVATE KEY-----');

define('MAIL_RSA_PUBL',
'-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDbFRDJj4ExXI2n4S8lTrwvCDOl
BRCMzMgzAHSsQRbf0612Bb2pNkAWwV06Cvpe1VtHnRDsAIelnPPfVayh3D6WrxwT
0R8zgHqa2aWh1bHnAez03N8k2v2wLNbWSvQm8HpzweMIykUWMHwyDmHsx8/pSca7
AM/B7LD1jHazWRQhPwIDAQAB
-----END PUBLIC KEY-----');

// Domain or subdomain of the signing entity (i.e. the domain where the e-mail comes from)
define('MAIL_DOMAIN', 'example.com');  

// Allowed user, defaults is "@<MAIL_DKIM_DOMAIN>", meaning anybody in the MAIL_DKIM_DOMAIN domain. Ex: 'admin@mydomain.tld'. You'll never have to use this unless you do not control the "From" value in the e-mails you send.
define('MAIL_IDENTITY', NULL);

// Selector used in your DKIM DNS record, e.g. : selector._domainkey.MAIL_DKIM_DOMAIN
define('MAIL_SELECTOR', 'selector');

?>