<?php
// session_protect.php
// Live session guard: every protected page re-validates the account against
// the database (account status + role + session version) so that blocked or
// role-changed accounts can never keep using an existing session.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prevent cached pages even after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/index.php?action=login");
    exit();
}

require_once __DIR__ . '/../../models/User.php';

$userModel = new User();
$authState = $userModel->getAccountAuthState($_SESSION['user_id']);

// Account no longer exists in the database
if (!$authState) {
    session_unset();
    session_destroy();
    header("Location: ../auth/index.php?action=login");
    exit();
}

// 1. A blocked account must never be allowed to keep using an active session
if ($authState['status'] !== 'active') {
    session_unset();
    session_destroy();
    header("Location: ../auth/index.php?action=login&blocked=1");
    exit();
}

// 2. Session version mismatch (role changed / block toggled elsewhere) -> force re-login
if ((int)($_SESSION['session_version'] ?? 0) !== (int)$authState['session_version']) {
    session_unset();
    session_destroy();
    header("Location: ../auth/index.php?action=login");
    exit();
}

// 3. Refresh the live role and username from the database
$_SESSION['role'] = $authState['role'];
$_SESSION['username'] = $authState['username'];
?>
