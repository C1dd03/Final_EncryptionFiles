<?php
// session_protect.php
session_start();

// ✅ Prevent cached dashboard even after logout
// These headers must be sent BEFORE any output
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

// ✅ Redirect if not logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Clear any remaining session data
    session_unset();
    session_destroy();
    
    // Redirect to login - this prevents access to dashboard
    header("Location: index.php?action=login");
    exit();
}
?>
