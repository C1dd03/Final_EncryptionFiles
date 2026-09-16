<?php
require_once __DIR__ . '/../models/Mailer.php';

trait InvitationController
{
    public function sendAccountInvitationEmail(string $email, string $username, string $password): bool
    {
        $safeUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $safePassword = htmlspecialchars($password, ENT_QUOTES, 'UTF-8');
        $result = (new Mailer())->send($email, 'Your account invitation',
            "<h2>Complete your account setup</h2><p>You have been invited to create an account.</p>
            <p>Username: <strong>{$safeUsername}</strong><br>Temporary password: <strong>{$safePassword}</strong></p>
            <p>Open the application's Login page and enter these credentials. Complete your personal details, address,
            and security questions, and choose a new password before accessing your account.</p>
            <p>This invitation expires in 7 days. If you did not expect it, contact the administrator.</p>");
        return !empty($result['sent']);
    }

    public function resendAccountInvitation(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $actor = $this->validateLiveSession(['superadmin','admin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success'=>false, 'message'=>'Invalid request method.']);
            return;
        }
        $this->requireActorPassword($actor);
        echo json_encode($this->userModel->resendAccountInvitation(trim($_POST['id_number'] ?? ''), $actor['id_number'], [$this, 'sendAccountInvitationEmail']));
    }

    public function invitationSetup(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $setup = $_SESSION['invitation_setup'] ?? [];
        $invitation = !empty($setup['id']) ? $this->userModel->getPendingRegistrationById($setup['id']) : null;
        if (!$invitation || ($setup['expires'] ?? 0) < time() || $invitation['status'] !== 'pending' ||
            $invitation['invitation_state'] !== 'awaiting_setup' || strtotime($invitation['invitation_expires_at'] ?? '') <= time() ||
            !hash_equals($invitation['password_hash'], (string)($setup['hash'] ?? ''))) {
            unset($_SESSION['invitation_setup'], $_SESSION['invitation_csrf']);
            header('Location: index.php?action=login');
            return;
        }
        $errors = [];
        $completed = false;
        $message = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!hash_equals((string)($_SESSION['invitation_csrf'] ?? ''), (string)($_POST['csrf'] ?? '')) || empty($_SESSION['invitation_csrf'])) {
                $errors[] = 'Your form session is invalid. Reload the page and try again.';
            }
            $data = [];
            $lengths = ['first_name'=>50,'middle_name'=>50,'last_name'=>50,'extension'=>10,'birthdate'=>10,'gender'=>6,
                'contact_number'=>20,'street'=>100,'barangay'=>100,'city'=>100,'province'=>100,'country'=>100,'zip'=>10];
            foreach ($lengths as $key=>$max) {
                $data[$key] = trim((string)($_POST[$key] ?? ''));
                if (strlen($data[$key]) > $max || (!in_array($key, ['middle_name','extension'], true) && $data[$key] === '')) {
                    $errors[] = ucfirst(str_replace('_', ' ', $key)) . " is required and must be at most {$max} characters.";
                }
            }
            foreach (['first_name','middle_name','last_name'] as $key) {
                $errors = array_merge($errors, $this->validateName($data[$key], ucfirst(str_replace('_', ' ', $key))));
            }
            foreach (['barangay','city','province','country'] as $key) {
                $errors = array_merge($errors, $this->validateAddressField($data[$key], ucfirst($key)));
            }
            $birth = DateTimeImmutable::createFromFormat('!Y-m-d', $data['birthdate']);
            if (!$birth || $birth->format('Y-m-d') !== $data['birthdate'] || $birth > new DateTimeImmutable('-18 years') || $birth < new DateTimeImmutable('-120 years')) {
                $errors[] = 'Enter a valid birthdate. You must be at least 18 years old.';
            }
            if (!in_array($data['gender'], ['male','female'], true)) $errors[] = 'Select a valid gender.';
            if (!preg_match('/^\+?[0-9 ()-]{7,20}$/', $data['contact_number'])) $errors[] = 'Enter a valid contact number.';
            if ($data['extension'] !== '' && !preg_match('/^(Jr\.?|Sr\.?|I|II|III|IV|V|VI|VII|VIII|IX|X)$/i', $data['extension'])) $errors[] = 'Select a valid name extension.';
            if (strcasecmp(trim($_POST['email'] ?? ''), $invitation['email']) !== 0) $errors[] = 'Use the email address to which you were invited.';
            $data['password'] = (string)($_POST['password'] ?? '');
            if (strlen($data['password']) > 72) $errors[] = 'Password must be at most 72 bytes.';
            if ($error = $this->passwordPolicyError($data['password'])) $errors[] = $error;
            if ($data['password'] !== ($_POST['confirm_password'] ?? '')) $errors[] = 'Passwords do not match.';
            if (password_verify($data['password'], $invitation['password_hash'])) $errors[] = 'Choose a new password different from the temporary password.';
            $data['security_answers'] = [];
            for ($i=1; $i<=3; $i++) {
                $qid = (int)($_POST["security_question_{$i}"] ?? 0);
                $answer = trim((string)($_POST["security_answer_{$i}"] ?? ''));
                if ($qid < ($i-1)*3+1 || $qid > $i*3 || $answer === '' || strlen($answer)>72) {
                    $errors[] = "Select security question {$i} and enter an answer of 1–72 bytes.";
                }
                $data['security_answers'][] = ['question_id'=>$qid,'answer'=>$answer];
            }
            if (!$errors) {
                $result = $this->userModel->completeAccountInvitation($setup['id'], $setup['hash'], $data);
                if ($result['success']) {
                    $completed = true;
                    $message = $result['message'];
                    unset($_SESSION['invitation_setup'], $_SESSION['invitation_csrf']);
                    session_regenerate_id(true);
                } else $errors[] = $result['message'];
            }
        }
        require __DIR__ . '/../php/auth/invitation_setup.php';
    }
}
