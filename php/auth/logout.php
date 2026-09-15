<?php
date_default_timezone_set('Asia/Manila');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../models/User.php';

$userId   = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? null;
$role     = $_SESSION['role'] ?? 'user';
$auditId  = $_SESSION['audit_log_id'] ?? null;

if ($username) {
    $userModel = new User();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '::1';
    if ($ip === '127.0.0.1') $ip = '::1';
    $host = gethostname() ?: 'DESKTOP-SYSTEM';
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Browser';
    $device = 'Microsoft Edge';
    if (strpos($agent, 'Chrome') !== false && strpos($agent, 'Edg') === false) {
        $device = 'Google Chrome';
    } elseif (strpos($agent, 'Firefox') !== false) {
        $device = 'Mozilla Firefox';
    }

    $details = "User logged out. IP: {$ip} | Host: {$host} | Device: {$device}";

    // Update the existing Login audit record to Logout — do NOT insert a new row
    $userModel->updateActiveLoginToLogout(
        $username,
        $userId,
        $auditId ? (int)$auditId : null,
        $details
    );

    // Deactivate the outgoing Super Admin and activate the selected successor
    // in one transaction. Without a selected successor, keep the current account active.
    if (strtolower((string)$role) === 'superadmin' && $userId) {
        $userModel->rotateSuperAdminOnLogout((string)$userId, (string)$username);
    }
}

session_unset();
session_destroy();

// ✅ Extra protection against cached dashboard after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// ✅ Redirect to login
header("Location: index.php?action=login");
exit();
