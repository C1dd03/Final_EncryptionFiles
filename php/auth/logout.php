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
$currentSessionId = session_id();

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

    // Only one Super Admin is active. On logout, hand access to the oldest
    // eligible blocked Super Admin and place this account at the back of the
    // queue. The current account is blocked even if no successor is available.
    if (strtolower((string)$role) === 'superadmin' && $userId) {
        $userModel->releaseSuperAdminSession((string)$userId, $currentSessionId);
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
