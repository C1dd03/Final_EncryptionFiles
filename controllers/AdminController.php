<?php

require_once __DIR__ . '/../models/User.php';

class AdminController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Live session validation for Admin AJAX endpoints. Verifies against the
     * database: logged-in user + current role + account status + session version.
     * Returns the live auth state on success.
     */
    private function requireAdmin(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['user_id'] ?? '';
        $authState = $userId !== '' ? $this->userModel->getAccountAuthState($userId) : null;

        if (!$authState) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.', 'sessionExpired' => true]);
            exit;
        }

        if ($authState['status'] !== 'active') {
            session_unset();
            session_destroy();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Your account has been blocked. Please contact the Super Admin.', 'accountBlocked' => true]);
            exit;
        }

        if ((int)($_SESSION['session_version'] ?? 0) !== (int)$authState['session_version']) {
            session_unset();
            session_destroy();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Your role has changed. Please log in again.', 'sessionExpired' => true]);
            exit;
        }

        // Refresh live role from DB
        $_SESSION['role'] = $authState['role'];
        $_SESSION['username'] = $authState['username'];

        if (strtolower($authState['role']) !== 'admin') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Unauthorized access. Admin role required.']);
            exit;
        }

        return $authState;
    }

    /**
     * Server-side privilege gate. Every protected admin action must pass this.
     */
    private function requirePrivilege(string $privilegeKey, string $idNumber): void
    {
        if (!$this->userModel->hasAdminPrivilege($idNumber, $privilegeKey)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'You do not have the required privilege (' . $privilegeKey . ') to perform this action.']);
            exit;
        }
    }

    /* ========================== SSR DATA FETCHING FOR VIEWS ======================== */

    public function getUsersForView(string $search = '', string $status = 'all', int $page = 1, int $limit = 10): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $role = strtolower($_SESSION['role'] ?? '');
        if ($role !== 'admin') {
            return ['records' => [], 'totalRecords' => 0, 'totalPages' => 1, 'currentPage' => 1, 'limit' => $limit];
        }

        $limit = in_array($limit, [10, 25, 50, 100], true) ? $limit : 10;
        $page = max(1, $page);

        $totalRecords = $this->userModel->getUsersCount($search, $status);
        $totalPages = max(1, (int)ceil($totalRecords / $limit));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $limit;

        $records = $this->userModel->getUsersList($search, $status, $offset, $limit);

        return [
            'records'      => $records,
            'totalRecords' => $totalRecords,
            'totalPages'   => $totalPages,
            'currentPage'  => $page,
            'limit'        => $limit
        ];
    }

    /* ========================== ADMIN: MANAGE USERS (JSON API) ======================== */

    public function getAdminUserDetail()
    {
        $authState = $this->requireAdmin();
        $this->requirePrivilege('view_users', $authState['id_number']);
        header('Content-Type: application/json; charset=utf-8');

        $id_number = trim($_GET['id_number'] ?? $_POST['id_number'] ?? '');
        if (empty($id_number)) {
            echo json_encode(['success' => false, 'message' => 'ID Number is required.']);
            exit;
        }

        $user = $this->userModel->getUserByIdNumber($id_number);
        if ($user) {
            echo json_encode(['success' => true, 'data' => $user]);
        } else {
            echo json_encode(['success' => false, 'message' => 'User account not found.']);
        }
        exit;
    }

    private function validateName($value, $field)
    {
        $errors = [];
        if ($value === '') return $errors;
        if (preg_match('/^\s/', $value)) {
            $errors[] = "$field cannot start with a space.";
        }
        if (!preg_match('/^[A-Za-z]/', $value)) {
            $errors[] = "$field must start with a letter only.";
        }
        if (preg_match('/\s{2,}/', $value)) {
            $errors[] = "$field: Double spaces not allowed.";
        }
        if ($value === strtoupper($value) && strlen($value) > 1) {
            $errors[] = "$field: All capital letters not allowed.";
        }
        if (preg_match('/(.)\1\1/', strtolower($value))) {
            $errors[] = "$field: 3 identical letters in a row not allowed.";
        }
        if (isset($value[0]) && $value[0] !== strtoupper($value[0])) {
            $errors[] = "$field: Must start with a capital letter.";
        }
        $words = preg_split('/\s+/', $value);
        foreach ($words as $word) {
            for ($i = 1; $i < strlen($word); $i++) {
                $char = $word[$i];
                if (ctype_alpha($char) && ctype_upper($char)) {
                    $errors[] = "$field: Cannot contain capital letters after the first letter of each name.";
                    break 2;
                }
            }
        }
        return $errors;
    }

    private function validateAddressField($value, $field)
    {
        $errors = [];
        if ($value === '') {
            $errors[] = "$field: This field is required.";
            return $errors;
        }
        if (preg_match('/^\s/', $value)) {
            $errors[] = "$field cannot start with a space.";
        }
        if ($field !== 'Purok/Street' && !preg_match('/^[A-Za-z]/', $value)) {
            $errors[] = "$field must start with a letter only.";
        }
        if (preg_match('/\s{2,}/', $value)) {
            $errors[] = "$field: Double spaces not allowed.";
        }
        if ($field !== 'Purok/Street' && preg_match('/\d/', $value)) {
            $errors[] = "$field: Cannot include numbers.";
        }
        if ($value === strtoupper($value) && strlen($value) > 1) {
            $errors[] = "$field: Should avoid all caps.";
        }
        if (preg_match('/(.)\1\1/', strtolower($value))) {
            $errors[] = "$field: No 3 same letters in a row.";
        }
        if (isset($value[0]) && $value[0] !== strtoupper($value[0])) {
            $errors[] = "$field: Must start with a capital letter.";
        }
        return $errors;
    }

    public function addAdminUser()
    {
        $authState = $this->requireAdmin();
        $this->requirePrivilege('edit_users', $authState['id_number']);
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $id_number = trim($_POST['id_number'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $middleName = trim($_POST['middle_name'] ?? '');
        $lastName  = trim($_POST['last_name'] ?? '');
        $extension = trim($_POST['extension'] ?? '');
        $birthdate = trim($_POST['birthdate'] ?? '');
        $gender    = strtolower(trim($_POST['gender'] ?? ''));

        $street   = trim($_POST['street'] ?? '');
        $barangay = trim($_POST['barangay'] ?? '');
        $city     = trim($_POST['city'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $country  = trim($_POST['country'] ?? '');
        $zip      = trim($_POST['zip'] ?? '');

        $securityAnswers = [];
        for ($i = 1; $i <= 3; $i++) {
            $qVal = $_POST["security_question_$i"] ?? '';
            $aVal = trim($_POST["security_q$i"] ?? '');
            if ($qVal !== '' && ctype_digit((string)$qVal)) {
                $securityAnswers[] = [
                    'question_id' => (int)$qVal,
                    'answer'      => $aVal
                ];
            }
        }

        $username  = trim($_POST['username'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $password  = $_POST['password'] ?? '';
        $confirm   = $_POST['confirm_password'] ?? '';
        $status    = trim($_POST['status'] ?? 'active');

        if (empty($firstName) && empty($lastName) && !empty($_POST['name'])) {
            $nameParts = preg_split('/\s+/', trim($_POST['name']));
            $firstName = array_shift($nameParts) ?? 'User';
            $lastName  = array_pop($nameParts) ?? 'Account';
            $middleName = !empty($nameParts) ? implode(' ', $nameParts) : '';
        }

        $fieldErrors = [];

        // ✅ Auto-generate the next available YYYY-#### User ID if blank,
        // and validate the format (4-digit year + dash + 4 digits) when provided.
        if ($id_number === '') {
            $id_number = $this->userModel->generateIdNumber();
        } elseif (!preg_match('/^\d{4}-\d{4}$/', $id_number)) {
            $fieldErrors['id_number'] = 'User ID must follow the format YYYY-#### (4 digits). Example: 2026-0001.';
        } elseif ($this->userModel->findById($id_number)) {
            $fieldErrors['id_number'] = 'This User ID is already in use. Please use a different one.';
        }

        $nameValidations = [
            'first_name'  => ['val' => $firstName, 'label' => 'First Name', 'req' => true],
            'middle_name' => ['val' => $middleName, 'label' => 'Middle Name', 'req' => false],
            'last_name'   => ['val' => $lastName, 'label' => 'Last Name', 'req' => true]
        ];
        foreach ($nameValidations as $key => $info) {
            if ($info['req'] && $info['val'] === '') {
                $fieldErrors[$key] = "{$info['label']} is required.";
            } elseif ($info['val'] !== '') {
                $errs = $this->validateName($info['val'], $info['label']);
                if (!empty($errs)) {
                    $fieldErrors[$key] = $errs[0];
                }
            }
        }

        $cleanExtension = null;
        if ($extension !== '') {
            $normalizedExtension = strtoupper($extension);
            if (in_array($normalizedExtension, ['JR', 'JR.'], true)) {
                $cleanExtension = 'Jr.';
            } elseif (in_array($normalizedExtension, ['SR', 'SR.'], true)) {
                $cleanExtension = 'Sr.';
            } else {
                $cleanExtension = strtoupper(str_replace('.', '', $extension));
            }
            $validExtensions = ['Jr.', 'Sr.', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];
            if (!in_array($cleanExtension, $validExtensions, true)) {
                $fieldErrors['extension'] = "Extension must be Jr., Sr., or Roman numerals I to X.";
            }
        }

        if (empty($birthdate)) {
            $fieldErrors['birthdate'] = "Birthdate is required.";
        } else {
            $dob = new DateTime($birthdate);
            $today = new DateTime();
            $age = $today->diff($dob)->y;
            if ($age < 18) {
                $fieldErrors['birthdate'] = "User must be 18 or older.";
            }
        }

        if (empty($gender) || !in_array($gender, ['male', 'female'], true)) {
            $fieldErrors['gender'] = "Please select a valid gender.";
        }

        $addrValidations = [
            'street'   => ['val' => $street, 'label' => 'Purok/Street'],
            'barangay' => ['val' => $barangay, 'label' => 'Barangay'],
            'city'     => ['val' => $city, 'label' => 'Municipal/City'],
            'province' => ['val' => $province, 'label' => 'Province'],
            'country'  => ['val' => $country, 'label' => 'Country']
        ];
        foreach ($addrValidations as $key => $info) {
            $errs = $this->validateAddressField($info['val'], $info['label']);
            if (!empty($errs)) {
                $fieldErrors[$key] = $errs[0];
            }
        }

        if (empty($zip)) {
            $fieldErrors['zip'] = "Zip Code is required.";
        } elseif (!preg_match('/^\d{4,6}$/', $zip)) {
            $fieldErrors['zip'] = "Zip Code must be between 4 and 6 digits.";
        }

        for ($i = 1; $i <= 3; $i++) {
            $qVal = $_POST["security_question_$i"] ?? '';
            $aVal = trim($_POST["security_q$i"] ?? '');
            if ($qVal === '' || !ctype_digit((string)$qVal)) {
                $fieldErrors["security_question_$i"] = "Please select Question $i.";
            }
            if ($aVal === '') {
                $fieldErrors["security_q$i"] = "Answer $i is required.";
            } elseif (preg_match('/^\s+$/', $aVal)) {
                $fieldErrors["security_q$i"] = "Answer $i cannot contain only spaces.";
            } elseif (preg_match('/\s/', $aVal)) {
                $fieldErrors["security_q$i"] = "Answer $i cannot contain spaces.";
            }
        }

        if ($username === '') {
            $fieldErrors['username'] = "Username is required.";
        } else {
            if (preg_match('/\s/', $username)) {
                $fieldErrors['username'] = "Username cannot contain spaces.";
            } elseif (preg_match('/([a-zA-Z])\1\1/i', $username)) {
                $fieldErrors['username'] = "Username cannot contain 3 identical letters in a row.";
            } elseif ($this->userModel->usernameExists($username)) {
                $fieldErrors['username'] = "Username is already taken.";
            }
        }

        if ($email === '') {
            $fieldErrors['email'] = "Email is required.";
        } else {
            if (preg_match('/\s/', $email)) {
                $fieldErrors['email'] = "Email cannot contain spaces.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $fieldErrors['email'] = "Invalid email format.";
            } elseif ($this->userModel->emailExists($email)) {
                $fieldErrors['email'] = "Email is already registered.";
            }
        }

        if ($password === '') {
            $fieldErrors['password'] = "Password is required.";
        } else {
            if ($password !== $confirm) {
                $fieldErrors['confirm_password'] = "Passwords do not match.";
            }
            $hasLower = preg_match('/[a-z]/', $password);
            $hasUpper = preg_match('/[A-Z]/', $password);
            $hasNumber = preg_match('/[0-9]/', $password);
            $hasSpecial = preg_match('/[^a-zA-Z0-9]/', $password);
            $hasLength = strlen($password) >= 8;
            if (!$hasLower || !$hasUpper || !$hasNumber || !$hasSpecial || !$hasLength) {
                $missing = [];
                if (!$hasLower) $missing[] = "lowercase letter";
                if (!$hasUpper) $missing[] = "uppercase letter";
                if (!$hasNumber) $missing[] = "number";
                if (!$hasSpecial) $missing[] = "special character";
                if (!$hasLength) $missing[] = "8+ characters";
                $fieldErrors['password'] = "Password too weak. Missing: " . implode(", ", $missing);
            }
            if (preg_match('/([a-zA-Z])\1\1/i', $password)) {
                $fieldErrors['password'] = "Password cannot contain 3 identical letters in a row.";
            }
        }

        if (!empty($fieldErrors)) {
            echo json_encode([
                'success'     => false,
                'message'     => 'Please fix the errors highlighted below.',
                'fieldErrors' => $fieldErrors
            ]);
            exit;
        }

        try {
            $newId = $this->userModel->createStandardUser([
                'id_number'   => $id_number,
                'first_name'  => $firstName,
                'middle_name' => $middleName ?: null,
                'last_name'   => $lastName,
                'extension'   => $cleanExtension,
                'birthdate'   => $birthdate,
                'gender'      => $gender,
                'street'      => $street,
                'barangay'    => $barangay,
                'city'        => $city,
                'province'    => $province,
                'country'     => $country,
                'zip'         => $zip,
                'security_answers' => $securityAnswers,
                'username'    => $username,
                'email'       => $email,
                'password'    => $password,
                'status'      => $status
            ]);

            echo json_encode(['success' => true, 'message' => 'User account created successfully.', 'id_number' => $newId]);
            $this->userModel->logAuditAction($_SESSION['user_id'] ?? null, $_SESSION['username'] ?? 'admin', 'admin', 'Create User', "Created User: {$username}");
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to create user: ' . $e->getMessage()]);
        }
        exit;
    }

    public function updateAdminUser()
    {
        $authState = $this->requireAdmin();
        $this->requirePrivilege('edit_users', $authState['id_number']);
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $id_number = trim($_POST['id_number'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $middleName = trim($_POST['middle_name'] ?? '');
        $lastName  = trim($_POST['last_name'] ?? '');
        $extension = trim($_POST['extension'] ?? '');
        $birthdate = trim($_POST['birthdate'] ?? '');
        $gender    = strtolower(trim($_POST['gender'] ?? ''));

        $street   = trim($_POST['street'] ?? '');
        $barangay = trim($_POST['barangay'] ?? '');
        $city     = trim($_POST['city'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $country  = trim($_POST['country'] ?? '');
        $zip      = trim($_POST['zip'] ?? '');

        $securityAnswers = [];
        for ($i = 1; $i <= 3; $i++) {
            $qVal = $_POST["security_question_$i"] ?? '';
            $aVal = trim($_POST["security_q$i"] ?? '');
            if ($qVal !== '' && ctype_digit((string)$qVal) && $aVal !== '') {
                $securityAnswers[] = [
                    'question_id' => (int)$qVal,
                    'answer'      => $aVal
                ];
            }
        }

        $username  = trim($_POST['username'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $password  = $_POST['password'] ?? '';
        $status    = trim($_POST['status'] ?? 'active');

        if (empty($firstName) && empty($lastName) && !empty($_POST['name'])) {
            $nameParts = preg_split('/\s+/', trim($_POST['name']));
            $firstName = array_shift($nameParts) ?? 'User';
            $lastName  = array_pop($nameParts) ?? 'Account';
            $middleName = !empty($nameParts) ? implode(' ', $nameParts) : '';
        }

        $fieldErrors = [];

        if (empty($id_number)) {
            echo json_encode(['success' => false, 'message' => 'User ID Number is required.']);
            exit;
        }

        $existing = $this->userModel->getUserByIdNumber($id_number);
        if (!$existing) {
            echo json_encode(['success' => false, 'message' => 'User account not found.']);
            exit;
        }

        $nameValidations = [
            'first_name'  => ['val' => $firstName, 'label' => 'First Name', 'req' => true],
            'middle_name' => ['val' => $middleName, 'label' => 'Middle Name', 'req' => false],
            'last_name'   => ['val' => $lastName, 'label' => 'Last Name', 'req' => true]
        ];
        foreach ($nameValidations as $key => $info) {
            if ($info['req'] && $info['val'] === '') {
                $fieldErrors[$key] = "{$info['label']} is required.";
            } elseif ($info['val'] !== '') {
                $errs = $this->validateName($info['val'], $info['label']);
                if (!empty($errs)) {
                    $fieldErrors[$key] = $errs[0];
                }
            }
        }

        $cleanExtension = null;
        if ($extension !== '') {
            $normalizedExtension = strtoupper($extension);
            if (in_array($normalizedExtension, ['JR', 'JR.'], true)) {
                $cleanExtension = 'Jr.';
            } elseif (in_array($normalizedExtension, ['SR', 'SR.'], true)) {
                $cleanExtension = 'Sr.';
            } else {
                $cleanExtension = strtoupper(str_replace('.', '', $extension));
            }
            $validExtensions = ['Jr.', 'Sr.', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];
            if (!in_array($cleanExtension, $validExtensions, true)) {
                $fieldErrors['extension'] = "Extension must be Jr., Sr., or Roman numerals I to X.";
            }
        }

        if (!empty($birthdate)) {
            $dob = new DateTime($birthdate);
            $today = new DateTime();
            $age = $today->diff($dob)->y;
            if ($age < 18) {
                $fieldErrors['birthdate'] = "User must be 18 or older.";
            }
        }

        if (empty($gender) || !in_array($gender, ['male', 'female'], true)) {
            $fieldErrors['gender'] = "Please select a valid gender.";
        }

        $addrValidations = [
            'street'   => ['val' => $street, 'label' => 'Purok/Street'],
            'barangay' => ['val' => $barangay, 'label' => 'Barangay'],
            'city'     => ['val' => $city, 'label' => 'Municipal/City'],
            'province' => ['val' => $province, 'label' => 'Province'],
            'country'  => ['val' => $country, 'label' => 'Country']
        ];
        foreach ($addrValidations as $key => $info) {
            $errs = $this->validateAddressField($info['val'], $info['label']);
            if (!empty($errs)) {
                $fieldErrors[$key] = $errs[0];
            }
        }

        if (empty($zip)) {
            $fieldErrors['zip'] = "Zip Code is required.";
        } elseif (!preg_match('/^\d{4,6}$/', $zip)) {
            $fieldErrors['zip'] = "Zip Code must be between 4 and 6 digits.";
        }

        if ($username === '') {
            $fieldErrors['username'] = "Username is required.";
        } else {
            if (preg_match('/\s/', $username)) {
                $fieldErrors['username'] = "Username cannot contain spaces.";
            } elseif (preg_match('/([a-zA-Z])\1\1/i', $username)) {
                $fieldErrors['username'] = "Username cannot contain 3 identical letters in a row.";
            } elseif ($username !== $existing['username'] && $this->userModel->usernameExists($username)) {
                $fieldErrors['username'] = "Username is already in use by another user.";
            }
        }

        if ($email === '') {
            $fieldErrors['email'] = "Email is required.";
        } else {
            if (preg_match('/\s/', $email)) {
                $fieldErrors['email'] = "Email cannot contain spaces.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $fieldErrors['email'] = "Invalid email format.";
            } elseif ($email !== $existing['email'] && $this->userModel->emailExists($email)) {
                $fieldErrors['email'] = "Email is already registered to another user.";
            }
        }

        if ($password !== '') {
            $hasLower = preg_match('/[a-z]/', $password);
            $hasUpper = preg_match('/[A-Z]/', $password);
            $hasNumber = preg_match('/[0-9]/', $password);
            $hasSpecial = preg_match('/[^a-zA-Z0-9]/', $password);
            $hasLength = strlen($password) >= 8;
            if (!$hasLower || !$hasUpper || !$hasNumber || !$hasSpecial || !$hasLength) {
                $missing = [];
                if (!$hasLower) $missing[] = "lowercase letter";
                if (!$hasUpper) $missing[] = "uppercase letter";
                if (!$hasNumber) $missing[] = "number";
                if (!$hasSpecial) $missing[] = "special character";
                if (!$hasLength) $missing[] = "8+ characters";
                $fieldErrors['password'] = "Password too weak. Missing: " . implode(", ", $missing);
            }
            if (preg_match('/([a-zA-Z])\1\1/i', $password)) {
                $fieldErrors['password'] = "Password cannot contain 3 identical letters in a row.";
            }
        }

        if (!empty($fieldErrors)) {
            echo json_encode([
                'success'     => false,
                'message'     => 'Please fix the errors highlighted below.',
                'fieldErrors' => $fieldErrors
            ]);
            exit;
        }

        try {
            $updated = $this->userModel->updateStandardUser($id_number, [
                'first_name'  => $firstName,
                'middle_name' => $middleName ?: null,
                'last_name'   => $lastName,
                'extension'   => $cleanExtension,
                'birthdate'   => $birthdate,
                'gender'      => $gender,
                'street'      => $street,
                'barangay'    => $barangay,
                'city'        => $city,
                'province'    => $province,
                'country'     => $country,
                'zip'         => $zip,
                'security_answers' => $securityAnswers,
                'username'    => $username,
                'email'       => $email,
                'password'    => $password,
                'status'      => $status
            ]);

            if ($updated) {
                echo json_encode(['success' => true, 'message' => 'User account updated successfully.']);
                $this->userModel->logAuditAction($_SESSION['user_id'] ?? null, $_SESSION['username'] ?? 'admin', 'admin', 'Edit User', "Edited User: {$username}");
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update user account.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    public function toggleBlockAdminUser()
    {
        $authState = $this->requireAdmin();
        $this->requirePrivilege('block_users', $authState['id_number']);
        header('Content-Type: application/json; charset=utf-8');

        $id_number  = trim($_POST['id_number'] ?? '');
        $new_status = trim($_POST['status'] ?? '');
        $reason     = trim($_POST['reason'] ?? '');
        $ip         = $_SERVER['REMOTE_ADDR'] ?? '';

        if (empty($id_number) || !in_array($new_status, ['active', 'block'], true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters provided.']);
            exit;
        }

        $updated = $this->userModel->toggleStandardUserStatus($id_number, $new_status, $_SESSION['user_id'] ?? 'admin', $reason, $ip);
        if ($updated) {
            $actionText = ($new_status === 'block') ? 'blocked' : 'unblocked';
            echo json_encode(['success' => true, 'message' => "User account has been {$actionText}."]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update user status.']);
        }
        exit;
    }

    public function deleteAdminUser()
    {
        $authState = $this->requireAdmin();
        $this->requirePrivilege('delete_users', $authState['id_number']);
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $id_number = trim($_POST['id_number'] ?? '');
        $reason    = trim($_POST['reason'] ?? '');

        if (empty($id_number)) {
            echo json_encode(['success' => false, 'message' => 'ID Number is required.']);
            exit;
        }

        if (empty($reason)) {
            echo json_encode(['success' => false, 'message' => 'Please provide a valid reason for requesting account deletion.']);
            exit;
        }

        $result = $this->userModel->submitDeleteRequest(
            $id_number,
            $reason,
            $authState['id_number'],
            $authState['username']
        );

        echo json_encode($result);
        exit;
    }
}
