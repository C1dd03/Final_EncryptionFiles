<?php
/**
 * Mailer — dependency-free SMTP client used to deliver OTP verification emails.
 *
 * Falls back to writing the message (including the OTP) to logs/otp.log when
 * SMTP is not configured or the send fails, so the auth flows stay testable
 * during development.
 */

class Mailer
{
    private $config;

    public function __construct()
    {
        $this->config = is_file(__DIR__ . '/../config/mail.php')
            ? require __DIR__ . '/../config/mail.php'
            : [];
    }

    /**
     * Send an HTML email over SMTP.
     *
     * @return array ['sent' => bool, 'logged' => bool]
     */
    public function send(string $to, string $subject, string $htmlBody): array
    {
        $host = trim($this->config['host'] ?? '');

        // No SMTP configured -> dev fallback: log the message locally.
        if ($host === '') {
            $this->logEmail($to, $subject, $htmlBody);
            return ['sent' => false, 'logged' => true];
        }

        try {
            $this->smtpSend($to, $subject, $htmlBody);
            return ['sent' => true, 'logged' => false];
        } catch (Exception $e) {
            error_log('Mailer error: ' . $e->getMessage());
            $this->logEmail($to, $subject, $htmlBody);
            return ['sent' => false, 'logged' => true];
        }
    }

    /**
     * Write the email body to logs/otp.log so the OTP is recoverable in dev.
     */
    private function logEmail(string $to, string $subject, string $htmlBody): void
    {
        try {
            $dir = __DIR__ . '/../logs';
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $plain = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
            $entry = sprintf(
                "[%s] TO: %s | SUBJECT: %s\n%s\n%s\n",
                date('Y-m-d H:i:s'),
                $to,
                $subject,
                $plain,
                str_repeat('-', 60)
            );
            @file_put_contents($dir . '/otp.log', $entry, FILE_APPEND);
        } catch (Exception $e) {
            error_log('Failed to log OTP email: ' . $e->getMessage());
        }
    }

    /**
     * Raw SMTP conversation. Supports STARTTLS + AUTH LOGIN.
     *
     * @throws RuntimeException on any protocol error.
     */
    private function smtpSend(string $to, string $subject, string $htmlBody): void
    {
        $host       = trim($this->config['host'] ?? '');
        $port       = (int)($this->config['port'] ?? 587);
        $username   = trim($this->config['username'] ?? '');
        $password   = (string)($this->config['password'] ?? '');
        $encryption = strtolower(trim($this->config['encryption'] ?? 'tls'));
        $fromEmail  = trim($this->config['from_email'] ?? 'no-reply@localhost');
        $fromName   = trim($this->config['from_name'] ?? '');

        if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Invalid recipient or sender email address.');
        }

        $socket = @fsockopen($host, $port, $errno, $errstr, 15);
        if (!$socket) {
            throw new RuntimeException("Could not connect to SMTP server {$host}:{$port} ({$errno}: {$errstr})");
        }
        stream_set_timeout($socket, 15);

        $this->expect($socket, 220, 'Server greeting');

        $this->command($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        $this->expect($socket, 250, 'EHLO');

        if ($encryption === 'tls') {
            $this->command($socket, "STARTTLS");
            $this->expect($socket, 220, 'STARTTLS');
            $ok = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if (!$ok) {
                fclose($socket);
                throw new RuntimeException('STARTTLS negotiation failed.');
            }
            $this->command($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
            $this->expect($socket, 250, 'EHLO after STARTTLS');
        }

        if ($username !== '') {
            $this->command($socket, "AUTH LOGIN");
            $this->expect($socket, 334, 'AUTH LOGIN');
            $this->command($socket, base64_encode($username));
            $this->expect($socket, 334, 'AUTH username');
            $this->command($socket, base64_encode($password));
            $this->expect($socket, 235, 'AUTH password');
        }

        $this->command($socket, "MAIL FROM:<{$fromEmail}>");
        $this->expect($socket, 250, 'MAIL FROM');
        $this->command($socket, "RCPT TO:<{$to}>");
        $this->expect($socket, 250, 'RCPT TO');

        $this->command($socket, "DATA");
        $this->expect($socket, 354, 'DATA');

        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $headers = [
            "From: " . ($fromName !== '' ? "{$fromName} <{$fromEmail}>" : $fromEmail),
            "To: <{$to}>",
            "Subject: {$encodedSubject}",
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset=UTF-8",
            "Content-Transfer-Encoding: base64",
        ];

        $body = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($htmlBody), 76, "\r\n");

        // End-of-data marker: a lone dot on its own line.
        $body = preg_replace('/^\./m', '..', $body);
        $this->command($socket, $body . "\r\n.");
        $this->expect($socket, 250, 'Message accepted');

        $this->command($socket, "QUIT");
        fclose($socket);
    }

    private function command($socket, string $line): void
    {
        fwrite($socket, $line . "\r\n");
    }

    /**
     * Read SMTP responses until the expected code is found.
     *
     * @throws RuntimeException when a 4xx/5xx code arrives or the code never matches.
     */
    private function expect($socket, int $expectedCode, string $context): void
    {
        $response = '';
        $sawError = false;
        while (($line = fgets($socket, 1024)) !== false) {
            $response .= $line;
            $code = (int)substr($line, 0, 3);
            $more = isset($line[3]) && $line[3] === '-';

            if ($code >= 400) {
                $sawError = true;
            }
            if (!$more) {
                break;
            }
        }

        if ($sawError || (int)substr($response, 0, 3) !== $expectedCode) {
            throw new RuntimeException("SMTP {$context} failed. Server responded: " . trim($response));
        }
    }
}
