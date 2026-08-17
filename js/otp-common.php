<?php
header("Content-Type: application/javascript");

// Allow only if coming from your site
$referrer = $_SERVER['HTTP_REFERER'] ?? '';
$host = $_SERVER['SERVER_NAME'] ?? '';

// If a referrer is provided and it does not contain the current host, block access.
if (!empty($referrer) && strpos($referrer, $host) === false) {
    header("Location: /Final_EncryptionFiles/public/404.html");
    exit;
}

// Output the contents of the actual JS file
readfile(__DIR__ . '/otp-common.js');
