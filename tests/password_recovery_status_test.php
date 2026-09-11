<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../models/User.php';

$checks = 0;
foreach (['user', 'admin', 'superadmin'] as $role) {
    foreach ([
        'active' => null,
        'pending_deletion' => null,
        'inactive' => 'Account is inactive.',
        'blocked' => 'Account is blocked.',
        'block' => 'Account is blocked.',
        'deleted' => 'Account has been deleted.',
        'pending_approval' => 'This account cannot reset its password at this time.',
    ] as $status => $expected) {
        $actual = User::passwordRecoveryError(['role' => $role, 'status' => $status]);
        if ($actual !== $expected) {
            throw new RuntimeException("Unexpected recovery policy for {$role}/{$status}");
        }
        $checks++;
    }
}
if (User::passwordRecoveryError(null) !== 'Account not found or has been deleted.') {
    throw new RuntimeException('A removed account was not rejected.');
}
$checks++;
echo "PASS: {$checks} recovery status checks across all roles.\n";
