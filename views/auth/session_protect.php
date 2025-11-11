<?php
// Session protection for authenticated pages (e.g., dashboard)
// Starts session, prevents caching, and redirects to login if not authenticated.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prevent caching so users can't navigate back into protected pages after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Require logged-in session
if (!isset($_SESSION['user_id'])) {
    // Optional flash message shown on login page
    $_SESSION['flash_message'] = 'Please log in to access the dashboard.';
    header('Location: index.php?action=login');
    exit();
}

?>