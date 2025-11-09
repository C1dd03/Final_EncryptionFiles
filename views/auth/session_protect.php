<?php
// Basic session protection for pages that require authentication
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?action=login');
    exit();
}

// Prevent caching of protected pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");