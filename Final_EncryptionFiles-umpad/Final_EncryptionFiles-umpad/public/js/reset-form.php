<?php
header("Content-Type: application/javascript");

// Allow only if coming from your site
$referrer = $_SERVER['HTTP_REFERER'] ?? '';
$host = $_SERVER['SERVER_NAME'] ?? '';

if (empty($referrer) || strpos($referrer, $host) === false) {
    header("Location: /encryption/public/404.html");
    exit;
}

// Output the contents of the actual JS file
readfile(__DIR__ . '/reset-form.js');