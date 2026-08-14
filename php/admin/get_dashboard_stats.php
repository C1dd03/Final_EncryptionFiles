<?php
require_once __DIR__ . '/../auth/session_protect.php';
require_once __DIR__ . '/../../models/User.php';

header('Content-Type: application/json');

$role = strtolower($_SESSION['role'] ?? '');
if ($role !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

$userModel = new User();

// Privilege gate: without "View All User Accounts", no statistics are exposed
$canViewUsers = $userModel->hasAdminPrivilege($_SESSION['user_id'] ?? '', 'view_users');
if (!$canViewUsers) {
    echo json_encode([
        'success'    => true,
        'restricted' => true,
        'total_users'   => 'N/A',
        'active_users'  => 'N/A',
        'blocked_users' => 'N/A',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

$stats = $userModel->getAdminDashboardStats();

echo json_encode([
    'success' => true,
    'total_users' => (int)$stats['total_users'],
    'active_users' => (int)$stats['active_users'],
    'blocked_users' => (int)$stats['blocked_users'],
    'timestamp' => date('Y-m-d H:i:s')
]);
