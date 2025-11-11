<?php
session_start();

// ✅ Clear all session data
$_SESSION = array();

// ✅ Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// ✅ Destroy the session
session_unset();
session_destroy();

// ✅ Extra protection against cached dashboard after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

// ✅ Redirect to login with logout parameter to trigger history clearing
header("Location: index.php?action=login&logout=1");
exit();
