<?php
header("Content-Type: text/css");

// Allow only if coming from your site
$referrer = $_SERVER['HTTP_REFERER'] ?? '';
$host = $_SERVER['SERVER_NAME'] ?? '';

// If a referrer is provided and it does not contain the current host, block access.
if (!empty($referrer) && strpos($referrer, $host) === false) {
    header("Location: /Final_EncryptionFiles/public/404.html");
    exit;
}

// Serve the actual CSS file
readfile(__DIR__ . '/style.css');
