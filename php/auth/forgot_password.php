<form class="forgot-password" id="forgotForm" novalidate>
    <h2 class="text-xl font-semibold mb-4 text-center">Forgot Password</h2>

    <div class="forgot-pass" aria-label="Password recovery progress">
        <div class="recovery-step">
            <div class="number active">1</div>
            <div class="title">ID Number</div>
            <div class="line" aria-hidden="true"></div>
        </div>
        <div class="recovery-step">
            <div class="number">2</div>
            <div class="title">OTP</div>
            <div class="line" aria-hidden="true"></div>
        </div>
        <div class="recovery-step">
            <div class="number">3</div>
            <div class="title">Security Questions</div>
            <div class="line" aria-hidden="true"></div>
        </div>
        <div class="recovery-step">
            <div class="number">4</div>
            <div class="title">New Password</div>
        </div>
    </div>

    <div class="forgot-account-summary" id="forgotAccountSummary" hidden>
        <p>ID Number: <strong id="maskedForgotId">-</strong></p>
        <p>Email Address: <strong id="maskedForgotEmail">-</strong></p>
    </div>

    <section class="step step-1 active" aria-labelledby="forgotStep1Title">
        <span id="forgotStep1Title">Enter your registered ID Number</span>
        <div class="input-field password-field" id="forgotIdField" style="margin:3px;">
            <input type="password" name="id_number" id="forgotIdInput" placeholder=" " autocomplete="off" inputmode="numeric" maxlength="9" pattern="[0-9]{4}-[0-9]{4}" aria-describedby="idError" />
            <label>ID Number</label>
            <i class="fas fa-eye-slash toggle-password" role="button" tabindex="0" aria-label="Show ID Number" aria-controls="forgotIdInput" aria-pressed="false"></i>
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
        <span id="forgotStep3Title">Security Questions</span>
        <p class="forgot-help">Select and answer all 3 security questions. At least 2 must be answered correctly to proceed.</p>
        <div class="forgot-security-grid">
            <div class="forgot-security-col">
                <div class="form-field">
                    <div class="input-field">
                        <select id="forgotSecurityQuestion1" name="security_question_1" required>
                            <option value="" disabled selected hidden>Select a question</option>
                            <option value="1">Who is your best friend in elementary?</option>
                            <option value="2">What is the name of your favorite pet?</option>
                            <option value="3">Who is your favorite teacher in high school?</option>
                        </select>
                        <label style="width: 120px; text-align: start;">Question 1</label>
                    </div>
                </div>

                <div class="form-field">
                    <div class="input-field">
                        <select id="forgotSecurityQuestion2" name="security_question_2" required>
                            <option value="" disabled selected hidden>Select a question</option>
                            <option value="4">What is your mother’s maiden name?</option>
                            <option value="5">What city were you born in?</option>
                            <option value="6">What is your favorite color?</option>
                        </select>
                        <label style="width: 120px; text-align: start;">Question 2</label>
                    </div>
                </div>

                <div class="form-field">
                    <div class="input-field">
                        <select id="forgotSecurityQuestion3" name="security_question_3" required>
                            <option value="" disabled selected hidden>Select a question</option>
                            <option value="7">What is your favorite food?</option>
                            <option value="8">What was the name of your first school?</option>
                            <option value="9">What is your father’s middle name?</option>
                        </select>
                        <label style="width: 120px; text-align: start;">Question 3</label>
                    </div>
                </div>
            </div>

            <div class="forgot-security-col">
                <div class="form-field">
                    <div class="input-field password-field">
                        <input type="password" id="forgotSecurityAnswer1" name="security_answer_1" required placeholder=" " autocomplete="off" />
                        <label>Answer 1</label>
                        <i class="fas fa-eye-slash toggle-password"></i>
                    </div>
                    <p class="forgot-field-feedback" id="securityAnswerFeedback1" aria-live="polite"></p>
                </div>

                <div class="form-field">
                    <div class="input-field password-field">
                        <input type="password" id="forgotSecurityAnswer2" name="security_answer_2" required placeholder=" " autocomplete="off" />
                        <label>Answer 2</label>
                        <i class="fas fa-eye-slash toggle-password"></i>
                    </div>
                    <p class="forgot-field-feedback" id="securityAnswerFeedback2" aria-live="polite"></p>
                </div>

                <div class="form-field">
                    <div class="input-field password-field">
                        <input type="password" id="forgotSecurityAnswer3" name="security_answer_3" required placeholder=" " autocomplete="off" />
                        <label>Answer 3</label>
                        <i class="fas fa-eye-slash toggle-password"></i>
                    </div>
                    <p class="forgot-field-feedback" id="securityAnswerFeedback3" aria-live="polite"></p>
                </div>
            </div>
        </div>
        <p class="message-error" id="securityError" aria-live="polite"></p>
        <div class="flex justify-between mt-3">
            <button type="button" class="btn prev-btn" data-step="2">&lt; Prev</button>
            <button type="button" class="btn next-btn" id="forgotVerifySecurityBtn">Verify Answers &gt;</button>
        </div>
    </section>

    <section class="step step-4" aria-labelledby="forgotStep4Title">
        <span id="forgotStep4Title">Change Password</span>
        <div class="forgot-password-group">
            <div class="pass-input-field" id="forgotNewPasswordField">
                <input type="password" name="new_password" id="newPassword" placeholder=" " autocomplete="new-password" />
                <label>New Password</label>
                <i class="fas fa-eye-slash toggle-password"></i>
            </div>
            <div class="forgot-strength-track" aria-hidden="true"><div class="forgot-strength-bar" id="passwordStrengthBar"></div></div>
            <div class="forgot-password-feedback" id="passwordStrengthMessage" aria-live="polite"></div>
        </div>
        <div class="forgot-password-group forgot-confirm-group">
            <div class="input-field password-field" id="forgotConfirmPasswordField">
                <input type="password" name="confirm_password" id="confirmPassword" placeholder=" " autocomplete="new-password" />
                <label>Confirm New Password</label>
                <i class="fas fa-eye-slash toggle-password"></i>
            </div>
            <div class="forgot-password-feedback" id="confirmPasswordMessage" aria-live="polite"></div>
        </div>
        <p class="message-error" id="passwordError" aria-live="polite"></p>
        <button type="submit" class="btn_submit" id="forgotPasswordSubmit">Change Password</button>
    </section>
