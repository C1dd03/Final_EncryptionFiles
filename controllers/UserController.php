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

            // Check for spaces in username
            if (preg_match('/\s/', $_POST['username'] ?? '')) {
                $errors[] = "Username cannot contain spaces.";
            }

            // Check for double spaces in username
            if (preg_match('/\s{2,}/', $_POST['username'] ?? '')) {
                $errors[] = "Username cannot contain double spaces.";
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

            if (preg_match('/([a-zA-Z])\1\1/i', $username)) {
                $errors[] = "Username cannot contain 3 identical letters in a row.";
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
                'password'    => $password, // hashed once in User::insertUser
                'status'      => 'pending' // account activates after email OTP verification
            ];

            // --- INSERT INTO DATABASE (account starts as 'pending' until the email OTP is verified) ---
            $result = $this->userModel->insertUser($data);
            if ($result) {
                $registeredId = $result; // This is the generated ID number

                // Send the 6-digit verification code to the registered email
                $otp = new Otp();
                $issue = $otp->issue($data['email'], $result, 'register');

                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['otp_pending'] = [
                    'purpose'   => 'register',
                    'email'     => $data['email'],
                    'id_number' => $result
                ];

                $showOtpStep    = true;
                $otpMaskedEmail = $otp->maskEmail($data['email']);
                $otpIssueError  = $issue['success'] ? null : $issue['message'];
                $devOtp         = $issue['dev_otp'] ?? null;
                $otpExpiresIn   = $issue['expires_in'] ?? Otp::CODE_LIFETIME;
                $otpCooldown    = $issue['cooldown'] ?? Otp::RESEND_COOLDOWN;

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

            // Username wrong
            if (!$user) {
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

            // Blocked accounts cannot log in
            if ($userStatus !== 'active') {
                echo json_encode(['success' => false, 'message' => 'Your account has been blocked. Please contact the Super Admin.', 'errorType' => 'accountBlocked']);
                return;
            }

            // No email on file -> cannot deliver an OTP
            if (empty($user['email'])) {
                echo json_encode(['success' => false, 'message' => 'No email address is linked to this account. Please contact the Super Admin.', 'errorType' => 'noEmail']);
                return;
            }

            // Credentials are valid -> require a 6-digit OTP before creating the session
            $otp = new Otp();
            $issue = $otp->issue($user['email'], $user['id_number'], 'login');
            if (!$issue['success']) {
                echo json_encode([
                    'success'   => false,
                    'message'   => $issue['message'],
                    'errorType' => 'otpSendFailed',
                    'cooldown'  => $issue['cooldown'] ?? null,
                    'dev_otp'   => $issue['dev_otp'] ?? null
                ]);
                return;
            }

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['otp_pending'] = [
                'purpose'         => 'login',
                'email'           => $user['email'],
                'id_number'       => $user['id_number'],
                'username'        => $user['username'],
                'role'            => $user['role'] ?? 'user',
                'session_version' => (int)($user['session_version'] ?? 0)
            ];

            echo json_encode([
                'success'    => true,
                'needOtp'    => true,
                'email'      => $otp->maskEmail($user['email']),
                'expires_in' => $issue['expires_in'] ?? Otp::CODE_LIFETIME,
                'cooldown'   => $issue['cooldown'] ?? Otp::RESEND_COOLDOWN,
                'dev_otp'    => $issue['dev_otp'] ?? null
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

        // Re-fetch the account: it must still exist and be active.
        $user = $this->userModel->findByUsername($pending['username']);
        if (!$user || ($user['status'] ?? 'active') !== 'active') {
            unset($_SESSION['otp_pending']);
            echo json_encode(['success' => false, 'message' => 'Your account is no longer active. Please contact the Super Admin.', 'errorType' => 'accountBlocked']);
            exit;
        }

        $_SESSION['user_id'] = $user['id_number'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = strtolower($user['role'] ?? 'user');
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

        echo json_encode(['success' => true, 'redirect' => $redirectUrl]);
        exit;
    }






    //========================================== Verify Forgot-Password Email =====================================
    public function verifyForgotEmail()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $email = strtolower(trim($_POST['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
            exit;
        }

        $user = $this->userModel->findByEmail($email);
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'No account found with that email address.']);
            exit;
        }

        if (($user['status'] ?? 'active') === 'block') {
            echo json_encode(['success' => false, 'message' => 'This account is blocked. Please contact the Super Admin.']);
            exit;
        }

        $questions = $this->userModel->getUserAuthAnswers($user['id_number']);
        if (empty($questions)) {
            echo json_encode(['success' => false, 'message' => 'No security questions are set for this account.']);
            exit;
        }

        // Never send the answer hashes to the browser — only the question text.
        $safeQuestions = array_map(function ($q) {
            return [
                'question_id'   => $q['question_id'],
                'question_text' => $q['question_text']
            ];
        }, $questions);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['otp_pending'] = [
            'purpose'   => 'forgot_password',
            'email'     => $email,
            'id_number' => $user['id_number']
        ];

        echo json_encode([
            'success'   => true,
            'user'      => [
                'email'    => $email,
                'username' => $user['username'],
                'id_number' => $user['id_number']
            ],
            'questions' => $safeQuestions
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

        $email = strtolower(trim($_POST['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
            exit;
        }

        $user = $this->userModel->findByEmail($email);
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'No account found with that email address.']);
            exit;
        }

        if (($user['status'] ?? 'active') === 'block') {
            echo json_encode(['success' => false, 'message' => 'This account is blocked. Please contact the Super Admin.']);
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
        $_SESSION['otp_pending'] = [
            'purpose'   => 'forgot_password',
            'email'     => $email,
            'id_number' => $user['id_number']
        ];

        echo json_encode([
            'success'    => true,
            'message'    => $issue['message'],
            'email'      => $otp->maskEmail($email),
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

        $otp = new Otp();
        $result = $otp->verify($pending['email'], 'forgot_password', trim($_POST['otp'] ?? ''));
        if (!$result['success']) {
            echo json_encode(['success' => false, 'message' => $result['message']]);
            exit;
        }

        $_SESSION['otp_pending']['verified'] = true;
        echo json_encode(['success' => true, 'message' => 'Code verified! You can now set a new password.']);
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
        $issue = $otp->resend($pending['email'], $pending['purpose']);
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
            $user = $this->userModel->findById($id_number);

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

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_number = trim($_POST['id_number'] ?? '');

            // Fall back to the pending forgot-password account from the session.
            if ($id_number === '') {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $id_number = trim($_SESSION['otp_pending']['id_number'] ?? '');
            }

            // Validate ID number is provided
            if (empty($id_number)) {
                echo json_encode(['success' => false, 'message' => 'ID Number is required.']);
                exit;
            }

            // Get the user's actual security questions and answers
            $userQuestions = $this->userModel->getUserAuthAnswers($id_number);

            if (empty($userQuestions)) {
                echo json_encode(['success' => false, 'message' => 'No security questions found for this user.']);
                exit;
            }

            // Map the user's questions to their answers from the form
            $answers = [
                trim($_POST['security_answer_1'] ?? ''),
                trim($_POST['security_answer_2'] ?? ''),
                trim($_POST['security_answer_3'] ?? '')
            ];

            // Validate all answers are provided
            $emptyAnswers = [];
            foreach ($answers as $index => $ans) {
                if (empty($ans)) {
                    $emptyAnswers[] = $index + 1;
                }
            }

            if (!empty($emptyAnswers)) {
                echo json_encode(['success' => false, 'message' => 'Please answer all security questions.']);
                exit;
            }

            $correctCount = 0;

            // Verify each answer against the corresponding user question
            for ($i = 0; $i < min(count($userQuestions), count($answers)); $i++) {
                $record = $userQuestions[$i];
                $answer = $answers[$i];

                if (password_verify($answer, $record['answer_hash'])) {
                    $correctCount++;
                }
            }

            // User must answer at least 2 out of 3 questions correctly
            if ($correctCount >= 2) {
                echo json_encode(['success' => true, 'message' => 'Verification successful! You answered at least 2 questions correctly.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Verification failed. You must answer at least 2 out of 3 questions correctly.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        }
        exit;
    }


    //========================================== Validate Individual Security Answer =====================================
    public function validateSecurityAnswer()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_number = trim($_POST['id_number'] ?? '');
            $question_index = (int)($_POST['question_id'] ?? 0); // This is now the index (1, 2, 3) rather than question_id
            $answer = trim($_POST['answer'] ?? '');

            // Fall back to the pending forgot-password account from the session.
            if ($id_number === '') {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $id_number = trim($_SESSION['otp_pending']['id_number'] ?? '');
            }

            // Validate required fields
            if (empty($id_number)) {
                echo json_encode(['valid' => false, 'message' => 'ID Number is required.']);
                exit;
            }

            if (empty($question_index) || $question_index < 1 || $question_index > 3) {
                echo json_encode(['valid' => false, 'message' => 'Invalid question index.']);
                exit;
            }

            if (empty($answer)) {
                echo json_encode(['valid' => false, 'message' => 'Answer is required.']);
                exit;
            }

            // Get the user's actual security questions
            $userQuestions = $this->userModel->getUserAuthAnswers($id_number);

            if (empty($userQuestions) || !isset($userQuestions[$question_index - 1])) {
                echo json_encode(['valid' => false, 'message' => 'No answer found for this question.']);
                exit;
            }

            // Get the stored answer hash for the specific question
            $record = $userQuestions[$question_index - 1];

            // Verify the answer
            $isValid = password_verify($answer, $record['answer_hash']);

            echo json_encode(['valid' => $isValid]);
        } else {
            echo json_encode(['valid' => false, 'message' => 'Invalid request method.']);
        }
        exit;
    }



    //=========================================== Reset Password =======================================
    public function resetPassword()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $pending = $_SESSION['otp_pending'] ?? null;
        if (!$pending || ($pending['purpose'] ?? '') !== 'forgot_password' || empty($pending['verified'])) {
            echo json_encode(['success' => false, 'message' => 'OTP verification required. Please start the password reset again.']);
            return;
        }

        $id_number = $pending['id_number'];
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($new_password !== $confirm_password) {
            echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
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

        $hashed = password_hash($new_password, PASSWORD_BCRYPT);
        if ($this->userModel->updatePassword($id_number, $hashed)) {
            unset($_SESSION['otp_pending']);
            echo json_encode(['success' => true, 'message' => 'Your password has been successfully changed!']);
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
        $this->validateLiveSession(['superadmin']);
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
            } elseif ($this->userModel->usernameExists($username)) {
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
            } elseif ($this->userModel->emailExists($email)) {
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
            $this->userModel->logAuditAction($_SESSION['user_id'] ?? null, $_SESSION['username'] ?? 'superadmin', 'superadmin', 'Create Admin', "Created Admin: {$username}");
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to create admin: ' . $e->getMessage()]);
        }
        exit;
    }

    public function updateAdmin()
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
        $this->requireSuperAdmin();
        header('Content-Type: application/json; charset=utf-8');

        $id_number  = trim($_POST['id_number'] ?? '');
        $new_status = trim($_POST['status'] ?? '');
        $reason     = trim($_POST['reason'] ?? '');
        $ip         = $_SERVER['REMOTE_ADDR'] ?? '';
        $operator   = $_SESSION['username'] ?? 'superadmin';

        if (empty($id_number) || !in_array($new_status, ['active', 'block'], true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters provided.']);
            exit;
        }

        // Protect the currently logged-in Super Admin from self-blocking
        if (strtolower($operator) === 'superadmin' && $id_number === ($_SESSION['user_id'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'You cannot block your own account.']);
            exit;
        }

        $updated = $this->userModel->toggleAdminStatus($id_number, $new_status, $operator, $reason, $ip);
        if ($updated) {
            $actionText = ($new_status === 'block') ? 'blocked' : 'unblocked';
            echo json_encode(['success' => true, 'message' => "Admin account has been {$actionText}."]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update admin status.']);
        }
        exit;
    }

    public function deleteAdmin()
    {
        $this->requireSuperAdmin();
        header('Content-Type: application/json; charset=utf-8');

        $id_number = trim($_POST['id_number'] ?? '');
        if (empty($id_number)) {
            echo json_encode(['success' => false, 'message' => 'ID Number is required.']);
            exit;
        }

        $deleted = $this->userModel->deleteAdmin($id_number);
        if ($deleted) {
            echo json_encode(['success' => true, 'message' => 'Admin account deleted successfully.']);
            $this->userModel->logAuditAction($_SESSION['user_id'] ?? null, $_SESSION['username'] ?? 'superadmin', 'superadmin', 'Delete Admin', "Deleted Admin ID: {$id_number}");
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete admin account.']);
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
        $this->requireSuperAdmin();
        header('Content-Type: application/json; charset=utf-8');

        $id_number  = trim($_POST['id_number'] ?? '');
        $new_status = trim($_POST['status'] ?? '');
        $reason     = trim($_POST['reason'] ?? '');
        $ip         = $_SERVER['REMOTE_ADDR'] ?? '';
        $operator   = $_SESSION['username'] ?? 'superadmin';

        if (empty($id_number) || !in_array($new_status, ['active', 'block'], true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters provided.']);
            exit;
        }

        $updated = $this->userModel->toggleStandardUserStatus($id_number, $new_status, $operator, $reason, $ip);
        if ($updated) {
            $actionText = ($new_status === 'block') ? 'blocked' : 'unblocked';
            echo json_encode(['success' => true, 'message' => "User account has been {$actionText}."]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update user status.']);
        }
        exit;
    }

    public function deleteStandardUser()
    {
        $this->requireSuperAdmin();
        header('Content-Type: application/json; charset=utf-8');

        $id_number = trim($_POST['id_number'] ?? '');
        if (empty($id_number)) {
            echo json_encode(['success' => false, 'message' => 'ID Number is required.']);
            exit;
        }

        $deleted = $this->userModel->deleteStandardUser($id_number);
        if ($deleted) {
            echo json_encode(['success' => true, 'message' => 'User account deleted successfully.']);
            $this->userModel->logAuditAction($_SESSION['user_id'] ?? null, $_SESSION['username'] ?? 'superadmin', 'superadmin', 'Delete User', "Deleted User ID: {$id_number}");
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete user account.']);
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
        $this->requireSuperAdmin();
        header('Content-Type: application/json; charset=utf-8');

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid block record ID.']);
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
        $startDate = trim($_GET['start_date'] ?? '');
        $endDate   = trim($_GET['end_date'] ?? '');
        $month     = trim($_GET['month'] ?? 'all');
        $year      = trim($_GET['year'] ?? 'all');
        $page      = max(1, (int)($_GET['page'] ?? 1));
        $limit     = in_array((int)($_GET['limit'] ?? 10), [10, 25, 50, 100], true) ? (int)$_GET['limit'] : 10;

        $totalRecords = $this->userModel->getPendingRegistrationsCount($search, $startDate, $endDate, $month, $year);
        $totalPages   = max(1, (int)ceil($totalRecords / $limit));
        if ($page > $totalPages && $totalPages > 0) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $limit;

        $records = $this->userModel->getPendingRegistrations($search, $startDate, $endDate, $month, $year, $offset, $limit);

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

        $id_number = trim($_POST['id_number'] ?? '');
        if (empty($id_number)) {
            echo json_encode(['success' => false, 'message' => 'ID Number is required.']);
            exit;
        }

        $approved = $this->userModel->approveRegistration(
            $id_number,
            $authState['id_number'],
            $authState['username'],
            $authState['role']
        );

        if ($approved) {
            echo json_encode(['success' => true, 'message' => 'Registration approved successfully. Account is now active.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to approve registration. Record may have already been approved or removed.']);
        }
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
            echo json_encode(['success' => true, 'message' => 'Registration rejected and removed.']);
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
        $this->requireSuperAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

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
        $this->requireSuperAdmin();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $id_number = trim($_POST['id_number'] ?? '');
        $newRole   = strtolower(trim($_POST['new_role'] ?? ''));
        $confirmSelf = ($_POST['confirm_self'] ?? '') === 'true';

        if (empty($id_number) || !in_array($newRole, ['user', 'admin', 'superadmin'], true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters provided.']);
            exit;
        }

        // Protect the currently logged-in Super Admin from self-demotion
        // unless a deliberate confirmation was provided.
        $currentId = $_SESSION['user_id'] ?? '';
        if ($id_number === $currentId && $newRole !== 'superadmin') {
            if (!$confirmSelf) {
                echo json_encode([
                    'success'      => false,
                    'confirmation' => true,
                    'message'      => 'You are about to change your own role. This will remove your Super Admin access. Confirm to continue.'
                ]);
                exit;
            }
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
        $this->requireSuperAdmin();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

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
}


