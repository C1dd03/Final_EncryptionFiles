<?php
require_once __DIR__ . '/../config/db.php';


class User
{
    /**
     * @var \PDO
     */
    private $conn;
    private string $pendingTable;

    public function __construct()
    {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
        $pendingDatabase = $database->getPendingDatabaseName();
        if (!preg_match('/^[A-Za-z0-9_]+$/', $pendingDatabase)) {
            throw new RuntimeException('Invalid pending database name.');
        }
        $this->pendingTable = "`{$pendingDatabase}`.`pending_registrations`";
        $this->ensureBlockListSchema();
        $this->ensureDeleteRequestsAndApprovalSchema();
        $this->ensurePrivilegeSchema();
        $this->ensureViewDetailsSchema();
        $this->ensureAccountManagementSchema();
        $this->ensurePendingRegistrationsSchema();
    }

    public function insertUser(array $data)
    {
        try {
            $this->conn->beginTransaction();

            // ✅ Generate new ID and calculate age
            $age = $this->calculateAge($data['birthdate']);
            $id_number = $this->generateIdNumber();

            // ✅ 1. Insert into users
            $sqlUser = "INSERT INTO users 
                        (id_number, first_name, middle_name, last_name, extension, birthdate, gender, age, username, email, password_hash, status) 
                        VALUES 
                        (:id_number, :first_name, :middle_name, :last_name, :extension, :birthdate, :gender, :age, :username, :email, :password_hash, :status)";
            $stmt = $this->conn->prepare($sqlUser);
            $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
            $stmt->execute([
                ':id_number'     => $id_number,
                ':first_name'    => $data['first_name'],
                ':middle_name'   => $data['middle_name'] ?? null,
                ':last_name'     => $data['last_name'],
                ':extension'     => $data['extension'] ?? null,
                ':birthdate'     => $data['birthdate'],
                ':gender'        => $data['gender'],
                ':age'           => $age,
                ':username'      => $data['username'],
                ':email'         => $data['email'] ?? null,
                ':password_hash' => $passwordHash,
                ':status'        => $data['status'] ?? 'active'
            ]);

            // ✅ 2. Insert into addresses
            $sqlAddress = "INSERT INTO addresses 
                            (id_number, purok_street, barangay, city_municipality, province, country, zip_code) 
                            VALUES 
                            (:id_number, :street, :barangay, :city, :province, :country, :zip)";
            $stmt = $this->conn->prepare($sqlAddress);
            $stmt->execute([
                ':id_number' => $id_number, // ✅ fixed to use $id_number
                ':street'    => $data['street'],
                ':barangay'  => $data['barangay'],
                ':city'      => $data['city'],
                ':province'  => $data['province'],
                ':country'   => $data['country'],
                ':zip'       => $data['zip']
            ]);

            // ✅ 3. Insert into user_auth_answers
            $sqlAuth = "INSERT INTO user_auth_answers (id_number, question_id, answer_hash) 
                        VALUES (:id_number, :question_id, :answer_hash)";
            $stmt = $this->conn->prepare($sqlAuth);

            foreach ($data['security_answers'] as $entry) {
                $questionId = (int)($entry['question_id'] ?? 0);
                $answer = $entry['answer'] ?? '';

                if ($questionId <= 0 || $answer === '') {
                    throw new InvalidArgumentException('Invalid security question selection or empty answer.');
                }

                $stmt->execute([
                    ':id_number'   => $id_number, // ✅ fixed to use same generated ID
                    ':question_id' => $questionId,
                    ':answer_hash' => password_hash($answer, PASSWORD_BCRYPT)
                ]);
            }

            $this->conn->commit();
            return $id_number; // Return the generated ID number
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Registration failed: " . $e->getMessage());
            return false;
        }
    }


    private function calculateAge(string $birthdate): int
    {
        $birthdate = trim($birthdate);

        if ($birthdate === '') {
            throw new InvalidArgumentException('Birthdate is required.');
        }

        $dob = DateTime::createFromFormat('Y-m-d', $birthdate);
        $errors = DateTime::getLastErrors();

        if (!$dob || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new InvalidArgumentException('Invalid birthdate format.');
        }

        $today = new DateTime();
        if ($dob > $today) {
            throw new InvalidArgumentException('Birthdate cannot be in the future.');
        }

        return $today->diff($dob)->y;
    }

