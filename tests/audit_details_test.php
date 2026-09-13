<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../models/User.php';

$before = ['username' => 'old.user01', 'status' => 'active', 'email' => 'old@example.com',
    'privileges' => ['view_users', 'block_users'], 'password' => 'old-secret', 'security_answers' => ['old-answer']];
$after = ['username' => 'new.user01', 'status' => 'blocked', 'email' => 'new@example.com',
    'privileges' => ['view_users', 'edit_users'], 'password' => 'new-secret', 'security_answers' => ['new-answer'], 'otp' => '123456'];
$details = implode("\n", User::describeAuditChanges($before, $after));
foreach (['Username: "old.user01" -> "new.user01"', 'Status: "active" -> "blocked"',
    'Email: "old@example.com" -> "new@example.com"', 'Privileges added: ' . User::PRIVILEGES['edit_users'],
    'Privileges removed: ' . User::PRIVILEGES['block_users']] as $expected) {
    if (!str_contains($details, $expected)) throw new RuntimeException('Missing change: ' . $expected);
}
foreach (['old-secret', 'new-secret', 'old-answer', 'new-answer', '123456'] as $secret) {
    if (str_contains($details, $secret)) throw new RuntimeException('Secret was logged.');
}
if (User::describeAuditChanges($before, $before) !== []) throw new RuntimeException('Unchanged fields were logged.');
if (User::describeAuditChanges(['privileges' => ['view_users', 'edit_users']], ['privileges' => ['edit_users', 'view_users']]) !== []) {
    throw new RuntimeException('Privilege ordering was logged as a change.');
}
$escaped = implode("\n", User::describeAuditChanges([], ['first_name' => "Name\nFake entry"]));
if (str_contains($escaped, "\n")) throw new RuntimeException('Field value injected a log line.');
echo "PASS: audit changes, privilege differences, unchanged values, and secret exclusion.\n";
