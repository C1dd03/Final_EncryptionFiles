<?php
require_once __DIR__ . '/../config/db.php';


class User
{
    /**
     * @var \PDO
     */
    private $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
        $this->ensureBlockListSchema();
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
                        (id_number, first_name, middle_name, last_name, extension, birthdate, gender, age, username, email, password_hash) 
                        VALUES 
                        (:id_number, :first_name, :middle_name, :last_name, :extension, :birthdate, :gender, :age, :username, :email, :password_hash)";
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
                ':password_hash' => $passwordHash
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

        if (!$dob || $errors['warning_count'] > 0 || $errors['error_count'] > 0) {
            throw new InvalidArgumentException('Invalid birthdate format.');
        }

        $today = new DateTime();
        if ($dob > $today) {
            throw new InvalidArgumentException('Birthdate cannot be in the future.');
        }

        return $today->diff($dob)->y;
    }

    public function generateIdNumber()
    {
        $year = date("Y");

        // ✅ Get the last inserted ID for the current year only
        $stmt = $this->conn->prepare("
            SELECT id_number 
            FROM users 
            WHERE id_number LIKE :yearPrefix 
            ORDER BY id_number DESC 
            LIMIT 1
        ");
        $stmt->execute([':yearPrefix' => $year . '-%']);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && preg_match('/^' . $year . '-(\d{4})$/', $row['id_number'], $matches)) {
            // ✅ Increment the last 4 digits
            $lastNum = (int)$matches[1];
            $nextNum = str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
        } else {
            // ✅ Start fresh if no ID exists for this year
            $nextNum = '0001';
        }

        return $year . '-' . $nextNum;
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
    public function findById(int $id_number)
    {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id_number = :id_number");
        $stmt->execute([':id_number' => $id_number]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserAuthAnswers(int $id_number)
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

    public function getUserAuthAnswer(int $id_number, int $question_id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM user_auth_answers WHERE id_number = :id_number AND question_id = :question_id");
        $stmt->execute([':id_number' => $id_number, ':question_id' => $question_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updatePassword(int $id_number, string $password_hash)
    {
        $stmt = $this->conn->prepare("UPDATE users SET password_hash=:password WHERE id_number=:id_number");
        return $stmt->execute([':password' => $password_hash, ':id_number' => $id_number]);
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
                $stmt = $this->conn->query("SELECT COUNT(*) FROM users WHERE status = 'block'");
                $userBlockedCount = (int) $stmt->fetchColumn();
            } catch (PDOException $e) {}

            $blockListCount = 0;
            try {
                $stmt = $this->conn->query("SELECT COUNT(*) FROM block_list WHERE status = 'blocked'");
                $blockListCount = (int) $stmt->fetchColumn();
            } catch (PDOException $e) {}

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

            $stmt = $this->conn->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND status = 'block'");
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
                WHERE role = 'admin'";
        $params = [];

        if (!empty($status) && $status !== 'all') {
            if ($status === 'active') {
                $sql .= " AND status = 'active'";
            } elseif ($status === 'blocked' || $status === 'block') {
                $sql .= " AND status = 'block'";
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
        $sql = "SELECT COUNT(*) FROM users WHERE role = 'admin'";
        $params = [];

        if (!empty($status) && $status !== 'all') {
            if ($status === 'active') {
                $sql .= " AND status = 'active'";
            } elseif ($status === 'blocked' || $status === 'block') {
                $sql .= " AND status = 'block'";
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
        $stmt = $this->conn->prepare("SELECT id_number, first_name, middle_name, last_name, extension, birthdate, gender, age, username, email, role, status, created_at FROM users WHERE id_number = :id_number AND role = 'admin'");
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
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
        $birthdate = !empty($data['birthdate']) ? $data['birthdate'] : '2000-01-01';
        $age = $this->calculateAge($birthdate);

        $sql = "INSERT INTO users (id_number, first_name, middle_name, last_name, extension, birthdate, gender, age, username, email, password_hash, role, status)
                VALUES (:id_number, :first_name, :middle_name, :last_name, :extension, :birthdate, :gender, :age, :username, :email, :password_hash, 'admin', :status)";
        
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

        return $id_number;
    }

    public function updateAdmin(string $id_number, array $data): bool
    {
        $fields = [
            'first_name = :first_name',
            'middle_name = :middle_name',
            'last_name = :last_name',
            'username = :username',
            'email = :email',
            'status = :status'
        ];
        $params = [
            ':id_number'   => $id_number,
            ':first_name'  => $data['first_name'],
            ':middle_name' => $data['middle_name'] ?? null,
            ':last_name'   => $data['last_name'],
            ':username'    => $data['username'],
            ':email'       => $data['email'] ?? null,
            ':status'      => $data['status'] ?? 'active'
        ];

        if (!empty($data['password'])) {
            $fields[] = 'password_hash = :password_hash';
            $params[':password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id_number = :id_number AND role = 'admin'";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    public function toggleAdminStatus(string $id_number, string $new_status, string $operator = 'superadmin'): bool
    {
        $stmt = $this->conn->prepare("UPDATE users SET status = :status WHERE id_number = :id_number AND role = 'admin'");
        $res = $stmt->execute([':status' => $new_status, ':id_number' => $id_number]);
        if ($res) {
            $targetUser = $this->getUserOrAdminByIdNumber($id_number);
            $targetName = $targetUser['name'] ?? $id_number;
            $opUser = $this->getUserOrAdminByIdNumber($operator);
            $opId = $opUser['id_number'] ?? $operator;

            if ($new_status === 'block') {
                $this->syncBlockListOnBlock($id_number, $operator);
                $details = "Blocked admin {$targetName}. Reason: Restricted by Super Admin";
                $this->logAuditAction($opId, $operator, 'superadmin', 'Block User', $details);
            } else {
                $uName = $targetUser['username'] ?? $id_number;
                $this->conn->prepare("UPDATE block_list SET status = 'unblocked' WHERE id_number = :id_number OR username = :username")->execute([':id_number' => $id_number, ':username' => $uName]);
                $details = "Unblocked admin {$targetName}.";
                $this->logAuditAction($opId, $operator, 'superadmin', 'Unblock User', $details);
            }
        }
        return $res;
    }

    public function deleteAdmin(string $id_number): bool
    {
        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare("DELETE FROM addresses WHERE id_number = :id_number");
            $stmt->execute([':id_number' => $id_number]);

            $stmt = $this->conn->prepare("DELETE FROM user_auth_answers WHERE id_number = :id_number");
            $stmt->execute([':id_number' => $id_number]);

            $stmt = $this->conn->prepare("DELETE FROM users WHERE id_number = :id_number AND role = 'admin'");
            $result = $stmt->execute([':id_number' => $id_number]);

            $this->conn->commit();
            return $result;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Failed to delete admin: " . $e->getMessage());
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
                $sql .= " AND status = 'block'";
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
                $sql .= " AND status = 'block'";
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
        $stmt = $this->conn->prepare("SELECT id_number, first_name, middle_name, last_name, extension, birthdate, gender, age, username, email, role, status, created_at FROM users WHERE id_number = :id_number AND role = 'user'");
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
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
        $birthdate = !empty($data['birthdate']) ? $data['birthdate'] : '2000-01-01';
        $age = $this->calculateAge($birthdate);

        $sql = "INSERT INTO users (id_number, first_name, middle_name, last_name, extension, birthdate, gender, age, username, email, password_hash, role, status)
                VALUES (:id_number, :first_name, :middle_name, :last_name, :extension, :birthdate, :gender, :age, :username, :email, :password_hash, 'user', :status)";
        
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

        return $id_number;
    }

    public function updateStandardUser(string $id_number, array $data): bool
    {
        $fields = [
            'first_name = :first_name',
            'middle_name = :middle_name',
            'last_name = :last_name',
            'username = :username',
            'email = :email',
            'status = :status'
        ];
        $params = [
            ':id_number'   => $id_number,
            ':first_name'  => $data['first_name'],
            ':middle_name' => $data['middle_name'] ?? null,
            ':last_name'   => $data['last_name'],
            ':username'    => $data['username'],
            ':email'       => $data['email'] ?? null,
            ':status'      => $data['status'] ?? 'active'
        ];

        if (!empty($data['password'])) {
            $fields[] = 'password_hash = :password_hash';
            $params[':password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id_number = :id_number AND role = 'user'";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    public function toggleStandardUserStatus(string $id_number, string $new_status, string $operator = 'superadmin'): bool
    {
        $stmt = $this->conn->prepare("UPDATE users SET status = :status WHERE id_number = :id_number AND role = 'user'");
        $res = $stmt->execute([':status' => $new_status, ':id_number' => $id_number]);
        if ($res) {
            $targetUser = $this->getUserOrAdminByIdNumber($id_number);
            $targetName = $targetUser['name'] ?? $id_number;
            $opUser = $this->getUserOrAdminByIdNumber($operator);
            $opId = $opUser['id_number'] ?? $operator;

            if ($new_status === 'block') {
                $this->syncBlockListOnBlock($id_number, $operator);
                $details = "Blocked user {$targetName}. Reason: Restricted by Super Admin";
                $this->logAuditAction($opId, $operator, 'superadmin', 'Block User', $details);
            } else {
                $uName = $targetUser['username'] ?? $id_number;
                $this->conn->prepare("UPDATE block_list SET status = 'unblocked' WHERE id_number = :id_number OR username = :username")->execute([':id_number' => $id_number, ':username' => $uName]);
                $details = "Unblocked user {$targetName}.";
                $this->logAuditAction($opId, $operator, 'superadmin', 'Unblock User', $details);
            }
        }
        return $res;
    }

    public function deleteStandardUser(string $id_number): bool
    {
        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare("DELETE FROM addresses WHERE id_number = :id_number");
            $stmt->execute([':id_number' => $id_number]);

            $stmt = $this->conn->prepare("DELETE FROM user_auth_answers WHERE id_number = :id_number");
            $stmt->execute([':id_number' => $id_number]);

            $stmt = $this->conn->prepare("DELETE FROM users WHERE id_number = :id_number AND role = 'user'");
            $result = $stmt->execute([':id_number' => $id_number]);

            $this->conn->commit();
            return $result;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Failed to delete user: " . $e->getMessage());
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

    public function syncBlockListOnBlock(string $id_number, string $blockedBy = 'Super Admin'): bool
    {
        try {
            $user = $this->getUserOrAdminByIdNumber($id_number);
            if (!$user) return false;

            $stmt = $this->conn->prepare("SELECT id FROM block_list WHERE id_number = :id_number OR (username = :username AND username != '') LIMIT 1");
            $stmt->execute([':id_number' => $id_number, ':username' => $user['username']]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $updateStmt = $this->conn->prepare("UPDATE block_list SET id_number = :id_number, name = :name, username = :username, email = :email, role = :role, status = 'blocked', blocked_by = :blocked_by, blocked_at = NOW() WHERE id = :id");
                return $updateStmt->execute([
                    ':id_number'  => $id_number,
                    ':name'       => $user['name'],
                    ':username'   => $user['username'],
                    ':email'      => $user['email'] ?? '',
                    ':role'       => $user['role'],
                    ':blocked_by' => $blockedBy,
                    ':id'         => $existing['id']
                ]);
            } else {
                $insertStmt = $this->conn->prepare("INSERT INTO block_list (user_id, id_number, name, username, email, role, status, blocked_by, blocked_at) VALUES (0, :id_number, :name, :username, :email, :role, 'blocked', :blocked_by, NOW())");
                return $insertStmt->execute([
                    ':id_number'  => $id_number,
                    ':name'       => $user['name'],
                    ':username'   => $user['username'],
                    ':email'      => $user['email'] ?? '',
                    ':role'       => $user['role'],
                    ':blocked_by' => $blockedBy
                ]);
            }
        } catch (Exception $e) {
            error_log("Failed to sync block list entry: " . $e->getMessage());
            return false;
        }
    }

    public function getBlockList(string $search = '', string $status = 'blocked', int $offset = 0, int $limit = 10): array
    {
        // First sync users with status='block' into block_list if not present
        $syncStmt = $this->conn->query("SELECT id_number FROM users WHERE status = 'block'");
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

        $sql = "SELECT b.id, b.id_number, b.name, b.username, b.email, b.role, b.status, b.blocked_by, b.blocked_at 
                FROM block_list b 
                WHERE 1=1";
        $params = [];

        if (!empty($status) && $status !== 'all') {
            $sql .= " AND b.status = :status";
            $params[':status'] = $status;
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

    public function getBlockListCount(string $search = '', string $status = 'blocked'): int
    {
        $sql = "SELECT COUNT(*) FROM block_list b WHERE 1=1";
        $params = [];

        if (!empty($status) && $status !== 'all') {
            $sql .= " AND b.status = :status";
            $params[':status'] = $status;
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

            // 1. Change the user's status from block to active in users table
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
            $details = "Unblocked user {$targetName}.";
            $this->logAuditAction($opId, $unblockedByUsername, $unblockedByRole, 'Unblock User', $details);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Failed to unblock account: " . $e->getMessage());
            return false;
        }
    }

    public function getAuditLogs(string $search = '', string $action = 'all', string $role = 'all', string $startDate = '', string $endDate = '', int $offset = 0, int $limit = 10): array
    {
        $sql = "SELECT id, id_number, username, role, action, details, time_in, time_out FROM audit_logs WHERE 1=1";
        $params = [];

        if (!empty($action) && $action !== 'all') {
            $sql .= " AND action = :action";
            $params[':action'] = $action;
        }

        if (!empty($role) && $role !== 'all') {
            $sql .= " AND LOWER(role) = :role";
            $params[':role'] = strtolower($role);
        }

        if (!empty($startDate)) {
            $sql .= " AND DATE(time_in) >= :startDate";
            $params[':startDate'] = $startDate;
        }

        if (!empty($endDate)) {
            $sql .= " AND DATE(time_in) <= :endDate";
            $params[':endDate'] = $endDate;
        }

        if (!empty($search)) {
            $sql .= " AND (id_number LIKE :search OR username LIKE :search OR action LIKE :search OR details LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY id DESC LIMIT :limit OFFSET :offset";

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
        }
        return $rows;
    }

    public function getAuditLogsCount(string $search = '', string $action = 'all', string $role = 'all', string $startDate = '', string $endDate = ''): int
    {
        $sql = "SELECT COUNT(*) FROM audit_logs WHERE 1=1";
        $params = [];

        if (!empty($action) && $action !== 'all') {
            $sql .= " AND action = :action";
            $params[':action'] = $action;
        }

        if (!empty($role) && $role !== 'all') {
            $sql .= " AND LOWER(role) = :role";
            $params[':role'] = strtolower($role);
        }

        if (!empty($startDate)) {
            $sql .= " AND DATE(time_in) >= :startDate";
            $params[':startDate'] = $startDate;
        }

        if (!empty($endDate)) {
            $sql .= " AND DATE(time_in) <= :endDate";
            $params[':endDate'] = $endDate;
        }

        if (!empty($search)) {
            $sql .= " AND (id_number LIKE :search OR username LIKE :search OR action LIKE :search OR details LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }
}