    /**
     * Generate the next available ID number for the current year in YYYY-#### format.
     * Considers both `users` and `pending_registrations` to avoid collisions.
     * Falls back to the latest numeric ID across all years in both tables if no
     * current-year IDs exist.
     */
    public function generateIdNumber(): string
    {
        $year = date("Y");

        $getMaxFromTables = function (string $yearPrefix): ?int {
            $maxNum = null;

            $stmt = $this->conn->prepare("
                SELECT id_number FROM users WHERE id_number LIKE :yearPrefix
                UNION
                SELECT user_id FROM {$this->pendingTable} WHERE user_id LIKE :yearPrefix
                ORDER BY 1 DESC LIMIT 1
            ");
            $stmt->execute([':yearPrefix' => $yearPrefix]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && preg_match('/^' . $yearPrefix . '(\d{4})$/', $row[array_keys($row)[0]], $matches)) {
                $maxNum = (int)$matches[1];
            }

            return $maxNum;
        };

        $nextNum = $getMaxFromTables($year . '-%');
        if ($nextNum !== null) {
            return $year . '-' . str_pad($nextNum + 1, 4, '0', STR_PAD_LEFT);
        }

        $stmt = $this->conn->prepare("
            SELECT id_number FROM users WHERE id_number REGEXP '^[0-9]{4}-[0-9]{4}$'
            UNION
            SELECT user_id FROM {$this->pendingTable} WHERE user_id REGEXP '^[0-9]{4}-[0-9]{4}$'
            ORDER BY 1 DESC LIMIT 1
        ");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $val = reset($row);
            if (preg_match('/^(\d{4})-(\d{4})$/', $val, $matches)) {
                $nextNum = str_pad(((int)$matches[2]) + 1, 4, '0', STR_PAD_LEFT);
                return $matches[1] . '-' . $nextNum;
            }
        }

        return $year . '-0001';
    }

    /**
     * Validates that the supplied Admin ID follows the ADMIN-#### format
     * (literal "ADMIN-" prefix + exactly four digits, no other characters).
     */
    public static function isValidAdminIdFormat(string $id): bool
    {
        return (bool)preg_match('/^ADMIN-\d{4}$/', $id);
    }

    /**
     * Generate a unique ADMIN-#### ID by inspecting existing admin IDs and
     * incrementing the highest 4-digit suffix. This runs server-side so
     * duplicate IDs are impossible.
     */
    public function generateAdminIdNumber(): string
    {
        $stmt = $this->conn->prepare("
            SELECT id_number
            FROM users
            WHERE id_number REGEXP '^ADMIN-[0-9]{4}$'
            ORDER BY CAST(SUBSTRING(id_number, 7) AS UNSIGNED) DESC
            LIMIT 1
        ");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && preg_match('/^ADMIN-(\d{4})$/', $row['id_number'], $matches)) {
            $next = (int)$matches[1] + 1;
        } else {
            $next = 1;
        }
        return 'ADMIN-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Returns both the next admin ID (ADMIN-####) and next standard user ID
     * (YYYY-####) so the frontend can populate the form on open.
     */
    public function getNextIdsForForms(): array
    {
        return [
            'admin_id'      => $this->generateAdminIdNumber(),
            'standard_id'   => $this->generateIdNumber()
        ];
    }


    /* ========================== ADD LOGIN MODEL ======================== */
    public function findByUsername(string $username)
    {
        $username = trim($username);

        if ($username === '') {
            return false;
        }

        $sql = "SELECT * FROM users WHERE username = :username";

        $stmt = $this->conn->prepare($sql);

        $stmt->execute([':username' => $username]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    /* ========================== ADD FORGOT PASSWORD MODEL ======================== */
    public function findById(string $id_number)
    {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id_number = :id_number");
        $stmt->execute([':id_number' => $id_number]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserAuthAnswers(string $id_number)
    {
        $stmt = $this->conn->prepare("
            SELECT ua.question_id, ua.answer_hash, aq.question_text 
            FROM user_auth_answers ua
            JOIN auth_questions aq ON ua.question_id = aq.question_id
            WHERE ua.id_number = :id_number 
            ORDER BY ua.question_id ASC
        ");
        $stmt->execute([':id_number' => $id_number]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserAuthAnswer(string $id_number, int $question_id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM user_auth_answers WHERE id_number = :id_number AND question_id = :question_id");
        $stmt->execute([':id_number' => $id_number, ':question_id' => $question_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updatePassword(string $id_number, string $password_hash, bool $clearRequiredChange = true)
    {
        // Bump session_version so any existing sessions are invalidated after a password change.
        $sql = "UPDATE users SET password_hash=:password, session_version = session_version + 1";
        if ($clearRequiredChange) {
            $sql .= ", must_change_password = 0";
        }
        $sql .= " WHERE id_number=:id_number";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':password' => $password_hash, ':id_number' => $id_number]);
    }

    /* ========================== FIND BY EMAIL & ACCOUNT ACTIVATION ======================== */
    public function findByEmail(string $email)
    {
        $email = trim($email);
        if ($email === '') {
            return false;
        }
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function activateAccount(string $id_number): bool
    {
        $stmt = $this->conn->prepare("UPDATE users SET status = 'active' WHERE id_number = :id_number");
        return $stmt->execute([':id_number' => $id_number]);
    }

    /* ========================== CHECK USERNAME AND EMAIL AVAILABILITY ======================== */
    public function usernameExists(string $username)
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        return $stmt->fetchColumn() > 0;
    }

    public function emailExists(string $email)
    {
        // Check if email column exists in users table
        // If email column doesn't exist, return false (email is available)
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            // If email column doesn't exist, return false
            return false;
        }
    }

    /* ========================== DASHBOARD STATS ======================== */
    public function getDashboardStats(): array
    {
        $stats = [
            'total_accounts'   => 0,
            'active_admins'    => 0,
            'active_users'     => 0,
            'blocked_accounts' => 0
        ];

        try {
            // Total Accounts
            $stmt = $this->conn->query("SELECT COUNT(*) FROM users");
            $stats['total_accounts'] = (int) $stmt->fetchColumn();

            // Active Admins (role admin or superadmin & status active)
            $stmt = $this->conn->query("SELECT COUNT(*) FROM users WHERE role IN ('admin', 'superadmin') AND status = 'active'");
            $stats['active_admins'] = (int) $stmt->fetchColumn();

            // Active Users (role user & status active)
            $stmt = $this->conn->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND status = 'active'");
            $stats['active_users'] = (int) $stmt->fetchColumn();

            // Blocked Accounts (status block in users OR blocked in block_list)
            $userBlockedCount = 0;
            try {
                $stmt = $this->conn->query("SELECT COUNT(*) FROM users WHERE status = 'blocked'");
                $userBlockedCount = (int) $stmt->fetchColumn();
            } catch (PDOException $e) {
            }

            $blockListCount = 0;
            try {
                $stmt = $this->conn->query("SELECT COUNT(*) FROM block_list WHERE status = 'blocked'");
                $blockListCount = (int) $stmt->fetchColumn();
            } catch (PDOException $e) {
            }

            $stats['blocked_accounts'] = max($userBlockedCount, $blockListCount);
        } catch (PDOException $e) {
            error_log("Failed to fetch dashboard stats: " . $e->getMessage());
        }

        return $stats;
    }

    public function getAdminDashboardStats(): array
    {
        $stats = [
            'total_users'   => 0,
            'active_users'  => 0,
            'blocked_users' => 0
        ];

        try {
            $stmt = $this->conn->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
            $stats['total_users'] = (int) $stmt->fetchColumn();

            $stmt = $this->conn->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND status = 'active'");
            $stats['active_users'] = (int) $stmt->fetchColumn();

            $stmt = $this->conn->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND status = 'blocked'");
            $stats['blocked_users'] = (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Failed to fetch admin dashboard stats: " . $e->getMessage());
        }

        return $stats;
    }

    /* ========================== MANAGE ADMINS METHODS ======================== */

    public function getAdminsList(string $search = '', string $status = 'all', int $offset = 0, int $limit = 10): array
    {
        $sql = "SELECT id_number, first_name, middle_name, last_name, extension, username, email, role, status, created_at 
                FROM users 
                WHERE role IN ('admin', 'superadmin')";
        $params = [];

        if (!empty($status) && $status !== 'all') {
            if ($status === 'active') {
                $sql .= " AND status = 'active'";
            } elseif ($status === 'blocked' || $status === 'block') {
                $sql .= " AND status = 'blocked'";
            } elseif ($status === 'pending_deletion') {
                $sql .= " AND status = 'pending_deletion'";
            } elseif ($status === 'inactive') {
                $sql .= " AND status = 'inactive'";
            }
        }

        if (!empty($search)) {
            $sql .= " AND (id_number LIKE :search 
                      OR username LIKE :search 
                      OR email LIKE :search 
                      OR CONCAT(first_name, ' ', last_name) LIKE :search
                      OR CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $nameParts = array_filter([$row['first_name'], $row['middle_name'], $row['last_name'], $row['extension']]);
            $row['name'] = implode(' ', $nameParts);
        }
        return $rows;
    }

    public function getAdminsCount(string $search = '', string $status = 'all'): int
    {
        $sql = "SELECT COUNT(*) FROM users WHERE role IN ('admin', 'superadmin')";
        $params = [];

        if (!empty($status) && $status !== 'all') {
            if ($status === 'active') {
                $sql .= " AND status = 'active'";
            } elseif ($status === 'blocked' || $status === 'block') {
                $sql .= " AND status = 'blocked'";
            } elseif ($status === 'pending_deletion') {
                $sql .= " AND status = 'pending_deletion'";
            } elseif ($status === 'inactive') {
                $sql .= " AND status = 'inactive'";
            }
        }

        if (!empty($search)) {
            $sql .= " AND (id_number LIKE :search 
                      OR username LIKE :search 
                      OR email LIKE :search 
                      OR CONCAT(first_name, ' ', last_name) LIKE :search
                      OR CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getAdminByIdNumber(string $id_number): ?array
    {
        $stmt = $this->conn->prepare("SELECT u.id_number, u.first_name, u.middle_name, u.last_name, u.extension, u.birthdate, u.gender, u.age, u.username, u.email, u.role, u.status, u.created_at, a.purok_street AS street, a.barangay, a.city_municipality AS city, a.province, a.country, a.zip_code AS zip FROM users u LEFT JOIN addresses a ON u.id_number = a.id_number WHERE u.id_number = :id_number AND u.role IN ('admin', 'superadmin')");
        $stmt->execute([':id_number' => $id_number]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $nameParts = array_filter([$user['first_name'], $user['middle_name'], $user['last_name'], $user['extension']]);
            $user['name'] = implode(' ', $nameParts);
            return $user;
        }
        return null;
    }

    public function createAdmin(array $data): string
    {
        $id_number = !empty($data['id_number']) ? trim($data['id_number']) : $this->generateIdNumber();
        $passwordHash = password_hash('@Abcde12345', PASSWORD_BCRYPT);
        $birthdate = !empty($data['birthdate']) ? $data['birthdate'] : '2000-01-01';
        $age = !empty($data['birthdate']) ? $this->calculateAge($data['birthdate']) : 0;

        $sql = "INSERT INTO users (id_number, first_name, middle_name, last_name, extension, birthdate, gender, age, username, email, password_hash, role, status, must_change_password)
                VALUES (:id_number, :first_name, :middle_name, :last_name, :extension, :birthdate, :gender, :age, :username, :email, :password_hash, 'admin', :status, 1)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id_number'     => $id_number,
            ':first_name'    => $data['first_name'],
            ':middle_name'   => $data['middle_name'] ?? null,
            ':last_name'     => $data['last_name'],
            ':extension'     => $data['extension'] ?? null,
            ':birthdate'     => $birthdate,
            ':gender'        => $data['gender'] ?? 'male',
            ':age'           => $age,
            ':username'      => $data['username'],
            ':email'         => $data['email'] ?? null,
            ':password_hash' => $passwordHash,
            ':status'        => $data['status'] ?? 'active'
        ]);

        $this->saveOrUpdateAddress($id_number, $data);
        if (!empty($data['security_answers'])) {
            $this->saveSecurityAnswers($id_number, $data['security_answers']);
        }

        return $id_number;
    }

    public function updateAdmin(string $id_number, array $data): bool
    {
        $current = $this->getUserOrAdminByIdNumber($id_number);
        $currentStatus = $current['status'] ?? 'active';
        $requestedStatus = ($data['status'] ?? 'active') === 'block' ? 'blocked' : ($data['status'] ?? 'active');
        $data['status'] = in_array($currentStatus, ['pending_deletion', 'inactive'], true)
            ? $currentStatus
            : $requestedStatus;

        $fields = [
            'first_name = :first_name',
            'middle_name = :middle_name',
            'last_name = :last_name',
            'extension = :extension',
            'gender = :gender',
            'username = :username',
            'email = :email',
            'status = :status'
        ];
        $params = [
            ':id_number'   => $id_number,
            ':first_name'  => $data['first_name'],
            ':middle_name' => $data['middle_name'] ?? null,
            ':last_name'   => $data['last_name'],
            ':extension'   => $data['extension'] ?? null,
            ':gender'      => $data['gender'] ?? 'male',
            ':username'    => $data['username'],
            ':email'       => $data['email'] ?? null,
            ':status'      => $data['status'] ?? 'active'
        ];

        if (!empty($data['birthdate'])) {
            $fields[] = 'birthdate = :birthdate';
            $fields[] = 'age = :age';
            $params[':birthdate'] = $data['birthdate'];
            $params[':age'] = $this->calculateAge($data['birthdate']);
        }

        if (!empty($data['password'])) {
            $fields[] = 'password_hash = :password_hash';
            $fields[] = 'must_change_password = 1';
            $params[':password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id_number = :id_number AND role IN ('admin', 'superadmin')";
        $stmt = $this->conn->prepare($sql);
        $res = $stmt->execute($params);
        if ($res) {
            $this->saveOrUpdateAddress($id_number, $data);
            if (!empty($data['security_answers'])) {
                $this->saveSecurityAnswers($id_number, $data['security_answers']);
            }
            // Sync block_list when status changes via edit form
            $newStatus = $data['status'] ?? 'active';
            if ($newStatus === 'blocked') {
                $this->syncBlockListOnBlock($id_number, 'superadmin');
            } else {
                $targetUser = $this->getAdminByIdNumber($id_number);
                $uName = $targetUser['username'] ?? $id_number;
                $this->conn->prepare("UPDATE block_list SET status = 'unblocked' WHERE id_number = :id_number OR username = :username")
                    ->execute([':id_number' => $id_number, ':username' => $uName]);
            }
        }
        return $res;
    }

    public function toggleAdminStatus(string $id_number, string $new_status, string $operator = 'superadmin', ?string $reason = null, ?string $ip = null): bool
    {
        $new_status = $new_status === 'block' ? 'blocked' : $new_status;
        $stmt = $this->conn->prepare("UPDATE users SET status = :status, session_version = session_version + 1 WHERE id_number = :id_number AND role = 'admin' AND status IN ('active', 'blocked')");
        $stmt->execute([':status' => $new_status, ':id_number' => $id_number]);
        $res = $stmt->rowCount() === 1;
        if ($res) {
            $targetUser = $this->getUserOrAdminByIdNumber($id_number);
            $targetName = $targetUser['name'] ?? $id_number;
            $opUser = $this->getUserOrAdminByIdNumber($operator);
            $opId = $opUser['id_number'] ?? $operator;
            $opRole = strtolower($opUser['role'] ?? 'superadmin');

            if ($new_status === 'blocked') {
                $this->syncBlockListOnBlock($id_number, $operator, $reason, $ip);
                $reasonText = !empty($reason) ? $reason : 'Restricted by Super Admin';
                $details = "Blocked Admin: {$targetName} | Blocked By: {$operator} | Reason: {$reasonText}";
                if (!empty($ip)) $details .= " | IP Address: {$ip}";
                $this->logAuditAction($opId, $operator, $opRole, 'Block Admin', $details);
            } else {
                $uName = $targetUser['username'] ?? $id_number;
                $this->conn->prepare("UPDATE block_list SET status = 'unblocked' WHERE id_number = :id_number OR username = :username")->execute([':id_number' => $id_number, ':username' => $uName]);
                $details = "Unblocked admin {$targetName}.";
                $this->logAuditAction($opId, $operator, $opRole, 'Unblock Admin', $details);
            }
        }
        return $res;
    }

    public function deleteAdmin(string $id_number, string $reviewer = 'superadmin'): bool
    {
        try {
            $this->conn->beginTransaction();
            $stmt = $this->conn->prepare(
                "UPDATE users
                 SET status = 'inactive', session_version = session_version + 1
                 WHERE id_number = :id_number AND role IN ('admin', 'superadmin') AND status <> 'inactive'"
            );
            $stmt->execute([':id_number' => $id_number]);
            if ($stmt->rowCount() !== 1) {
                $this->conn->rollBack();
                return false;
            }
            $this->conn->prepare("UPDATE block_list SET status = 'unblocked' WHERE id_number = :id_number")
                ->execute([':id_number' => $id_number]);
            $this->conn->prepare(
                "UPDATE delete_requests SET status = 'approved', reviewed_at = NOW(), reviewed_by = :reviewer,
                 review_notes = 'Resolved by direct Super Admin deactivation.'
                 WHERE user_id_number = :id_number AND status = 'pending'"
            )->execute([':reviewer' => $reviewer, ':id_number' => $id_number]);
            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();
            error_log('Failed to deactivate admin: ' . $e->getMessage());
            return false;
        }
    }

    /* ========================== MANAGE USERS METHODS ======================== */

    public function getUsersList(string $search = '', string $status = 'all', int $offset = 0, int $limit = 10): array
    {
        $sql = "SELECT id_number, first_name, middle_name, last_name, extension, username, email, role, status, created_at 
                FROM users 
                WHERE role = 'user'";
        $params = [];

        if (!empty($status) && $status !== 'all') {
            if ($status === 'active') {
                $sql .= " AND status = 'active'";
            } elseif ($status === 'blocked' || $status === 'block') {
                $sql .= " AND status = 'blocked'";
            } elseif ($status === 'pending_deletion') {
                $sql .= " AND status = 'pending_deletion'";
            } elseif ($status === 'inactive') {
                $sql .= " AND status = 'inactive'";
            }
        }

        if (!empty($search)) {
            $sql .= " AND (id_number LIKE :search 
                      OR username LIKE :search 
                      OR email LIKE :search 
                      OR CONCAT(first_name, ' ', last_name) LIKE :search
                      OR CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $nameParts = array_filter([$row['first_name'], $row['middle_name'], $row['last_name'], $row['extension']]);
            $row['name'] = implode(' ', $nameParts);
        }
        return $rows;
    }

    public function getUsersCount(string $search = '', string $status = 'all'): int
    {
        $sql = "SELECT COUNT(*) FROM users WHERE role = 'user'";
        $params = [];

        if (!empty($status) && $status !== 'all') {
            if ($status === 'active') {
                $sql .= " AND status = 'active'";
            } elseif ($status === 'blocked' || $status === 'block') {
                $sql .= " AND status = 'blocked'";
            } elseif ($status === 'pending_deletion') {
                $sql .= " AND status = 'pending_deletion'";
            } elseif ($status === 'inactive') {
                $sql .= " AND status = 'inactive'";
            }
        }

        if (!empty($search)) {
            $sql .= " AND (id_number LIKE :search 
                      OR username LIKE :search 
                      OR email LIKE :search 
                      OR CONCAT(first_name, ' ', last_name) LIKE :search
                      OR CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getUserByIdNumber(string $id_number): ?array
    {
        $stmt = $this->conn->prepare("SELECT u.id_number, u.first_name, u.middle_name, u.last_name, u.extension, u.birthdate, u.gender, u.age, u.username, u.email, u.role, u.status, u.created_at, a.purok_street AS street, a.barangay, a.city_municipality AS city, a.province, a.country, a.zip_code AS zip FROM users u LEFT JOIN addresses a ON u.id_number = a.id_number WHERE u.id_number = :id_number AND u.role = 'user'");
        $stmt->execute([':id_number' => $id_number]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $nameParts = array_filter([$user['first_name'], $user['middle_name'], $user['last_name'], $user['extension']]);
            $user['name'] = implode(' ', $nameParts);
            return $user;
        }
        return null;
    }

    public function createStandardUser(array $data): string
    {
        $id_number = !empty($data['id_number']) ? trim($data['id_number']) : $this->generateIdNumber();
        $passwordHash = password_hash('@Abcde12345', PASSWORD_BCRYPT);
        $birthdate = !empty($data['birthdate']) ? $data['birthdate'] : '2000-01-01';
        $age = !empty($data['birthdate']) ? $this->calculateAge($data['birthdate']) : 0;

        $sql = "INSERT INTO users (id_number, first_name, middle_name, last_name, extension, birthdate, gender, age, username, email, password_hash, role, status, must_change_password)
                VALUES (:id_number, :first_name, :middle_name, :last_name, :extension, :birthdate, :gender, :age, :username, :email, :password_hash, 'user', :status, 1)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id_number'     => $id_number,
            ':first_name'    => $data['first_name'],
            ':middle_name'   => $data['middle_name'] ?? null,
            ':last_name'     => $data['last_name'],
            ':extension'     => $data['extension'] ?? null,
            ':birthdate'     => $birthdate,
            ':gender'        => $data['gender'] ?? 'male',
            ':age'           => $age,
            ':username'      => $data['username'],
            ':email'         => $data['email'] ?? null,
            ':password_hash' => $passwordHash,
            ':status'        => $data['status'] ?? 'active'
        ]);

        $this->saveOrUpdateAddress($id_number, $data);
        if (!empty($data['security_answers'])) {
            $this->saveSecurityAnswers($id_number, $data['security_answers']);
        }

        return $id_number;
    }

    public function updateStandardUser(string $id_number, array $data): bool
    {
        $current = $this->getUserOrAdminByIdNumber($id_number);
        $currentStatus = $current['status'] ?? 'active';
        $requestedStatus = ($data['status'] ?? 'active') === 'block' ? 'blocked' : ($data['status'] ?? 'active');
        $data['status'] = in_array($currentStatus, ['pending_deletion', 'inactive'], true)
            ? $currentStatus
            : $requestedStatus;

        $fields = [
            'first_name = :first_name',
            'middle_name = :middle_name',
            'last_name = :last_name',
            'extension = :extension',
            'gender = :gender',
            'username = :username',
            'email = :email',
            'status = :status'
        ];
        $params = [
            ':id_number'   => $id_number,
            ':first_name'  => $data['first_name'],
            ':middle_name' => $data['middle_name'] ?? null,
            ':last_name'   => $data['last_name'],
            ':extension'   => $data['extension'] ?? null,
            ':gender'      => $data['gender'] ?? 'male',
            ':username'    => $data['username'],
            ':email'       => $data['email'] ?? null,
            ':status'      => $data['status'] ?? 'active'
        ];

        if (!empty($data['birthdate'])) {
            $fields[] = 'birthdate = :birthdate';
            $fields[] = 'age = :age';
            $params[':birthdate'] = $data['birthdate'];
            $params[':age'] = $this->calculateAge($data['birthdate']);
        }

        if (!empty($data['password'])) {
            $fields[] = 'password_hash = :password_hash';
            $fields[] = 'must_change_password = 1';
            $params[':password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id_number = :id_number AND role = 'user'";
        $stmt = $this->conn->prepare($sql);
        $res = $stmt->execute($params);
        if ($res) {
            $this->saveOrUpdateAddress($id_number, $data);
            if (!empty($data['security_answers'])) {
                $this->saveSecurityAnswers($id_number, $data['security_answers']);
            }
            // Sync block_list when status changes via edit form
            $newStatus = $data['status'] ?? 'active';
            if ($newStatus === 'blocked') {
                $this->syncBlockListOnBlock($id_number, 'superadmin');
            } else {
                $targetUser = $this->getUserByIdNumber($id_number);
                $uName = $targetUser['username'] ?? $id_number;
                $this->conn->prepare("UPDATE block_list SET status = 'unblocked' WHERE id_number = :id_number OR username = :username")
                    ->execute([':id_number' => $id_number, ':username' => $uName]);
            }
        }
        return $res;
    }

    private function saveOrUpdateAddress(string $id_number, array $data): void
    {
        $street   = trim($data['street'] ?? '');
        $barangay = trim($data['barangay'] ?? '');
        $city     = trim($data['city'] ?? '');
        $province = trim($data['province'] ?? '');
        $country  = trim($data['country'] ?? '');
        $zip      = trim($data['zip'] ?? '');

        if ($street === '' && $barangay === '' && $city === '') {
            return;
        }

        $stmtCheck = $this->conn->prepare("SELECT id_number FROM addresses WHERE id_number = :id_number");
        $stmtCheck->execute([':id_number' => $id_number]);
        if ($stmtCheck->fetch()) {
            $sql = "UPDATE addresses SET purok_street = :street, barangay = :barangay, city_municipality = :city, province = :province, country = :country, zip_code = :zip WHERE id_number = :id_number";
        } else {
            $sql = "INSERT INTO addresses (id_number, purok_street, barangay, city_municipality, province, country, zip_code) VALUES (:id_number, :street, :barangay, :city, :province, :country, :zip)";
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id_number' => $id_number,
            ':street'    => $street,
            ':barangay'  => $barangay,
            ':city'      => $city,
            ':province'  => $province,
            ':country'   => $country,
            ':zip'       => $zip
        ]);
    }

    private function saveSecurityAnswers(string $id_number, array $securityAnswers): void
    {
        if (empty($securityAnswers)) return;

        $stmtDel = $this->conn->prepare("DELETE FROM user_auth_answers WHERE id_number = :id_number");
        $stmtDel->execute([':id_number' => $id_number]);

        $sqlAuth = "INSERT INTO user_auth_answers (id_number, question_id, answer_hash) VALUES (:id_number, :question_id, :answer_hash)";
        $stmt = $this->conn->prepare($sqlAuth);

        foreach ($securityAnswers as $entry) {
            $questionId = (int)($entry['question_id'] ?? 0);
            $answer = trim($entry['answer'] ?? '');
            if ($questionId > 0 && $answer !== '') {
                $stmt->execute([
                    ':id_number'   => $id_number,
                    ':question_id' => $questionId,
                    ':answer_hash' => password_hash($answer, PASSWORD_BCRYPT)
                ]);
            }
        }
    }

    public function toggleStandardUserStatus(string $id_number, string $new_status, string $operator = 'superadmin', ?string $reason = null, ?string $ip = null): bool
    {
        $new_status = $new_status === 'block' ? 'blocked' : $new_status;
        $stmt = $this->conn->prepare("UPDATE users SET status = :status, session_version = session_version + 1 WHERE id_number = :id_number AND role = 'user' AND status IN ('active', 'blocked')");
        $stmt->execute([':status' => $new_status, ':id_number' => $id_number]);
        $res = $stmt->rowCount() === 1;
        if ($res) {
            $targetUser = $this->getUserOrAdminByIdNumber($id_number);
            $targetName = $targetUser['name'] ?? $id_number;
            $opUser = $this->getUserOrAdminByIdNumber($operator);
            $opId = $opUser['id_number'] ?? $operator;
            $opUsername = $opUser['username'] ?? $operator;
            $opRole = strtolower($opUser['role'] ?? 'superadmin');

            if ($new_status === 'blocked') {
                $this->syncBlockListOnBlock($id_number, $opUsername, $reason, $ip);
                $reasonText = !empty($reason) ? $reason : 'Restricted by ' . ucfirst($opRole);
                $details = "Blocked User: {$targetName} | Blocked By: {$opUsername} | Reason: {$reasonText}";
                if (!empty($ip)) $details .= " | IP Address: {$ip}";
                $this->logAuditAction($opId, $opUsername, $opRole, 'Block User', $details);
            } else {
                $uName = $targetUser['username'] ?? $id_number;
                $this->conn->prepare("UPDATE block_list SET status = 'unblocked' WHERE id_number = :id_number OR username = :username")->execute([':id_number' => $id_number, ':username' => $uName]);
                $details = "Unblocked user {$targetName}.";
                $this->logAuditAction($opId, $opUsername, $opRole, 'Unblock User', $details);
            }
        }
        return $res;
    }

    public function deleteStandardUser(string $id_number, string $reviewer = 'superadmin'): bool
    {
        try {
            $this->conn->beginTransaction();
            $stmt = $this->conn->prepare(
                "UPDATE users
                 SET status = 'inactive', session_version = session_version + 1
                 WHERE id_number = :id_number AND role = 'user' AND status <> 'inactive'"
            );
            $stmt->execute([':id_number' => $id_number]);
            if ($stmt->rowCount() !== 1) {
                $this->conn->rollBack();
                return false;
            }
            $this->conn->prepare("UPDATE block_list SET status = 'unblocked' WHERE id_number = :id_number")
                ->execute([':id_number' => $id_number]);
            $this->conn->prepare(
                "UPDATE delete_requests SET status = 'approved', reviewed_at = NOW(), reviewed_by = :reviewer,
                 review_notes = 'Resolved by direct Super Admin deactivation.'
                 WHERE user_id_number = :id_number AND status = 'pending'"
            )->execute([':reviewer' => $reviewer, ':id_number' => $id_number]);
            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();
            error_log('Failed to deactivate user: ' . $e->getMessage());
            return false;
        }
    }

    /* ========================== BLOCK LIST & AUDIT LOG METHODS ======================== */

    public function ensureBlockListSchema(): void
    {
        try {
            // Check if id_number column exists in block_list
            $stmt = $this->conn->query("SHOW COLUMNS FROM block_list LIKE 'id_number'");
            if (!$stmt->fetch()) {
                $this->conn->exec("ALTER TABLE block_list ADD COLUMN id_number VARCHAR(20) DEFAULT NULL AFTER user_id");
            }

            // Check if blocked_by column exists in block_list
            $stmt = $this->conn->query("SHOW COLUMNS FROM block_list LIKE 'blocked_by'");
            if (!$stmt->fetch()) {
                $this->conn->exec("ALTER TABLE block_list ADD COLUMN blocked_by VARCHAR(100) DEFAULT NULL AFTER role");
            }

            // Check if reason column exists in block_list
            $stmt = $this->conn->query("SHOW COLUMNS FROM block_list LIKE 'reason'");
            if (!$stmt->fetch()) {
                $this->conn->exec("ALTER TABLE block_list ADD COLUMN reason VARCHAR(255) DEFAULT NULL AFTER blocked_by");
            }

            // Check if ip_address column exists in block_list
            $stmt = $this->conn->query("SHOW COLUMNS FROM block_list LIKE 'ip_address'");
            if (!$stmt->fetch()) {
                $this->conn->exec("ALTER TABLE block_list ADD COLUMN ip_address VARCHAR(45) DEFAULT NULL AFTER reason");
            }

            // Ensure audit_logs id_number is VARCHAR(50)
            $stmt = $this->conn->query("SHOW COLUMNS FROM audit_logs LIKE 'id_number'");
            $col = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($col && strpos(strtolower($col['Type']), 'int') !== false) {
                $this->conn->exec("ALTER TABLE audit_logs MODIFY COLUMN id_number VARCHAR(50) DEFAULT NULL");
            }
        } catch (Exception $e) {
            error_log("Schema sync warning: " . $e->getMessage());
        }
    }

    /**
     * Creates the admin_privileges table and the users.session_version
     * column used for role-change / block session invalidation.
     */
    public function ensurePrivilegeSchema(): void
    {
        try {
            $stmt = $this->conn->query("SHOW TABLES LIKE 'admin_privileges'");
            if (!$stmt->fetch()) {
                $this->conn->exec(
                    "CREATE TABLE admin_privileges (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        id_number VARCHAR(20) NOT NULL,
                        privilege_key VARCHAR(50) NOT NULL,
                        granted_at DATETIME DEFAULT NULL,
                        UNIQUE KEY uq_admin_priv (id_number, privilege_key)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
                );
            }

            $stmt = $this->conn->query("SHOW COLUMNS FROM users LIKE 'session_version'");
            if (!$stmt->fetch()) {
                $this->conn->exec("ALTER TABLE users ADD COLUMN session_version INT NOT NULL DEFAULT 0 AFTER status");
            }
        } catch (Exception $e) {
            error_log("Privilege schema sync warning: " . $e->getMessage());
        }
    }

    /* ========================== ADMIN PRIVILEGES & ROLE MANAGEMENT ======================== */

    public const PRIVILEGES = [
        'approve_registrations' => 'Approve/Reject Registrations',
        'view_user_logs' => 'View User Activity Logs',
        'view_admin_logs' => 'View Admin Activity Logs',
        'view_users' => 'View All User Accounts',
        'block_users' => 'Block/Unblock Users',
        'delete_users' => 'Delete User Accounts',
        'edit_users' => 'Edit User Information'
    ];

    public function getAdminPrivileges(string $id_number): array
    {
        $stmt = $this->conn->prepare("SELECT privilege_key FROM admin_privileges WHERE id_number = :id_number");
        $stmt->execute([':id_number' => $id_number]);
        return array_map(function ($row) {
            return $row['privilege_key'];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function saveAdminPrivileges(string $id_number, array $privileges): bool
    {
        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare("DELETE FROM admin_privileges WHERE id_number = :id_number");
            $stmt->execute([':id_number' => $id_number]);

            $insert = $this->conn->prepare("INSERT INTO admin_privileges (id_number, privilege_key, granted_at) VALUES (:id_number, :privilege_key, :granted_at)");
            foreach ($privileges as $key) {
                if (array_key_exists($key, self::PRIVILEGES)) {
                    $insert->execute([
                        ':id_number' => $id_number,
                        ':privilege_key' => $key,
                        ':granted_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Failed to save admin privileges: " . $e->getMessage());
            return false;
        }
    }

    public function hasAdminPrivilege(string $id_number, string $key): bool
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM admin_privileges WHERE id_number = :id_number AND privilege_key = :key");
        $stmt->execute([':id_number' => $id_number, ':key' => $key]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function removeAllPrivileges(string $id_number): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM admin_privileges WHERE id_number = :id_number");
        return $stmt->execute([':id_number' => $id_number]);
    }

    /**
     * Returns the live authentication state of an account:
     * id_number, username, role, status, session_version.
     * Used by session_protect.php and endpoints to validate active sessions.
     */
    public function getAccountAuthState(string $id_number): ?array
    {
        $stmt = $this->conn->prepare("SELECT id_number, username, role, status, session_version, must_change_password FROM users WHERE id_number = :id_number");
        $stmt->execute([':id_number' => $id_number]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function bumpSessionVersion(string $id_number): bool
    {
        $stmt = $this->conn->prepare("UPDATE users SET session_version = session_version + 1 WHERE id_number = :id_number");
        return $stmt->execute([':id_number' => $id_number]);
    }

    /**
     * Changes the role of an account while keeping role, privileges,
     * session invalidation and audit logs synchronized.
     *
     * A promotion to Super Admin always creates an eligible blocked account.
     * It cannot replace the active Super Admin outside the logout handoff.
     *
     * @param string $targetId            id_number of the account to change
     * @param string $newRole             user | admin | superadmin
     * @param string $performedById       id_number of the super admin performing the change
     * @param string $performedByUsername username of the performing super admin
     * @return array ['success' => bool, 'message' => string, 'auto_logout' => bool]
     */
    public function changeRole(string $targetId, string $newRole, string $performedById, string $performedByUsername): array
    {
        $allowedRoles = ['user', 'admin', 'superadmin'];
        if (!in_array($newRole, $allowedRoles, true)) {
            return ['success' => false, 'message' => 'Invalid role.'];
        }

        $target = $this->getUserOrAdminByIdNumber($targetId);
        if (!$target) {
            return ['success' => false, 'message' => 'Account not found.'];
        }

        $oldRole = $target['role'];
        if ($oldRole === $newRole) {
            return ['success' => false, 'message' => 'Role is already set to ' . $newRole . '.'];
        }

        try {
            $this->conn->beginTransaction();
            if ($targetId === $performedById && $oldRole === 'superadmin' && $newRole !== 'superadmin') {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'The active Super Admin can only rotate through Logout.'];
            }

            $targetStatus = $target['status'];
            if ($newRole === 'superadmin') {
                $targetStatus = 'blocked';
                $this->conn->prepare(
                    "UPDATE users SET role = 'superadmin', status = 'blocked', superadmin_eligible = 1,
                        superadmin_queue_at = COALESCE(superadmin_queue_at, NOW()),
                        session_version = session_version + 1 WHERE id_number = :id_number"
                )->execute([':id_number' => $targetId]);
                $this->syncBlockListOnBlock(
                    $targetId,
                    $performedByUsername,
                    'Queued as an eligible Super Admin; the oldest eligible account activates on logout.',
                    $_SERVER['REMOTE_ADDR'] ?? ''
                );
            } else {
                $this->conn->prepare(
                    "UPDATE users SET role = :role, superadmin_eligible = 0, superadmin_queue_at = NULL,
                        session_version = session_version + 1 WHERE id_number = :id_number"
                )->execute([':role' => $newRole, ':id_number' => $targetId]);
            }

            // Keep block_list role in sync (if a record exists).
            $this->conn->prepare("UPDATE block_list SET role = :role WHERE id_number = :id_number")
                ->execute([':role' => $newRole, ':id_number' => $targetId]);

            // User accounts must never retain administrative privileges.
            if ($newRole === 'user') {
                $this->removeAllPrivileges($targetId);
            }

            // Audit the role change and any queueing decision.
            $details = "Changed By: {$performedByUsername} | Target User: {$target['username']} | Old Role: {$oldRole} | New Role: {$newRole}";
            if ($newRole === 'superadmin') {
                $details .= ' | Status: blocked and queued for logout handoff';
            }
            $this->logAuditAction($performedById, $performedByUsername, 'superadmin', 'Change Role', $details);

            $this->conn->commit();

            return [
                'success' => true,
                'message' => $newRole === 'superadmin'
                    ? 'Role updated to superadmin. The account is blocked and queued for the next eligible handoff.'
                    : 'Role updated to ' . $newRole . '.',
                'auto_logout' => false,
                'status' => $targetStatus
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Failed to change role: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update role.'];
        }
    }

    /**
     * Counts pending approval items the Super Admin needs to act on.
     * Used by the Super Admin dashboard approval card.
     */
    public function getSuperAdminPendingApprovalCount(): int
    {
        $total = 0;
        try {
            $stmt = $this->conn->query("SELECT COUNT(*) FROM {$this->pendingTable} WHERE status = 'pending'");
            $total += (int)$stmt->fetchColumn();

            $stmt = $this->conn->query("SELECT COUNT(*) FROM delete_requests WHERE status = 'pending'");
            $total += (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("Pending approval count failed: " . $e->getMessage());
        }
        return $total;
    }

    /**
     * Counts pending approval items that an Admin is allowed to handle.
     * Admins typically only see pending user registrations.
     */
    public function getAdminPendingApprovalCount(string $adminIdNumber): int
    {
        $total = 0;
        try {
            $hasApprove = $this->hasAdminPrivilege($adminIdNumber, 'approve_registrations')
                || $this->hasAdminPrivilege($adminIdNumber, 'view_users');
            if ($hasApprove) {
                $stmt = $this->conn->query("SELECT COUNT(*) FROM {$this->pendingTable} WHERE status = 'pending'");
                $total += (int)$stmt->fetchColumn();
            }
        } catch (Exception $e) {
            error_log("Admin pending approval count failed: " . $e->getMessage());
        }
        return $total;
    }

    public function logAuditAction(?string $id_number, string $username, string $role, string $action, string $details, ?string $timeIn = null, ?string $timeOut = null): int
    {
        try {
            date_default_timezone_set('Asia/Manila');
            $sql = "INSERT INTO audit_logs (id_number, username, role, action, details, time_in, time_out) 
                    VALUES (:id_number, :username, :role, :action, :details, :time_in, :time_out)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':id_number' => $id_number,
                ':username'  => $username,
                ':role'      => strtolower($role),
                ':action'    => $action,
                ':details'   => $details,
                ':time_in'   => $timeIn ?? date('Y-m-d H:i:s'),
                ':time_out'  => $timeOut
            ]);
            return (int)$this->conn->lastInsertId();
        } catch (Exception $e) {
            error_log("Failed to log audit action: " . $e->getMessage());
            return 0;
        }
    }

    public function updateActiveLoginToLogout(?string $username = null, ?string $id_number = null, ?int $auditId = null, ?string $details = null): bool
    {
        try {
            date_default_timezone_set('Asia/Manila');
            $now = date('Y-m-d H:i:s');

            // 1. Try to update by session auditId first if provided
            if ($auditId && $auditId > 0) {
                $sql = "UPDATE audit_logs SET action = 'Logout', time_out = :time_out WHERE id = :id";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([':time_out' => $now, ':id' => $auditId]);
                if ($stmt->rowCount() > 0) {
                    return true;
                }
            }

            // 2. If auditId didn't match or wasn't provided, find the active Login record for this user
            if (!empty($username) || !empty($id_number)) {
                $sql = "UPDATE audit_logs SET action = 'Logout', time_out = :time_out 
                        WHERE action = 'Login' AND time_out IS NULL 
                        AND (username = :username OR (id_number = :id_number AND id_number != '')) 
                        ORDER BY id DESC LIMIT 1";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    ':time_out'  => $now,
                    ':username'  => $username ?? '',
                    ':id_number' => $id_number ?? ''
                ]);
                if ($stmt->rowCount() > 0) {
                    return true;
                }
            }

            // 3. Fallback: If no active Login record was found at all, log a Logout entry
            if (!empty($username) || !empty($id_number)) {
                $this->logAuditAction(
                    $id_number,
                    $username ?? 'Unknown',
                    'user',
                    'Logout',
                    $details ?? 'User logged out.',
                    $now,
                    $now
                );
            }
            return true;
        } catch (Exception $e) {
            error_log("Failed to update active login to logout: " . $e->getMessage());
            return false;
        }
    }

    public function updateAuditLogTimeout(int $auditId, ?string $details = null): bool
    {
        return $this->updateActiveLoginToLogout(null, null, $auditId, $details);
    }

    public function getUserOrAdminByIdNumber(string $id_number): ?array
    {
        $stmt = $this->conn->prepare("SELECT id_number, first_name, middle_name, last_name, extension, birthdate, gender, age, username, email, role, status, created_at FROM users WHERE id_number = :id_number");
        $stmt->execute([':id_number' => $id_number]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $nameParts = array_filter([$user['first_name'], $user['middle_name'], $user['last_name'], $user['extension']]);
            $user['name'] = implode(' ', $nameParts);
            return $user;
        }
        return null;
    }

    public function syncBlockListOnBlock(string $id_number, string $blockedBy = 'Super Admin', ?string $reason = null, ?string $ip = null): bool
    {
        try {
            $user = $this->getUserOrAdminByIdNumber($id_number);
            if (!$user) return false;

            $stmt = $this->conn->prepare("SELECT id FROM block_list WHERE id_number = :id_number OR (username = :username AND username != '') LIMIT 1");
            $stmt->execute([':id_number' => $id_number, ':username' => $user['username']]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $updateStmt = $this->conn->prepare("UPDATE block_list SET id_number = :id_number, name = :name, username = :username, email = :email, role = :role, status = 'blocked', blocked_by = :blocked_by, reason = :reason, ip_address = :ip, blocked_at = NOW() WHERE id = :id");
                return $updateStmt->execute([
                    ':id_number'  => $id_number,
                    ':name'       => $user['name'],
                    ':username'   => $user['username'],
                    ':email'      => $user['email'] ?? '',
                    ':role'       => $user['role'],
                    ':blocked_by' => $blockedBy,
                    ':reason'     => $reason,
                    ':ip'         => $ip,
                    ':id'         => $existing['id']
                ]);
            } else {
                $insertStmt = $this->conn->prepare("INSERT INTO block_list (user_id, id_number, name, username, email, role, status, blocked_by, reason, ip_address, blocked_at) VALUES (0, :id_number, :name, :username, :email, :role, 'blocked', :blocked_by, :reason, :ip, NOW())");
                return $insertStmt->execute([
                    ':id_number'  => $id_number,
                    ':name'       => $user['name'],
                    ':username'   => $user['username'],
                    ':email'      => $user['email'] ?? '',
                    ':role'       => $user['role'],
                    ':blocked_by' => $blockedBy,
                    ':reason'     => $reason,
                    ':ip'         => $ip
                ]);
            }
        } catch (Exception $e) {
            error_log("Failed to sync block list entry: " . $e->getMessage());
            return false;
        }
    }

    public function getBlockList(string $search = '', string $status = 'blocked', string $roleFilter = 'all', int $offset = 0, int $limit = 10): array
    {
        // First sync blocked users into block_list if not present
        $syncStmt = $this->conn->query("SELECT id_number FROM users WHERE status = 'blocked'");
        if ($syncStmt) {
            $blockedUsers = $syncStmt->fetchAll(PDO::FETCH_COLUMN);
            foreach ($blockedUsers as $bId) {
                if (!empty($bId)) {
                    $chk = $this->conn->prepare("SELECT id FROM block_list WHERE id_number = :id_number LIMIT 1");
                    $chk->execute([':id_number' => $bId]);
                    if (!$chk->fetch()) {
                        $this->syncBlockListOnBlock($bId, 'System Admin');
                    }
                }
            }
        }

        $sql = "SELECT b.id, b.id_number, b.name, b.username, b.email, b.role, b.status, b.blocked_by, b.reason, b.ip_address, b.blocked_at 
                FROM block_list b 
                WHERE 1=1";
        $params = [];

        if (!empty($status) && $status !== 'all') {
            $sql .= " AND b.status = :status";
            $params[':status'] = $status;
        }

        // Super Admin accounts should never appear in the regular block lists
        if ($roleFilter === 'admin') {
            $sql .= " AND b.role = 'admin'";
        } elseif ($roleFilter === 'user') {
            $sql .= " AND b.role = 'user'";
        } else {
            $sql .= " AND b.role IN ('admin', 'user')";
        }

        if (!empty($search)) {
            $sql .= " AND (b.id_number LIKE :search 
                      OR b.name LIKE :search 
                      OR b.username LIKE :search 
                      OR b.email LIKE :search 
                      OR b.blocked_by LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY b.blocked_at DESC, b.id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            if (empty($row['id_number']) && !empty($row['username'])) {
                $uStmt = $this->conn->prepare("SELECT id_number FROM users WHERE username = :u LIMIT 1");
                $uStmt->execute([':u' => $row['username']]);
                $uRow = $uStmt->fetch(PDO::FETCH_ASSOC);
                if ($uRow) {
                    $row['id_number'] = $uRow['id_number'];
                }
            }
            if (empty($row['blocked_by'])) {
                $row['blocked_by'] = 'Super Admin';
            }
        }
        return $rows;
    }

    public function getBlockListCount(string $search = '', string $status = 'blocked', string $roleFilter = 'all'): int
    {
        $sql = "SELECT COUNT(*) FROM block_list b WHERE 1=1";
        $params = [];

        if (!empty($status) && $status !== 'all') {
            $sql .= " AND b.status = :status";
            $params[':status'] = $status;
        }

        if ($roleFilter === 'admin') {
            $sql .= " AND b.role = 'admin'";
        } elseif ($roleFilter === 'user') {
            $sql .= " AND b.role = 'user'";
        } else {
            $sql .= " AND b.role IN ('admin', 'user')";
        }

        if (!empty($search)) {
            $sql .= " AND (b.id_number LIKE :search 
                      OR b.name LIKE :search 
                      OR b.username LIKE :search 
                      OR b.email LIKE :search 
                      OR b.blocked_by LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getBlockDetail(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT b.*, u.first_name, u.last_name, u.birthdate, u.gender, u.created_at as user_created_at 
                                      FROM block_list b 
                                      LEFT JOIN users u ON (b.id_number = u.id_number OR b.username = u.username)
                                      WHERE b.id = :id");
        $stmt->execute([':id' => $id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($record) {
            if (empty($record['id_number']) && !empty($record['username'])) {
                $uStmt = $this->conn->prepare("SELECT id_number FROM users WHERE username = :u LIMIT 1");
                $uStmt->execute([':u' => $record['username']]);
                $uRow = $uStmt->fetch(PDO::FETCH_ASSOC);
                if ($uRow) {
                    $record['id_number'] = $uRow['id_number'];
                }
            }
            if (empty($record['blocked_by'])) {
                $record['blocked_by'] = 'Super Admin';
            }
            return $record;
        }
        return null;
    }

    public function unblockAccount(int $id, string $unblockedByUsername = 'superadmin', string $unblockedByRole = 'superadmin'): bool
    {
        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare("SELECT * FROM block_list WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $blockRecord = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$blockRecord) {
                $this->conn->rollBack();
                return false;
            }

            $id_number = $blockRecord['id_number'] ?? '';
            $username  = $blockRecord['username'] ?? '';

            // 1. Change the user's status from blocked to active in users table
            if (!empty($id_number)) {
                $uStmt = $this->conn->prepare("UPDATE users SET status = 'active' WHERE id_number = :id_number");
                $uStmt->execute([':id_number' => $id_number]);
            }
            if (!empty($username)) {
                $uStmt = $this->conn->prepare("UPDATE users SET status = 'active' WHERE username = :username");
                $uStmt->execute([':username' => $username]);
            }

            // 2. Update the Block List record to unblocked
            $bStmt = $this->conn->prepare("UPDATE block_list SET status = 'unblocked' WHERE id = :id");
            $bStmt->execute([':id' => $id]);

            // 3. Record the unblock action in the Audit Logs
            $targetUser = $this->getUserOrAdminByIdNumber($id_number);
            $targetName = $targetUser['name'] ?? ($username ?: $id_number);
            $opUser = $this->getUserOrAdminByIdNumber($unblockedByUsername);
            $opId = $opUser['id_number'] ?? $unblockedByUsername;
            $targetRole = $blockRecord['role'] ?? ($targetUser['role'] ?? 'user');
            $action = ($targetRole === 'admin') ? 'Unblock Admin' : 'Unblock User';
            $kind = ($targetRole === 'admin') ? 'admin' : 'user';
            $details = "Unblocked {$kind} {$targetName}.";
            $this->logAuditAction($opId, $unblockedByUsername, $unblockedByRole, $action, $details);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Failed to unblock account: " . $e->getMessage());
            return false;
        }
    }

    /** Ensures deletion/approval tables and all account lifecycle statuses exist. */
    public function ensureDeleteRequestsAndApprovalSchema(): void
    {
        try {
            // Migrate legacy `block` values to the canonical `blocked` status,
            // while adding the non-destructive deletion states.
            $stmt = $this->conn->query("SHOW COLUMNS FROM users LIKE 'status'");
            $col = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$col) {
                $this->conn->exec(
                    "ALTER TABLE users ADD COLUMN status ENUM('blocked','pending','pending_approval','pending_deletion','active','inactive') NOT NULL DEFAULT 'active' AFTER password_hash"
                );
                $stmt = $this->conn->query("SHOW COLUMNS FROM users LIKE 'status'");
                $col = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            $statusType = strtolower($col['Type'] ?? '');
            $needsLifecycleStatuses = strpos($statusType, "'pending_deletion'") === false
                || strpos($statusType, "'inactive'") === false
                || strpos($statusType, "'blocked'") === false;
            $hasLegacyBlock = strpos($statusType, "'block'") !== false;

            if ($col && ($needsLifecycleStatuses || $hasLegacyBlock)) {
                $this->conn->exec(
                    "ALTER TABLE users MODIFY COLUMN status ENUM('block','blocked','pending','pending_approval','pending_deletion','active','inactive') NOT NULL DEFAULT 'active'"
                );
                $this->conn->exec("UPDATE users SET status = 'blocked' WHERE status = 'block'");
                $this->conn->exec(
                    "ALTER TABLE users MODIFY COLUMN status ENUM('blocked','pending','pending_approval','pending_deletion','active','inactive') NOT NULL DEFAULT 'active'"
                );
            }

            // Older dumps may contain MySQL's empty ENUM sentinel.
            $this->conn->exec("UPDATE users SET status = 'inactive', session_version = session_version + 1 WHERE status = '' OR status IS NULL");

            $sessionCol = $this->conn->query("SHOW COLUMNS FROM users LIKE 'session_version'")->fetch(PDO::FETCH_ASSOC);
            if (!$sessionCol) {
                $this->conn->exec("ALTER TABLE users ADD COLUMN session_version INT NOT NULL DEFAULT 0 AFTER status");
            }

            // Ensure delete_requests table
            $stmt = $this->conn->query("SHOW TABLES LIKE 'delete_requests'");
            if (!$stmt->fetch()) {
                $this->conn->exec(
                    "CREATE TABLE delete_requests (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id_number VARCHAR(20) NOT NULL,
                        user_name VARCHAR(150) NOT NULL,
                        user_username VARCHAR(100) NOT NULL,
                        user_email VARCHAR(150) NOT NULL,
                        user_role VARCHAR(50) NOT NULL,
                        user_details TEXT NOT NULL,
                        reason TEXT NOT NULL,
                        requested_by_id VARCHAR(20) NOT NULL,
                        requested_by_username VARCHAR(100) NOT NULL,
                        status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
                        requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        reviewed_at DATETIME DEFAULT NULL,
                        reviewed_by VARCHAR(100) DEFAULT NULL,
                        review_notes TEXT DEFAULT NULL,
                        KEY idx_status (status),
                        KEY idx_user_id (user_id_number)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
                );
            }

            // Reconcile requests created before lifecycle statuses were added.
            $this->conn->exec(
                "UPDATE users u
                 INNER JOIN delete_requests d ON d.user_id_number = u.id_number
                 SET u.status = 'pending_deletion'
                 WHERE d.status = 'pending' AND u.status = 'active'"
            );
            $this->conn->exec(
                "UPDATE users u
                 INNER JOIN delete_requests d ON d.user_id_number = u.id_number
                 SET u.status = 'inactive', u.session_version = u.session_version + 1
                 WHERE d.status = 'approved' AND u.status <> 'inactive'"
            );
            $this->conn->exec(
                "UPDATE users u
                 SET u.status = 'active'
                 WHERE u.status = 'pending_deletion'
                   AND EXISTS (SELECT 1 FROM delete_requests r WHERE r.user_id_number = u.id_number AND r.status = 'rejected')
                   AND NOT EXISTS (SELECT 1 FROM delete_requests p WHERE p.user_id_number = u.id_number AND p.status = 'pending')"
            );
            $this->conn->exec(
                "UPDATE block_list b
                 INNER JOIN delete_requests d ON d.user_id_number = b.id_number
                 SET b.status = 'unblocked'
                 WHERE d.status = 'approved' AND b.status = 'blocked'"
            );
        } catch (Exception $e) {
            error_log("Approval and Delete Request schema sync warning: " . $e->getMessage());
        }
    }

    public function ensurePendingRegistrationsSchema(): void
    {
        try {
            $pendingDatabase = Database::getInstance()->getPendingDatabaseName();
            $this->conn->exec(
                "CREATE DATABASE IF NOT EXISTS `{$pendingDatabase}`
                 CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci"
            );
            $this->conn->exec(
                    "CREATE TABLE IF NOT EXISTS {$this->pendingTable} (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id VARCHAR(20) NOT NULL,
                        first_name VARCHAR(50) NOT NULL,
                        middle_name VARCHAR(50) DEFAULT NULL,
                        last_name VARCHAR(50) NOT NULL,
                        extension VARCHAR(10) DEFAULT NULL,
                        birthdate DATE DEFAULT NULL,
                        gender ENUM('male','female') DEFAULT NULL,
                        age INT DEFAULT NULL,
                        username VARCHAR(50) NOT NULL,
                        email VARCHAR(150) NOT NULL,
                        password_hash VARCHAR(255) NOT NULL,
                        role VARCHAR(20) NOT NULL DEFAULT 'user',
                        status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
                        street VARCHAR(100) DEFAULT NULL,
                        barangay VARCHAR(100) DEFAULT NULL,
                        city_municipality VARCHAR(100) DEFAULT NULL,
                        province VARCHAR(100) DEFAULT NULL,
                        country VARCHAR(100) DEFAULT NULL,
                        zip_code VARCHAR(10) DEFAULT NULL,
                        security_answers JSON DEFAULT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        reviewed_at DATETIME DEFAULT NULL,
                        reviewed_by VARCHAR(100) DEFAULT NULL,
                        rejection_reason TEXT DEFAULT NULL,
                        UNIQUE KEY uq_pr_user_id (user_id),
                        UNIQUE KEY uq_pr_email (email),
                        UNIQUE KEY uq_pr_username (username),
                        KEY idx_pr_status (status),
                        KEY idx_pr_created (created_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
            );

            // Preserve and copy records from older installations where the
            // pending table lived inside the main database. The legacy table
            // is intentionally retained as a recoverable backup.
            $legacyTable = $this->conn->query("SHOW TABLES LIKE 'pending_registrations'");
            if ($legacyTable->fetchColumn()) {
                $this->conn->exec(
                    "INSERT IGNORE INTO {$this->pendingTable}
                        (user_id, first_name, middle_name, last_name, extension, birthdate, gender, age,
                         username, email, password_hash, role, status, street, barangay, city_municipality,
                         province, country, zip_code, security_answers, created_at, updated_at, reviewed_at,
                         reviewed_by, rejection_reason)
                     SELECT user_id, first_name, middle_name, last_name, extension, birthdate, gender, age,
                            username, email, password_hash, role, status, street, barangay, city_municipality,
                            province, country, zip_code, security_answers, created_at, updated_at, reviewed_at,
                            reviewed_by, rejection_reason
                     FROM pending_registrations"
                );
            }
        } catch (Throwable $e) {
            error_log("Pending registrations schema sync warning: " . $e->getMessage());
        }
    }

    /**
     * Ensures view-detail columns exist on the users table.
     */
    public function ensureViewDetailsSchema(): void
    {
        try {
            $colsToAdd = [
                'contact_number' => "ALTER TABLE users ADD COLUMN IF NOT EXISTS contact_number VARCHAR(20) DEFAULT NULL AFTER email",
                'updated_at'     => "ALTER TABLE users ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL AFTER created_at",
                'last_login'     => "ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL DEFAULT NULL AFTER updated_at",
            ];

            foreach ($colsToAdd as $col => $sql) {
                $stmt = $this->conn->query("SHOW COLUMNS FROM users LIKE '{$col}'");
                if (!$stmt->fetch()) {
                    $this->conn->exec($sql);
                }
            }
        } catch (Exception $e) {
            error_log("View details schema sync warning: " . $e->getMessage());
        }
    }

    public function setAccountStatus(string $id_number, string $status): bool
    {
        $stmt = $this->conn->prepare("UPDATE users SET status = :status WHERE id_number = :id_number");
        return $stmt->execute([':status' => $status, ':id_number' => $id_number]);
    }

    /* ========================== PENDING REGISTRATIONS METHODS ======================== */

    public function createPendingRegistration(array $data): string|false
    {
        try {
            $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
            $age = $this->calculateAge($data['birthdate']);
            $id_number = !empty($data['id_number']) ? trim($data['id_number']) : $this->generateIdNumber();

            $securityAnswersJson = null;
            if (!empty($data['security_answers']) && is_array($data['security_answers'])) {
                $answers = [];
                foreach ($data['security_answers'] as $entry) {
                    $qId = (int)($entry['question_id'] ?? 0);
                    $ans = trim($entry['answer'] ?? '');
                    if ($qId > 0 && $ans !== '') {
                        $answers[] = [
                            'question_id' => $qId,
                            'answer_hash' => password_hash($ans, PASSWORD_BCRYPT)
                        ];
                    }
                }
                $securityAnswersJson = !empty($answers) ? json_encode($answers) : null;
            }

            $sql = "INSERT INTO {$this->pendingTable}
                        (user_id, first_name, middle_name, last_name, extension, birthdate, gender, age, username, email, password_hash, role, status,
                         street, barangay, city_municipality, province, country, zip_code, security_answers)
                    VALUES
                        (:user_id, :first_name, :middle_name, :last_name, :extension, :birthdate, :gender, :age, :username, :email, :password_hash, 'user', 'pending',
                         :street, :barangay, :city, :province, :country, :zip_code, :security_answers)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':user_id'      => $id_number,
                ':first_name'   => $data['first_name'],
                ':middle_name'  => $data['middle_name'] ?? null,
                ':last_name'    => $data['last_name'],
                ':extension'    => $data['extension'] ?? null,
                ':birthdate'    => $data['birthdate'],
                ':gender'       => $data['gender'],
                ':age'          => $age,
                ':username'     => $data['username'],
                ':email'        => $data['email'] ?? null,
                ':password_hash'=> $passwordHash,
                ':street'       => $data['street'] ?? null,
                ':barangay'     => $data['barangay'] ?? null,
                ':city'         => $data['city'] ?? null,
                ':province'     => $data['province'] ?? null,
                ':country'      => $data['country'] ?? null,
                ':zip_code'     => $data['zip'] ?? null,
                ':security_answers' => $securityAnswersJson
            ]);

            return $id_number;
        } catch (Exception $e) {
            error_log("Failed to create pending registration: " . $e->getMessage());
            return false;
        }
    }

    public function pendingUserIdExists(string $id_number): bool
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM {$this->pendingTable} WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $id_number]);
        return $stmt->fetchColumn() > 0;
    }

    public function pendingUsernameExists(string $username): bool
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM {$this->pendingTable} WHERE username = :username");
        $stmt->execute([':username' => $username]);
        return $stmt->fetchColumn() > 0;
    }

    public function pendingEmailExists(string $email): bool
    {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM {$this->pendingTable} WHERE email = :email");
            $stmt->execute([':email' => $email]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function findPendingRegistrationByUsername(string $username): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->pendingTable} WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findPendingRegistrationByEmail(string $email): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->pendingTable} WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getPendingRegistrationById(string $user_id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->pendingTable} WHERE user_id = :user_id LIMIT 1");
        $stmt->execute([':user_id' => $user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getPendingRegistrations(string $search = '', string $startDate = '', string $endDate = '', string $month = 'all', string $year = 'all', string $status = 'pending', int $offset = 0, int $limit = 10): array
    {
        $sql = "SELECT p.user_id AS id_number,
                        TRIM(CONCAT(COALESCE(p.first_name, ''),
                             IF(p.middle_name IS NOT NULL AND p.middle_name != '', CONCAT(' ', p.middle_name), ''),
                             IF(p.last_name IS NOT NULL AND p.last_name != '', CONCAT(' ', p.last_name), ''),
                             IF(p.extension IS NOT NULL AND p.extension != '', CONCAT(' ', p.extension), '')
                        )) AS full_name,
                        p.birthdate, p.gender, p.age, p.username, p.email, p.role, p.status, p.created_at,
                        p.street AS purok_street, p.barangay, p.city_municipality, p.province, p.country, p.zip_code
                 FROM {$this->pendingTable} p
                 WHERE 1=1";
        $params = [];

        if (!empty($status) && $status !== 'all') {
            $sql .= " AND p.status = :status";
            $params[':status'] = $status;
        }

        if (!empty($startDate)) {
            $sql .= " AND DATE(p.created_at) >= :startDate";
            $params[':startDate'] = $startDate;
        }

        if (!empty($endDate)) {
            $sql .= " AND DATE(p.created_at) <= :endDate";
            $params[':endDate'] = $endDate;
        }

        if (!empty($month) && $month !== 'all') {
            $sql .= " AND MONTH(p.created_at) = :month";
            $params[':month'] = (int)$month;
        }

        if (!empty($year) && $year !== 'all') {
            $sql .= " AND YEAR(p.created_at) = :year";
            $params[':year'] = (int)$year;
        }

        if (!empty($search)) {
            $sql .= " AND (p.user_id LIKE :search OR p.first_name LIKE :search OR p.last_name LIKE :search OR p.username LIKE :search OR p.email LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPendingRegistrationsCount(string $search = '', string $startDate = '', string $endDate = '', string $month = 'all', string $year = 'all', string $status = 'pending'): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->pendingTable} p WHERE 1=1";
        $params = [];

        if (!empty($status) && $status !== 'all') {
            $sql .= " AND p.status = :status";
            $params[':status'] = $status;
        }

        if (!empty($startDate)) {
            $sql .= " AND DATE(p.created_at) >= :startDate";
            $params[':startDate'] = $startDate;
        }

        if (!empty($endDate)) {
            $sql .= " AND DATE(p.created_at) <= :endDate";
            $params[':endDate'] = $endDate;
        }

        if (!empty($month) && $month !== 'all') {
            $sql .= " AND MONTH(p.created_at) = :month";
            $params[':month'] = (int)$month;
        }

        if (!empty($year) && $year !== 'all') {
            $sql .= " AND YEAR(p.created_at) = :year";
            $params[':year'] = (int)$year;
        }

        if (!empty($search)) {
            $sql .= " AND (p.user_id LIKE :search OR p.first_name LIKE :search OR p.last_name LIKE :search OR p.username LIKE :search OR p.email LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function approveRegistration(string $user_id, string $performedById, string $performedByUsername, string $performedByRole): array
    {
        try {
            $this->conn->beginTransaction();

            // Lock the pending record so two administrators cannot approve it
            // at the same time. Both databases use this same PDO transaction.
            $pendingStmt = $this->conn->prepare(
                "SELECT * FROM {$this->pendingTable} WHERE user_id = :user_id FOR UPDATE"
            );
            $pendingStmt->execute([':user_id' => $user_id]);
            $pending = $pendingStmt->fetch(PDO::FETCH_ASSOC);
            if (!$pending) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Pending registration not found.'];
            }

            if ($pending['status'] !== 'pending') {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'This registration has already been processed.'];
            }

            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE id_number = :id_number OR username = :username OR email = :email");
            $stmt->execute([
                ':id_number' => $user_id,
                ':username'  => $pending['username'],
                ':email'     => $pending['email']
            ]);
            if ((int)$stmt->fetchColumn() > 0) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'A user with this User ID, Username, or Email already exists in the system.'];
            }

            $sqlUser = "INSERT INTO users
                            (id_number, first_name, middle_name, last_name, extension, birthdate, gender, age, username, email, password_hash, role, status, session_version, created_at)
                        VALUES
                            (:id_number, :first_name, :middle_name, :last_name, :extension, :birthdate, :gender, :age, :username, :email, :password_hash, 'user', 'active', 0, NOW())";
            $stmt = $this->conn->prepare($sqlUser);
            $stmt->execute([
                ':id_number'     => $user_id,
                ':first_name'    => $pending['first_name'],
                ':middle_name'   => $pending['middle_name'],
                ':last_name'     => $pending['last_name'],
                ':extension'     => $pending['extension'],
                ':birthdate'     => $pending['birthdate'],
                ':gender'        => $pending['gender'],
                ':age'           => $pending['age'],
                ':username'      => $pending['username'],
                ':email'         => $pending['email'],
                ':password_hash' => $pending['password_hash']
            ]);

            $sqlAddress = "INSERT INTO addresses (id_number, purok_street, barangay, city_municipality, province, country, zip_code)
                           VALUES (:id_number, :street, :barangay, :city, :province, :country, :zip)";
            $stmt = $this->conn->prepare($sqlAddress);
            $stmt->execute([
                ':id_number' => $user_id,
                ':street'    => $pending['street'],
                ':barangay'  => $pending['barangay'],
                ':city'      => $pending['city_municipality'],
                ':province'  => $pending['province'],
                ':country'   => $pending['country'],
                ':zip'       => $pending['zip_code']
            ]);

            if (!empty($pending['security_answers'])) {
                $securityAnswers = json_decode($pending['security_answers'], true);
                if (is_array($securityAnswers)) {
                    $sqlAuth = "INSERT INTO user_auth_answers (id_number, question_id, answer_hash) VALUES (:id_number, :question_id, :answer_hash)";
                    $stmt = $this->conn->prepare($sqlAuth);
                    foreach ($securityAnswers as $entry) {
                        if (!empty($entry['question_id']) && !empty($entry['answer_hash'])) {
                            $stmt->execute([
                                ':id_number'   => $user_id,
                                ':question_id' => (int)$entry['question_id'],
                                ':answer_hash' => $entry['answer_hash']
                            ]);
                        }
                    }
                }
            }

            $stmt = $this->conn->prepare("UPDATE {$this->pendingTable} SET status = 'approved', reviewed_at = NOW(), reviewed_by = :reviewed_by WHERE user_id = :user_id AND status = 'pending'");
            $stmt->execute([':reviewed_by' => $performedByUsername, ':user_id' => $user_id]);
            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('The pending registration was already processed.');
            }

            $name = trim(($pending['first_name'] ?? '') . ' ' . ($pending['last_name'] ?? ''));
            $this->logAuditAction($performedById, $performedByUsername, $performedByRole, 'Approve Registration', "Approved registration for {$name} (ID: {$user_id})");

            $this->conn->commit();
            return ['success' => true, 'message' => 'Registration approved successfully. The account can now log in.'];
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Failed to approve registration: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to approve registration. Please try again.'];
        }
    }

    /**
     * Columns used by administrator-created accounts, first-login password
     * changes, and the deterministic Super Admin rotation queue.
     */
    public function ensureAccountManagementSchema(): void
    {
        try {
            $columns = [
                'must_change_password' => "ALTER TABLE users ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER session_version",
                'superadmin_eligible'  => "ALTER TABLE users ADD COLUMN superadmin_eligible TINYINT(1) NOT NULL DEFAULT 0 AFTER must_change_password",
                'superadmin_queue_at'  => "ALTER TABLE users ADD COLUMN superadmin_queue_at DATETIME DEFAULT NULL AFTER superadmin_eligible",
                'created_by'           => "ALTER TABLE users ADD COLUMN created_by VARCHAR(20) DEFAULT NULL AFTER superadmin_queue_at"
            ];

            foreach ($columns as $name => $sql) {
                $stmt = $this->conn->query("SHOW COLUMNS FROM users LIKE " . $this->conn->quote($name));
                if (!$stmt->fetch()) {
                    $this->conn->exec($sql);
                }
            }

            $this->conn->exec("UPDATE users SET superadmin_eligible = 1 WHERE role = 'superadmin'");
            $this->conn->exec("UPDATE users SET superadmin_queue_at = created_at WHERE role = 'superadmin' AND superadmin_queue_at IS NULL");

            // Repair legacy databases that happen to contain more than one
            // active Super Admin. The oldest account stays active.
            $active = $this->conn->query(
                "SELECT id_number FROM users WHERE role = 'superadmin' AND status = 'active' ORDER BY created_at ASC, id_number ASC"
            )->fetchAll(PDO::FETCH_COLUMN);
            if (count($active) > 1) {
                $keep = array_shift($active);
                $placeholders = implode(',', array_fill(0, count($active), '?'));
                $stmt = $this->conn->prepare(
                    "UPDATE users SET status = 'blocked', session_version = session_version + 1,
                     superadmin_queue_at = COALESCE(superadmin_queue_at, created_at)
                     WHERE id_number IN ({$placeholders}) AND id_number <> ?"
                );
                $stmt->execute(array_merge($active, [$keep]));
            }
        } catch (Throwable $e) {
            error_log('Account-management schema sync warning: ' . $e->getMessage());
        }
    }

    public function verifyAccountPassword(string $idNumber, string $password): bool
    {
        if ($idNumber === '' || $password === '') {
            return false;
        }
        $stmt = $this->conn->prepare("SELECT password_hash FROM users WHERE id_number = :id_number LIMIT 1");
        $stmt->execute([':id_number' => $idNumber]);
        $hash = $stmt->fetchColumn();
        return is_string($hash) && $hash !== '' && password_verify($password, $hash);
    }

    public function getManagedAccounts(
        string $search = '',
        string $status = 'all',
        string $role = 'all',
        int $offset = 0,
        int $limit = 10,
        bool $usersOnly = false
    ): array {
        $sql = "SELECT id_number, first_name, middle_name, last_name, extension, username, email,
                       role, status, created_at, must_change_password
                FROM users WHERE 1 = 1";
        $params = [];
        if ($usersOnly) {
            $sql .= " AND role = 'user'";
        } elseif (in_array($role, ['user', 'admin', 'superadmin'], true)) {
            $sql .= " AND role = :role";
            $params[':role'] = $role;
        }
        if (in_array($status, ['active', 'blocked', 'pending', 'pending_approval', 'pending_deletion', 'inactive'], true)) {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }
        if ($search !== '') {
            $sql .= " AND (id_number LIKE :search OR username LIKE :search OR email LIKE :search
                      OR CONCAT_WS(' ', first_name, middle_name, last_name, extension) LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        $sql .= " ORDER BY created_at DESC, id_number DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['name'] = trim(implode(' ', array_filter([
                $row['first_name'] ?? '', $row['middle_name'] ?? '', $row['last_name'] ?? '', $row['extension'] ?? ''
            ]))) ?: 'Details not completed';
        }
        unset($row);
        return $rows;
    }

    public function getManagedAccountsCount(string $search = '', string $status = 'all', string $role = 'all', bool $usersOnly = false): int
    {
        $sql = "SELECT COUNT(*) FROM users WHERE 1 = 1";
        $params = [];
        if ($usersOnly) {
            $sql .= " AND role = 'user'";
        } elseif (in_array($role, ['user', 'admin', 'superadmin'], true)) {
            $sql .= " AND role = :role";
            $params[':role'] = $role;
        }
        if (in_array($status, ['active', 'blocked', 'pending', 'pending_approval', 'pending_deletion', 'inactive'], true)) {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }
        if ($search !== '') {
            $sql .= " AND (id_number LIKE :search OR username LIKE :search OR email LIKE :search
                      OR CONCAT_WS(' ', first_name, middle_name, last_name, extension) LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getManagedAccountById(string $idNumber): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT u.id_number, u.first_name, u.middle_name, u.last_name, u.extension,
                    u.birthdate, u.gender, u.age, u.username, u.email, u.contact_number,
                    u.role, u.status, u.created_at, u.updated_at, u.last_login,
                    u.must_change_password, a.purok_street AS street, a.barangay,
                    a.city_municipality AS city, a.province, a.country, a.zip_code AS zip
             FROM users u LEFT JOIN addresses a ON a.id_number = u.id_number
             WHERE u.id_number = :id_number LIMIT 1"
        );
        $stmt->execute([':id_number' => $idNumber]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $row['full_name'] = trim(implode(' ', array_filter([
            $row['first_name'] ?? '', $row['middle_name'] ?? '', $row['last_name'] ?? '', $row['extension'] ?? ''
        ])));
        $row['privileges'] = in_array($row['role'], ['admin', 'superadmin'], true)
            ? $this->getAdminPrivileges($idNumber)
            : [];
        return $row;
    }

    public function hasOtherActiveSuperAdmin(string $excludeId = ''): bool
    {
        $sql = "SELECT COUNT(*) FROM users WHERE role = 'superadmin' AND status = 'active'";
        $params = [];
        if ($excludeId !== '') {
            $sql .= " AND id_number <> :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function createManagedAccount(array $data, string $createdBy): array
    {
        $role = strtolower((string)($data['role'] ?? 'user'));
        if (!in_array($role, ['user', 'admin', 'superadmin'], true)) {
            return ['success' => false, 'message' => 'Invalid account role.'];
        }
        $status = $role === 'superadmin' ? 'blocked' : 'active';
        $idNumber = trim((string)($data['id_number'] ?? ''));
        $username = trim((string)($data['username'] ?? ''));
        $password = (string)($data['password'] ?? '');
        $privileges = array_values(array_intersect(array_keys(self::PRIVILEGES), $data['privileges'] ?? []));

        try {
            $this->conn->beginTransaction();
            $stmt = $this->conn->prepare(
                "INSERT INTO users
                    (id_number, first_name, middle_name, last_name, extension, birthdate, gender, age,
                     username, email, password_hash, role, status, session_version, must_change_password,
                     superadmin_eligible, superadmin_queue_at, created_by, created_at)
                 VALUES
                    (:id_number, '', NULL, '', NULL, '1970-01-01', 'male', 0,
                     :username, NULL, :password_hash, :role, :status, 0, 1,
                     :eligible, :queue_at, :created_by, NOW())"
            );
            $stmt->execute([
                ':id_number' => $idNumber,
                ':username' => $username,
                ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ':role' => $role,
                ':status' => $status,
                ':eligible' => $role === 'superadmin' ? 1 : 0,
                ':queue_at' => $role === 'superadmin' ? date('Y-m-d H:i:s') : null,
                ':created_by' => $createdBy
            ]);

            if (in_array($role, ['admin', 'superadmin'], true)) {
                $insertPrivilege = $this->conn->prepare(
                    "INSERT INTO admin_privileges (id_number, privilege_key, granted_at)
                     VALUES (:id_number, :privilege_key, NOW())"
                );
                foreach ($privileges as $privilege) {
                    $insertPrivilege->execute([':id_number' => $idNumber, ':privilege_key' => $privilege]);
                }
            }
            $this->conn->commit();
            return ['success' => true, 'id_number' => $idNumber, 'status' => $status];
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log('Managed account creation failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Unable to create the account. The ID Number or Username may already be in use.'];
        }
    }

    public function updateManagedAccount(string $idNumber, array $data): bool
    {
        $fields = [
            'first_name = :first_name', 'middle_name = :middle_name', 'last_name = :last_name',
            'extension = :extension', 'username = :username', 'role = :role', 'status = :status',
            'superadmin_eligible = :eligible',
            "superadmin_queue_at = CASE WHEN :role_queue = 'superadmin' THEN COALESCE(superadmin_queue_at, NOW()) ELSE NULL END",
            'updated_at = NOW()', 'session_version = session_version + 1'
        ];
        $params = [
            ':first_name' => $data['first_name'], ':middle_name' => $data['middle_name'],
            ':last_name' => $data['last_name'], ':extension' => $data['extension'],
            ':username' => $data['username'], ':role' => $data['role'], ':status' => $data['status'],
            ':eligible' => $data['role'] === 'superadmin' ? 1 : 0, ':role_queue' => $data['role'], ':id_number' => $idNumber
        ];
        if (!empty($data['password'])) {
            $fields[] = 'password_hash = :password_hash';
            $fields[] = 'must_change_password = 1';
            $params[':password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        $stmt = $this->conn->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id_number = :id_number");
        $ok = $stmt->execute($params);
        if ($ok) {
            if (in_array($data['role'], ['admin', 'superadmin'], true)) {
                $this->saveAdminPrivileges($idNumber, $data['privileges'] ?? []);
            } else {
                $this->removeAllPrivileges($idNumber);
            }
            if ($data['status'] === 'blocked') {
                $this->syncBlockListOnBlock($idNumber, $data['operator'] ?? 'superadmin', $data['reason'] ?? null, $_SERVER['REMOTE_ADDR'] ?? '');
            } elseif ($data['status'] === 'active') {
                $this->conn->prepare("UPDATE block_list SET status = 'unblocked' WHERE id_number = :id_number")
                    ->execute([':id_number' => $idNumber]);
            }
        }
        return $ok;
    }

    public function setManagedAccountStatus(string $idNumber, string $status, string $operator, string $operatorRole, ?string $reason = null): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE users SET status = :status, session_version = session_version + 1, updated_at = NOW()
             WHERE id_number = :id_number"
        );
        $ok = $stmt->execute([':status' => $status, ':id_number' => $idNumber]);
        if (!$ok) {
            return false;
        }
        if ($status === 'blocked') {
            $this->syncBlockListOnBlock($idNumber, $operator, $reason, $_SERVER['REMOTE_ADDR'] ?? '');
        } else {
            $this->conn->prepare("UPDATE block_list SET status = 'unblocked' WHERE id_number = :id_number")
                ->execute([':id_number' => $idNumber]);
        }
        return true;
    }

    public function deactivateManagedAccount(string $idNumber): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE users SET status = 'inactive', session_version = session_version + 1, updated_at = NOW()
             WHERE id_number = :id_number AND status <> 'inactive'"
        );
        return $stmt->execute([':id_number' => $idNumber]) && $stmt->rowCount() === 1;
    }

    public function getPersonalDetails(string $idNumber): ?array
    {
        $account = $this->getManagedAccountById($idNumber);
        if (!$account) {
            return null;
        }
        unset($account['privileges'], $account['must_change_password']);
        $account['security_questions'] = array_map(static function (array $row): array {
            return [
                'question_id' => (int)$row['question_id'],
                'question_text' => (string)$row['question_text']
            ];
        }, $this->getUserAuthAnswers($idNumber));
        return $account;
    }

    public function replaceSecurityAnswers(string $idNumber, array $answers): bool
    {
        try {
            $this->conn->beginTransaction();
            $this->saveSecurityAnswers($idNumber, $answers);
            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log('Security question update failed: ' . $e->getMessage());
            return false;
        }
    }

    public function updatePersonalDetails(string $idNumber, array $data): bool
    {
        try {
            $this->conn->beginTransaction();
            $stmt = $this->conn->prepare(
                "UPDATE users SET first_name = :first_name, middle_name = :middle_name,
                    last_name = :last_name, extension = :extension, birthdate = :birthdate,
                    gender = :gender, age = :age, username = :username, email = :email,
                    contact_number = :contact_number, updated_at = NOW()
                 WHERE id_number = :id_number"
            );
            $stmt->execute([
                ':first_name' => $data['first_name'], ':middle_name' => $data['middle_name'],
                ':last_name' => $data['last_name'], ':extension' => $data['extension'],
                ':birthdate' => $data['birthdate'], ':gender' => $data['gender'], ':age' => $data['age'],
                ':username' => $data['username'], ':email' => $data['email'],
                ':contact_number' => $data['contact_number'], ':id_number' => $idNumber
            ]);
            $this->saveOrUpdateAddress($idNumber, $data);
            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log('Personal details update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Blocks the logging-out Super Admin and activates the oldest eligible
     * blocked Super Admin. If the queue is empty, no Super Admin remains active
     * until an eligible account is made available administratively.
     */
    public function rotateSuperAdminOnLogout(string $currentId, string $currentUsername): ?array
    {
        try {
            $this->conn->beginTransaction();
            $stmt = $this->conn->prepare(
                "SELECT id_number FROM users
                 WHERE id_number = :id_number AND role = 'superadmin' AND status = 'active' FOR UPDATE"
            );
            $stmt->execute([':id_number' => $currentId]);
            if (!$stmt->fetchColumn()) {
                $this->conn->rollBack();
                return null;
            }
            $stmt = $this->conn->prepare(
                "SELECT id_number, username FROM users
                 WHERE role = 'superadmin' AND status = 'blocked' AND superadmin_eligible = 1
                   AND id_number <> :current_id
                 ORDER BY COALESCE(superadmin_queue_at, created_at) ASC, created_at ASC, id_number ASC
                 LIMIT 1 FOR UPDATE"
            );
            $stmt->execute([':current_id' => $currentId]);
            $next = $stmt->fetch(PDO::FETCH_ASSOC);

            $this->conn->prepare(
                "UPDATE users SET status = 'blocked', session_version = session_version + 1,
                    superadmin_queue_at = NOW() WHERE id_number = :id_number"
            )->execute([':id_number' => $currentId]);

            if ($next) {
                $this->conn->prepare(
                    "UPDATE users SET status = 'active', session_version = session_version + 1
                     WHERE id_number = :id_number"
                )->execute([':id_number' => $next['id_number']]);
                $this->conn->prepare("UPDATE block_list SET status = 'unblocked' WHERE id_number = :id_number")
                    ->execute([':id_number' => $next['id_number']]);
            }

            $rotationDetails = $next
                ? "Logged out and transferred active Super Admin access to {$next['username']} (ID: {$next['id_number']}). Queue policy: oldest eligible account first."
                : 'Logged out and was automatically blocked. No eligible blocked Super Admin was available for activation.';
            $this->logAuditAction(
                $currentId,
                $currentUsername,
                'superadmin',
                'Rotate Super Admin',
                $rotationDetails
            );
            $this->conn->commit();
            $this->syncBlockListOnBlock(
                $currentId,
                'System',
                'Automatically blocked after logout; placed at the back of the Super Admin rotation queue.',
                $_SERVER['REMOTE_ADDR'] ?? ''
            );
            return $next;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log('Super Admin rotation failed: ' . $e->getMessage());
            return null;
        }
    }

    public function rejectRegistration(string $user_id, string $reason, string $performedById, string $performedByUsername, string $performedByRole): bool
    {
        try {
            $this->conn->beginTransaction();
            $pendingStmt = $this->conn->prepare(
                "SELECT * FROM {$this->pendingTable} WHERE user_id = :user_id FOR UPDATE"
            );
            $pendingStmt->execute([':user_id' => $user_id]);
            $pending = $pendingStmt->fetch(PDO::FETCH_ASSOC);
            if (!$pending) {
                $this->conn->rollBack();
                return false;
            }
            if ($pending['status'] !== 'pending') {
                $this->conn->rollBack();
                return false;
            }

            $name = trim(($pending['first_name'] ?? '') . ' ' . ($pending['last_name'] ?? ''));

            $stmt = $this->conn->prepare("UPDATE {$this->pendingTable} SET status = 'rejected', rejection_reason = :reason, reviewed_at = NOW(), reviewed_by = :reviewed_by WHERE user_id = :user_id AND status = 'pending'");
            $success = $stmt->execute([':reason' => $reason, ':reviewed_by' => $performedByUsername, ':user_id' => $user_id]);

            if (!$success || $stmt->rowCount() !== 1) {
                throw new RuntimeException('The pending registration was already processed.');
            }

            $this->logAuditAction($performedById, $performedByUsername, $performedByRole, 'Reject Registration', "Rejected registration for {$name} (ID: {$user_id}). Reason: {$reason}");
            $this->conn->commit();

            return true;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Failed to reject registration: " . $e->getMessage());
            return false;
        }
    }

    /* ========================== DELETE REQUESTS METHODS ======================== */

    public function submitDeleteRequest(string $id_number, string $reason, string $adminId, string $adminUsername): array
    {
        $user = $this->getUserOrAdminByIdNumber($id_number);
        if (!$user) {
            return ['success' => false, 'message' => 'Account not found.'];
        }

        if (strtolower($user['role']) === 'superadmin') {
            return ['success' => false, 'message' => 'Cannot request deletion for a Super Admin account.'];
        }

        if (($user['status'] ?? '') === 'inactive') {
            return ['success' => false, 'message' => 'This account is already inactive.'];
        }

        if (($user['status'] ?? '') === 'blocked') {
            return ['success' => false, 'message' => 'Blocked accounts must be unblocked before requesting deletion.'];
        }

        // Check if a pending delete request already exists
        $stmt = $this->conn->prepare("SELECT id FROM delete_requests WHERE user_id_number = :id_number AND status = 'pending'");
        $stmt->execute([':id_number' => $id_number]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'A pending deletion request already exists for this account.'];
        }

        $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['middle_name'] ?? '') . ' ' . ($user['last_name'] ?? '') . ' ' . ($user['extension'] ?? ''));

        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare(
                "INSERT INTO delete_requests (user_id_number, user_name, user_username, user_email, user_role, user_details, reason, requested_by_id, requested_by_username, status)
                 VALUES (:user_id_number, :user_name, :user_username, :user_email, :user_role, :user_details, :reason, :requested_by_id, :requested_by_username, 'pending')"
            );
            $stmt->execute([
                ':user_id_number'        => $id_number,
                ':user_name'             => $fullName ?: $user['username'],
                ':user_username'         => $user['username'],
                ':user_email'            => $user['email'] ?? '',
                ':user_role'             => $user['role'],
                ':user_details'          => json_encode($user),
                ':reason'                => $reason,
                ':requested_by_id'       => $adminId,
                ':requested_by_username' => $adminUsername
            ]);

            $statusStmt = $this->conn->prepare(
                "UPDATE users SET status = 'pending_deletion'
                 WHERE id_number = :id_number AND role IN ('user', 'admin') AND status = 'active'"
            );
            $statusStmt->execute([':id_number' => $id_number]);
            if ($statusStmt->rowCount() !== 1) {
                throw new RuntimeException('Unable to mark the account as pending deletion.');
            }

            $targetRole = strtolower($user['role'] ?? 'user');
            $this->logAuditAction(
                $adminId,
                $adminUsername,
                'admin',
                'Delete Request Submitted',
                "Requested deletion of {$targetRole} {$user['username']} (ID: {$id_number}). Status changed from {$user['status']} to Pending Deletion. Reason: {$reason}"
            );

            $this->conn->commit();
            return ['success' => true, 'message' => 'Deletion request submitted. Account status is now Pending Deletion.'];
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log('Failed to submit delete request: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to submit deletion request.'];
        }
    }

    public function getDeleteRequests(string $search = '', string $status = 'all', int $offset = 0, int $limit = 10): array
    {
        $sql = "SELECT id, user_id_number, user_name, user_username, user_email, user_role, user_details, reason,
                       requested_by_id, requested_by_username, status, requested_at, reviewed_at, reviewed_by, review_notes
                FROM delete_requests WHERE 1=1";
        $params = [];

        if (!empty($status) && $status !== 'all') {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }

        if (!empty($search)) {
            $sql .= " AND (user_id_number LIKE :search OR user_name LIKE :search OR user_username LIKE :search OR requested_by_username LIKE :search OR reason LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY requested_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDeleteRequestsCount(string $search = '', string $status = 'all'): int
    {
        $sql = "SELECT COUNT(*) FROM delete_requests WHERE 1=1";
        $params = [];

        if (!empty($status) && $status !== 'all') {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }

        if (!empty($search)) {
            $sql .= " AND (user_id_number LIKE :search OR user_name LIKE :search OR user_username LIKE :search OR requested_by_username LIKE :search OR reason LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function approveDeleteRequest(int $requestId, string $superAdminId, string $superAdminUsername): array
    {
        try {
            $this->conn->beginTransaction();
            $stmt = $this->conn->prepare("SELECT * FROM delete_requests WHERE id = :id AND status = 'pending' FOR UPDATE");
            $stmt->execute([':id' => $requestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$request) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Pending deletion request not found.'];
            }

            $targetId = $request['user_id_number'];
            $statusStmt = $this->conn->prepare(
                "UPDATE users SET status = 'inactive', session_version = session_version + 1
                 WHERE id_number = :id_number AND status IN ('pending_deletion', 'active')"
            );
            $statusStmt->execute([':id_number' => $targetId]);
            if ($statusStmt->rowCount() !== 1) {
                throw new RuntimeException('Target account is not eligible for deletion approval.');
            }

            $updateStmt = $this->conn->prepare(
                "UPDATE delete_requests SET status = 'approved', reviewed_at = NOW(), reviewed_by = :reviewer, review_notes = :notes WHERE id = :id"
            );
            $updateStmt->execute([
                ':reviewer' => $superAdminUsername,
                ':notes' => 'Approved; account status changed to Inactive.',
                ':id' => $requestId
            ]);

            $targetRole = strtolower($request['user_role'] ?? 'user');
            $this->logAuditAction(
                $superAdminId,
                $superAdminUsername,
                'superadmin',
                'Approve Delete Request',
                "Approved deletion of {$targetRole} {$request['user_username']} (ID: {$targetId}) requested by {$request['requested_by_username']}. Pending Deletion changed to Inactive. Reason: {$request['reason']}"
            );
            $this->conn->commit();
            return ['success' => true, 'message' => 'Deletion approved. Account status is now Inactive and its record was preserved.'];
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log('Failed to approve delete request: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to approve deletion request.'];
        }
    }

    public function rejectDeleteRequest(int $requestId, string $notes, string $superAdminId, string $superAdminUsername): array
    {
        try {
            $this->conn->beginTransaction();
            $stmt = $this->conn->prepare("SELECT * FROM delete_requests WHERE id = :id AND status = 'pending' FOR UPDATE");
            $stmt->execute([':id' => $requestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$request) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Pending deletion request not found.'];
            }

            $statusStmt = $this->conn->prepare(
                "UPDATE users SET status = 'active' WHERE id_number = :id_number AND status = 'pending_deletion'"
            );
            $statusStmt->execute([':id_number' => $request['user_id_number']]);
            if ($statusStmt->rowCount() !== 1) {
                throw new RuntimeException('Target account is not pending deletion.');
            }

            $updateStmt = $this->conn->prepare(
                "UPDATE delete_requests SET status = 'rejected', reviewed_at = NOW(), reviewed_by = :reviewer, review_notes = :notes WHERE id = :id"
            );
            $updateStmt->execute([':reviewer' => $superAdminUsername, ':notes' => $notes, ':id' => $requestId]);

            $targetRole = strtolower($request['user_role'] ?? 'user');
            $this->logAuditAction(
                $superAdminId,
                $superAdminUsername,
                'superadmin',
                'Reject Delete Request',
                "Rejected deletion of {$targetRole} {$request['user_username']} (ID: {$request['user_id_number']}). Pending Deletion changed to Active. Notes: {$notes}"
            );
            $this->conn->commit();
            return ['success' => true, 'message' => 'Deletion request rejected. Account status is Active.'];
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log('Failed to reject delete request: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to reject deletion request.'];
        }
    }

    /* ========================== ENHANCED AUDIT LOGS ======================== */

    public function getAuditLogs(string $search = '', string $action = 'all', string $role = 'all', string $startDate = '', string $endDate = '', int $offset = 0, int $limit = 10, array $rolesIn = [], string $month = 'all', string $year = 'all'): array
    {
        $sql = "SELECT a.id, a.id_number,
                       TRIM(CONCAT(
                            COALESCE(u.first_name, ''),
                            IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(' ', u.middle_name), ''),
                            IF(u.last_name IS NOT NULL AND u.last_name != '', CONCAT(' ', u.last_name), ''),
                            IF(u.extension IS NOT NULL AND u.extension != '', CONCAT(' ', u.extension), '')
                       )) AS full_name,
                       a.username, a.role, a.action, a.details, a.time_in, a.time_out
                FROM audit_logs a
                LEFT JOIN users u ON (u.id_number = a.id_number OR (u.username = a.username AND u.username != ''))
                WHERE 1=1";
        $params = [];

        if (!empty($action) && $action !== 'all') {
            $sql .= " AND a.action = :action";
            $params[':action'] = $action;
        }

        if (!empty($rolesIn)) {
            $placeholders = [];
            foreach ($rolesIn as $i => $r) {
                $placeholders[] = ':role_in_' . $i;
                $params[':role_in_' . $i] = strtolower(trim($r));
            }
            $sql .= " AND LOWER(TRIM(a.role)) IN (" . implode(', ', $placeholders) . ")";
        } elseif (!empty($role) && $role !== 'all') {
            $sql .= " AND LOWER(TRIM(a.role)) = :role";
            $params[':role'] = strtolower(trim($role));
        }

        if (!empty($startDate)) {
            $sql .= " AND DATE(a.time_in) >= :startDate";
            $params[':startDate'] = $startDate;
        }

        if (!empty($endDate)) {
            $sql .= " AND DATE(a.time_in) <= :endDate";
            $params[':endDate'] = $endDate;
        }

        if (!empty($month) && $month !== 'all') {
            $sql .= " AND MONTH(a.time_in) = :month";
            $params[':month'] = (int)$month;
        }

        if (!empty($year) && $year !== 'all') {
            $sql .= " AND YEAR(a.time_in) = :year";
            $params[':year'] = (int)$year;
        }

        if (!empty($search)) {
            $sql .= " AND (a.id_number LIKE :search OR a.username LIKE :search OR a.action LIKE :search OR a.details LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY a.id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            if (empty($row['id_number'])) {
                $row['id_number'] = '-';
            }
            if (empty($row['full_name'])) {
                $row['full_name'] = $row['username'];
            }
        }
        return $rows;
    }

    public function getAuditLogsCount(string $search = '', string $action = 'all', string $role = 'all', string $startDate = '', string $endDate = '', array $rolesIn = [], string $month = 'all', string $year = 'all'): int
    {
        $sql = "SELECT COUNT(*) FROM audit_logs a
                LEFT JOIN users u ON (u.id_number = a.id_number OR (u.username = a.username AND u.username != ''))
                WHERE 1=1";
        $params = [];

        if (!empty($action) && $action !== 'all') {
            $sql .= " AND a.action = :action";
            $params[':action'] = $action;
        }

        if (!empty($rolesIn)) {
            $placeholders = [];
            foreach ($rolesIn as $i => $r) {
                $placeholders[] = ':role_in_' . $i;
                $params[':role_in_' . $i] = strtolower(trim($r));
            }
            $sql .= " AND LOWER(TRIM(a.role)) IN (" . implode(', ', $placeholders) . ")";
        } elseif (!empty($role) && $role !== 'all') {
            $sql .= " AND LOWER(TRIM(a.role)) = :role";
            $params[':role'] = strtolower(trim($role));
        }

        if (!empty($startDate)) {
            $sql .= " AND DATE(a.time_in) >= :startDate";
            $params[':startDate'] = $startDate;
        }

        if (!empty($endDate)) {
            $sql .= " AND DATE(a.time_in) <= :endDate";
            $params[':endDate'] = $endDate;
        }

        if (!empty($month) && $month !== 'all') {
            $sql .= " AND MONTH(a.time_in) = :month";
            $params[':month'] = (int)$month;
        }

        if (!empty($year) && $year !== 'all') {
            $sql .= " AND YEAR(a.time_in) = :year";
            $params[':year'] = (int)$year;
        }

        if (!empty($search)) {
            $sql .= " AND (a.id_number LIKE :search OR a.username LIKE :search OR a.action LIKE :search OR a.details LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /* ========================== USER PERSONAL LOGS ======================== */

    public function getUserPersonalLogs(string $idNumber, string $username, string $search = '', string $startDate = '', string $endDate = '', string $month = 'all', string $year = 'all', int $offset = 0, int $limit = 10): array
    {
        $sql = "SELECT a.id, a.id_number,
                       TRIM(CONCAT(
                            COALESCE(u.first_name, ''),
                            IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(' ', u.middle_name), ''),
                            IF(u.last_name IS NOT NULL AND u.last_name != '', CONCAT(' ', u.last_name), ''),
                            IF(u.extension IS NOT NULL AND u.extension != '', CONCAT(' ', u.extension), '')
                       )) AS full_name,
                       a.username, a.role, a.action, a.details, a.time_in, a.time_out
                FROM audit_logs a
                LEFT JOIN users u ON (u.id_number = a.id_number OR (u.username = a.username AND u.username != ''))
                WHERE (a.id_number = :user_id OR a.username = :username)";
        $params = [
            ':user_id'  => $idNumber,
            ':username' => $username
        ];

        if (!empty($startDate)) {
            $sql .= " AND DATE(a.time_in) >= :startDate";
            $params[':startDate'] = $startDate;
        }

        if (!empty($endDate)) {
            $sql .= " AND DATE(a.time_in) <= :endDate";
            $params[':endDate'] = $endDate;
        }

        if (!empty($month) && $month !== 'all') {
            $sql .= " AND MONTH(a.time_in) = :month";
            $params[':month'] = (int)$month;
        }

        if (!empty($year) && $year !== 'all') {
            $sql .= " AND YEAR(a.time_in) = :year";
            $params[':year'] = (int)$year;
        }

        if (!empty($search)) {
            $sql .= " AND (a.action LIKE :search OR a.details LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY a.id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            if (empty($row['id_number'])) {
                $row['id_number'] = $idNumber;
            }
            if (empty($row['full_name'])) {
                $row['full_name'] = $username;
            }
        }
        return $rows;
    }

    public function getUserPersonalLogsCount(string $idNumber, string $username, string $search = '', string $startDate = '', string $endDate = '', string $month = 'all', string $year = 'all'): int
    {
        $sql = "SELECT COUNT(*) FROM audit_logs a
                WHERE (a.id_number = :user_id OR a.username = :username)";
        $params = [
            ':user_id'  => $idNumber,
            ':username' => $username
        ];

        if (!empty($startDate)) {
            $sql .= " AND DATE(a.time_in) >= :startDate";
            $params[':startDate'] = $startDate;
        }

        if (!empty($endDate)) {
            $sql .= " AND DATE(a.time_in) <= :endDate";
            $params[':endDate'] = $endDate;
        }

        if (!empty($month) && $month !== 'all') {
            $sql .= " AND MONTH(a.time_in) = :month";
            $params[':month'] = (int)$month;
        }

        if (!empty($year) && $year !== 'all') {
            $sql .= " AND YEAR(a.time_in) = :year";
            $params[':year'] = (int)$year;
        }

        if (!empty($search)) {
            $sql .= " AND (a.action LIKE :search OR a.details LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }
}

