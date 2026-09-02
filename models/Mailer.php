<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

/** Reusable PHPMailer SMTP adapter for authentication emails. */
class Mailer
{
    private array $config;

    public function __construct()
    {
        $this->config = is_file(__DIR__ . '/../config/mail.php')
            ? require __DIR__ . '/../config/mail.php'
            : [];
    }

    /** @return array{sent: bool, logged: bool} */
    public function send(string $to, string $subject, string $htmlBody): array
    {
        $host = trim((string)($this->config['host'] ?? ''));
        if ($host === '') {
            return $this->developmentFallback($to, $subject, $htmlBody);
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->Port = (int)($this->config['port'] ?? 587);
            $mail->Timeout = (int)($this->config['timeout'] ?? 15);
            $mail->CharSet = PHPMailer::CHARSET_UTF8;

            $username = trim((string)($this->config['username'] ?? ''));
            $password = (string)($this->config['password'] ?? '');
            $mail->SMTPAuth = $username !== '';
            if ($mail->SMTPAuth) {
                $mail->Username = $username;
                $mail->Password = $password;
            }

            $encryption = strtolower(trim((string)($this->config['encryption'] ?? 'tls')));
            if ($encryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif (in_array($encryption, ['ssl', 'smtps'], true)) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = '';
                $mail->SMTPAutoTLS = false;
            }

            $fromEmail = trim((string)($this->config['from_email'] ?? ''));
            $fromName = trim((string)($this->config['from_name'] ?? 'ArgiConnect'));
            if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Invalid sender or recipient email address.');
            }

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($htmlBody))));
            $mail->send();

            return ['sent' => true, 'logged' => false];
        } catch (PHPMailerException | RuntimeException $e) {
            error_log('PHPMailer error: ' . $e->getMessage());
            return $this->developmentFallback($to, $subject, $htmlBody);
        }
    }

    /** OTP logging is disabled unless explicitly enabled for development. */
    private function developmentFallback(string $to, string $subject, string $htmlBody): array
    {
        $isDevelopment = strtolower((string)($this->config['app_env'] ?? 'production')) === 'development';
        $allowLog = !empty($this->config['dev_log_otp']);
        if (!$isDevelopment || !$allowLog) {
            return ['sent' => false, 'logged' => false];
        }

        $logDirectory = __DIR__ . '/../logs';
        if (!is_dir($logDirectory) && !mkdir($logDirectory, 0775, true) && !is_dir($logDirectory)) {
            return ['sent' => false, 'logged' => false];
        }

        $plainBody = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($htmlBody))));
        $entry = sprintf(
            "[%s] TO: %s | SUBJECT: %s\n%s\n%s\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            $plainBody,
            str_repeat('-', 60)
        );
        $logged = file_put_contents($logDirectory . '/otp.log', $entry, FILE_APPEND | LOCK_EX) !== false;

        return ['sent' => false, 'logged' => $logged];
    }
}
