<?php
require 'vendor/autoload.php';

use phpseclib3\Crypt\RSA;

// Generate private key
$privateKey = RSA::createKey(4096);
file_put_contents('config/jwt/private.pem', $privateKey);

// Extract and save public key
$publicKey = $privateKey->getPublicKey();
file_put_contents('config/jwt/public.pem', $publicKey);

echo "Keys generated successfully!\n";
