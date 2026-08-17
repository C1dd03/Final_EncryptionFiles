<?php

/**
 * SMTP configuration for the OTP mailer (models/Mailer.php).
 *
 * Paste your SMTP credentials here (Mailtrap or Gmail App Password):
 *
 *  - Mailtrap (dev inbox):   host = sandbox.smtp.mailtrap.io, port = 587,
 *                            username/password from your Mailtrap inbox page,
 *                            encryption = 'tls'
 *  - Gmail App Password:     host = smtp.gmail.com, port = 587, encryption = 'tls',
 *                            username = your Gmail address,
 *                            password = the 16-char App Password (enable 2FA first),
 *                            from_email = the same Gmail address
 *
 * While 'host' is empty, no real email is sent: the OTP is written to
 * logs/otp.log and (when 'dev_show_otp' is true) shown on screen so the
 * full flow can be tested without a mail server.
 */
return [
    'host'         => 'smtp.gmail.com',        // e.g. 'smtp.gmail.com' or 'sandbox.smtp.mailtrap.io'
    'port'         => 587,       // 587 for STARTTLS, 2525 for Mailtrap plain
    'username'     => 'jerwil.umpad4456@gmail.com',        // SMTP username (usually the email address)
    'password'     => 'tqpc norg lbae nuyc',        // SMTP password / app password
    'encryption'   => 'tls',     // 'tls' (STARTTLS) or 'none'
    'from_email'   => 'jerwil.umpad4456@gmail.com',
    'from_name'    => 'ArgiConnect',
    'app_env'      => 'dev',     // 'dev' or 'production'
    'dev_show_otp' => true,      // dev only: reveal the OTP in the UI when sending is unavailable
];
