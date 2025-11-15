<?php
header("Content-Type: application/javascript");

// Allow only if coming from your site
$referrer = $_SERVER['HTTP_REFERER'] ?? '';

// Allow localhost/127.0.0.1 for local development
if (empty($referrer) || (!strpos($referrer, 'localhost') && !strpos($referrer, '127.0.0.1'))) {
    header("Location: ../404.html");
    exit;
}

// Output the contents of the actual JS file
readfile(__DIR__ . '/login-incorrect-atmp.js');