<?php

/**
 * SMTP configuration for PHPMailer.
 *
 * Keep credentials outside source control. For XAMPP/Apache, define these
 * with SetEnv directives in Apache's httpd-vhosts.conf (or as Windows
 * environment variables), then restart Apache.
 */
$localConfigFile = __DIR__ . '/smtp.local.php';
$localConfig = is_file($localConfigFile) ? require $localConfigFile : [];
if (!is_array($localConfig)) {
    $localConfig = [];
}

$env = static function (string $key, $default = '') use ($localConfig) {
    $value = getenv($key);
    if ($value === false && array_key_exists($key, $_ENV)) {
        $value = $_ENV[$key];
    }
    if ($value === false && array_key_exists($key, $_SERVER)) {
        $value = $_SERVER[$key];
    }
    if (($value === false || $value === '') && array_key_exists($key, $localConfig)) {
        $value = $localConfig[$key];
    }
    return $value === false || $value === '' ? $default : $value;
};

$envBool = static function (string $key, bool $default = false) use ($env): bool {
    return filter_var($env($key, $default ? 'true' : 'false'), FILTER_VALIDATE_BOOL);
};

return [
    'host'         => $env('SMTP_HOST'),
    'port'         => (int)$env('SMTP_PORT', '587'),
    'username'     => $env('SMTP_USERNAME'),
    'password'     => $env('SMTP_PASSWORD'),
    'encryption'   => $env('SMTP_ENCRYPTION', 'tls'),
    'from_email'   => $env('SMTP_FROM_EMAIL'),
    'from_name'    => $env('SMTP_FROM_NAME', 'ArgiConnect'),
    'timeout'      => (int)$env('SMTP_TIMEOUT', '15'),
    'app_env'      => $env('APP_ENV', 'production'),
    'dev_log_otp'  => $envBool('OTP_DEV_LOG', false),
    'dev_show_otp' => $envBool('OTP_DEV_SHOW', false),
];
