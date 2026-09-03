<form class="forgot-password" id="forgotForm" novalidate>
    <h2 class="text-xl font-semibold mb-4 text-center">Forgot Password</h2>

    <div class="forgot-pass" aria-label="Password recovery progress">
        <div class="number active">1</div><div class="line"></div>
        <div class="number">2</div><div class="line"></div>
        <div class="number">3</div><div class="line"></div>
        <div class="number">4</div>
    </div>
    <div class="title-container-forgot-pass">
        <div class="title">ID Number</div><div class="title">OTP</div>
        <div class="title">Security Question</div><div class="title">New Password</div>
    </div>

    <div class="forgot-account-summary" id="forgotAccountSummary" hidden>
        <p>ID Number: <strong id="maskedForgotId">-</strong></p>
        <p>Email Address: <strong id="maskedForgotEmail">-</strong></p>
    </div>

    <section class="step step-1 active" aria-labelledby="forgotStep1Title">
        <span id="forgotStep1Title">Enter your registered ID Number</span>
        <div class="input-field password-field" style="margin:3px;">
            <input type="password" name="id_number" id="forgotIdInput" placeholder=" " autocomplete="off" />
            <label>ID Number</label>
            <i class="fas fa-eye-slash toggle-password"></i>
        </div>
        <p class="message-error" id="idError" aria-live="polite"></p>
        <button type="button" class="btn next-btn" id="forgotStartBtn">Send OTP &gt;</button>
    </section>

    <section class="step step-2" aria-labelledby="forgotStep2Title">
        <span id="forgotStep2Title">OTP Verification</span>
        <div class="input-field" style="margin:3px;">
            <input type="text" name="otp" id="forgotOtpInput" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" placeholder=" " />
            <label>6-digit OTP</label>
        </div>
        <p class="message-error" id="otpError" aria-live="polite"></p>
        <p class="otp-timer" id="forgotOtpExpiry"></p>
        <p class="otp-timer" id="forgotOtpResendTimer"></p>
        <p class="otp-resend">Didn't receive the code? <a href="#" id="forgotResendOtp" style="display:none;">Resend OTP</a></p>
        <div class="dev-otp-banner" id="forgotOtpDevBanner" style="display:none;"></div>
        <div class="flex justify-between mt-5">
            <button type="button" class="btn prev-btn" data-back="1">&lt; Start Over</button>
            <button type="button" class="btn next-btn" id="forgotVerifyOtpBtn">Verify OTP &gt;</button>
        </div>
    </section>

    <section class="step step-3" aria-labelledby="forgotStep3Title">
        <span id="forgotStep3Title">Security Question</span>
        <p class="forgot-help">This is one of the questions selected during registration.</p>
        <div class="input-field static-label" style="margin:3px;">
            <label for="forgotSecurityQuestion">Security Question</label>
            <select id="forgotSecurityQuestion" name="question_id" required></select>
        </div>
        <div class="input-field password-field" style="margin:12px 3px 3px;">
            <input type="password" id="forgotSecurityAnswer" name="security_answer" placeholder=" " autocomplete="off" />
            <label>Security Answer</label>
            <i class="fas fa-eye-slash toggle-password"></i>
        </div>
        <p class="message-error" id="securityError" aria-live="polite"></p>
        <button type="button" class="btn next-btn" id="forgotVerifySecurityBtn">Verify Answer &gt;</button>
    </section>

    <section class="step step-4" aria-labelledby="forgotStep4Title">
        <span id="forgotStep4Title">Change Password</span>
        <div class="pass-input-field">
            <input type="password" name="new_password" id="newPassword" placeholder=" " autocomplete="new-password" />
            <label>New Password</label>
            <i class="fas fa-eye-slash toggle-password"></i>
        </div>
        <div class="password-strength-container"><div class="password-strength" id="passwordStrengthBar"></div></div>
        <div id="passwordStrengthMessage" style="visibility:hidden;"></div>
        <div class="input-field password-field" style="margin-top:14px;">
            <input type="password" name="confirm_password" id="confirmPassword" placeholder=" " autocomplete="new-password" />
            <label>Confirm New Password</label>
            <i class="fas fa-eye-slash toggle-password"></i>
        </div>
        <p class="message-error" id="passwordError" aria-live="polite"></p>
        <button type="submit" class="btn_submit" id="forgotPasswordSubmit">Change Password</button>
    </section>
</form>

<p class="toggle-link" style="margin-top:15px;">&larr; Back to <a href="index.php?action=login"><b>Login</b></a></p>

<style>
    .forgot-password .forgot-pass{display:flex;align-items:center;margin:8px 0}
    .forgot-password .title-container-forgot-pass{display:grid;grid-template-columns:repeat(4,1fr);gap:6px;text-align:center;margin-bottom:18px}
    .forgot-password .forgot-account-summary p{margin:4px 0;color:#555;font-size:13px}
    .forgot-password .otp-timer,.forgot-password .otp-resend,.forgot-help{text-align:center;font-size:12px;color:#6b7280}
    .forgot-password .dev-otp-banner{background:#fef9c3;color:#854d0e;font-size:12px;text-align:center;padding:8px;border-radius:6px;margin-top:8px}
</style>