</form>

<p class="toggle-link" style="margin-top:15px;">&larr; Back to <a href="index.php?action=login"><b>Login</b></a></p>

<style>
    .forgot-password .forgot-account-summary p{margin:4px 0;color:#555;font-size:13px}
    .forgot-password .otp-timer,.forgot-password .otp-resend,.forgot-help{text-align:center;font-size:12px;color:#6b7280}
    .forgot-password .dev-otp-banner{background:#fef9c3;color:#854d0e;font-size:12px;text-align:center;padding:8px;border-radius:6px;margin-top:8px}
    .forgot-security-grid{display:flex;align-items:flex-start;gap:15px;margin-top:12px;text-align:left}
    .forgot-security-col{display:flex;flex-direction:column;width:100%;gap:6px}
    .forgot-security-col .form-field{position:relative;margin-bottom:15px}
    .forgot-security-col .input-field select{width:100%;font-size:13px}
    .forgot-field-feedback{position:absolute;top:40px;left:0;width:100%;margin:0;text-align:center;font-size:11px;font-weight:500;line-height:1.35}
    .forgot-password-feedback{min-height:15px;margin:2px 0 0;text-align:center;font-size:11px;font-weight:500;line-height:1.35}
    .forgot-field-feedback.is-valid,.forgot-password-feedback.is-valid{color:#23ad5c}
    .forgot-field-feedback.is-invalid,.forgot-password-feedback.is-invalid{color:#dc2626}
    .forgot-field-feedback.is-checking{color:#64748b}
    .forgot-password-group{position:relative;margin-bottom:10px}
    .forgot-confirm-group{margin-top:10px}
    .forgot-password-group .pass-input-field,.forgot-password-group .input-field{margin-bottom:0}
    .forgot-strength-track{position:relative;z-index:2;width:100%;height:3px;margin-top:-3px;background:#e0e0e0;border-radius:0 0 5px 5px;overflow:hidden}
    .forgot-strength-bar{display:none;width:0;height:3px;margin:0;border:0;border-radius:0 0 5px 5px;background:red;transition:width .5s ease,background-color .5s ease}
    #forgotIdField.is-valid{border-color:#23ad5c}
    #forgotIdField.is-invalid{border-color:#dc2626}
    .forgot-password .step-3.active{width:635px;max-width:100%}
    .form-container:has(.forgot-password .step-3.active){width:min(700px,calc(100vw - 32px))}
    @media (max-width: 650px){
        .forgot-security-grid{flex-direction:column;gap:8px}
        .forgot-password .step-3.active{width:100%}
    }
</style>
