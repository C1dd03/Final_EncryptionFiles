<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../models/User.php';

// Exercise the real update/privilege/logout SQL in an isolated in-memory database.
// SQLite has no row-level FOR UPDATE; concurrent MySQL locking is not covered here.
final class HandoffConnection extends PDO
{
    public function __construct()
    {
        parent::__construct('sqlite::memory:');
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->sqliteCreateFunction('NOW', static fn() => date('Y-m-d H:i:s'));
    }
    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
    {
        $query = str_replace(' FOR UPDATE', '', $query);
        return $fetchMode === null ? parent::query($query) : parent::query($query, $fetchMode, ...$fetchModeArgs);
    }
}
final class HandoffUser extends User
{
    public function __construct(public PDO $testDb)
    {
        (new ReflectionProperty(User::class, 'conn'))->setValue($this, $testDb);
    }
    public function getManagedAccountById(string $idNumber): ?array
    {
        $stmt = $this->testDb->prepare('SELECT * FROM users WHERE id_number = ?');
        $stmt->execute([$idNumber]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        $row['privileges'] = $this->getAdminPrivileges($idNumber);
        return $row;
    }
}
function handoffAssert(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
$db = new HandoffConnection();
$db->exec("CREATE TABLE users (id_number TEXT PRIMARY KEY, username TEXT, first_name TEXT DEFAULT '', middle_name TEXT, last_name TEXT DEFAULT '', extension TEXT,
    birthdate TEXT, gender TEXT, age INTEGER, email TEXT, password_hash TEXT, role TEXT, status TEXT, session_version INTEGER DEFAULT 0,
    must_change_password INTEGER DEFAULT 0, superadmin_eligible INTEGER DEFAULT 0, superadmin_queue_at TEXT,
    handoff_pending INTEGER DEFAULT 0, created_at TEXT, updated_at TEXT, created_by TEXT, superadmin_otp_hash TEXT, superadmin_otp_expires_at TEXT)");
$db->exec('CREATE TABLE admin_privileges (id_number TEXT, privilege_key TEXT, granted_at TEXT)');
$db->exec('CREATE TABLE block_list (id_number TEXT, status TEXT)');
$db->exec('CREATE TABLE audit_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, id_number TEXT, username TEXT, role TEXT, action TEXT, details TEXT, time_in TEXT, time_out TEXT)');
$model = new HandoffUser($db);
$originalHash = password_hash('ExistingPass1!', PASSWORD_DEFAULT);
$insert = $db->prepare("INSERT INTO users (id_number,username,role,status,password_hash) VALUES (?,?,?,?,?)");
foreach ([['A','first.admin01','superadmin','active'], ['B','second.admin02','admin','active'], ['C','third.admin03','superadmin','inactive'], ['U','normal.user01','user','active']] as $row) {
    $insert->execute([...$row, $originalHash]);
}
function nominate(HandoffUser $model, string $id, string $actor, string $password = ''): bool
{
    $data = $model->getManagedAccountById($id);
    return $model->updateManagedAccount($id, array_merge($data, ['role'=>'superadmin', 'status'=>'inactive', 'queue_handoff'=>true, 'actor_id'=>$actor, 'password'=>$password]));
}
handoffAssert(!nominate($model, 'U', 'A'), 'User was promoted directly to Super Admin.');
handoffAssert(nominate($model, 'B', 'A'), 'Admin promotion failed.');
$b = $model->getManagedAccountById('B');
handoffAssert($b['status'] === 'inactive' && (int)$b['handoff_pending'] === 1, 'Promotion activated before logout.');
handoffAssert($b['password_hash'] === $originalHash && (int)$b['must_change_password'] === 0, 'Promotion changed existing credentials.');
handoffAssert(count($b['privileges']) === count(User::PRIVILEGES), 'Promotion did not grant all privileges.');
handoffAssert($model->rotateSuperAdminOnLogout('A','first.admin01')['activated_id'] === 'B', 'Wrong promotion successor activated.');
handoffAssert($model->getManagedAccountById('A')['status'] === 'inactive', 'Outgoing Super Admin stayed active.');
handoffAssert((int)$db->query("SELECT COUNT(*) FROM users WHERE role='superadmin' AND status='active'")->fetchColumn() === 1, 'Multiple active Super Admins.');
handoffAssert(nominate($model, 'A', 'B', 'DefaultPass1!'), 'Reactivation nomination failed.');
handoffAssert($model->rotateSuperAdminOnLogout('B','second.admin02')['activated_id'] === 'A', 'Reactivation did not use the same handoff.');
$a = $model->getManagedAccountById('A');
handoffAssert((int)$a['must_change_password'] === 1 && password_verify('DefaultPass1!', $a['password_hash']), 'Assigned password did not require replacement.');
handoffAssert(nominate($model, 'B', 'A'), 'First successor selection failed.');
handoffAssert(nominate($model, 'C', 'A'), 'Second successor selection failed.');
handoffAssert((int)$model->getManagedAccountById('B')['handoff_pending'] === 0, 'Previous successor was not replaced.');
// Failed privilege persistence must roll back the role/status/queue changes.
$db->exec("CREATE TRIGGER reject_privilege BEFORE INSERT ON admin_privileges BEGIN SELECT RAISE(ABORT, 'test privilege failure'); END");
handoffAssert(!nominate($model, 'B', 'A'), 'Privilege failure was reported as success.');
handoffAssert((int)$model->getManagedAccountById('C')['handoff_pending'] === 1, 'Failed update lost the existing nomination.');
$db->exec('DROP TRIGGER reject_privilege');
handoffAssert($model->rotateSuperAdminOnLogout('A','first.admin01')['activated_id'] === 'C', 'Latest nominee did not activate.');
handoffAssert(!nominate($model, 'B', 'A'), 'Inactive operator could select a successor.');
handoffAssert($model->rotateSuperAdminOnLogout('A','first.admin01') === null, 'Repeated logout performed another handoff.');
handoffAssert($model->rotateSuperAdminOnLogout('C','third.admin03')['activated_id'] === null, 'Logout invented a successor.');
handoffAssert((int)$db->query("SELECT COUNT(*) FROM users WHERE role='superadmin' AND status='active'")->fetchColumn() === 1, 'Logout without successor deactivated the current Super Admin.');
handoffAssert($model->getManagedAccountById('C')['status'] === 'active', 'Current Super Admin did not remain active.');
// Test the existing Create Account path using a fresh fixture owner.
$db->exec("UPDATE users SET status='inactive' WHERE id_number='C'");
$db->exec("UPDATE users SET status='active', must_change_password=0 WHERE id_number='A'");
$created = $model->createManagedAccount(['id_number'=>'N', 'username'=>'new.admin04', 'role'=>'superadmin', 'password'=>'DefaultPass1!'], 'A');
handoffAssert($created['success'], 'New Super Admin creation failed.');
handoffAssert($model->rotateSuperAdminOnLogout('A','first.admin01')['activated_id'] === 'N', 'Newly created successor was not activated.');
handoffAssert((int)$model->getManagedAccountById('N')['must_change_password'] === 1, 'First-login password requirement was lost.');
handoffAssert($model->resetRecoverablePassword('N', password_hash('ChangedPass1!', PASSWORD_DEFAULT)), 'Active successor could not replace default password.');
handoffAssert((int)$model->getManagedAccountById('N')['must_change_password'] === 0, 'Default-password requirement was not cleared.');
echo "PASS: promotion, reactivation, password preservation/defaults, privilege rollback, nomination replacement, and logout behavior.\n";
