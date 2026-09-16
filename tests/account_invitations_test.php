<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../models/User.php';
// Keep test sessions in memory as well as the database; never touch live session files.
session_set_save_handler(new class implements SessionHandlerInterface {
    public function open(string $path, string $name): bool { return true; }
    public function close(): bool { return true; }
    public function read(string $id): string|false { return ''; }
    public function write(string $id, string $data): bool { return true; }
    public function destroy(string $id): bool { return true; }
    public function gc(int $max_lifetime): int|false { return 0; }
});
session_start();

// Real model SQL against an isolated database; MySQL row-lock concurrency is not simulated.
final class InvitationTestDb extends PDO
{
    public function __construct() {
        parent::__construct('sqlite::memory:');
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->sqliteCreateFunction('NOW', static fn()=>date('Y-m-d H:i:s'));
    }
    private function translate(string $sql): string {
        return str_replace([' FOR UPDATE', 'DATE_ADD(NOW(), INTERVAL 7 DAY)', 'DATE_ADD(NOW(), INTERVAL 15 MINUTE)'],
            ['', "datetime(NOW(), '+7 days')", "datetime(NOW(), '+15 minutes')"], $sql);
    }
    public function prepare(string $query, array $options=[]): PDOStatement|false { return parent::prepare($this->translate($query), $options); }
    public function query(string $query, ?int $fetchMode=null, mixed ...$args): PDOStatement|false {
        return $fetchMode === null ? parent::query($this->translate($query)) : parent::query($this->translate($query), $fetchMode, ...$args);
    }
}
final class InvitationTestUser extends User
{
    private int $sequence = 100;
    public function __construct(public PDO $db) {
        (new ReflectionProperty(User::class,'conn'))->setValue($this,$db);
        (new ReflectionProperty(User::class,'pendingTable'))->setValue($this,'pending_registrations');
    }
    public function generateIdNumber(): string { return '2026-' . str_pad((string)++$this->sequence,4,'0',STR_PAD_LEFT); }
}
function verifyInvitation(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
date_default_timezone_set('Asia/Manila');
$db = new InvitationTestDb();
$db->exec("CREATE TABLE users (id_number TEXT PRIMARY KEY, username TEXT UNIQUE, first_name TEXT, middle_name TEXT, last_name TEXT,
    extension TEXT, birthdate TEXT, gender TEXT, age INTEGER, email TEXT UNIQUE, contact_number TEXT, password_hash TEXT, role TEXT, status TEXT,
    must_change_password INTEGER DEFAULT 0, session_version INTEGER DEFAULT 0, created_by TEXT, superadmin_eligible INTEGER DEFAULT 0,
    handoff_pending INTEGER DEFAULT 0, created_at TEXT)");
$db->exec("CREATE TABLE pending_registrations (user_id TEXT PRIMARY KEY, first_name TEXT, middle_name TEXT, last_name TEXT,
    username TEXT UNIQUE, email TEXT UNIQUE, password_hash TEXT, role TEXT, status TEXT, invitation_state TEXT,
    invitation_privileges TEXT, invited_by TEXT, invitation_expires_at TEXT, invitation_attempts INTEGER DEFAULT 0,
    invitation_locked_until TEXT, updated_at TEXT DEFAULT CURRENT_TIMESTAMP, reviewed_at TEXT, reviewed_by TEXT, rejection_reason TEXT)");
$db->exec('CREATE TABLE addresses (id_number TEXT, purok_street TEXT, barangay TEXT, city_municipality TEXT, province TEXT, country TEXT, zip_code TEXT)');
$db->exec('CREATE TABLE user_auth_answers (id_number TEXT, question_id INTEGER, answer_hash TEXT)');
$db->exec('CREATE TABLE admin_privileges (id_number TEXT, privilege_key TEXT, granted_at TEXT)');
$db->exec('CREATE TABLE audit_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, id_number TEXT, username TEXT, role TEXT, action TEXT, details TEXT, time_in TEXT, time_out TEXT)');
$db->exec("INSERT INTO users (id_number,username,role,status) VALUES ('SA','super.admin01','superadmin','active'), ('A','normal.admin01','admin','active')");
$model = new InvitationTestUser($db);
$mail = [];
$deliver = static function($email,$username,$password) use (&$mail) { $mail = compact('email','username','password'); return true; };
$input = ['username'=>'invited.person01','email'=>'invited@example.test','role'=>'admin','privileges'=>['view_users']];
$result = $model->createAccountInvitation($input,'SA',$deliver);
verifyInvitation($result['success'], 'Invitation creation failed.');
$id = $result['id_number'];
$row = $model->getPendingRegistrationById($id);
verifyInvitation($row['status']==='pending' && $row['invitation_state']==='awaiting_setup', 'Invitation not in Pending Approvals.');
verifyInvitation(password_verify($mail['password'],$row['password_hash']) && $mail['password']!==$row['password_hash'], 'Temporary password must be hashed.');
verifyInvitation((int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn()===2, 'Invitation created a premature account.');
verifyInvitation(!$model->approveRegistration($id,'SA','super.admin01','superadmin')['success'], 'Approval bypassed required setup.');
verifyInvitation($model->authenticateInvitation($id,'wrong')===null, 'Wrong credentials accepted.');
$authenticated = $model->authenticateInvitation($id,$mail['password']);
verifyInvitation($authenticated!==null, 'Temporary credentials did not authenticate.');
$data = ['first_name'=>'Test','middle_name'=>'','last_name'=>'Person','extension'=>'','birthdate'=>'1999-01-01','gender'=>'male',
    'contact_number'=>'09123456789','street'=>'Main','barangay'=>'Central','city'=>'City','province'=>'Province','country'=>'Philippines','zip'=>'8000',
    'password'=>'NewPassword1!','security_answers'=>[['question_id'=>1,'answer'=>'friend'],['question_id'=>4,'answer'=>'mother'],['question_id'=>7,'answer'=>'food']]];
$completed = $model->completeAccountInvitation($id,$authenticated['password_hash'],$data);
verifyInvitation($completed['success'], 'Setup failed.');
$user = $model->findByUsername($input['username']);
verifyInvitation($user['role']==='admin' && $user['status']==='active' && !(int)$user['must_change_password'], 'Wrong role/status after setup.');
verifyInvitation(password_verify($data['password'],$user['password_hash']) && !password_verify($mail['password'],$user['password_hash']), 'Temporary password was retained.');
verifyInvitation(count($model->getAdminPrivileges($id))===1, 'Assigned privileges lost.');
verifyInvitation((int)$db->query('SELECT COUNT(*) FROM addresses')->fetchColumn()===1, 'Address not saved.');
$answers=$db->query('SELECT * FROM user_auth_answers ORDER BY question_id')->fetchAll(PDO::FETCH_ASSOC);
verifyInvitation(count($answers)===3 && password_verify('friend',$answers[0]['answer_hash']), 'Security answers not hashed/saved.');
verifyInvitation($model->getPendingRegistrationById($id)['invitation_state']==='completed', 'Invitation not completed.');
verifyInvitation(!$model->completeAccountInvitation($id,$authenticated['password_hash'],$data)['success'], 'Invitation replay accepted.');
verifyInvitation($model->authenticateInvitation($id,$mail['password'])===null, 'Completed credentials accepted.');

$input['username']='failed.person02';$input['email']='failed@example.test';
$failed=$model->createAccountInvitation($input,'SA',static fn()=>false);
verifyInvitation(!$failed['success'] && $model->getPendingRegistrationById($failed['id_number'])['invitation_state']==='delivery_failed', 'Delivery failure mishandled.');
$db->exec("UPDATE pending_registrations SET updated_at=datetime(NOW(),'-2 minutes')");
verifyInvitation($model->resendAccountInvitation($failed['id_number'],'SA',$deliver)['success'], 'Retry failed.');
$retryId=$failed['id_number'];$retryHash=$model->getPendingRegistrationById($retryId)['password_hash'];
for($i=0;$i<5;$i++) $model->authenticateInvitation($retryId,'wrong');
verifyInvitation($model->authenticateInvitation($retryId,$mail['password'])===null, 'Brute-force lockout missing.');
$db->exec("UPDATE pending_registrations SET invitation_locked_until=NULL, invitation_expires_at=datetime(NOW(),'-1 day') WHERE user_id='$retryId'");
verifyInvitation($model->authenticateInvitation($retryId,$mail['password'])===null, 'Expired credentials accepted.');
verifyInvitation(!$model->completeAccountInvitation($retryId,$retryHash,$data)['success'], 'Expired setup accepted.');
verifyInvitation($model->resendAccountInvitation($retryId,'SA',$deliver)['success'], 'Expired invitation could not be renewed.');
verifyInvitation(!$model->completeAccountInvitation($retryId,$retryHash,$data)['success'], 'Old setup session survived resend.');
verifyInvitation($model->rejectRegistration($retryId,'Cancelled','SA','super.admin01','superadmin'), 'Cancellation failed.');
verifyInvitation($model->authenticateInvitation($retryId,$mail['password'])===null, 'Cancelled invitation accepted.');

$input['role']='superadmin';$input['username']='new.superadmin03';$input['email']='sa@example.test';
verifyInvitation(!$model->createAccountInvitation($input,'A',$deliver)['success'], 'Admin invited Super Admin.');
$next=$model->createAccountInvitation($input,'SA',$deliver);
verifyInvitation($next['success'], 'Super Admin invitation failed.');
$nextRow=$model->getPendingRegistrationById($next['id_number']);
verifyInvitation($model->completeAccountInvitation($next['id_number'],$nextRow['password_hash'],$data)['success'], 'Super Admin setup failed.');
$nextUser=$model->findByUsername($input['username']);
verifyInvitation($nextUser['status']==='inactive', 'Super Admin invitation bypassed activation.');
verifyInvitation((int)$db->query("SELECT COUNT(*) FROM users WHERE role='superadmin' AND status='active'")->fetchColumn()===1, 'Multiple active Super Admins.');
verifyInvitation(count($model->getAdminPrivileges($next['id_number']))===count(User::PRIVILEGES), 'Super Admin privileges incomplete.');

$input['role']='user';$input['username']='rollback.person04';$input['email']='rollback@example.test';
$rollback=$model->createAccountInvitation($input,'SA',$deliver);
$rollbackRow=$model->getPendingRegistrationById($rollback['id_number']);
$db->exec("CREATE TRIGGER fail_answers BEFORE INSERT ON user_auth_answers BEGIN SELECT RAISE(ABORT,'simulated failure'); END");
verifyInvitation(!$model->completeAccountInvitation($rollback['id_number'],$rollbackRow['password_hash'],$data)['success'], 'Partial setup reported success.');
verifyInvitation(!$model->findByUsername($input['username']), 'Partial user insertion survived rollback.');
verifyInvitation($model->getPendingRegistrationById($rollback['id_number'])['invitation_state']==='awaiting_setup', 'Failed setup consumed invitation.');
$db->exec('DROP TRIGGER fail_answers');
require_once __DIR__ . '/../controllers/UserController.php';
$controller = (new ReflectionClass(UserController::class))->newInstanceWithoutConstructor();
(new ReflectionProperty(UserController::class,'userModel'))->setValue($controller,$model);
$_SESSION = ['invitation_setup'=>['id'=>$rollback['id_number'],'hash'=>$rollbackRow['password_hash'],'expires'=>time()+1800], 'invitation_csrf'=>'test-token'];
$_SERVER['REQUEST_METHOD']='GET';
ob_start();$controller->invitationSetup();$html=ob_get_clean();
verifyInvitation(str_contains($html,'Complete Account Setup') && substr_count($html,'Select a question')===3, 'Setup form missing question placeholders.');
verifyInvitation(!isset($_SESSION['user_id'],$_SESSION['role']), 'Setup granted dashboard access.');
$_SERVER['REQUEST_METHOD']='POST';$_POST=['csrf'=>'test-token'];
ob_start();$controller->invitationSetup();$html=ob_get_clean();
verifyInvitation(str_contains($html,'role="alert"') && !$model->findByUsername($input['username']), 'Incomplete profile created an account.');
$_POST=$data+['csrf'=>'wrong','email'=>$input['email'],'confirm_password'=>$data['password']];
for($i=1;$i<=3;$i++) { $_POST["security_question_$i"]=$data['security_answers'][$i-1]['question_id']; $_POST["security_answer_$i"]=$data['security_answers'][$i-1]['answer']; }
ob_start();$controller->invitationSetup();$html=ob_get_clean();
verifyInvitation(str_contains($html,'form session is invalid') && !$model->findByUsername($input['username']), 'CSRF check missing.');
$_POST['csrf']='test-token';$_POST['security_question_2']=1;
ob_start();$controller->invitationSetup();$html=ob_get_clean();
verifyInvitation(str_contains($html,'Select security question 2') && !$model->findByUsername($input['username']), 'Duplicate questions accepted.');
$_POST['security_question_2']=4;
ob_start();$controller->invitationSetup();$html=ob_get_clean();
verifyInvitation(str_contains($html,'Setup complete.') && $model->findByUsername($input['username']), 'Validated form did not complete setup.');
verifyInvitation(!isset($_SESSION['invitation_setup']) && !isset($_SESSION['user_id']), 'Setup session not consumed or dashboard session granted prematurely.');
echo "PASS: staging, delivery/retry, expiry, lockout, cancellation, replay, hashes, privileges, activation isolation, rollback, setup validation, CSRF, and session isolation.\n";
