<?php

/** Invitations live in the existing Pending Approvals storage, never in users until setup. */
trait AccountInvitations
{
    public function ensureInvitationSchema(): void
    {
        $columns = [
            'invitation_state' => 'VARCHAR(24) DEFAULT NULL',
            'invitation_privileges' => 'JSON DEFAULT NULL',
            'invited_by' => 'VARCHAR(20) DEFAULT NULL',
            'invitation_expires_at' => 'DATETIME DEFAULT NULL',
            'invitation_attempts' => 'INT NOT NULL DEFAULT 0',
            'invitation_locked_until' => 'DATETIME DEFAULT NULL',
        ];
        foreach ($columns as $column => $definition) {
            if (!$this->conn->query("SHOW COLUMNS FROM {$this->pendingTable} LIKE '{$column}'")->fetch()) {
                $this->conn->exec("ALTER TABLE {$this->pendingTable} ADD COLUMN {$column} {$definition}");
            }
        }
    }

    public function createAccountInvitation(array $data, string $actorId, callable $deliver): array
    {
        try {
            $this->conn->beginTransaction();
            $this->lockSuperAdminHandoff();
            $actor = $this->getAccountAuthState($actorId);
            $role = $data['role'];
            if (!$actor || $actor['status'] !== 'active' ||
                !in_array($actor['role'], ['superadmin', 'admin'], true) ||
                !in_array($role, ['user', 'admin', 'superadmin'], true) ||
                ($actor['role'] === 'admin' && ($role === 'superadmin' || !$this->hasAdminPrivilege($actorId, 'create_accounts')))) {
                throw new RuntimeException('Invitation not authorized.');
            }
            if ($this->usernameExists($data['username']) || $this->emailExists($data['email'])) {
                throw new RuntimeException('Account identity already in use.');
            }
            $id = $this->generateIdNumber();
            $privileges = $role === 'superadmin' ? array_keys(self::PRIVILEGES)
                : ($role === 'admin' && $actor['role'] === 'superadmin'
                    ? array_values(array_intersect(array_keys(self::PRIVILEGES), $data['privileges'] ?? [])) : []);
            $password = 'Aa1!' . bin2hex(random_bytes(12));
            $this->conn->prepare("INSERT INTO {$this->pendingTable}
                (user_id, first_name, last_name, username, email, password_hash, role, status,
                 invitation_state, invitation_privileges, invited_by, invitation_expires_at)
                VALUES (:id, '', '', :username, :email, :hash, :role, 'pending', 'sending', :privileges, :actor, DATE_ADD(NOW(), INTERVAL 7 DAY))")
                ->execute([':id'=>$id, ':username'=>$data['username'], ':email'=>$data['email'],
                    ':hash'=>password_hash($password, PASSWORD_DEFAULT), ':role'=>$role,
                    ':privileges'=>json_encode($privileges), ':actor'=>$actorId]);
            // Persist before email delivery; sending/failed invitations cannot authenticate.
            $this->conn->commit();
            $sent = (bool)$deliver($data['email'], $data['username'], $password);
            $this->conn->prepare("UPDATE {$this->pendingTable} SET invitation_state = :state WHERE user_id = :id AND invitation_state = 'sending'")
                ->execute([':state'=>$sent ? 'awaiting_setup' : 'delivery_failed', ':id'=>$id]);
            $this->logAuditAction($actorId, $actor['username'], $actor['role'], 'Invite Account',
                "Invited {$data['username']} (ID: {$id}, Role: {$role}). Email delivery: " . ($sent ? 'sent' : 'failed') . '. Pending recipient setup.');
            return ['success'=>$sent, 'id_number'=>$id, 'invitation'=>true,
                'message'=>$sent ? 'Invitation emailed. The recipient is listed in Pending Approvals until account setup is complete.'
                    : 'Email delivery failed. The invitation is in Pending Approvals; use Resend Invitation after checking mail settings.'];
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();
            error_log('Invitation creation failed: ' . $e->getMessage());
            return ['success'=>false, 'message'=>'Unable to create the invitation. Check whether the email or username is already registered or pending.'];
        }
    }

    public function authenticateInvitation(string $id, string $password): ?array
    {
        try {
            $this->conn->beginTransaction();
            $stmt = $this->conn->prepare("SELECT * FROM {$this->pendingTable} WHERE user_id = :id FOR UPDATE");
            $stmt->execute([':id'=>$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row || $row['status'] !== 'pending' || $row['invitation_state'] !== 'awaiting_setup' ||
                strtotime($row['invitation_expires_at'] ?? '') <= time() ||
                strtotime($row['invitation_locked_until'] ?? '') > time()) {
                $this->conn->rollBack();
                return null;
            }
            if (!password_verify($password, $row['password_hash'])) {
                $this->conn->prepare("UPDATE {$this->pendingTable} SET invitation_locked_until = CASE WHEN invitation_attempts >= 4 THEN DATE_ADD(NOW(), INTERVAL 15 MINUTE) ELSE NULL END,
                    invitation_attempts = CASE WHEN invitation_attempts >= 4 THEN 0 ELSE invitation_attempts + 1 END WHERE user_id = :id")
                    ->execute([':id'=>$id]);
                $this->conn->commit();
                return null;
            }
            $this->conn->prepare("UPDATE {$this->pendingTable} SET invitation_attempts = 0, invitation_locked_until = NULL WHERE user_id = :id")->execute([':id'=>$id]);
            $this->conn->commit();
            return $row;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();
            return null;
        }
    }

    public function resendAccountInvitation(string $id, string $actorId, callable $deliver): array
    {
        try {
            $this->conn->beginTransaction();
            $stmt = $this->conn->prepare("SELECT * FROM {$this->pendingTable} WHERE user_id = :id FOR UPDATE");
            $stmt->execute([':id'=>$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $actor = $this->getAccountAuthState($actorId);
            if (!$row || $row['status'] !== 'pending' || empty($row['invitation_state']) || !$actor || $actor['status'] !== 'active' ||
                !in_array($actor['role'], ['admin','superadmin'], true) ||
                ($actor['role'] !== 'superadmin' && ($row['invited_by'] !== $actorId || !$this->hasAdminPrivilege($actorId, 'create_accounts')))) {
                throw new RuntimeException('Cannot resend invitation.');
            }
            if (strtotime($row['updated_at']) > time() - 60) {
                $this->conn->rollBack();
                return ['success'=>false, 'message'=>'Please wait one minute before resending.'];
            }
            $password = 'Aa1!' . bin2hex(random_bytes(12));
            $this->conn->prepare("UPDATE {$this->pendingTable} SET password_hash = :hash, invitation_state = 'sending', invitation_expires_at = DATE_ADD(NOW(), INTERVAL 7 DAY), invitation_attempts = 0, invitation_locked_until = NULL WHERE user_id = :id")
                ->execute([':hash'=>password_hash($password, PASSWORD_DEFAULT), ':id'=>$id]);
            $this->conn->commit();
            $sent = (bool)$deliver($row['email'], $row['username'], $password);
            $this->conn->prepare("UPDATE {$this->pendingTable} SET invitation_state = :state WHERE user_id = :id AND invitation_state = 'sending'")
                ->execute([':state'=>$sent ? 'awaiting_setup' : 'delivery_failed', ':id'=>$id]);
            $this->logAuditAction($actorId, $actor['username'], $actor['role'], 'Resend Invitation', "Invitation {$id}: " . ($sent ? 'email sent' : 'delivery failed'));
            return ['success'=>$sent, 'message'=>$sent ? 'Invitation resent with a new temporary password.' : 'Email delivery failed. Please check mail settings.'];
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();
            return ['success'=>false, 'message'=>'Unable to resend this invitation.'];
        }
    }

    public function completeAccountInvitation(string $id, string $credentialHash, array $data): array
    {
        try {
            $this->conn->beginTransaction();
            $this->lockSuperAdminHandoff();
            $stmt = $this->conn->prepare("SELECT * FROM {$this->pendingTable} WHERE user_id = :id FOR UPDATE");
            $stmt->execute([':id'=>$id]);
            $pending = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$pending || $pending['status'] !== 'pending' || $pending['invitation_state'] !== 'awaiting_setup' ||
                strtotime($pending['invitation_expires_at'] ?? '') <= time() || !hash_equals($pending['password_hash'], $credentialHash)) {
                throw new RuntimeException('Invitation no longer available.');
            }
            if ($this->usernameExists($pending['username']) || $this->emailExists($pending['email'])) {
                throw new RuntimeException('Account identity already in use.');
            }
            $status = $pending['role'] === 'superadmin' ? 'inactive' : 'active';
            $this->conn->prepare("INSERT INTO users (id_number, first_name, middle_name, last_name, extension, birthdate, gender, age,
                username, email, contact_number, password_hash, role, status, must_change_password, session_version, created_by, superadmin_eligible, handoff_pending, created_at)
                VALUES (:id, :first, :middle, :last, :extension, :birthdate, :gender, :age, :username, :email, :contact, :hash, :role, :status, 0, 0, :creator, :eligible, 0, NOW())")
                ->execute([':id'=>$id, ':first'=>$data['first_name'], ':middle'=>$data['middle_name'] ?: null,
                    ':last'=>$data['last_name'], ':extension'=>$data['extension'] ?: null, ':birthdate'=>$data['birthdate'],
                    ':gender'=>$data['gender'], ':age'=>$this->calculateAge($data['birthdate']), ':username'=>$pending['username'],
                    ':email'=>$pending['email'], ':contact'=>$data['contact_number'], ':hash'=>password_hash($data['password'], PASSWORD_DEFAULT),
                    ':role'=>$pending['role'], ':status'=>$status, ':creator'=>$pending['invited_by'], ':eligible'=>$pending['role'] === 'superadmin' ? 1 : 0]);
            $this->conn->prepare("INSERT INTO addresses (id_number, purok_street, barangay, city_municipality, province, country, zip_code)
                VALUES (:id, :street, :barangay, :city, :province, :country, :zip)")
                ->execute([':id'=>$id, ':street'=>$data['street'], ':barangay'=>$data['barangay'], ':city'=>$data['city'],
                    ':province'=>$data['province'], ':country'=>$data['country'], ':zip'=>$data['zip']]);
            $stmt = $this->conn->prepare('INSERT INTO user_auth_answers (id_number, question_id, answer_hash) VALUES (:id, :question, :hash)');
            foreach ($data['security_answers'] as $answer) {
                $stmt->execute([':id'=>$id, ':question'=>$answer['question_id'], ':hash'=>password_hash($answer['answer'], PASSWORD_DEFAULT)]);
            }
            $privileges = $pending['role'] === 'superadmin' ? array_keys(self::PRIVILEGES)
                : json_decode($pending['invitation_privileges'] ?? '[]', true);
            if ($pending['role'] !== 'user' && !$this->saveAdminPrivileges($id, $privileges ?: [])) {
                throw new RuntimeException('Unable to save privileges.');
            }
            // A completed Super Admin invitation is inactive until explicitly activated by the active Super Admin.
            $this->conn->prepare("UPDATE {$this->pendingTable} SET status = 'approved', invitation_state = 'completed',
                first_name = :first, middle_name = :middle, last_name = :last, reviewed_at = NOW(), reviewed_by = :username,
                password_hash = :spent WHERE user_id = :id")
                ->execute([':first'=>$data['first_name'], ':middle'=>$data['middle_name'] ?: null, ':last'=>$data['last_name'],
                    ':username'=>$pending['username'], ':spent'=>password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT), ':id'=>$id]);
            $this->logAuditAction($id, $pending['username'], $pending['role'], 'Complete Account Setup', "Invitation completed. Account created with status {$status}.");
            $this->conn->commit();
            return ['success'=>true, 'message'=>$status === 'inactive'
                ? 'Setup complete. Your Super Admin account is inactive. Contact the active Super Admin for activation.'
                : 'Setup complete. You can now log in with your new password.'];
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();
            error_log('Invitation setup failed: ' . $e->getMessage());
            return ['success'=>false, 'message'=>'Unable to complete setup. The invitation may have expired, been cancelled, or been replaced. Please log in again or contact the administrator.'];
        }
    }
}
