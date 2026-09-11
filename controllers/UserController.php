<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Otp.php';

class UserController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    // Show Login Page
    public function showLogin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $forcePasswordChange = isset($_SESSION['user_id'], $_GET['force_password_change'])
            && $_GET['force_password_change'] === '1';
        if (isset($_SESSION['user_id']) && !$forcePasswordChange) {
            header('Location: index.php?action=dashboard');
            exit();
        }
        $page = 'login';               // ✅ define first
        $formView = "login.php";
        require __DIR__ . '/../php/auth/auth.php';
    }

    // Show Register Page
    public function showRegister()
    {
        $page = 'register';            // ✅ define first
        $nextId = $this->userModel->generateIdNumber();
        $formView = "register.php";
        require __DIR__ . '/../php/auth/auth.php';
    }




    /* ========================== ADD LOGOUT ======================== */
    public function logout()
    {
        $formView = "logout.php";
        require __DIR__ . '/../php/auth/auth.php';
    }





    // Show Forgot Password Page
    public function showForgotPassword()
    {
        $page = 'forgot-password';
        $formView = "forgot_password.php";
        require __DIR__ . '/../php/auth/auth.php';
    }

    // ✅ Handle Registration (POST)
    public function registerUser()
    {
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = [];

            // --- NAME VALIDATION ---
            $fields = [
                'First Name'  => $_POST['first_name'] ?? '',
                'Middle Name' => $_POST['middle_name'] ?? '',
                'Last Name'   => $_POST['last_name'] ?? ''
            ];

            foreach ($fields as $label => $value) {
                $errors = array_merge($errors, $this->validateName(trim($value), $label));
            }

            // --- ADDRESS VALIDATION (excluding street) ---
            $addressFields = [
                'Barangay' => $_POST['barangay'] ?? '',
                'City'     => $_POST['city'] ?? '',
                'Province' => $_POST['province'] ?? '',
                'Country'  => $_POST['country'] ?? ''
            ];

            foreach ($addressFields as $label => $value) {
                $errors = array_merge($errors, $this->validateAddressField(trim($value), $label));
            }

            // --- EXTENSION VALIDATION (separate from name validation) ---
            $extension = trim($_POST['extension'] ?? '');
            if ($extension !== '') {
                $normalizedExtension = strtoupper(trim($extension));
                if (in_array($normalizedExtension, ['JR', 'JR.'], true)) {
                    $cleanExtension = 'JR';
                } elseif (in_array($normalizedExtension, ['SR', 'SR.'], true)) {
                    $cleanExtension = 'SR';
                } else {
                    $cleanExtension = strtoupper(str_replace('.', '', $extension));
                }

                $validExtensions = ['JR', 'SR', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];
                if (!in_array($cleanExtension, $validExtensions, true)) {
                    $errors[] = "Extension: Must be Jr., Sr., or a Roman numeral between I and X.";
                }
            }

            // --- AGE VALIDATION ---
            if (!empty($_POST['birthdate'])) {
                $birthDate = new DateTime($_POST['birthdate']);
                $today = new DateTime();
                $age = $today->diff($birthDate)->y;

                if ($age < 18) {
                    $errors[] = "You must be 18 or older to register.";
                }
            }

            // --- SECURITY QUESTION SELECTION VALIDATION ---
            $questionSelections = [
                'Question 1' => $_POST['security_question_1'] ?? '',
                'Question 2' => $_POST['security_question_2'] ?? '',
                'Question 3' => $_POST['security_question_3'] ?? ''
            ];

            foreach ($questionSelections as $label => $value) {
                if ($value === '' || !ctype_digit((string)$value)) {
                    $errors[] = "$label: Please select a security question.";
                }
            }

            // --- USERNAME & PASSWORD VALIDATION ---
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (!preg_match('/^(?=.{3,50}$)[a-z]+(?:\.[a-z]+)+\d{2}$/i', $username)) {
                $errors[] = "Username must follow this example format: juan.delacruz01.";
            }

            // Check for spaces in email
            if (preg_match('/\s/', $_POST['email'] ?? '')) {
                $errors[] = "Email cannot contain spaces.";
            }

            // Check for double spaces in email
            if (preg_match('/\s{2,}/', $_POST['email'] ?? '')) {
                $errors[] = "Email cannot contain double spaces.";
            }

            // Check password strength
            $hasLower = preg_match('/[a-z]/', $password);
            $hasUpper = preg_match('/[A-Z]/', $password);
            $hasNumber = preg_match('/[0-9]/', $password);
            $hasSpecial = preg_match('/[^a-zA-Z0-9]/', $password);
            $hasLength = strlen($password) >= 8;

            $missing = [];
            if (!$hasLower) $missing[] = "lowercase letter";
            if (!$hasUpper) $missing[] = "uppercase letter";
            if (!$hasNumber) $missing[] = "number";
            if (!$hasSpecial) $missing[] = "special character";
            if (!$hasLength) $missing[] = "8+ characters";

            // Calculate strength (0-5)
            $strength = 0;
            if ($hasLower) $strength++;
            if ($hasUpper) $strength++;
            if ($hasNumber) $strength++;
            if ($hasSpecial) $strength++;
            if ($hasLength) $strength++;

            // Password must meet minimum requirements (at least 4 criteria)
            if ($strength < 4) {
                if (!empty($missing)) {
                    $errors[] = "Password is too weak. Missing: " . implode(", ", $missing);
                } else {
                    $errors[] = "Password is too weak. Must be 8+ characters with uppercase, lowercase, number, and special character.";
                }
            }

            if (preg_match('/([a-zA-Z])\1\1/i', $password)) {
                $errors[] = "Password cannot contain 3 identical letters in a row.";
            }

            // --- IF ERRORS EXIST ---
            if (!empty($errors)) {
                $error = implode("<br>", $errors);
                $formView = "register.php";
                require __DIR__ . '/../php/auth/auth.php';
                return;
            }

            // --- HANDLE EXTENSION ---
            $extension = trim($_POST['extension'] ?? '');
            if ($extension === '') {
                $extension = null;
            } else {
                $normalizedExtension = strtoupper(trim($extension));
                if (in_array($normalizedExtension, ['JR', 'JR.'], true)) {
                    $extension = 'Jr.';
                } elseif (in_array($normalizedExtension, ['SR', 'SR.'], true)) {
                    $extension = 'Sr.';
                } else {
                    $extension = strtoupper(str_replace('.', '', $extension));
                }
            }

            // --- SANITIZED DATA ---
            $securityAnswers = [
                [
                    'question_id' => (int)($questionSelections['Question 1'] ?? 0),
                    'answer'      => trim($_POST['security_q1'] ?? '')
                ],
                [
                    'question_id' => (int)($questionSelections['Question 2'] ?? 0),
                    'answer'      => trim($_POST['security_q2'] ?? '')
                ],
                [
                    'question_id' => (int)($questionSelections['Question 3'] ?? 0),
                    'answer'      => trim($_POST['security_q3'] ?? '')
                ],
            ];

            foreach ($securityAnswers as $index => $entry) {
                if ($entry['answer'] === '') {
                    $errors[] = 'Answer ' . ($index + 1) . ': Please provide an answer.';
                }
                // Check if answer contains only spaces
                elseif (preg_match('/^\s+$/', $entry['answer'])) {
                    $errors[] = 'Answer ' . ($index + 1) . ': Cannot contain only spaces.';
                }
                // Check if answer contains any spaces
                elseif (preg_match('/\s/', $entry['answer'])) {
                    $errors[] = 'Answer ' . ($index + 1) . ': Cannot contain spaces.';
                }
            }

            if (!empty($errors)) {
                $error = implode("<br>", $errors);
                $formView = "register.php";
                require __DIR__ . '/../php/auth/auth.php';
                return;
            }

            $fieldErrors = [];
            $submittedIdNumber = trim($_POST['id_number'] ?? '');
            if (!empty($submittedIdNumber) && ($this->userModel->findById($submittedIdNumber) || $this->userModel->pendingUserIdExists($submittedIdNumber))) {
                $fieldErrors['id_number'] = "This User ID is already in use. Please use a different one.";
            }
            if ($username === '') {
                $fieldErrors['username'] = 'Username is required.';
            } elseif ($this->userModel->usernameExists($username) || $this->userModel->pendingUsernameExists($username)) {
                $fieldErrors['username'] = 'Username is already registered or waiting for approval.';
            }
            if ($email === '') {
                $fieldErrors['email'] = 'Email is required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $fieldErrors['email'] = 'Invalid email format.';
            } elseif ($this->userModel->emailExists($email) || $this->userModel->pendingEmailExists($email)) {
                $fieldErrors['email'] = 'Email is already registered or waiting for approval.';
            }
            if (!hash_equals($password, (string)($_POST['confirm_password'] ?? ''))) {
                $fieldErrors['confirm_password'] = 'Passwords do not match.';
            }

            $data = [
                'first_name'  => trim($_POST['first_name'] ?? ''),
                'middle_name' => trim($_POST['middle_name'] ?? null),
                'last_name'   => trim($_POST['last_name'] ?? ''),
                'extension'   => $extension,
                'birthdate'   => $_POST['birthdate'] ?? '',
                'gender'      => $_POST['gender'] ?? '',
                'street'      => trim($_POST['street'] ?? ''),
                'barangay'    => trim($_POST['barangay'] ?? ''),
                'city'        => trim($_POST['city'] ?? ''),
                'province'    => trim($_POST['province'] ?? ''),
                'country'     => trim($_POST['country'] ?? ''),
                'zip'         => $_POST['zip'] ?? '',
                'security_answers' => $securityAnswers,
                'username'    => $username,
                'email'       => $email,
                'password'    => $password, // hashed inside createPendingRegistration
                'id_number'   => $submittedIdNumber
            ];

            if (!empty($fieldErrors)) {
                $error = implode("<br>", $fieldErrors);
                $formView = "register.php";
                require __DIR__ . '/../php/auth/auth.php';
                return;
            }

            // --- INSERT INTO PENDING REGISTRATIONS ---
            $result = $this->userModel->createPendingRegistration($data);
            if ($result) {
                $registeredId = $result;
                $showSuccessModal = true;

                $formView = "register.php";
                require __DIR__ . '/../php/auth/auth.php';
                exit;
            } else {
                $error = "Registration failed. Please try again.";
            }
        }

        // --- DEFAULT SHOW REGISTER FORM ---
        $formView = "register.php";
        require __DIR__ . '/../php/auth/auth.php';
    }

    // --- PRIVATE VALIDATION METHODS ---
    private function validateName($value, $field)
    {
        $errors = [];

        if ($value === '') return $errors; // skip optional empty

        // Check if starts with a space
        if (preg_match('/^\s/', $value)) {
            $errors[] = "$field cannot start with a space.";
        }

        // Must start with a letter (not space, number, or special character)
        if (!preg_match('/^[A-Za-z]/', $value)) {
            $errors[] = "$field must start with a letter only.";
        }

        // No double spaces
        if (preg_match('/\s{2,}/', $value)) {
            $errors[] = "$field: Double spaces not allowed.";
        }

        // No all caps
        if ($value === strtoupper($value) && strlen($value) > 1) {
            $errors[] = "$field: All capital letters not allowed.";
        }

        // No 3 identical letters in a row
        if (preg_match('/(.)\1\1/', strtolower($value))) {
            $errors[] = "$field: 3 identical letters in a row not allowed.";
        }

        // Must start with a capital letter
        if (isset($value[0]) && $value[0] !== strtoupper($value[0])) {
            $errors[] = "$field: Must start with a capital letter.";
        }

        // Check for capital letters after the first letter of each name
        // For street field, allow capital letters after digits (e.g., in "Purok-1C")
        if ($field === 'Purok/Street') {
            // Split by spaces and dashes to get words  
            $words = preg_split('/[\s\-]+/', $value);
            foreach ($words as $word) {
                // Check each character in the word after the first
                for ($i = 1; $i < strlen($word); $i++) {
                    $char = $word[$i];
                    // If it's a letter and uppercase
                    if (ctype_alpha($char) && ctype_upper($char)) {
                        // Check if the previous character is a digit
                        $prevChar = $word[$i - 1];
                        if (!ctype_digit($prevChar)) {
                            $errors[] = "$field: Cannot contain capital letters after the first letter of each name.";
                            break 2; // Break out of both loops
                        }
                    }
                }
            }
        } else {
            // For other fields, check normal capitalization rules
            $words = preg_split('/\s+/', $value);
            foreach ($words as $word) {
                for ($i = 1; $i < strlen($word); $i++) {
                    $char = $word[$i];
                    if (ctype_alpha($char) && ctype_upper($char)) {
                        $errors[] = "$field: Cannot contain capital letters after the first letter of each name.";
                        break 2; // Break out of both loops
                    }
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

        // Check if starts with a space
        if (preg_match('/^\s/', $value)) {
            $errors[] = "$field cannot start with a space.";
        }

        // Must start with a letter (not space, number, or special character)
        if ($field !== 'Purok/Street' && !preg_match('/^[A-Za-z]/', $value)) {
            $errors[] = "$field must start with a letter only.";
        }

        // No double spaces
        if (preg_match('/\s{2,}/', $value)) {
            $errors[] = "$field: Double spaces not allowed.";
        }

        // Special validation for street field
        if ($field === 'Purok/Street') {
            if (preg_match('/^\s*[A-Za-z]\s+/', $value)) {
                $errors[] = "$field: Invalid street format.";
            }
            if (preg_match('/[A-Za-z]\d/', $value) && !preg_match('/[A-Za-z][ .-]\d/', $value)) {
                $errors[] = "$field: Numbers must be separated from letters by a space, period, or dash.";
            }
            if (preg_match('/\d[a-zA-Z]{2,}/', $value)) {
                $errors[] = "$field: Only a single letter may follow a number directly.";
            }
        } else {
            // No numbers allowed in other address fields
            if (preg_match('/\d/', $value)) {
                $errors[] = "$field: Cannot include numbers.";
            }
        }

        // No all caps
        if ($value === strtoupper($value) && strlen($value) > 1) {
            $errors[] = "$field: Should avoid all caps.";
        }

        // No 3 identical letters in a row
        if (preg_match('/(.)\1\1/', strtolower($value))) {
            $errors[] = "$field: No 3 same letters in a row.";
        }

        // Must start with a capital letter
        if (isset($value[0]) && $value[0] !== strtoupper($value[0])) {
            $errors[] = "$field: Must start with a capital letter.";
        }

        return $errors;
    }







    /* ========================== ADD LOGIN CONTROLLER ======================== */
    public function loginUser()
    {
        session_start();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            // Both empty
            if (empty($username) && empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Username and password are required.', 'errorType' => 'bothEmpty']);
                return;
            }

            // Username empty
            if (empty($username)) {
                echo json_encode(['success' => false, 'message' => 'Username is required.', 'errorType' => 'usernameEmpty']);
                return;
            }

            // Password empty
            if (empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Password is required.', 'errorType' => 'passwordEmpty']);
                return;
            }

            // Check if username exists
            $user = $this->userModel->findByUsername($username);



            /*++++++++++++++++++++ ADD INVALID USERNAME AND PASSWORD =============================================*/

            // Both wrong: username does not exist AND password entered
            // if (!$user && !empty($password)) {
            //     echo json_encode(['success' => false, 'message' => 'Invalid Username and password .', 'errorType' => 'bothWrong']);
            //     return;
            // }

            // Username not found in users — check pending registrations
            if (!$user) {
                $pending = $this->userModel->findPendingRegistrationByUsername($username)
                    ?? $this->userModel->findPendingRegistrationByEmail($username);

                if ($pending) {
                    if ($pending['status'] === 'pending') {
                        echo json_encode(['success' => false, 'message' => 'Your account is still waiting for administrator approval.', 'errorType' => 'accountPendingApproval']);
                        return;
                    }
                    if ($pending['status'] === 'rejected') {
                        echo json_encode(['success' => false, 'message' => 'Your registration has been rejected. Please contact the administrator.', 'errorType' => 'accountRejected']);
                        return;
                    }
                }

                echo json_encode(['success' => false, 'message' => 'Username does not exist.', 'errorType' => 'usernameWrong']);
                return;
            }

            // Password wrong
            if (!password_verify($password, $user['password_hash'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid password.', 'errorType' => 'passwordWrong']);
                return;
            }

            // Pending (unverified email) accounts cannot log in yet
            $userStatus = $user['status'] ?? 'active';
            if ($userStatus === 'pending') {
                echo json_encode(['success' => false, 'message' => 'Your account has not been verified yet. Please check your email for the verification code.', 'errorType' => 'accountPending']);
                return;
            }

            // Pending approval accounts cannot log in yet
            if ($userStatus === 'pending_approval') {
                echo json_encode(['success' => false, 'message' => 'Your registration has been submitted and is currently pending administrator approval before you can log in.', 'errorType' => 'accountPendingApproval']);
                return;
            }

            $pendingSuperAdminClaim = strtolower($user['role'] ?? '') === 'superadmin' && !empty($user['handoff_pending']);
            if ($userStatus === 'inactive' && !$pendingSuperAdminClaim) {
                echo json_encode(['success' => false, 'message' => 'This account is inactive and can no longer access the system.', 'errorType' => 'accountInactive']);
                return;
            }

            if ($userStatus === 'blocked' || ($userStatus === 'inactive' && $pendingSuperAdminClaim)) {
                // Check if this is a Super Admin in pending handoff claim
                if (strtolower($user['role'] ?? '') === 'superadmin' && !empty($user['handoff_pending'])) {
                    $activeSuperAdmin = $this->userModel->getActiveSuperAdmin();
                    if ($activeSuperAdmin && $activeSuperAdmin['id_number'] !== $user['id_number']) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'The previous Super Admin has not logged out yet. Please wait for them to log out before claiming this account.',
                            'errorType' => 'accountInactive'
                        ]);
                        return;
                    }

                    // Previous Super Admin has logged out. Credentials verified, establish claiming session:
                    $_SESSION['user_id'] = $user['id_number'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = 'superadmin';
                    $_SESSION['email'] = $user['email'] ?? '';
                    $_SESSION['session_version'] = (int)($user['session_version'] ?? 0);
                    $_SESSION['claiming_superadmin'] = true;

                    date_default_timezone_set('Asia/Manila');
                    $loginTime = date('Y-m-d H:i:s');
                    $ip = $_SERVER['REMOTE_ADDR'] ?? '::1';
                    if ($ip === '127.0.0.1') $ip = '::1';
                    $host = gethostname() ?: 'DESKTOP-SYSTEM';
                    $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Browser';
                    $device = 'Browser';
                    if (strpos($agent, 'Chrome') !== false) $device = 'Google Chrome';
                    elseif (strpos($agent, 'Firefox') !== false) $device = 'Mozilla Firefox';

                    $this->userModel->logAuditAction(
                        $user['id_number'],
                        $user['username'],
                        'superadmin',
                        'Login (Handoff)',
                        "Super Admin initial claim login. IP: {$ip} | Host: {$host}",
                        $loginTime,
                        null
                    );

                    echo json_encode([
                        'success' => true,
                        'redirect' => '../super_admin/dashboard.php',
                        'mustChangePassword' => true
                    ]);
                    return;
                }

                echo json_encode(['success' => false, 'message' => 'Your account has been blocked. Please contact the Super Admin.', 'errorType' => 'accountBlocked']);
                return;
            }

            // Pending-deletion accounts remain usable until the Super Admin decides.
            if (!in_array($userStatus, ['active', 'pending_deletion'], true)) {
                echo json_encode(['success' => false, 'message' => 'This account is not currently active.', 'errorType' => 'accountUnavailable']);
                return;
            }

            // Credentials are valid and account is active -> establish session directly
            $_SESSION['user_id'] = $user['id_number'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = strtolower($user['role'] ?? 'user');
            $_SESSION['email'] = $user['email'] ?? '';
            $_SESSION['session_version'] = (int)($user['session_version'] ?? 0);

            // Record Login Audit Log — always use Philippine local time
            date_default_timezone_set('Asia/Manila');
            $loginTime = date('Y-m-d H:i:s');

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

            $details = "Login successful. IP: {$ip} | Host: {$host} | Device: {$device}";
            $auditId = $this->userModel->logAuditAction(
                $user['id_number'],
                $user['username'],
                strtolower($user['role'] ?? 'user'),
                'Login',
                $details,
                $loginTime,
                null
            );
            $_SESSION['audit_log_id'] = $auditId;

            $redirectUrl = 'index.php?action=dashboard';
            if ($_SESSION['role'] === 'superadmin') {
                $redirectUrl = '../super_admin/dashboard.php';
            } elseif ($_SESSION['role'] === 'admin') {
                $redirectUrl = '../admin/dashboard.php';
            }

            echo json_encode([
                'success' => true,
                'redirect' => $redirectUrl,
                'mustChangePassword' => !empty($user['must_change_password'])
            ]);
            return;
        }

        echo json_encode(['success' => false, 'message' => 'Invalid request method.', 'errorType' => 'invalidMethod']);
    }

    //========================================== Verify Login OTP =====================================
    public function verifyLoginOtp()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.', 'errorType' => 'invalidMethod']);
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $pending = $_SESSION['otp_pending'] ?? null;
        if (!$pending || ($pending['purpose'] ?? '') !== 'login') {
            echo json_encode(['success' => false, 'message' => 'Your login session has expired. Please log in again.', 'errorType' => 'otpSessionExpired']);
            exit;
        }

        $otp = new Otp();
        $result = $otp->verify($pending['email'], 'login', trim($_POST['otp'] ?? ''));
        if (!$result['success']) {
            echo json_encode(['success' => false, 'message' => $result['message'], 'errorType' => 'otpInvalid']);
            exit;
        }

        // Re-fetch the account: pending deletion remains usable until review.
        $user = $this->userModel->findByUsername($pending['username']);
        $otpStatus = $user['status'] ?? '';
        if (!$user || !in_array($otpStatus, ['active', 'pending_deletion'], true)) {
            unset($_SESSION['otp_pending']);
            $inactive = $otpStatus === 'inactive';
            echo json_encode(['success' => false, 'message' => $inactive ? 'This account is inactive.' : 'Your account is not currently active.', 'errorType' => $inactive ? 'accountInactive' : 'accountUnavailable']);
            exit;
        }

        $_SESSION['user_id'] = $user['id_number'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = strtolower($user['role'] ?? 'user');
        $_SESSION['email'] = $user['email'] ?? '';
        $_SESSION['session_version'] = (int)($user['session_version'] ?? 0);

        // Record Login Audit Log — always use Philippine local time
        date_default_timezone_set('Asia/Manila');
        $loginTime = date('Y-m-d H:i:s');

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

        $details = "Login successful (OTP verified). IP: {$ip} | Host: {$host} | Device: {$device}";
        $auditId = $this->userModel->logAuditAction(
            $user['id_number'],
            $user['username'],
            strtolower($user['role'] ?? 'user'),
            'Login',
            $details,
            $loginTime,
            null
        );
        $_SESSION['audit_log_id'] = $auditId;

        $redirectUrl = 'index.php?action=dashboard';
        if ($_SESSION['role'] === 'superadmin') {
            $redirectUrl = '../super_admin/dashboard.php';
        } elseif ($_SESSION['role'] === 'admin') {
            $redirectUrl = '../admin/dashboard.php';
        }

        unset($_SESSION['otp_pending']);

        echo json_encode([
            'success' => true,
            'redirect' => $redirectUrl,
            'mustChangePassword' => !empty($user['must_change_password'])
        ]);
        exit;
    }






    private function requireRecoverableAccount(string $idNumber): array
    {
        $user = $this->userModel->findById($idNumber);
        if (!$user && $this->userModel->wasAccountDeleted($idNumber)) {
            $user = ['status' => 'deleted'];
        }
        $message = User::passwordRecoveryError($user ?: null);
        if ($message !== null) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if (($_SESSION['otp_pending']['purpose'] ?? '') === 'forgot_password') {
                unset($_SESSION['otp_pending']);
            }
            echo json_encode(['success' => false, 'valid' => false, 'message' => $message]);
            exit;
        }
        return $user;
    }

    //========================================== Start Forgot-Password by ID =====================================
    public function verifyForgotEmail()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $idNumber = trim($_POST['id_number'] ?? '');
        if ($idNumber === '') {
            echo json_encode(['success' => false, 'message' => 'Please enter your registered ID Number.']);
            exit;
        }
        if (!preg_match('/^\d{4}-\d{4}$/', $idNumber)) {
            echo json_encode(['success' => false, 'message' => 'ID Number must contain exactly 8 digits.']);
            exit;
        }

        $user = $this->requireRecoverableAccount($idNumber);

        $email = strtolower(trim((string)($user['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'This account has no recovery email. Update Personal Details or contact an administrator.']);
            exit;
        }
        $questions = $this->userModel->getUserAuthAnswers($user['id_number']);
        if (!$questions) {
            echo json_encode(['success' => false, 'message' => 'This account has no security question configured. Update Personal Details or contact an administrator.']);
            exit;
        }

        $otp = new Otp();
        $issue = $otp->issue($email, $user['id_number'], 'forgot_password');
        if (!$issue['success']) {
            echo json_encode(['success' => false, 'message' => $issue['message'], 'cooldown' => $issue['cooldown'] ?? null]);
            exit;
        }
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_regenerate_id(true);
        $_SESSION['otp_pending'] = [
            'purpose'   => 'forgot_password',
            'email'     => $email,
            'id_number' => $user['id_number'],
            'issued_at' => time(),
            'otp_verified' => false,
            'security_verified' => false,
            'security_attempts' => 0,
            'security_question_id' => null
        ];

        $dash = strpos($user['id_number'], '-');
        $maskedId = $dash === false
            ? substr($user['id_number'], 0, min(2, strlen($user['id_number']))) . str_repeat('*', max(1, strlen($user['id_number']) - 2))
            : substr($user['id_number'], 0, $dash + 1) . str_repeat('*', max(6, strlen($user['id_number']) - $dash - 1));

        echo json_encode([
            'success' => true,
            'message' => $issue['message'],
            'masked_id' => $maskedId,
            'masked_email' => $otp->maskEmail($email),
            'expires_in' => $issue['expires_in'] ?? Otp::CODE_LIFETIME,
            'cooldown' => $issue['cooldown'] ?? Otp::RESEND_COOLDOWN,
            'dev_otp' => $issue['dev_otp'] ?? null
        ]);
        exit;
    }

    //========================================== Send Forgot-Password OTP =====================================
    public function sendForgotOtp()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $pending = $_SESSION['otp_pending'] ?? null;
        if (!$pending || ($pending['purpose'] ?? '') !== 'forgot_password' || empty($pending['email'])) {
            echo json_encode(['success' => false, 'message' => 'Session expired. Please enter your ID Number again.']);
            exit;
        }
        $this->requireRecoverableAccount((string)($pending['id_number'] ?? ''));

        $otp = new Otp();
        $issue = $otp->resend($pending['email'], 'forgot_password', $pending['id_number'] ?? null);
        if (!$issue['success']) {
            echo json_encode(['success' => false, 'message' => $issue['message'], 'cooldown' => $issue['cooldown'] ?? null]);
            exit;
        }
        unset($_SESSION['otp_pending']['otp_verified'], $_SESSION['otp_pending']['otp_verified_at'],
            $_SESSION['otp_pending']['security_verified'], $_SESSION['otp_pending']['security_verified_at'],
            $_SESSION['otp_pending']['reset_token']);
        $_SESSION['otp_pending']['issued_at'] = time();

        echo json_encode([
            'success'    => true,
            'message'    => $issue['message'],
            'email'      => $otp->maskEmail($pending['email']),
            'expires_in' => $issue['expires_in'] ?? Otp::CODE_LIFETIME,
            'cooldown'   => $issue['cooldown'] ?? Otp::RESEND_COOLDOWN,
            'dev_otp'    => $issue['dev_otp'] ?? null
        ]);
        exit;
    }

    //========================================== Verify Forgot-Password OTP =====================================
    public function verifyForgotOtp()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $pending = $_SESSION['otp_pending'] ?? null;
        if (!$pending || ($pending['purpose'] ?? '') !== 'forgot_password') {
            echo json_encode(['success' => false, 'message' => 'Session expired. Please start the password reset again.']);
            exit;
        }

        $this->requireRecoverableAccount((string)($pending['id_number'] ?? ''));

        $otp = new Otp();
        $result = $otp->verify($pending['email'], 'forgot_password', trim($_POST['otp'] ?? ''));
        if (!$result['success']) {
            echo json_encode(['success' => false, 'message' => $result['message']]);
            exit;
        }

        $questions = $this->userModel->getUserAuthAnswers($pending['id_number']);
        if (!$questions) {
            unset($_SESSION['otp_pending']);
            echo json_encode(['success' => false, 'message' => 'No security questions are configured for this account.']);
            exit;
        }
        session_regenerate_id(true);
        $_SESSION['otp_pending']['otp_verified'] = true;
        $_SESSION['otp_pending']['otp_verified_at'] = time();
        $_SESSION['otp_pending']['security_question_ids'] = array_map(static function ($q) {
            return (int)$q['question_id'];
        }, $questions);
        $_SESSION['otp_pending']['security_question_id'] = (int)$questions[0]['question_id'];

        echo json_encode([
            'success' => true,
            'message' => 'OTP verified. Please answer your security questions.',
            'questions' => array_map(static function ($q) {
                return [
                    'question_id' => (int)$q['question_id'],
                    'question_text' => $q['question_text']
                ];
            }, $questions),
            'question' => [
                'question_id' => (int)$questions[0]['question_id'],
                'question_text' => $questions[0]['question_text']
            ]
        ]);
        exit;
    }

    //========================================== Verify Registration OTP =====================================
    public function verifyRegisterOtp()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $pending = $_SESSION['otp_pending'] ?? null;
        if (!$pending || ($pending['purpose'] ?? '') !== 'register') {
            echo json_encode(['success' => false, 'message' => 'Registration session expired. Please register again.']);
            exit;
        }

        $otp = new Otp();
        $result = $otp->verify($pending['email'], 'register', trim($_POST['otp'] ?? ''));
        if (!$result['success']) {
            echo json_encode(['success' => false, 'message' => $result['message']]);
            exit;
        }

        // Set status to pending_approval (awaits Super Admin / Admin approval)
        $this->userModel->setAccountStatus($pending['id_number'], 'pending_approval');
        $registeredId = $pending['id_number'];
        unset($_SESSION['otp_pending']);

        echo json_encode([
            'success' => true,
            'message' => 'Email verified successfully! Your account registration is now pending administrator approval.',
            'id_number' => $registeredId,
            'pendingApproval' => true
        ]);
        exit;
    }

    //========================================== Resend OTP (any pending flow) =====================================
    public function resendOtp()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $pending = $_SESSION['otp_pending'] ?? null;
        if (!$pending || empty($pending['email']) || empty($pending['purpose'])) {
            echo json_encode(['success' => false, 'message' => 'Session expired. Please start the process again.']);
            exit;
        }

        $otp = new Otp();
        $issue = $otp->resend($pending['email'], $pending['purpose'], $pending['id_number'] ?? null);
        if ($issue['success']) {
            unset(
                $_SESSION['otp_pending']['verified'],
                $_SESSION['otp_pending']['verified_at'],
                $_SESSION['otp_pending']['reset_token'],
                $_SESSION['otp_pending']['otp_verified'],
                $_SESSION['otp_pending']['otp_verified_at'],
                $_SESSION['otp_pending']['security_verified'],
                $_SESSION['otp_pending']['security_verified_at'],
                $_SESSION['otp_pending']['security_question_id']
            );
            $_SESSION['otp_pending']['issued_at'] = time();
        }
        echo json_encode([
            'success'    => $issue['success'],
            'message'    => $issue['message'],
            'cooldown'   => $issue['cooldown'] ?? Otp::RESEND_COOLDOWN,
            'expires_in' => $issue['expires_in'] ?? Otp::CODE_LIFETIME,
            'dev_otp'    => $issue['dev_otp'] ?? null
        ]);
        exit;
    }

    //========================================== Verify ID =====================================
    public function verifyId()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_number = trim($_POST['id_number'] ?? '');
            $user = $this->requireRecoverableAccount($id_number);

            if ($user) {
                // Get user's security questions
                $questions = $this->userModel->getUserAuthAnswers($id_number);

                echo json_encode([
                    'success' => true,
                    'user' => [
                        'id_number' => $user['id_number'],
                        'username' => $user['username']
                    ],
                    'questions' => $questions
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid ID Number']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        }
        exit; // Important to stop any extra output
    }


    //========================================== Verify Security Answers =====================================
    public function verifySecurityAnswers()
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $pending = $_SESSION['otp_pending'] ?? null;
        $otpVerifiedAt = (int)($pending['otp_verified_at'] ?? 0);
        if (!$pending || ($pending['purpose'] ?? '') !== 'forgot_password' ||
            empty($pending['otp_verified']) || $otpVerifiedAt <= 0 ||
            (time() - $otpVerifiedAt) > Otp::CODE_LIFETIME) {
            echo json_encode(['success' => false, 'message' => 'OTP verification is required before the security question.']);
            exit;
        }
        $this->requireRecoverableAccount((string)($pending['id_number'] ?? ''));

        $attempts = (int)($pending['security_attempts'] ?? 0);
        if ($attempts >= 5) {
            unset($_SESSION['otp_pending']);
            echo json_encode(['success' => false, 'message' => 'Too many incorrect security-answer attempts. Please start again.']);
            exit;
        }
        $q1 = (int)($_POST['security_question_1'] ?? $_POST['question_1'] ?? 0);
        $a1 = trim($_POST['security_answer_1'] ?? $_POST['answer_1'] ?? '');

        $q2 = (int)($_POST['security_question_2'] ?? $_POST['question_2'] ?? 0);
        $a2 = trim($_POST['security_answer_2'] ?? $_POST['answer_2'] ?? '');

        $q3 = (int)($_POST['security_question_3'] ?? $_POST['question_3'] ?? 0);
        $a3 = trim($_POST['security_answer_3'] ?? $_POST['answer_3'] ?? '');

        // Fallback if legacy single question was passed
        if (!$q1 && isset($_POST['question_id'])) {
            $q1 = (int)$_POST['question_id'];
            $a1 = trim($_POST['security_answer'] ?? '');
        }

        $items = [];
        if ($q1 > 0 && $a1 !== '') {
            $items[] = ['qid' => $q1, 'ans' => $a1];
        }
        if ($q2 > 0 && $a2 !== '') {
            $items[] = ['qid' => $q2, 'ans' => $a2];
        }
        if ($q3 > 0 && $a3 !== '') {
            $items[] = ['qid' => $q3, 'ans' => $a3];
        }

        if (count($items) < 3) {
            echo json_encode(['success' => false, 'message' => 'Please select all 3 security questions and provide answers for each.']);
            exit;
        }

        $correctCount = 0;
        foreach ($items as $item) {
            $record = $this->userModel->getUserAuthAnswer($pending['id_number'], $item['qid']);
            if ($record && password_verify($item['ans'], $record['answer_hash'])) {
                $correctCount++;
            }
        }

        if ($correctCount < 2) {
            $_SESSION['otp_pending']['security_attempts'] = $attempts + 1;
            $remaining = 5 - $_SESSION['otp_pending']['security_attempts'];
            if ($remaining <= 0) {
                unset($_SESSION['otp_pending']);
                echo json_encode(['success' => false, 'message' => 'Too many incorrect security-answer attempts. Please start again.']);
            } else {
                echo json_encode(['success' => false, 'message' => "At least 2 questions must be answered correctly. {$remaining} attempt(s) remaining."]);
            }
            exit;
        }

        session_regenerate_id(true);
        $_SESSION['otp_pending']['security_verified'] = true;
        $_SESSION['otp_pending']['security_verified_at'] = time();
        $_SESSION['otp_pending']['reset_token'] = bin2hex(random_bytes(32));
        echo json_encode([
            'success' => true,
            'message' => 'Security answers verified. You may now change your password.',
            'reset_token' => $_SESSION['otp_pending']['reset_token']
        ]);
        exit;
    }


    //========================================== Validate Individual Security Answer =====================================
    public function validateSecurityAnswer()
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['valid' => false, 'message' => 'Invalid request method.']);
            exit;
        }
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $pending = $_SESSION['otp_pending'] ?? null;
        $otpVerifiedAt = (int)($pending['otp_verified_at'] ?? 0);
        if (!$pending || ($pending['purpose'] ?? '') !== 'forgot_password' ||
            empty($pending['otp_verified']) || $otpVerifiedAt <= 0 ||
            (time() - $otpVerifiedAt) > Otp::CODE_LIFETIME) {
            echo json_encode(['valid' => false, 'message' => 'OTP verification is required first.']);
            exit;
        }

        $this->requireRecoverableAccount((string)($pending['id_number'] ?? ''));

        $questionId = (int)($_POST['question_id'] ?? 0);
        $answer = trim((string)($_POST['security_answer'] ?? ''));
        $allowedQuestionIds = array_map('intval', $pending['security_question_ids'] ?? []);
        if ($questionId <= 0 || !in_array($questionId, $allowedQuestionIds, true)) {
            echo json_encode(['valid' => false, 'message' => 'This is not one of your registered security questions.']);
            exit;
        }
        if ($answer === '') {
            echo json_encode(['valid' => false, 'message' => 'Enter your security answer.']);
            exit;
        }

        // Cache repeated checks from blur/input events and cap distinct guesses
        // during one OTP-verified recovery session.
        $answerKey = hash('sha256', $questionId . "\0" . $answer);
        $cachedChecks = $_SESSION['otp_pending']['security_live_checks'] ?? [];
        if (array_key_exists($answerKey, $cachedChecks)) {
            $valid = (bool)$cachedChecks[$answerKey];
        } else {
            if (count($cachedChecks) >= 30) {
                echo json_encode(['valid' => false, 'message' => 'Too many answer checks. Please start again.']);
                exit;
            }
            $record = $this->userModel->getUserAuthAnswer($pending['id_number'], $questionId);
            $valid = (bool)($record && password_verify($answer, $record['answer_hash']));
            $_SESSION['otp_pending']['security_live_checks'][$answerKey] = $valid;
        }

        echo json_encode([
            'valid' => $valid,
            'message' => $valid ? 'Correct answer.' : 'Incorrect answer.'
        ]);
        exit;
    }



    //=========================================== Reset Password =======================================
    public function resetPassword()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $pending = $_SESSION['otp_pending'] ?? null;
        $otpVerifiedAt = (int)($pending['otp_verified_at'] ?? 0);
        $securityVerifiedAt = (int)($pending['security_verified_at'] ?? 0);
        $submittedToken = (string)($_POST['reset_token'] ?? '');
        $sessionToken = (string)($pending['reset_token'] ?? '');
        if (
            !$pending ||
            ($pending['purpose'] ?? '') !== 'forgot_password' ||
            empty($pending['otp_verified']) ||
            empty($pending['security_verified']) ||
            $otpVerifiedAt <= 0 ||
            $securityVerifiedAt <= 0 ||
            (time() - $otpVerifiedAt) > Otp::CODE_LIFETIME ||
            (time() - $securityVerifiedAt) > Otp::CODE_LIFETIME ||
            $submittedToken === '' ||
            $sessionToken === '' ||
            !hash_equals($sessionToken, $submittedToken)
        ) {
            echo json_encode(['success' => false, 'message' => 'OTP and security-question verification are required. Please start again.']);
            return;
        }

        $id_number = $pending['id_number'];
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        $user = $this->requireRecoverableAccount($id_number);
        if (!$user || strtolower((string)($user['email'] ?? '')) !== strtolower((string)$pending['email'])) {
            unset($_SESSION['otp_pending']);
            echo json_encode(['success' => false, 'message' => 'Password reset session is no longer valid.']);
            return;
        }

        if ($new_password === '' || $confirm_password === '') {
            echo json_encode(['success' => false, 'message' => 'New Password and Confirm Password are required.']);
            return;
        }

        if ($new_password !== $confirm_password) {
            echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
            return;
        }

        if (password_verify($new_password, $user['password_hash'])) {
            echo json_encode(['success' => false, 'message' => 'The new password must be different from your current password.']);
            return;
        }

        // Check password strength
        $hasLower = preg_match('/[a-z]/', $new_password);
        $hasUpper = preg_match('/[A-Z]/', $new_password);
        $hasNumber = preg_match('/[0-9]/', $new_password);
        $hasSpecial = preg_match('/[^a-zA-Z0-9]/', $new_password);
        $hasLength = strlen($new_password) >= 8;

        $missing = [];
        if (!$hasLower) $missing[] = "lowercase letter";
        if (!$hasUpper) $missing[] = "uppercase letter";
        if (!$hasNumber) $missing[] = "number";
        if (!$hasSpecial) $missing[] = "special character";
        if (!$hasLength) $missing[] = "8+ characters";

        // Calculate strength (0-5)
        $strength = 0;
        if ($hasLower) $strength++;
        if ($hasUpper) $strength++;
        if ($hasNumber) $strength++;
        if ($hasSpecial) $strength++;
        if ($hasLength) $strength++;

        // Updated: Password must meet minimum requirements (at least 4 criteria) - medium strength (3/5) is not acceptable
        if ($strength < 4) {
            if (!empty($missing)) {
                echo json_encode(['success' => false, 'message' => 'Password is too weak. Missing: ' . implode(", ", $missing)]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Password is too weak. Must be 8+ characters with uppercase, lowercase, and number.']);
            }
            return;
        }

        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        if ($this->userModel->resetRecoverablePassword($id_number, $hashed)) {
            unset($_SESSION['otp_pending']);
            $this->userModel->logAuditAction(
                $id_number,
                $user['username'],
                strtolower($user['role'] ?? 'user'),
                'Reset Password',
                'Password reset completed after ID, OTP, and security-question verification.'
            );
            echo json_encode([
                'success' => true,
                'message' => 'Your password has been successfully changed!',
                'redirect' => 'index.php?action=login&password_reset=1'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to reset password']);
        }
    }

    //========================================== Check Username Availability =====================================
    public function checkUsername()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');

            if (empty($username)) {
                echo json_encode(['available' => false, 'message' => 'Username is required.']);
                exit;
            }

            if (!preg_match('/^(?=.{3,50}$)[a-z]+(?:\.[a-z]+)+\d{2}$/i', $username) || preg_match('/(.)\1\1/i', $username)) {
                echo json_encode(['available' => false, 'message' => 'Username must follow this example format: juan.delacruz01.']);
                exit;
            }

            if ($this->userModel->usernameExists($username)) {
                echo json_encode(['available' => false, 'message' => 'Username is already taken.']);
            } else {
                echo json_encode(['available' => true, 'message' => 'Username is available.']);
            }
        } else {
            echo json_encode(['available' => false, 'message' => 'Invalid request method.']);
        }
        exit;
    }

    //========================================== Check Email Availability =====================================
    public function checkEmail()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');

            if (empty($email)) {
                echo json_encode(['available' => false, 'message' => 'Email is required.']);
                exit;
            }

            // Basic email validation
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['available' => false, 'message' => 'Invalid email format.']);
                exit;
            }

            if ($this->userModel->emailExists($email)) {
                echo json_encode(['available' => false, 'message' => 'Email is already registered.']);
            } else {
                echo json_encode(['available' => true, 'message' => 'Email is available.']);
            }
        } else {
            echo json_encode(['available' => false, 'message' => 'Invalid request method.']);
        }
        exit;
    }

    /* ========================== SUPER ADMIN: MANAGE ADMINS ACTIONS ======================== */

    /**
     * Live session validation for AJAX endpoints. Verifies, against the database:
     * logged-in user + current role + account status + session version.
     */
    private function validateLiveSession(array $allowedRoles): ?array
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

        if (!in_array($authState['status'], ['active', 'pending_deletion'], true)) {
            session_unset();
            session_destroy();
            header('Content-Type: application/json; charset=utf-8');
            $inactive = $authState['status'] === 'inactive';
            echo json_encode([
                'success' => false,
                'message' => $inactive ? 'This account is inactive.' : 'Your account has been blocked. Please contact the Super Admin.',
                'accountInactive' => $inactive,
                'accountBlocked' => !$inactive
            ]);
            exit;
        }

        if (!empty($authState['must_change_password'])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Change the default password before accessing the portal.',
                'passwordChangeRequired' => true
            ]);
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

        $role = strtolower($authState['role']);
        if (!in_array($role, $allowedRoles, true)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Unauthorized access. Required role: ' . implode(' or ', $allowedRoles) . '.']);
            exit;
        }

        return $authState;
    }

    private function requireSuperAdmin()
    {
        return $this->validateLiveSession(['superadmin']);
    }

    private function requireActorPassword(array $authState): void
    {
        $password = (string)($_POST['operator_password'] ?? '');
        if (!$this->userModel->verifyAccountPassword($authState['id_number'], $password)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Your current password is incorrect. The action was cancelled.',
                'passwordInvalid' => true
            ]);
            exit;
        }
    }

    private function passwordPolicyError(string $password): ?string
    {
        if (strlen($password) < 8 ||
            !preg_match('/[a-z]/', $password) ||
            !preg_match('/[A-Z]/', $password) ||
            !preg_match('/\d/', $password) ||
            !preg_match('/[^a-zA-Z0-9]/', $password)) {
            return 'Password must be at least 8 characters and include uppercase, lowercase, a number, and a special character.';
        }
        return null;
    }

    /**
     * Admin session validation used by endpoints shared with the Admin module.
     * Returns the live auth state (id_number, username, role, status).
     */
    public function requireValidAdmin(): array
    {
        return $this->validateLiveSession(['admin']);
    }

    /**
     * Requires one of the given admin privileges for the current admin account.
     */
    private function requireAdminPrivilege(string $privilegeKey, string $idNumber): void
    {
        if (!$this->userModel->hasAdminPrivilege($idNumber, $privilegeKey)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'You do not have the required privilege (' . $privilegeKey . ').']);
            exit;
        }
    }

    public function getAdmins()
    {
        $this->requireSuperAdmin();
        header('Content-Type: application/json; charset=utf-8');

        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? 'all');
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = in_array((int)($_GET['limit'] ?? 10), [10, 25, 50, 100], true) ? (int)$_GET['limit'] : 10;

        $totalRecords = $this->userModel->getAdminsCount($search, $status);
        $totalPages   = max(1, (int)ceil($totalRecords / $limit));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $limit;

        $admins = $this->userModel->getAdminsList($search, $status, $offset, $limit);

        echo json_encode([
            'success'      => true,
            'data'         => $admins,
            'totalRecords' => $totalRecords,
            'totalPages'   => $totalPages,
            'currentPage'  => $page,
            'limit'        => $limit
        ]);
        exit;
    }

    public function getAdminDetail()
    {
        $this->requireSuperAdmin();
        header('Content-Type: application/json; charset=utf-8');

        $id_number = trim($_GET['id_number'] ?? $_POST['id_number'] ?? '');
        if (empty($id_number)) {
            echo json_encode(['success' => false, 'message' => 'ID Number is required.']);
            exit;
        }

        $admin = $this->userModel->getAdminByIdNumber($id_number);
        if ($admin) {
            echo json_encode(['success' => true, 'data' => $admin]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Admin account not found.']);
        }
        exit;
    }

    public function addAdmin()
    {
        $this->requireSuperAdmin();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $id_number  = trim($_POST['id_number'] ?? '');
        $firstName  = trim($_POST['first_name'] ?? '');
        $middleName = trim($_POST['middle_name'] ?? '');
        $lastName   = trim($_POST['last_name'] ?? '');
        $extension  = trim($_POST['extension'] ?? '');
        $birthdate  = trim($_POST['birthdate'] ?? '');
        $gender     = strtolower(trim($_POST['gender'] ?? ''));

        // Address
        $street   = trim($_POST['street'] ?? '');
        $barangay = trim($_POST['barangay'] ?? '');
        $city     = trim($_POST['city'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $country  = trim($_POST['country'] ?? '');
        $zip      = trim($_POST['zip'] ?? '');

        // Account
        $username  = trim($_POST['username'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $password  = $_POST['password'] ?? '';
        $confirm   = $_POST['confirm_password'] ?? '';
        $status    = trim($_POST['status'] ?? 'active');

        // Fallback for single 'name' input
        if (empty($firstName) && empty($lastName) && !empty($_POST['name'])) {
            $nameParts = preg_split('/\s+/', trim($_POST['name']));
            $firstName = array_shift($nameParts) ?? 'Admin';
            $lastName  = array_pop($nameParts) ?? 'User';
            $middleName = !empty($nameParts) ? implode(' ', $nameParts) : '';
        }

        $fieldErrors = [];

        // ✅ Admin ID format validation (YYYY-####)
        // If the client sent an id_number, it must match the strict format; otherwise
        // a fresh YYYY-#### ID is generated server-side.
        if ($id_number === '') {
            $id_number = $this->userModel->generateAdminIdNumber();
        } elseif (!User::isValidAdminIdFormat($id_number)) {
            $fieldErrors['id_number'] = "Admin ID must follow the format YYYY-####. Example: 2026-0001.";
        } elseif ($this->userModel->findById($id_number)) {
            $fieldErrors['id_number'] = "This Admin ID is already in use. Please use a different one.";
        }

        // Validate Names
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

        // Validate Extension
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

        // Validate Birthdate & Age
        if (empty($birthdate)) {
            $fieldErrors['birthdate'] = "Birthdate is required.";
        } else {
            $dob = new DateTime($birthdate);
            $today = new DateTime();
            $age = $today->diff($dob)->y;
            if ($age < 18) {
                $fieldErrors['birthdate'] = "Admin must be 18 or older.";
            }
        }

        // Gender
        if (empty($gender) || !in_array($gender, ['male', 'female'], true)) {
            $fieldErrors['gender'] = "Please select a valid gender.";
        }

        // Address Fields
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

        // Zip Code
        if (empty($zip)) {
            $fieldErrors['zip'] = "Zip Code is required.";
        } elseif (!preg_match('/^\d{4,6}$/', $zip)) {
            $fieldErrors['zip'] = "Zip Code must be between 4 and 6 digits.";
        }

        // Security Questions
        $securityAnswers = [];
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

            if ($qVal !== '' && $aVal !== '') {
                $securityAnswers[] = [
                    'question_id' => (int)$qVal,
                    'answer'      => $aVal
                ];
            }
        }

        // Username
        if ($username === '') {
            $fieldErrors['username'] = "Username is required.";
        } else {
            if (preg_match('/\s/', $username)) {
                $fieldErrors['username'] = "Username cannot contain spaces.";
            } elseif (preg_match('/([a-zA-Z])\1\1/i', $username)) {
                $fieldErrors['username'] = "Username cannot contain 3 identical letters in a row.";
            } elseif ($this->userModel->usernameExists($username) || $this->userModel->pendingUsernameExists($username)) {
                $fieldErrors['username'] = "Username is already taken.";
            }
        }

        // Email
        if ($email === '') {
            $fieldErrors['email'] = "Email is required.";
        } else {
            if (preg_match('/\s/', $email)) {
                $fieldErrors['email'] = "Email cannot contain spaces.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $fieldErrors['email'] = "Invalid email format.";
            } elseif ($this->userModel->emailExists($email) || $this->userModel->pendingEmailExists($email)) {
                $fieldErrors['email'] = "Email is already registered.";
            }
        }

        // Password & Confirm
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
            $newId = $this->userModel->createAdmin([
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

            echo json_encode(['success' => true, 'message' => 'Admin account created successfully.', 'id_number' => $newId]);
            $this->userModel->logAuditAction($_SESSION['user_id'] ?? null, $_SESSION['username'] ?? 'superadmin', 'superadmin', 'Create Admin', "Created Admin: {$username} (ID: {$newId})");
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to create admin: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Returns the next available ID numbers for the admin/user creation forms.
     * All roles share the YYYY-#### sequence generated server-side.
     */
    public function getNextIds()
    {
        $authState = $this->validateLiveSession(['superadmin', 'admin']);
        if (strtolower($authState['role']) === 'admin') {
            $this->requireAdminPrivilege('create_accounts', $authState['id_number']);
        }
        header('Content-Type: application/json; charset=utf-8');

        $type = strtolower(trim($_GET['type'] ?? $_POST['type'] ?? 'all'));

        $nextId = $this->userModel->generateIdNumber();
        $payload = ['success' => true];
        if ($type === 'admin' || $type === 'all') {
            $payload['admin_id'] = $nextId;
        }
        if ($type === 'user' || $type === 'standard' || $type === 'all') {
            $payload['standard_id'] = $nextId;
        }
        echo json_encode($payload);
        exit;
    }

    /**
     * Returns the dashboard counts needed by the cards on the Super Admin or
     * Admin dashboard. Includes the pending approvals count.
     */
    public function getDashboardCounts()
    {
        header('Content-Type: application/json; charset=utf-8');
        $authState = $this->validateLiveSession(['superadmin', 'admin']);

        if (strtolower($authState['role']) === 'superadmin') {
            $stats = $this->userModel->getDashboardStats();
            echo json_encode([
                'success'           => true,
                'role'              => 'superadmin',
                'total_accounts'    => $stats['total_accounts'],
                'active_admins'     => $stats['active_admins'],
                'active_users'      => $stats['active_users'],
                'blocked_accounts'  => $stats['blocked_accounts'],
                'pending_approvals' => $this->userModel->getSuperAdminPendingApprovalCount()
            ]);
        } else {
            $stats = $this->userModel->getAdminDashboardStats();
            echo json_encode([
                'success'           => true,
                'role'              => 'admin',
                'total_users'       => $stats['total_users'],
                'active_users'      => $stats['active_users'],
                'blocked_users'     => $stats['blocked_users'],
                'pending_approvals' => $this->userModel->getAdminPendingApprovalCount($authState['id_number'])
            ]);
        }
        exit;
    }

    public function updateAdmin()
    {
        $authState = $this->requireSuperAdmin();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $this->requireActorPassword($authState);

        $id_number  = trim($_POST['id_number'] ?? '');
        $firstName  = trim($_POST['first_name'] ?? '');
        $middleName = trim($_POST['middle_name'] ?? '');
        $lastName   = trim($_POST['last_name'] ?? '');
        $extension  = trim($_POST['extension'] ?? '');
        $birthdate  = trim($_POST['birthdate'] ?? '');
        $gender     = strtolower(trim($_POST['gender'] ?? ''));

        // Address
        $street   = trim($_POST['street'] ?? '');
        $barangay = trim($_POST['barangay'] ?? '');
        $city     = trim($_POST['city'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $country  = trim($_POST['country'] ?? '');
        $zip      = trim($_POST['zip'] ?? '');

        // Account
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $status   = trim($_POST['status'] ?? 'active');

        // Fallback for single 'name' input
        if (empty($firstName) && empty($lastName) && !empty($_POST['name'])) {
            $nameParts = preg_split('/\s+/', trim($_POST['name']));
            $firstName = array_shift($nameParts) ?? 'Admin';
            $lastName  = array_pop($nameParts) ?? 'User';
            $middleName = !empty($nameParts) ? implode(' ', $nameParts) : '';
        }

        $fieldErrors = [];

        if (empty($id_number)) {
            echo json_encode(['success' => false, 'message' => 'Admin ID Number is required.']);
            exit;
        }

        $existing = $this->userModel->getAdminByIdNumber($id_number);
        if (!$existing) {
            echo json_encode(['success' => false, 'message' => 'Admin account not found.']);
            exit;
        }
        if (($existing['role'] ?? '') === 'superadmin' && $status === 'active' && $this->userModel->hasOtherActiveSuperAdmin($id_number)) {
            echo json_encode(['success' => false, 'message' => 'Only the queued Super Admin handoff may activate this account.']);
            exit;
        }

        // Validate Names
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

        // Validate Extension
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

        // Birthdate & Age
        if (!empty($birthdate)) {
            $dob = new DateTime($birthdate);
            $today = new DateTime();
            $age = $today->diff($dob)->y;
            if ($age < 18) {
                $fieldErrors['birthdate'] = "Admin must be 18 or older.";
            }
        }

        // Gender
        if (empty($gender) || !in_array($gender, ['male', 'female'], true)) {
            $fieldErrors['gender'] = "Please select a valid gender.";
        }

        // Address Fields
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

        // Zip Code
        if (empty($zip)) {
            $fieldErrors['zip'] = "Zip Code is required.";
        } elseif (!preg_match('/^\d{4,6}$/', $zip)) {
            $fieldErrors['zip'] = "Zip Code must be between 4 and 6 digits.";
        }

        // Username
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

        // Email
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

        // Password (optional on update)
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
            $updated = $this->userModel->updateAdmin($id_number, [
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
                'username'    => $username,
                'email'       => $email,
                'password'    => $password,
                'status'      => $status
            ]);

            if ($updated) {
                echo json_encode(['success' => true, 'message' => 'Admin account updated successfully.']);
                $this->userModel->logAuditAction($_SESSION['user_id'] ?? null, $_SESSION['username'] ?? 'superadmin', 'superadmin', 'Update Admin', "Updated Admin: {$username}");
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update admin account.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    public function toggleBlockAdmin()
    {
        $authState = $this->requireSuperAdmin();
        header('Content-Type: application/json; charset=utf-8');
        $this->requireActorPassword($authState);

        $id_number  = trim($_POST['id_number'] ?? '');
        $new_status = trim($_POST['status'] ?? '');
        $reason     = trim($_POST['reason'] ?? '');
        $ip         = $_SERVER['REMOTE_ADDR'] ?? '';
        $operator   = $_SESSION['username'] ?? 'superadmin';

        if (empty($id_number) || !in_array($new_status, ['active', 'blocked', 'block'], true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters provided.']);
            exit;
        }
        if (in_array($new_status, ['blocked', 'block'], true) && $reason === '') {
            echo json_encode(['success' => false, 'message' => 'A reason is required when blocking an account.']);
            exit;
        }
        $target = $this->userModel->getManagedAccountById($id_number);
        if ($target && $target['role'] === 'superadmin' && $new_status === 'active' && $this->userModel->hasOtherActiveSuperAdmin($id_number)) {
            echo json_encode(['success' => false, 'message' => 'Queued Super Admins are activated automatically when the active Super Admin logs out.']);
            exit;
        }

        // Protect the currently logged-in Super Admin from self-blocking
        if (strtolower($operator) === 'superadmin' && $id_number === ($_SESSION['user_id'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'You cannot block your own account.']);
            exit;
        }

        $updated = $this->userModel->toggleAdminStatus($id_number, $new_status, $operator, $reason, $ip);
        if ($updated) {
            $actionText = in_array($new_status, ['block', 'blocked'], true) ? 'blocked' : 'unblocked';
            echo json_encode(['success' => true, 'message' => "Admin account has been {$actionText}."]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update admin status.']);
        }
        exit;
    }

    public function deleteAdmin()
    {
        $authState = $this->requireSuperAdmin();
        header('Content-Type: application/json; charset=utf-8');
        $this->requireActorPassword($authState);

        $id_number = trim($_POST['id_number'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        if (empty($id_number)) {
            echo json_encode(['success' => false, 'message' => 'ID Number is required.']);
            exit;
        }

        if ($id_number === ($_SESSION['user_id'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'You cannot deactivate your own Super Admin account.']);
            exit;
        }
        if ($reason === '') {
            echo json_encode(['success' => false, 'message' => 'A reason is required when deleting an account.']);
            exit;
        }

        $target = $this->userModel->getUserOrAdminByIdNumber($id_number);
        if (!$target || !in_array(strtolower($target['role'] ?? ''), ['admin', 'superadmin'], true)) {
            echo json_encode(['success' => false, 'message' => 'Admin account not found.']);
            exit;
        }

        $deleted = $this->userModel->deleteAdmin($id_number, $_SESSION['username'] ?? 'superadmin');
        if ($deleted) {
            $targetRole = strtolower($target['role']);
            echo json_encode(['success' => true, 'message' => ucfirst($targetRole) . ' account is now Inactive. The record was preserved.']);
            $this->userModel->logAuditAction(
                $_SESSION['user_id'] ?? null,
                $_SESSION['username'] ?? 'superadmin',
                'superadmin',
                $targetRole === 'superadmin' ? 'Deactivate Super Admin' : 'Deactivate Admin',
                "Directly deactivated {$targetRole} {$target['username']} (ID: {$id_number}). Status changed to Inactive; record preserved."
            );
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to deactivate admin account or it is already inactive.']);
        }
        exit;
    }

    /* ========================== SUPER ADMIN: MANAGE USERS ACTIONS ======================== */

    public function getUsers()
    {
        $this->requireSuperAdmin();
        header('Content-Type: application/json; charset=utf-8');

        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? 'all');
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = in_array((int)($_GET['limit'] ?? 10), [10, 25, 50, 100], true) ? (int)$_GET['limit'] : 10;

        $totalRecords = $this->userModel->getUsersCount($search, $status);
        $totalPages   = max(1, (int)ceil($totalRecords / $limit));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $limit;

        $users = $this->userModel->getUsersList($search, $status, $offset, $limit);

        echo json_encode([
            'success'      => true,
            'data'         => $users,
            'totalRecords' => $totalRecords,
            'totalPages'   => $totalPages,
            'currentPage'  => $page,
            'limit'        => $limit
        ]);
        exit;
    }

    public function getUserDetail()
    {
        $this->requireSuperAdmin();
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

    public function addStandardUser()
    {
        $this->requireSuperAdmin();
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
        $gender    = trim($_POST['gender'] ?? '');

        // Address
        $street   = trim($_POST['street'] ?? '');
        $barangay = trim($_POST['barangay'] ?? '');
        $city     = trim($_POST['city'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $country  = trim($_POST['country'] ?? '');
        $zip      = trim($_POST['zip'] ?? '');

        // Security questions
        $securityAnswers = [];
        if (!empty($_POST['security_question_1']) && isset($_POST['security_q1'])) {
            $securityAnswers[] = [
                'question_id' => (int)$_POST['security_question_1'],
                'answer' => trim($_POST['security_q1'])
            ];
        }
        if (!empty($_POST['security_question_2']) && isset($_POST['security_q2'])) {
            $securityAnswers[] = [
                'question_id' => (int)$_POST['security_question_2'],
                'answer' => trim($_POST['security_q2'])
            ];
        }
        if (!empty($_POST['security_question_3']) && isset($_POST['security_q3'])) {
            $securityAnswers[] = [
                'question_id' => (int)$_POST['security_question_3'],
                'answer' => trim($_POST['security_q3'])
            ];
        }

        // Account
        $username  = trim($_POST['username'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $password  = $_POST['password'] ?? '';
        $confirm   = $_POST['confirm_password'] ?? '';
        $status    = trim($_POST['status'] ?? 'active');

        // Fallback for single 'name' input
        if (empty($firstName) && empty($lastName) && !empty($_POST['name'])) {
            $nameParts = preg_split('/\s+/', trim($_POST['name']));
            $firstName = array_shift($nameParts) ?? 'User';
            $lastName  = array_pop($nameParts) ?? 'Account';
            $middleName = !empty($nameParts) ? implode(' ', $nameParts) : null;
        }

        // --- FIELD VALIDATION ---
        $fieldErrors = [];
        $cleanExtension = $extension;

        // Name fields
        $nameRegex = '/^[A-Z][a-zA-Z\s\'\-]*$/';
        foreach (['first_name' => ['First Name', $firstName, true], 'last_name' => ['Last Name', $lastName, true], 'middle_name' => ['Middle Name', $middleName, false]] as $key => [$label, $val, $required]) {
            if ($required && empty($val)) {
                $fieldErrors[$key] = "$label is required.";
            } elseif (!empty($val) && !preg_match($nameRegex, $val)) {
                $fieldErrors[$key] = "$label must start with a capital letter and contain only valid characters.";
            }
        }

        // Extension
        if (!empty($extension)) {
            $normalizedExt = strtoupper($extension);
            if (in_array($normalizedExt, ['JR', 'JR.'], true)) {
                $cleanExtension = 'Jr.';
            } elseif (in_array($normalizedExt, ['SR', 'SR.'], true)) {
                $cleanExtension = 'Sr.';
            } else {
                $cleanExtension = strtoupper(str_replace('.', '', $extension));
            }
            $validExtensions = ['Jr.', 'Sr.', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];
            if (!in_array($cleanExtension, $validExtensions, true)) {
                $fieldErrors['extension'] = "Extension must be Jr., Sr., or Roman numerals I to X.";
            }
        }

        // Birthdate
        if (empty($birthdate)) {
            $fieldErrors['birthdate'] = "Birthdate is required.";
        } else {
            $dob   = new DateTime($birthdate);
            $today = new DateTime();
            $age   = $today->diff($dob)->y;
            if ($age < 18) {
                $fieldErrors['birthdate'] = "User must be at least 18 years old.";
            }
        }

        // Gender
        if (empty($gender) || !in_array($gender, ['male', 'female'], true)) {
            $fieldErrors['gender'] = "Please select a valid gender.";
        }

        // Address
        $addrRegex = '/^[A-Za-z0-9][A-Za-z0-9\s\'.,#\-\/&()]*$/';
        $addrValidations = [
            'street'   => ['Purok/Street',    $street],
            'barangay' => ['Barangay',         $barangay],
            'city'     => ['Municipal/City',   $city],
            'province' => ['Province',         $province],
            'country'  => ['Country',          $country],
        ];
        foreach ($addrValidations as $key => [$label, $val]) {
            if (empty($val)) {
                $fieldErrors[$key] = "$label is required.";
            } elseif (!preg_match($addrRegex, $val)) {
                $fieldErrors[$key] = "$label contains invalid characters.";
            }
        }

        // Zip
        if (empty($zip)) {
            $fieldErrors['zip'] = "Zip Code is required.";
        } elseif (!preg_match('/^\d{4,6}$/', $zip)) {
            $fieldErrors['zip'] = "Zip Code must be between 4 and 6 digits.";
        }

        // Username
        if (empty($username)) {
            $fieldErrors['username'] = "Username is required.";
        } elseif (preg_match('/\s/', $username)) {
            $fieldErrors['username'] = "Username cannot contain spaces.";
        } elseif (preg_match('/([a-zA-Z])\1\1/i', $username)) {
            $fieldErrors['username'] = "Username cannot contain 3 identical letters in a row.";
        } elseif ($this->userModel->usernameExists($username)) {
            $fieldErrors['username'] = "Username is already in use.";
        }

        // Email
        if (empty($email)) {
            $fieldErrors['email'] = "Email is required.";
        } elseif (preg_match('/\s/', $email)) {
            $fieldErrors['email'] = "Email cannot contain spaces.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $fieldErrors['email'] = "Invalid email format.";
        } elseif ($this->userModel->emailExists($email)) {
            $fieldErrors['email'] = "Email is already registered.";
        }

        // Password
        if (empty($password)) {
            $fieldErrors['password'] = "Password is required.";
        } else {
            $hasLower   = preg_match('/[a-z]/', $password);
            $hasUpper   = preg_match('/[A-Z]/', $password);
            $hasNumber  = preg_match('/[0-9]/', $password);
            $hasSpecial = preg_match('/[^a-zA-Z0-9]/', $password);
            $hasLength  = strlen($password) >= 8;
            if (!$hasLower || !$hasUpper || !$hasNumber || !$hasSpecial || !$hasLength) {
                $missing = [];
                if (!$hasLower)   $missing[] = "lowercase letter";
                if (!$hasUpper)   $missing[] = "uppercase letter";
                if (!$hasNumber)  $missing[] = "number";
                if (!$hasSpecial) $missing[] = "special character";
                if (!$hasLength)  $missing[] = "8+ characters";
                $fieldErrors['password'] = "Password too weak. Missing: " . implode(", ", $missing);
            } elseif (preg_match('/([a-zA-Z])\1\1/i', $password)) {
                $fieldErrors['password'] = "Password cannot contain 3 identical letters in a row.";
            }
        }

        // Confirm password
        if ($password !== $confirm) {
            $fieldErrors['confirm_password'] = "Passwords do not match.";
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
                'middle_name' => $middleName,
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
            $this->userModel->logAuditAction($_SESSION['user_id'] ?? null, $_SESSION['username'] ?? 'superadmin', 'superadmin', 'Create User', "Created User: {$username}");
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to create user: ' . $e->getMessage()]);
        }
        exit;
    }

    public function updateStandardUser()
    {
        $authState = $this->requireSuperAdmin();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $this->requireActorPassword($authState);

        $id_number = trim($_POST['id_number'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $middleName = trim($_POST['middle_name'] ?? '');
        $lastName  = trim($_POST['last_name'] ?? '');
        $extension = trim($_POST['extension'] ?? '');
        $birthdate = trim($_POST['birthdate'] ?? '');
        $gender    = trim($_POST['gender'] ?? '');

        // Address
        $street   = trim($_POST['street'] ?? '');
        $barangay = trim($_POST['barangay'] ?? '');
        $city     = trim($_POST['city'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $country  = trim($_POST['country'] ?? '');
        $zip      = trim($_POST['zip'] ?? '');

        // Security questions
        $securityAnswers = [];
        if (!empty($_POST['security_question_1']) && isset($_POST['security_q1'])) {
            $securityAnswers[] = [
                'question_id' => (int)$_POST['security_question_1'],
                'answer' => trim($_POST['security_q1'])
            ];
        }
        if (!empty($_POST['security_question_2']) && isset($_POST['security_q2'])) {
            $securityAnswers[] = [
                'question_id' => (int)$_POST['security_question_2'],
                'answer' => trim($_POST['security_q2'])
            ];
        }
        if (!empty($_POST['security_question_3']) && isset($_POST['security_q3'])) {
            $securityAnswers[] = [
                'question_id' => (int)$_POST['security_question_3'],
                'answer' => trim($_POST['security_q3'])
            ];
        }

        // Account
        $username  = trim($_POST['username'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $password  = $_POST['password'] ?? '';
        $status    = trim($_POST['status'] ?? 'active');

        // Fallback for single 'name' input
        if (empty($firstName) && empty($lastName) && !empty($_POST['name'])) {
            $nameParts = preg_split('/\s+/', trim($_POST['name']));
            $firstName = array_shift($nameParts) ?? 'User';
            $lastName  = array_pop($nameParts) ?? 'Account';
            $middleName = !empty($nameParts) ? implode(' ', $nameParts) : null;
        }

        if (empty($id_number)) {
            echo json_encode(['success' => false, 'message' => 'ID Number is required.']);
            exit;
        }

        $existing = $this->userModel->getUserByIdNumber($id_number);
        if (!$existing) {
            echo json_encode(['success' => false, 'message' => 'User account not found.']);
            exit;
        }

        // --- FIELD VALIDATION ---
        $fieldErrors = [];
        $cleanExtension = $extension;

        // Name fields
        $nameRegex = '/^[A-Z][a-zA-Z\s\'\-]*$/';
        foreach (['first_name' => ['First Name', $firstName, true], 'last_name' => ['Last Name', $lastName, true], 'middle_name' => ['Middle Name', $middleName, false]] as $key => [$label, $val, $required]) {
            if ($required && empty($val)) {
                $fieldErrors[$key] = "$label is required.";
            } elseif (!empty($val) && !preg_match($nameRegex, $val)) {
                $fieldErrors[$key] = "$label must start with a capital letter and contain only valid characters.";
            }
        }

        // Extension
        if (!empty($extension)) {
            $normalizedExt = strtoupper($extension);
            if (in_array($normalizedExt, ['JR', 'JR.'], true)) {
                $cleanExtension = 'Jr.';
            } elseif (in_array($normalizedExt, ['SR', 'SR.'], true)) {
                $cleanExtension = 'Sr.';
            } else {
                $cleanExtension = strtoupper(str_replace('.', '', $extension));
            }
            $validExtensions = ['Jr.', 'Sr.', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];
            if (!in_array($cleanExtension, $validExtensions, true)) {
                $fieldErrors['extension'] = "Extension must be Jr., Sr., or Roman numerals I to X.";
            }
        }

        // Birthdate
        if (empty($birthdate)) {
            $fieldErrors['birthdate'] = "Birthdate is required.";
        } else {
            $dob   = new DateTime($birthdate);
            $today = new DateTime();
            $age   = $today->diff($dob)->y;
            if ($age < 18) {
                $fieldErrors['birthdate'] = "User must be at least 18 years old.";
            }
        }

        // Gender
        if (empty($gender) || !in_array($gender, ['male', 'female'], true)) {
            $fieldErrors['gender'] = "Please select a valid gender.";
        }

        // Address
        $addrRegex = '/^[A-Za-z0-9][A-Za-z0-9\s\'.,#\-\/&()]*$/';
        $addrValidations = [
            'street'   => ['Purok/Street',  $street],
            'barangay' => ['Barangay',       $barangay],
            'city'     => ['Municipal/City', $city],
            'province' => ['Province',       $province],
            'country'  => ['Country',        $country],
        ];
        foreach ($addrValidations as $key => [$label, $val]) {
            if (empty($val)) {
                $fieldErrors[$key] = "$label is required.";
            } elseif (!preg_match($addrRegex, $val)) {
                $fieldErrors[$key] = "$label contains invalid characters.";
            }
        }

        // Zip
        if (empty($zip)) {
            $fieldErrors['zip'] = "Zip Code is required.";
        } elseif (!preg_match('/^\d{4,6}$/', $zip)) {
            $fieldErrors['zip'] = "Zip Code must be between 4 and 6 digits.";
        }

        // Username
        if (empty($username)) {
            $fieldErrors['username'] = "Username is required.";
        } elseif (preg_match('/\s/', $username)) {
            $fieldErrors['username'] = "Username cannot contain spaces.";
        } elseif (preg_match('/([a-zA-Z])\1\1/i', $username)) {
            $fieldErrors['username'] = "Username cannot contain 3 identical letters in a row.";
        } elseif ($username !== $existing['username'] && $this->userModel->usernameExists($username)) {
            $fieldErrors['username'] = "Username is already in use by another user.";
        }

        // Email
        if (empty($email)) {
            $fieldErrors['email'] = "Email is required.";
        } elseif (preg_match('/\s/', $email)) {
            $fieldErrors['email'] = "Email cannot contain spaces.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $fieldErrors['email'] = "Invalid email format.";
        } elseif ($email !== $existing['email'] && $this->userModel->emailExists($email)) {
            $fieldErrors['email'] = "Email is already registered to another user.";
        }

        // Password (optional on update)
        if (!empty($password)) {
            $hasLower   = preg_match('/[a-z]/', $password);
            $hasUpper   = preg_match('/[A-Z]/', $password);
            $hasNumber  = preg_match('/[0-9]/', $password);
            $hasSpecial = preg_match('/[^a-zA-Z0-9]/', $password);
            $hasLength  = strlen($password) >= 8;
            if (!$hasLower || !$hasUpper || !$hasNumber || !$hasSpecial || !$hasLength) {
                $missing = [];
                if (!$hasLower)   $missing[] = "lowercase letter";
                if (!$hasUpper)   $missing[] = "uppercase letter";
                if (!$hasNumber)  $missing[] = "number";
                if (!$hasSpecial) $missing[] = "special character";
                if (!$hasLength)  $missing[] = "8+ characters";
                $fieldErrors['password'] = "Password too weak. Missing: " . implode(", ", $missing);
            } elseif (preg_match('/([a-zA-Z])\1\1/i', $password)) {
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
                'middle_name' => $middleName,
                'last_name'   => $lastName,
                'extension'   => $extension,
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
                $this->userModel->logAuditAction($_SESSION['user_id'] ?? null, $_SESSION['username'] ?? 'superadmin', 'superadmin', 'Edit User', "Edited User: {$username}");
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update user account.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    public function toggleBlockUser()
    {
        $authState = $this->requireSuperAdmin();
        header('Content-Type: application/json; charset=utf-8');
        $this->requireActorPassword($authState);

        $id_number  = trim($_POST['id_number'] ?? '');
        $new_status = trim($_POST['status'] ?? '');
        $reason     = trim($_POST['reason'] ?? '');
        $ip         = $_SERVER['REMOTE_ADDR'] ?? '';
        $operator   = $_SESSION['username'] ?? 'superadmin';

        if (empty($id_number) || !in_array($new_status, ['active', 'blocked', 'block'], true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters provided.']);
            exit;
        }
        if (in_array($new_status, ['blocked', 'block'], true) && $reason === '') {
            echo json_encode(['success' => false, 'message' => 'A reason is required when blocking an account.']);
            exit;
        }

        $updated = $this->userModel->toggleStandardUserStatus($id_number, $new_status, $operator, $reason, $ip);
        if ($updated) {
            $actionText = in_array($new_status, ['block', 'blocked'], true) ? 'blocked' : 'unblocked';
            echo json_encode(['success' => true, 'message' => "User account has been {$actionText}."]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update user status.']);
        }
        exit;
    }

    public function deleteStandardUser()
    {
        $authState = $this->requireSuperAdmin();
        header('Content-Type: application/json; charset=utf-8');
        $this->requireActorPassword($authState);

        $id_number = trim($_POST['id_number'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        if (empty($id_number)) {
            echo json_encode(['success' => false, 'message' => 'ID Number is required.']);
            exit;
        }


        $target = $this->userModel->getUserOrAdminByIdNumber($id_number);
        if (!$target || strtolower($target['role'] ?? '') !== 'user') {
            echo json_encode(['success' => false, 'message' => 'User account not found.']);
            exit;
        }
        if ($reason === '') {
            echo json_encode(['success' => false, 'message' => 'A reason is required when deleting an account.']);
            exit;
        }

        $deleted = $this->userModel->deleteStandardUser($id_number, $_SESSION['username'] ?? 'superadmin');
        if ($deleted) {
            echo json_encode(['success' => true, 'message' => 'User account is now Inactive. The record was preserved.']);
            $this->userModel->logAuditAction(
                $_SESSION['user_id'] ?? null,
                $_SESSION['username'] ?? 'superadmin',
                'superadmin',
                'Deactivate User',
                "Directly deactivated user {$target['username']} (ID: {$id_number}). Status changed from {$target['status']} to Inactive; record preserved."
            );
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to deactivate user account or it is already inactive.']);
        }
        exit;
    }

    /* ========================== BLOCK LIST ACTIONS ======================== */

    public function getBlockList()
    {
        $this->requireSuperAdmin();
        header('Content-Type: application/json; charset=utf-8');

        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? 'blocked'); // Default filter is 'blocked'
        $roleFilter = trim($_GET['role_filter'] ?? $_GET['role'] ?? 'all');
        if (!in_array($roleFilter, ['all', 'admin', 'user'], true)) {
            $roleFilter = 'all';
        }
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = in_array((int)($_GET['limit'] ?? 10), [10, 25, 50, 100], true) ? (int)$_GET['limit'] : 10;

        $totalRecords = $this->userModel->getBlockListCount($search, $status, $roleFilter);
        $totalPages   = max(1, (int)ceil($totalRecords / $limit));
        if ($page > $totalPages && $totalPages > 0) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $limit;

        $records = $this->userModel->getBlockList($search, $status, $roleFilter, $offset, $limit);

        echo json_encode([
            'success'      => true,
            'data'         => $records,
            'totalRecords' => $totalRecords,
            'totalPages'   => $totalPages,
            'currentPage'  => $page,
            'limit'        => $limit
        ]);
        exit;
    }

    public function getBlockDetail()
    {
        $this->requireSuperAdmin();
        header('Content-Type: application/json; charset=utf-8');

        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Valid block record ID is required.']);
            exit;
        }

        $detail = $this->userModel->getBlockDetail($id);
        if ($detail) {
            echo json_encode(['success' => true, 'data' => $detail]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Block list record not found.']);
        }
        exit;
    }

    public function unblockAccount()
    {
        $authState = $this->requireSuperAdmin();
        header('Content-Type: application/json; charset=utf-8');
        $this->requireActorPassword($authState);

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid block record ID.']);
            exit;
        }

        $blockedAccount = $this->userModel->getBlockDetail($id);
        if ($blockedAccount && ($blockedAccount['role'] ?? '') === 'superadmin' && $this->userModel->hasOtherActiveSuperAdmin((string)($blockedAccount['id_number'] ?? ''))) {
            echo json_encode(['success' => false, 'message' => 'Queued Super Admins are activated automatically when the active Super Admin logs out.']);
            exit;
        }

        $adminUsername = $_SESSION['username'] ?? 'superadmin';
        $adminRole     = $_SESSION['role'] ?? 'superadmin';

        $unblocked = $this->userModel->unblockAccount($id, $adminUsername, $adminRole);
        if ($unblocked) {
            echo json_encode(['success' => true, 'message' => 'Account has been unblocked successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to unblock account.']);
        }
        exit;
    }

    /* ========================== AUDIT LOGS ACTIONS ======================== */

    public function getAuditLogs()
    {
        header('Content-Type: application/json; charset=utf-8');

        // Super Admin sees everything; Admin access is privilege-gated below
        $authState = $this->validateLiveSession(['superadmin', 'admin']);
        $isSuperAdmin = strtolower($authState['role']) === 'superadmin';

        $search    = trim($_GET['search'] ?? '');
        $action    = trim($_GET['action_filter'] ?? $_GET['action'] ?? 'all');
        $role      = trim($_GET['role_filter'] ?? $_GET['role'] ?? 'all');
        $startDate = trim($_GET['start_date'] ?? '');
        $endDate   = trim($_GET['end_date'] ?? '');
        $month     = trim($_GET['month'] ?? 'all');
        $year      = trim($_GET['year'] ?? 'all');
        $page      = max(1, (int)($_GET['page'] ?? 1));
        $limit     = in_array((int)($_GET['limit'] ?? 10), [10, 25, 50, 100], true) ? (int)$_GET['limit'] : 10;

        $rolesIn = [];
        if (!$isSuperAdmin) {
            // Determine which role logs this admin is allowed to see
            $canUserLogs  = $this->userModel->hasAdminPrivilege($authState['id_number'], 'view_user_logs');
            $canAdminLogs = $this->userModel->hasAdminPrivilege($authState['id_number'], 'view_admin_logs');

            if (!$canUserLogs && !$canAdminLogs) {
                echo json_encode([
                    'success'      => true,
                    'restricted'   => true,
                    'message'      => 'You do not have the required audit log privileges.',
                    'data'         => [],
                    'totalRecords' => 0,
                    'totalPages'   => 1,
                    'currentPage'  => 1,
                    'limit'        => $limit
                ]);
                exit;
            }

            // Build the allowed roles set — superadmin is NEVER included on the Admin side
            $allowedRoles = [];
            if ($canUserLogs)  $allowedRoles[] = 'user';
            if ($canAdminLogs) $allowedRoles[] = 'admin';

            // If the caller passed a role filter, honour it only if it is within the allowed set
            $roleLower = strtolower(trim($role));
            if ($roleLower !== 'all' && in_array($roleLower, $allowedRoles, true)) {
                // Narrow to the requested role only
                $rolesIn = [$roleLower];
            } else {
                // Show all allowed roles (never superadmin)
                $rolesIn = $allowedRoles;
            }
            // Reset $role so the model's elseif branch is not hit; $rolesIn takes full control
            $role = 'all';
        }

        $totalRecords = $this->userModel->getAuditLogsCount($search, $action, $role, $startDate, $endDate, $rolesIn, $month, $year);
        $totalPages   = max(1, (int)ceil($totalRecords / $limit));
        if ($page > $totalPages && $totalPages > 0) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $limit;

        $records = $this->userModel->getAuditLogs($search, $action, $role, $startDate, $endDate, $offset, $limit, $rolesIn, $month, $year);

        echo json_encode([
            'success'      => true,
            'data'         => $records,
            'totalRecords' => $totalRecords,
            'totalPages'   => $totalPages,
            'currentPage'  => $page,
            'limit'        => $limit
        ]);
        exit;
    }

    /* ========================== USER PERSONAL LOGS ======================== */

    public function getMyLogs()
    {
        header('Content-Type: application/json; charset=utf-8');
        $authState = $this->validateLiveSession(['user', 'admin', 'superadmin']);

        $search    = trim($_GET['search'] ?? '');
        $startDate = trim($_GET['start_date'] ?? '');
        $endDate   = trim($_GET['end_date'] ?? '');
        $month     = trim($_GET['month'] ?? 'all');
        $year      = trim($_GET['year'] ?? 'all');
        $page      = max(1, (int)($_GET['page'] ?? 1));
        $limit     = in_array((int)($_GET['limit'] ?? 10), [10, 25, 50, 100], true) ? (int)$_GET['limit'] : 10;

        $totalRecords = $this->userModel->getUserPersonalLogsCount(
            $authState['id_number'],
            $authState['username'],
            $search,
            $startDate,
            $endDate,
            $month,
            $year
        );

        $totalPages = max(1, (int)ceil($totalRecords / $limit));
        if ($page > $totalPages && $totalPages > 0) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $limit;

        $records = $this->userModel->getUserPersonalLogs(
            $authState['id_number'],
            $authState['username'],
            $search,
            $startDate,
            $endDate,
            $month,
            $year,
            $offset,
            $limit
        );

        echo json_encode([
            'success'      => true,
            'data'         => $records,
            'totalRecords' => $totalRecords,
            'totalPages'   => $totalPages,
            'currentPage'  => $page,
            'limit'        => $limit
        ]);
        exit;
    }

    /* ========================== REGISTRATION APPROVAL ACTIONS ======================== */

    public function getPendingRegistrations()
    {
        header('Content-Type: application/json; charset=utf-8');
        $authState = $this->validateLiveSession(['superadmin', 'admin']);

        if (strtolower($authState['role']) === 'admin') {
            if (!$this->userModel->hasAdminPrivilege($authState['id_number'], 'approve_registrations') &&
                !$this->userModel->hasAdminPrivilege($authState['id_number'], 'view_users')) {
                echo json_encode(['success' => false, 'message' => 'You do not have privilege to view pending registrations.']);
                exit;
            }
        }

        $search    = trim($_GET['search'] ?? '');
        $status    = trim($_GET['status'] ?? 'pending');
        $startDate = trim($_GET['start_date'] ?? '');
        $endDate   = trim($_GET['end_date'] ?? '');
        $month     = trim($_GET['month'] ?? 'all');
        $year      = trim($_GET['year'] ?? 'all');
        $page      = max(1, (int)($_GET['page'] ?? 1));
        $limit     = in_array((int)($_GET['limit'] ?? 10), [10, 25, 50, 100], true) ? (int)$_GET['limit'] : 10;

        $totalRecords = $this->userModel->getPendingRegistrationsCount($search, $startDate, $endDate, $month, $year, $status);
        $totalPages   = max(1, (int)ceil($totalRecords / $limit));
        if ($page > $totalPages && $totalPages > 0) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $limit;

        $records = $this->userModel->getPendingRegistrations($search, $startDate, $endDate, $month, $year, $status, $offset, $limit);

        echo json_encode([
            'success'      => true,
            'data'         => $records,
            'totalRecords' => $totalRecords,
            'totalPages'   => $totalPages,
            'currentPage'  => $page,
            'limit'        => $limit
        ]);
        exit;
    }

    public function approveRegistration()
    {
        header('Content-Type: application/json; charset=utf-8');
        $authState = $this->validateLiveSession(['superadmin', 'admin']);

        if (strtolower($authState['role']) === 'admin') {
            if (!$this->userModel->hasAdminPrivilege($authState['id_number'], 'approve_registrations') &&
                !$this->userModel->hasAdminPrivilege($authState['id_number'], 'edit_users')) {
                echo json_encode(['success' => false, 'message' => 'You do not have privilege to approve registrations.']);
                exit;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $this->requireActorPassword($authState);

        $id_number = trim($_POST['id_number'] ?? '');
        if (empty($id_number)) {
            echo json_encode(['success' => false, 'message' => 'ID Number is required.']);
            exit;
        }

        $result = $this->userModel->approveRegistration(
            $id_number,
            $authState['id_number'],
            $authState['username'],
            $authState['role']
        );

        echo json_encode($result);
        exit;
    }

    public function rejectRegistration()
    {
        header('Content-Type: application/json; charset=utf-8');
        $authState = $this->validateLiveSession(['superadmin', 'admin']);

        if (strtolower($authState['role']) === 'admin') {
            if (!$this->userModel->hasAdminPrivilege($authState['id_number'], 'approve_registrations') &&
                !$this->userModel->hasAdminPrivilege($authState['id_number'], 'block_users')) {
                echo json_encode(['success' => false, 'message' => 'You do not have privilege to reject registrations.']);
                exit;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $id_number = trim($_POST['id_number'] ?? '');
        $reason    = trim($_POST['reason'] ?? 'Registration rejected by administrator');
        if (empty($id_number)) {
            echo json_encode(['success' => false, 'message' => 'ID Number is required.']);
            exit;
        }

        $rejected = $this->userModel->rejectRegistration(
            $id_number,
            $reason,
            $authState['id_number'],
            $authState['username'],
            $authState['role']
        );

        if ($rejected) {
            echo json_encode(['success' => true, 'message' => 'Registration rejected successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to reject registration.']);
        }
        exit;
    }

    /* ========================== DELETE REQUEST ACTIONS (SUPER ADMIN) ======================== */

    public function getDeleteRequests()
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->requireSuperAdmin();

        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? 'pending');
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = in_array((int)($_GET['limit'] ?? 10), [10, 25, 50, 100], true) ? (int)$_GET['limit'] : 10;

        $totalRecords = $this->userModel->getDeleteRequestsCount($search, $status);
        $totalPages   = max(1, (int)ceil($totalRecords / $limit));
        if ($page > $totalPages && $totalPages > 0) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $limit;

        $records = $this->userModel->getDeleteRequests($search, $status, $offset, $limit);

        echo json_encode([
            'success'      => true,
            'data'         => $records,
            'totalRecords' => $totalRecords,
            'totalPages'   => $totalPages,
            'currentPage'  => $page,
            'limit'        => $limit
        ]);
        exit;
    }

    public function approveDeleteRequest()
    {
        header('Content-Type: application/json; charset=utf-8');
        $authState = $this->requireSuperAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }
        $this->requireActorPassword($authState);

        $requestId = (int)($_POST['request_id'] ?? 0);
        if ($requestId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Valid request ID is required.']);
            exit;
        }

        $result = $this->userModel->approveDeleteRequest(
            $requestId,
            $_SESSION['user_id'],
            $_SESSION['username'] ?? 'superadmin'
        );

        echo json_encode($result);
        exit;
    }

    public function rejectDeleteRequest()
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->requireSuperAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $requestId = (int)($_POST['request_id'] ?? 0);
        $notes     = trim($_POST['notes'] ?? 'Rejected by Super Admin');
        if ($requestId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Valid request ID is required.']);
            exit;
        }

        $result = $this->userModel->rejectDeleteRequest(
            $requestId,
            $notes,
            $_SESSION['user_id'],
            $_SESSION['username'] ?? 'superadmin'
        );

        echo json_encode($result);
        exit;
    }

    /* ========================== SUPER ADMIN: ROLE CHANGE & PRIVILEGES ======================== */

    public function changeRole()
    {
        $authState = $this->requireSuperAdmin();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $this->requireActorPassword($authState);

        $id_number = trim($_POST['id_number'] ?? '');
        $newRole   = strtolower(trim($_POST['new_role'] ?? ''));

        if (empty($id_number) || !in_array($newRole, ['user', 'admin', 'superadmin'], true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters provided.']);
            exit;
        }

        $currentId = $_SESSION['user_id'] ?? '';
        if ($id_number === $currentId && $newRole !== 'superadmin') {
            echo json_encode([
                'success' => false,
                'message' => 'The active Super Admin cannot be demoted. Use Logout to perform the Super Admin handoff.'
            ]);
            exit;
        }

        // Prevent changing the role of another super admin through this flow
        // (super admin accounts are not listed on the management pages).
        $target = $this->userModel->getUserOrAdminByIdNumber($id_number);
        if ($target && strtolower($target['role']) === 'superadmin' && $id_number !== $currentId) {
            echo json_encode(['success' => false, 'message' => 'Super Admin accounts cannot be changed from this page.']);
            exit;
        }

        $result = $this->userModel->changeRole(
            $id_number,
            $newRole,
            $_SESSION['user_id'],
            $_SESSION['username'] ?? 'superadmin'
        );

        // If the change resulted in the operator losing Super Admin privileges,
        // destroy their session so they are forced to log in again. This is the
        // "auto-logout" requirement when an Admin is promoted to Super Admin.
        if (!empty($result['auto_logout']) && $result['auto_logout'] === true) {
            // Record the logout in the audit log
            $this->userModel->updateActiveLoginToLogout(
                $_SESSION['username'] ?? null,
                $_SESSION['user_id'] ?? null,
                null,
                'Auto-logged out: Super Admin role was transferred to another account.'
            );

            // Destroy the session
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
            session_unset();
            session_destroy();

            $result['auto_logout'] = true;
            $result['logout_redirect'] = '../../php/auth/index.php?action=login&reason=role_transferred';
        }

        echo json_encode($result);
        exit;
    }

    public function getAdminPrivileges()
    {
        $this->requireSuperAdmin();
        header('Content-Type: application/json; charset=utf-8');

        $id_number = trim($_GET['id_number'] ?? $_POST['id_number'] ?? '');
        if (empty($id_number)) {
            echo json_encode(['success' => false, 'message' => 'ID Number is required.']);
            exit;
        }

        $privileges = $this->userModel->getAdminPrivileges($id_number);
        echo json_encode(['success' => true, 'privileges' => $privileges]);
        exit;
    }

    public function saveAdminPrivileges()
    {
        $authState = $this->requireSuperAdmin();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $this->requireActorPassword($authState);

        $id_number = trim($_POST['id_number'] ?? '');
        if (empty($id_number)) {
            echo json_encode(['success' => false, 'message' => 'ID Number is required.']);
            exit;
        }

        // Only role='admin' accounts can hold privileges
        $target = $this->userModel->getUserOrAdminByIdNumber($id_number);
        if (!$target || strtolower($target['role']) !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Privileges can only be assigned to Admin accounts.']);
            exit;
        }

        $submitted = $_POST['privileges'] ?? [];
        if (!is_array($submitted)) {
            $submitted = json_decode((string)$submitted, true) ?: [];
        }
        $granted = array_values(array_intersect(array_keys(User::PRIVILEGES), array_map('strval', $submitted)));

        $oldPrivileges = $this->userModel->getAdminPrivileges($id_number);
        $saved = $this->userModel->saveAdminPrivileges($id_number, $granted);

        if ($saved) {
            $added   = array_values(array_diff($granted, $oldPrivileges));
            $removed = array_values(array_diff($oldPrivileges, $granted));

            $details = "Changed By: {$_SESSION['username']} | Target Admin: {$target['username']}";
            if (!empty($added))   $details .= " | Granted: " . implode(', ', array_map(fn($k) => User::PRIVILEGES[$k], $added));
            if (!empty($removed)) $details .= " | Removed: " . implode(', ', array_map(fn($k) => User::PRIVILEGES[$k], $removed));

            if (!empty($added)) {
                $this->userModel->logAuditAction($_SESSION['user_id'], $_SESSION['username'], 'superadmin', 'Assign Privilege', $details);
            }
            if (!empty($removed)) {
                $this->userModel->logAuditAction($_SESSION['user_id'], $_SESSION['username'], 'superadmin', 'Remove Privilege', $details);
            }

            echo json_encode(['success' => true, 'message' => 'Admin privileges updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save admin privileges.']);
        }
        exit;
    }

    /* ========================== UNIFIED ACCOUNT MANAGEMENT ======================== */

    public function getManagedAccounts()
    {
        header('Content-Type: application/json; charset=utf-8');
        $authState = $this->validateLiveSession(['superadmin', 'admin']);
        $isAdmin = strtolower($authState['role']) === 'admin';
        if ($isAdmin) {
            $this->requireAdminPrivilege('view_users', $authState['id_number']);
        }

        $search = trim($_GET['search'] ?? '');
        $status = strtolower(trim($_GET['status'] ?? 'all'));
        $role = strtolower(trim($_GET['role'] ?? 'all'));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = in_array((int)($_GET['limit'] ?? 10), [10, 25, 50, 100], true)
            ? (int)$_GET['limit'] : 10;
        $total = $this->userModel->getManagedAccountsCount($search, $status, $role, $isAdmin);
        $pages = max(1, (int)ceil($total / $limit));
        $page = min($page, $pages);
        $records = $this->userModel->getManagedAccounts(
            $search,
            $status,
            $role,
            ($page - 1) * $limit,
            $limit,
            $isAdmin
        );
        echo json_encode([
            'success' => true,
            'data' => $records,
            'totalRecords' => $total,
            'totalPages' => $pages,
            'currentPage' => $page,
            'limit' => $limit,
            'scope' => $isAdmin ? 'user' : 'all'
        ]);
        exit;
    }

    public function getManagedAccountDetail()
    {
        header('Content-Type: application/json; charset=utf-8');
        $authState = $this->validateLiveSession(['superadmin', 'admin']);
        $target = $this->userModel->getManagedAccountById(trim($_GET['id_number'] ?? ''));
        if (!$target) {
            echo json_encode(['success' => false, 'message' => 'Account not found.']);
            exit;
        }
        if (strtolower($authState['role']) === 'admin') {
            $this->requireAdminPrivilege('view_users', $authState['id_number']);
            if (strtolower($target['role']) !== 'user') {
                echo json_encode(['success' => false, 'message' => 'Admins may view User accounts only.']);
                exit;
            }
        }
        echo json_encode(['success' => true, 'data' => $target, 'availablePrivileges' => User::PRIVILEGES]);
        exit;
    }

    public function createManagedAccount()
    {
        header('Content-Type: application/json; charset=utf-8');
        $authState = $this->validateLiveSession(['superadmin', 'admin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }
        $isAdmin = strtolower($authState['role']) === 'admin';
        if ($isAdmin) {
            $this->requireAdminPrivilege('create_accounts', $authState['id_number']);
        }

        $idNumber = $this->userModel->generateIdNumber();
        $username = trim($_POST['username'] ?? '');
        $role = $isAdmin ? 'user' : strtolower(trim($_POST['role'] ?? 'user'));
        $errors = [];
        if (!preg_match('/^[A-Za-z0-9-]{4,20}$/', $idNumber)) {
            $errors['id_number'] = 'ID Number must be 4-20 letters, numbers, or hyphens.';
        } elseif ($this->userModel->findById($idNumber) || $this->userModel->pendingUserIdExists($idNumber)) {
            $errors['id_number'] = 'This ID Number is already registered.';
        }
        if (!preg_match('/^(?=.{3,50}$)[a-z]+(?:\.[a-z]+)+\d{2}$/i', $username) || preg_match('/(.)\1\1/i', $username)) {
            $errors['username'] = 'Username must follow this example format: juan.delacruz01.';
        } elseif ($this->userModel->usernameExists($username) || $this->userModel->pendingUsernameExists($username)) {
            $errors['username'] = 'This Username is already registered.';
        }
        if (!in_array($role, ['user', 'admin', 'superadmin'], true) || ($isAdmin && $role !== 'user')) {
            $errors['role'] = 'You are not allowed to create this account role.';
        }
        $rawPassword = trim((string)($_POST['password'] ?? ''));
        if ($rawPassword === '') {
            $rawPassword = '@Abcde12345';
        } else {
            if ($err = $this->passwordPolicyError($rawPassword)) {
                $errors['password'] = $err;
            }
        }
        if ($errors) {
            echo json_encode(['success' => false, 'message' => 'Please correct the highlighted fields.', 'fieldErrors' => $errors]);
            exit;
        }

        $submitted = $_POST['privileges'] ?? [];
        if (!is_array($submitted)) {
            $submitted = json_decode((string)$submitted, true) ?: [];
        }
        $result = $this->userModel->createManagedAccount([
            'id_number' => $idNumber,
            'username' => $username,
            'role' => $role,
            'password' => $rawPassword,
            'privileges' => $role === 'user' ? [] : array_map('strval', $submitted)
        ], $authState['id_number']);
        if ($result['success']) {
            $statusText = $role === 'superadmin'
                ? 'Pending claim (awaiting previous Super Admin logout & password change)'
                : 'Active';
            $this->userModel->logAuditAction(
                $authState['id_number'],
                $authState['username'],
                $authState['role'],
                'Create Account',
                "Created {$role} {$username} (ID: {$idNumber}). Status: {$statusText}. First-login password change required."
            );
            $result['message'] = "Account {$username} created successfully. Status: {$statusText}.";
            $result['role'] = $role;
        }
        echo json_encode($result);
        exit;
    }

    public function updateManagedAccount()
    {
        header('Content-Type: application/json; charset=utf-8');
        $authState = $this->validateLiveSession(['superadmin', 'admin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }
        $targetId = trim($_POST['id_number'] ?? '');
        $target = $this->userModel->getManagedAccountById($targetId);
        if (!$target) {
            echo json_encode(['success' => false, 'message' => 'Account not found.']);
            exit;
        }
        $isAdmin = strtolower($authState['role']) === 'admin';
        if ($isAdmin) {
            $this->requireAdminPrivilege('edit_users', $authState['id_number']);
            if (strtolower($target['role']) !== 'user') {
                echo json_encode(['success' => false, 'message' => 'Admins may edit User accounts only.']);
                exit;
            }
        }
        $this->requireActorPassword($authState);

        $username = trim($_POST['username'] ?? $target['username']);
        if ($username !== $target['username'] && (!preg_match('/^(?=.{3,50}$)[a-z]+(?:\.[a-z]+)+\d{2}$/i', $username) || preg_match('/(.)\1\1/i', $username))) {
            echo json_encode(['success' => false, 'message' => 'Username must follow this example format: juan.delacruz01.', 'fieldErrors' => ['username' => 'Username must follow this example format: juan.delacruz01.']]);
            exit;
        }
        if ($username !== $target['username'] && $this->userModel->usernameExists($username)) {
            echo json_encode(['success' => false, 'message' => 'Username is already registered.', 'fieldErrors' => ['username' => 'Username is already registered.']]);
            exit;
        }

        // Personal information is managed by the owner through Personal Details.
        $firstName = $target['first_name'] ?? '';
        $middleName = $target['middle_name'] ?? null;
        $lastName = $target['last_name'] ?? '';
        $email = $target['email'] ?? null;

        $role = $isAdmin ? 'user' : strtolower(trim($_POST['role'] ?? $target['role']));
        $status = $isAdmin ? $target['status'] : strtolower(trim($_POST['status'] ?? $target['status']));
        if (!in_array($role, ['user', 'admin', 'superadmin'], true) ||
            !in_array($status, ['active', 'blocked', 'pending_approval', 'pending_deletion', 'inactive'], true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid role or account status.']);
            exit;
        }
        if ($targetId === $authState['id_number'] && ($role !== 'superadmin' || $status !== 'active')) {
            echo json_encode(['success' => false, 'message' => 'Use Logout to rotate the active Super Admin account.']);
            exit;
        }
        $autoQueuedSuperAdmin = false;
        if ($role === 'superadmin' && $status === 'active' && $this->userModel->hasOtherActiveSuperAdmin($targetId)) {
            $status = 'inactive';
            $autoQueuedSuperAdmin = true;
        }
        $reason = trim($_POST['reason'] ?? '');
        if ($status === 'blocked' && $target['status'] !== 'blocked' && $reason === '' && !$autoQueuedSuperAdmin) {
            echo json_encode(['success' => false, 'message' => 'A reason is required when blocking an account.']);
            exit;
        }

        $newPassword = (string)($_POST['password'] ?? '');
        if ($newPassword !== '' && ($error = $this->passwordPolicyError($newPassword))) {
            echo json_encode(['success' => false, 'message' => $error, 'fieldErrors' => ['password' => $error]]);
            exit;
        }
        if ($newPassword !== '' && password_verify($newPassword, (string)($this->userModel->findById($targetId)['password_hash'] ?? ''))) {
            echo json_encode(['success' => false, 'message' => 'The replacement password must be different from the current password.', 'fieldErrors' => ['password' => 'The replacement password must be different from the current password.']]);
            exit;
        }
        $submitted = $_POST['privileges'] ?? [];
        if (!is_array($submitted)) {
            $submitted = json_decode((string)$submitted, true) ?: [];
        }
        $privileges = $isAdmin ? [] : array_values(array_intersect(array_keys(User::PRIVILEGES), array_map('strval', $submitted)));
        if (!$isAdmin && $role === 'superadmin') {
            $privileges = array_keys(User::PRIVILEGES);
        }
        $oldPrivileges = $target['privileges'] ?? [];
        sort($privileges);
        sort($oldPrivileges);
        $ok = $this->userModel->updateManagedAccount($targetId, [
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'extension' => $target['extension'] ?? null,
            'email' => $email,
            'username' => $username,
            'role' => $role,
            'status' => $status,
            'password' => $newPassword,
            'privileges' => $isAdmin ? $oldPrivileges : $privileges,
            'operator' => $authState['username'],
            'reason' => $reason ?: ($autoQueuedSuperAdmin ? 'Queued as an eligible Super Admin; oldest eligible account activates first.' : null)
        ]);
        if ($ok) {
            $changes = [];
            if ($role !== $target['role']) $changes[] = "role {$target['role']} -> {$role}";
            if ($status !== $target['status']) $changes[] = "status {$target['status']} -> {$status}";
            if (!$isAdmin && $privileges !== $oldPrivileges) $changes[] = 'assigned privileges updated';
            if ($newPassword !== '') $changes[] = 'password replaced; next-login change required';
            if ($reason !== '') $changes[] = "reason: {$reason}";
            if (!$changes) $changes[] = 'account profile updated';
            $auditAction = 'Edit Account';
            if ($role !== $target['role']) {
                $auditAction = 'Change Role';
            } elseif (!$isAdmin && $privileges !== $oldPrivileges) {
                $auditAction = 'Change Privileges';
            } elseif ($status !== $target['status']) {
                $auditAction = $status === 'blocked' ? 'Block Account' : ($status === 'active' ? 'Unblock Account' : 'Change Account Status');
            }
            $this->userModel->logAuditAction(
                $authState['id_number'], $authState['username'], $authState['role'],
                $auditAction, "Updated {$target['username']} (ID: {$targetId}): " . implode(', ', $changes)
            );
        }
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Account updated successfully.' : 'Unable to update the account.']);
        exit;
    }

    public function setManagedAccountStatus()
    {
        header('Content-Type: application/json; charset=utf-8');
        $authState = $this->validateLiveSession(['superadmin', 'admin']);
        $targetId = trim($_POST['id_number'] ?? '');
        $status = strtolower(trim($_POST['status'] ?? ''));
        $reason = trim($_POST['reason'] ?? '');
        $target = $this->userModel->getManagedAccountById($targetId);
        if (!$target || !in_array($status, ['active', 'blocked'], true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid account or status.']);
            exit;
        }
        $isAdmin = strtolower($authState['role']) === 'admin';
        if ($isAdmin) {
            $this->requireAdminPrivilege('block_users', $authState['id_number']);
            if (strtolower($target['role']) !== 'user') {
                echo json_encode(['success' => false, 'message' => 'Admins may block or unblock User accounts only.']);
                exit;
            }
        }
        $this->requireActorPassword($authState);
        if ($status === 'blocked' && $reason === '') {
            echo json_encode(['success' => false, 'message' => 'A reason is required when blocking an account.']);
            exit;
        }
        if ($targetId === $authState['id_number']) {
            echo json_encode(['success' => false, 'message' => 'You cannot block your own account.']);
            exit;
        }
        if ($target['role'] === 'superadmin' && $status === 'active' && $this->userModel->hasOtherActiveSuperAdmin($targetId)) {
            echo json_encode(['success' => false, 'message' => 'This queued Super Admin will be activated automatically when the current Super Admin logs out.']);
            exit;
        }
        $ok = $this->userModel->setManagedAccountStatus(
            $targetId, $status, $authState['username'], $authState['role'], $reason ?: null
        );
        if ($ok) {
            $verb = $status === 'blocked' ? 'Block Account' : 'Unblock Account';
            $details = "{$verb}: {$target['username']} (ID: {$targetId})";
            if ($reason !== '') $details .= " | Reason: {$reason}";
            $this->userModel->logAuditAction($authState['id_number'], $authState['username'], $authState['role'], $verb, $details);
        }
        echo json_encode(['success' => $ok, 'message' => $ok ? "Account {$status} successfully." : 'Unable to update account status.']);
        exit;
    }

    public function deleteManagedAccount()
    {
        header('Content-Type: application/json; charset=utf-8');
        $authState = $this->validateLiveSession(['superadmin', 'admin']);
        $targetId = trim($_POST['id_number'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        $target = $this->userModel->getManagedAccountById($targetId);
        if (!$target) {
            echo json_encode(['success' => false, 'message' => 'Account not found.']);
            exit;
        }
        $isAdmin = strtolower($authState['role']) === 'admin';
        if ($isAdmin) {
            $this->requireAdminPrivilege('delete_users', $authState['id_number']);
            if (strtolower($target['role']) !== 'user') {
                echo json_encode(['success' => false, 'message' => 'Admins may delete User accounts only.']);
                exit;
            }
        }
        $this->requireActorPassword($authState);
        if ($reason === '') {
            echo json_encode(['success' => false, 'message' => 'A reason is required when deleting an account.']);
            exit;
        }
        if ($targetId === $authState['id_number']) {
            echo json_encode(['success' => false, 'message' => 'You cannot delete your own account.']);
            exit;
        }
        $ok = $this->userModel->permanentlyDeleteManagedAccount($targetId);
        if ($ok) {
            $this->userModel->logAuditAction(
                $authState['id_number'], $authState['username'], $authState['role'],
                'Delete Account', "Permanently deleted {$target['username']} (ID: {$targetId}). Reason: {$reason}"
            );
        }
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Account permanently deleted.' : 'Unable to permanently delete the account.']);
        exit;
    }

    /* ========================== PERSONAL DETAILS ======================== */

    public function getPersonalDetails()
    {
        header('Content-Type: application/json; charset=utf-8');
        $authState = $this->validateLiveSession(['user', 'admin', 'superadmin']);
        $details = $this->userModel->getPersonalDetails($authState['id_number']);
        echo json_encode($details
            ? ['success' => true, 'data' => $details]
            : ['success' => false, 'message' => 'Account details were not found.']);
        exit;
    }

    public function updatePersonalDetails()
    {
        header('Content-Type: application/json; charset=utf-8');
        $authState = $this->validateLiveSession(['user', 'admin', 'superadmin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }
        $current = $this->userModel->getPersonalDetails($authState['id_number']);
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $birthdate = trim($_POST['birthdate'] ?? '');
        $gender = strtolower(trim($_POST['gender'] ?? ''));
        $username = trim($_POST['username'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $errors = [];
        if ($firstName === '') $errors['first_name'] = 'First Name is required.';
        if ($lastName === '') $errors['last_name'] = 'Last Name is required.';
        $dob = DateTime::createFromFormat('Y-m-d', $birthdate);
        if (!$dob || $dob > new DateTime()) {
            $errors['birthdate'] = 'Enter a valid Birthdate.';
            $age = 0;
        } else {
            $age = (new DateTime())->diff($dob)->y;
            if ($age < 18) $errors['birthdate'] = 'You must be at least 18 years old.';
        }
        if (!in_array($gender, ['male', 'female'], true)) $errors['gender'] = 'Select a valid Gender.';
        if ($username !== ($current['username'] ?? '') && (!preg_match('/^(?=.{3,50}$)[a-z]+(?:\.[a-z]+)+\d{2}$/i', $username) || preg_match('/(.)\1\1/i', $username))) {
            $errors['username'] = 'Username must follow this example format: juan.delacruz01.';
        } elseif ($username !== ($current['username'] ?? '') && $this->userModel->usernameExists($username)) {
            $errors['username'] = 'Username is already registered.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'A valid Email Address is required for OTP recovery.';
        } elseif ($email !== strtolower((string)($current['email'] ?? '')) && $this->userModel->emailExists($email)) {
            $errors['email'] = 'Email Address is already registered.';
        }
        foreach (['street' => 'Purok / Street', 'barangay' => 'Barangay', 'city' => 'Municipal / City', 'province' => 'Province', 'country' => 'Country'] as $field => $label) {
            if (trim($_POST[$field] ?? '') === '') $errors[$field] = "{$label} is required.";
        }
        if (!preg_match('/^\d{4,6}$/', trim($_POST['zip'] ?? ''))) $errors['zip'] = 'Zip Code must contain 4-6 digits.';
        $securityAnswers = [];
        $currentQuestions = $current['security_questions'] ?? [];
        $currentQuestionIds = array_map(static fn(array $question): int => (int)$question['question_id'], $currentQuestions);
        $hasAnyAnswer = false;
        $hasQuestionChanged = false;
        for ($i = 1; $i <= 3; $i++) {
            $questionId = (int)($_POST["security_question_{$i}"] ?? 0);
            $answer = trim($_POST["security_answer_{$i}"] ?? '');
            if ($answer !== '') {
                $hasAnyAnswer = true;
            }
            if ($questionId > 0 && $questionId !== ($currentQuestionIds[$i - 1] ?? 0)) {
                $hasQuestionChanged = true;
            }
            $securityAnswers[] = ['question_id' => $questionId, 'answer' => $answer];
        }
        $securityUpdateRequested = $hasAnyAnswer || $hasQuestionChanged;
        if ($securityUpdateRequested) {
            foreach ($securityAnswers as $index => $entry) {
                $number = $index + 1;
                if ($entry['question_id'] <= 0) {
                    $errors["security_question_{$number}"] = "Question {$number} is required.";
                }
                if ($entry['answer'] === '') {
                    $errors["security_answer_{$number}"] = "Answer {$number} is required.";
                } elseif (preg_match('/\s/', $entry['answer'])) {
                    $errors["security_answer_{$number}"] = "Answer cannot contain spaces.";
                }
            }
            $questionIds = array_column($securityAnswers, 'question_id');
            if (count(array_unique($questionIds)) !== 3) {
                $errors['security_question_1'] = 'Choose three different security questions.';
                $errors['security_question_2'] = 'Choose three different security questions.';
                $errors['security_question_3'] = 'Choose three different security questions.';
                $errors['security_questions'] = 'Choose three different security questions.';
            }
        }
        if ($errors) {
            echo json_encode(['success' => false, 'message' => 'Please correct the highlighted fields.', 'fieldErrors' => $errors]);
            exit;
        }
        $ok = $this->userModel->updatePersonalDetails($authState['id_number'], [
            'first_name' => $firstName,
            'middle_name' => trim($_POST['middle_name'] ?? '') ?: null,
            'last_name' => $lastName,
            'extension' => trim($_POST['extension'] ?? '') ?: null,
            'birthdate' => $birthdate,
            'gender' => $gender,
            'age' => $age,
            'username' => $username,
            'email' => $email,
            'contact_number' => $current['contact_number'] ?? null,
            'street' => trim($_POST['street'] ?? ''),
            'barangay' => trim($_POST['barangay'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'province' => trim($_POST['province'] ?? ''),
            'country' => trim($_POST['country'] ?? ''),
            'zip' => trim($_POST['zip'] ?? '')
        ]);
        if ($ok && $securityUpdateRequested) {
            $ok = $this->userModel->replaceSecurityAnswers($authState['id_number'], $securityAnswers);
        }
        if ($ok) {
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $this->userModel->logAuditAction(
                $authState['id_number'], $username, $authState['role'], 'Update Personal Details',
                'Account owner updated their own personal details after current-password verification.'
            );
        }
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Personal details saved successfully.' : 'Unable to save personal details.']);
        exit;
    }

    public function changeRequiredPassword()
    {
        header('Content-Type: application/json; charset=utf-8');
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $idNumber = (string)($_SESSION['user_id'] ?? '');
        $isClaiming = !empty($_SESSION['claiming_superadmin']);
        $authState = $idNumber !== '' ? $this->userModel->getAccountAuthState($idNumber) : null;
        if (!$authState || (!in_array($authState['status'], ['active', 'pending_deletion'], true) && !$isClaiming)) {
            echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
            exit;
        }
        if (empty($authState['must_change_password'])) {
            echo json_encode(['success' => false, 'message' => 'A password change is not currently required.']);
            exit;
        }
        $password = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');
        if ($password !== $confirm) {
            echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
            exit;
        }
        if ($error = $this->passwordPolicyError($password)) {
            echo json_encode(['success' => false, 'message' => $error]);
            exit;
        }
        $existing = $this->userModel->findById($idNumber);
        if ($existing && password_verify($password, $existing['password_hash'])) {
            echo json_encode(['success' => false, 'message' => 'The new password must be different from the default/current password.']);
            exit;
        }
        $ok = $this->userModel->updatePassword($idNumber, password_hash($password, PASSWORD_DEFAULT));
        if ($ok) {
            if ($isClaiming || strtolower($authState['role'] ?? '') === 'superadmin') {
                $this->userModel->finalizeSuperAdminClaim($idNumber);
                unset($_SESSION['claiming_superadmin']);
            }
            $fresh = $this->userModel->getAccountAuthState($idNumber);
            $_SESSION['session_version'] = (int)($fresh['session_version'] ?? 0);
            $_SESSION['role'] = $fresh['role'] ?? 'superadmin';
            $this->userModel->logAuditAction($idNumber, $authState['username'], $authState['role'], 'Change Default Password', 'Required first-login password change completed. Super Admin role activated.');
        }
        $redirect = 'index.php?action=dashboard';
        if (strtolower($authState['role'] ?? '') === 'superadmin' || $isClaiming) $redirect = '../super_admin/dashboard.php';
        elseif (strtolower($authState['role'] ?? '') === 'admin') $redirect = '../admin/dashboard.php';
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Password changed successfully.' : 'Unable to change password.', 'redirect' => $redirect]);
        exit;
    }
}


