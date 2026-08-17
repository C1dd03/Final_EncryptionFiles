<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/Mailer.php';

/**
 * OTP service for Register / Login / Forgot Password flows.
 *
 * - 6-digit numeric codes
 * - stored hashed (password_hash) in the `otp_codes` table
 * - expire after 5 minutes
 * - max 5 failed attempts, then the code is invalidated
 * - rate-limited: 60s resend cooldown, max 5 sends per email+purpose per 10 minutes
 */

class Otp
{
    public const PURPOSES      = ['register', 'login', 'forgot_password'];
    public const CODE_LIFETIME = 300;   // seconds a code stays valid (5 min)
    public const RESEND_COOLDOWN = 60;  // seconds between sends
    public const MAX_ATTEMPTS  = 5;     // failed attempts before invalidation
    public const MAX_SENDS     = 5;     // max sends per window
    public const SEND_WINDOW   = 600;   // rate-limit window (10 min)

    private $conn;
    private $mailer;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
        $this->ensureOtpSchema();
        $this->mailer = new Mailer();
    }

    /**
     * Creates the otp_codes table and adds the 'pending' status to users.status
     * (used by the registration OTP activation flow). Safe to run on every request.
     */
    public function ensureOtpSchema(): void
    {
        try {
            $stmt = $this->conn->query("SHOW TABLES LIKE 'otp_codes'");
            if (!$stmt->fetch()) {
                $this->conn->exec(
                    "CREATE TABLE otp_codes (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        email VARCHAR(150) NOT NULL,
                        id_number VARCHAR(20) DEFAULT NULL,
                        purpose ENUM('register','login','forgot_password') NOT NULL,
                        code_hash VARCHAR(255) NOT NULL,
                        expires_at DATETIME NOT NULL,
                        attempts TINYINT NOT NULL DEFAULT 0,
                        used TINYINT(1) NOT NULL DEFAULT 0,
                        sent_at DATETIME NOT NULL,
                        consumed_at DATETIME DEFAULT NULL,
                        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        KEY idx_email_purpose (email, purpose),
                        KEY idx_expires (expires_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
                );
            }

            // Add 'pending' to users.status so unverified registrations cannot log in.
            $stmt = $this->conn->query("SHOW COLUMNS FROM users LIKE 'status'");
            $col = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($col && strpos($col['Type'], 'pending') === false) {
                $this->conn->exec(
                    "ALTER TABLE users MODIFY COLUMN status ENUM('block','pending','active') NOT NULL DEFAULT 'active'"
                );
            }
        } catch (Exception $e) {
            error_log('OTP schema sync warning: ' . $e->getMessage());
        }
    }

    /**
     * Generate a new OTP, store its hash, and email it to $email.
     *
     * @return array ['success' => bool, 'message' => string, 'expires_in' => int,
     *                'cooldown' => int, 'dev_otp' => ?string]
     */
    public function issue(string $email, ?string $idNumber, string $purpose): array
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email address.'];
        }
        if (!in_array($purpose, self::PURPOSES, true)) {
            return ['success' => false, 'message' => 'Invalid OTP purpose.'];
        }

        $this->purgeExpired($email, $purpose);

        // Rate limit: max sends within the window.
        $sentCount = $this->countRecentSends($email, $purpose);
        if ($sentCount >= self::MAX_SENDS) {
            $wait = $this->secondsUntilWindowReset($email, $purpose);
            return [
                'success' => false,
                'message' => 'Too many OTP requests. Please try again in about ' . max(1, (int)ceil($wait / 60)) . ' minute(s).',
                'cooldown' => (int)$wait
            ];
        }

        // Resend cooldown.
        $lastSentAt = $this->lastSentAt($email, $purpose);
        if ($lastSentAt !== null) {
            $elapsed = time() - $lastSentAt;
            if ($elapsed < self::RESEND_COOLDOWN) {
                return [
                    'success' => false,
                    'message' => 'Please wait ' . (self::RESEND_COOLDOWN - $elapsed) . ' second(s) before requesting another code.',
                    'cooldown' => self::RESEND_COOLDOWN - $elapsed
                ];
            }
        }

        $code = (string)random_int(100000, 999999);
        $now  = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', time() + self::CODE_LIFETIME);

        $stmt = $this->conn->prepare(
            "INSERT INTO otp_codes (email, id_number, purpose, code_hash, expires_at, sent_at)
             VALUES (:email, :id_number, :purpose, :code_hash, :expires_at, :sent_at)"
        );
        $stmt->execute([
            ':email'      => $email,
            ':id_number'  => $idNumber,
            ':purpose'    => $purpose,
            ':code_hash'  => password_hash($code, PASSWORD_DEFAULT),
            ':expires_at' => $expiresAt,
            ':sent_at'    => $now
        ]);

        $subject = 'Your ArgiConnect verification code';
        $body = $this->buildEmailBody($code, $purpose);

        $result = $this->mailer->send($email, $subject, $body);
        $devOtp = null;

        if (!$result['sent']) {
            $isDev = ($this->mailerConfig('app_env') ?? 'dev') === 'dev';
            if ($result['logged'] && $isDev && $this->mailerConfig('dev_show_otp')) {
                // Keep the flow testable without a mail server.
                return [
                    'success'    => true,
                    'message'    => 'Email not configured — code shown for development.',
                    'expires_in' => self::CODE_LIFETIME,
                    'cooldown'   => self::RESEND_COOLDOWN,
                    'dev_otp'    => $code
                ];
            }
            return [
                'success' => false,
                'message' => 'Could not send the verification email. Please check the SMTP settings in config/mail.php and try again.',
                'cooldown' => self::RESEND_COOLDOWN
            ];
        }

        return [
            'success'    => true,
            'message'    => "A 6-digit code was sent to {$this->maskEmail($email)}.",
            'expires_in' => self::CODE_LIFETIME,
            'cooldown'   => self::RESEND_COOLDOWN,
            'dev_otp'    => $devOtp
        ];
    }

    /**
     * Verify a submitted code against the latest valid record for email+purpose.
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function verify(string $email, string $purpose, string $code): array
    {
        $email = strtolower(trim($email));
        $code  = trim($code);

        if ($code === '' || !ctype_digit($code) || strlen($code) !== 6) {
            return ['success' => false, 'message' => 'Please enter the 6-digit code.'];
        }

        $stmt = $this->conn->prepare(
            "SELECT * FROM otp_codes
             WHERE email = :email AND purpose = :purpose AND used = 0 AND expires_at > NOW()
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([':email' => $email, ':purpose' => $purpose]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            return ['success' => false, 'message' => 'The code has expired. Please request a new one.'];
        }

        if ((int)$record['attempts'] >= self::MAX_ATTEMPTS) {
            $this->invalidate($record['id']);
            return ['success' => false, 'message' => 'Too many incorrect attempts. Please request a new code.'];
        }

        if (!password_verify($code, $record['code_hash'])) {
            $newAttempts = (int)$record['attempts'] + 1;
            if ($newAttempts >= self::MAX_ATTEMPTS) {
                $this->invalidate($record['id']);
                return ['success' => false, 'message' => 'Too many incorrect attempts. Please request a new code.'];
            }
            $this->conn->prepare("UPDATE otp_codes SET attempts = :attempts WHERE id = :id")
                ->execute([':attempts' => $newAttempts, ':id' => $record['id']]);
            $remaining = self::MAX_ATTEMPTS - $newAttempts;
            return [
                'success' => false,
                'message' => 'Incorrect code. ' . $remaining . ' attempt(s) remaining.'
            ];
        }

        // Success: mark used, consume, and invalidate any other live codes for this email+purpose.
        $this->conn->prepare(
            "UPDATE otp_codes SET used = 1, consumed_at = :consumed_at WHERE id = :id"
        )->execute([':consumed_at' => date('Y-m-d H:i:s'), ':id' => $record['id']]);
        $this->conn->prepare(
            "UPDATE otp_codes SET used = 1 WHERE email = :email AND purpose = :purpose AND used = 0"
        )->execute([':email' => $email, ':purpose' => $purpose]);

        return ['success' => true, 'message' => 'Code verified successfully.'];
    }

    /**
     * Send a fresh code for an existing flow, respecting the resend cooldown.
     */
    public function resend(string $email, string $purpose): array
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email address.'];
        }

        $lastSentAt = $this->lastSentAt($email, $purpose);
        if ($lastSentAt !== null) {
            $elapsed = time() - $lastSentAt;
            if ($elapsed < self::RESEND_COOLDOWN) {
                return [
                    'success' => false,
                    'message' => 'Please wait ' . (self::RESEND_COOLDOWN - $elapsed) . ' second(s) before resending.',
                    'cooldown' => self::RESEND_COOLDOWN - $elapsed
                ];
            }
        }

        // The previous code is now useless.
        $this->conn->prepare(
            "UPDATE otp_codes SET used = 1 WHERE email = :email AND purpose = :purpose AND used = 0"
        )->execute([':email' => $email, ':purpose' => $purpose]);

        return $this->issue($email, null, $purpose);
    }

    /* ---------------------------------- helpers ---------------------------------- */

    private function purgeExpired(string $email, string $purpose): void
    {
        $this->conn->prepare(
            "DELETE FROM otp_codes WHERE email = :email AND purpose = :purpose AND (expires_at <= NOW() OR used = 1)"
        )->execute([':email' => $email, ':purpose' => $purpose]);
    }

    private function countRecentSends(string $email, string $purpose): int
    {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM otp_codes
             WHERE email = :email AND purpose = :purpose
               AND sent_at >= DATE_SUB(NOW(), INTERVAL " . self::SEND_WINDOW . " SECOND)"
        );
        $stmt->execute([':email' => $email, ':purpose' => $purpose]);
        return (int)$stmt->fetchColumn();
    }

    private function secondsUntilWindowReset(string $email, string $purpose): int
    {
        $stmt = $this->conn->prepare(
            "SELECT sent_at FROM otp_codes
             WHERE email = :email AND purpose = :purpose
             ORDER BY sent_at ASC LIMIT 1"
        );
        $stmt->execute([':email' => $email, ':purpose' => $purpose]);
        $oldest = $stmt->fetchColumn();
        if (!$oldest) {
            return 0;
        }
        return max(0, (strtotime($oldest) + self::SEND_WINDOW) - time());
    }

    private function lastSentAt(string $email, string $purpose): ?int
    {
        $stmt = $this->conn->prepare(
            "SELECT sent_at FROM otp_codes
             WHERE email = :email AND purpose = :purpose
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([':email' => $email, ':purpose' => $purpose]);
        $sentAt = $stmt->fetchColumn();
        return $sentAt ? (int)strtotime($sentAt) : null;
    }

    private function invalidate(int $id): void
    {
        $this->conn->prepare("UPDATE otp_codes SET used = 1 WHERE id = :id")
            ->execute([':id' => $id]);
    }

    private function mailerConfig(string $key)
    {
        static $config = null;
        if ($config === null) {
            $config = is_file(__DIR__ . '/../config/mail.php') ? require __DIR__ . '/../config/mail.php' : [];
        }
        return $config[$key] ?? null;
    }

    public function maskEmail(string $email): string
    {
        $parts = explode('@', $email, 2);
        $local = $parts[0];
        $domain = $parts[1] ?? '';
        $visible = strlen($local) <= 2 ? $local[0] : substr($local, 0, 2);
        return $visible . str_repeat('*', max(1, strlen($local) - 2)) . '@' . $domain;
    }

    private function buildEmailBody(string $code, string $purpose): string
    {
        $label = [
            'register'        => 'complete your registration',
            'login'           => 'verify your login',
            'forgot_password' => 'reset your password'
        ][$purpose] ?? 'verify your identity';

        return "
        <div style=\"font-family:Arial,Helvetica,sans-serif;max-width:480px;margin:0 auto;padding:24px;border:1px solid #e5e7eb;border-radius:8px;\">
            <h2 style=\"color:#16a34a;margin:0 0 8px;\">ArgiConnect</h2>
            <p style=\"color:#374151;font-size:14px;\">Hello! Use the code below to {$label}:</p>
            <p style=\"font-size:32px;font-weight:bold;letter-spacing:8px;color:#111827;text-align:center;margin:24px 0;padding:12px;background:#f3f4f6;border-radius:6px;\">{$code}</p>
            <p style=\"color:#6b7280;font-size:12px;\">This code expires in 5 minutes. If you did not request it, you can safely ignore this email.</p>
        </div>";
    }
}
