<!------------------------------- ADD STEP 1, 2, 3, 4 for forgot password -------------------------------------->
<form class="forgot-password" id="forgotForm">
    <h2 class="text-xl font-semibold mb-4 text-center">Forgot Password</h2>
    <div class="forgot-pass" style="display: flex; justify-content: space-around; align-items: center;">
        <div class=line2></div>
        <div class=number>1</div>
        <div class=line></div>
        <div class=number>2</div>
        <div class=line></div>
        <div class=number>3</div>
        <div class=line></div>
        <div class=number>4</div>
        <div class=line2></div>
    </div>
    <div class="title-container-forgot-pass" style="display: flex; justify-content:space-between; align-items: center;padding:0px 12px 0px 12px">

        <div class=title>Email Verification</div>
        <div class=line></div>
        <div class=title>Security Questions</div>
        <div class=line></div>
        <div class=title>OTP Code</div>
        <div class=line></div>
        <div class=title>Change Password</div>
    </div>

    <!-- Step 1: Verify Email -->
    <div class="step step-1 active">
        <label class="text-gray-600 text-sm">Enter the email address linked to your account</label>
        <div class="input-field" style="margin:3px;">
            <input type="email" name="email" id="forgotEmailInput" placeholder=" " />
            <label>Email Address</label>
        </div>
        <p class="message-error" id="emailError"></p>
        <div class="flex justify-between items-center mt-2 mb-2" style="margin-top: 10px;">
            <button type="button" class="btn next-btn" onclick="nextStepForgot(1)">Next &gt;</button>
        </div>
    </div>

    <!-- Step 2: Security Questions -->
    <div class="step step-2">
        <div style="background: #f5f5f5; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center;">
            <p style="margin: 0; color: #555; font-size: 13px;">Email: <strong id="displayEmail"></strong></p>
            <p style="margin: 5px 0 0 0; color: #555; font-size: 13px;">Username: <strong id="displayUsername"></strong></p>
        </div>

        <div style="margin-bottom: 10px; position: relative;">
            <div class="input-field password-field">
                <input type="password" name="security_answer_1" required placeholder=" " />
                <label id="question1Label">Security Question 1</label>
                <i class="fas fa-eye-slash toggle-password"></i>
            </div>
            <div class="answer-feedback" id="feedback1" style="display: none; font-size: 12px; position: absolute;top:27px;left:30%"></div>
            <p class="message-error" id="error1" style="margin-top: 5px;"></p>
        </div>

        <div style="margin-bottom: 10px; position: relative;">
            <div class="input-field password-field">
                <input type="password" name="security_answer_2" required placeholder=" " />
                <label id="question2Label">Security Question 2</label>
                <i class="fas fa-eye-slash toggle-password"></i>
            </div>
            <div class="answer-feedback" id="feedback2" style="display: none; font-size: 12px; position: absolute;top:27px;left:30%"></div>
            <p class="message-error" id="error2" style="margin-top: 5px;"></p>
        </div>

        <div style="margin-bottom: 10px; position: relative;">
            <div class="input-field password-field">
                <input type="password" name="security_answer_3" required placeholder=" " />
                <label id="question3Label">Security Question 3</label>
                <i class="fas fa-eye-slash toggle-password"></i>
            </div>
            <div class="answer-feedback" id="feedback3" style="display: none; font-size: 12px; position: absolute;top:27px;left:30%"></div>
            <p class="message-error" id="error3" style="margin-top: 5px;"></p>
        </div>
        <p class="message-error" id="securityError"></p>

        <div class="flex justify-between mt-5">
            <button type="button" class="btn prev-btn" onclick="prevStepForgot(2)">&lt; Prev</button>
            <button type="button" class="btn next-btn" onclick="nextStepForgot(2)">Next &gt;</button>
        </div>
    </div>

    <!-- Step 3: OTP Code -->
    <div class="step step-3">
        <div style="background: #f5f5f5; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center;">
            <p style="margin: 0; color: #555; font-size: 13px;">We sent a 6-digit code to<br /><strong id="otpEmailDisplay"></strong></p>
        </div>

        <div class="input-field" style="margin:3px;">
            <input type="text" name="otp" id="forgotOtpInput" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" placeholder=" " />
            <label>OTP Code</label>
        </div>
        <p class="message-error" id="otpError"></p>

        <p class="otp-timer" id="forgotOtpExpiry" style="text-align:center; font-size:12px; color:#6b7280;"></p>
        <p class="otp-timer" id="forgotOtpResendTimer" style="text-align:center; font-size:12px; color:#6b7280;"></p>
        <p class="otp-resend" style="text-align:center; font-size:13px; margin-top:8px;">
            Didn't receive the code? <a href="javascript:void(0)" id="forgotResendOtp" style="display:none;">Resend OTP</a>
        </p>
        <div class="dev-otp-banner" id="forgotOtpDevBanner" style="display:none; background:#fef9c3; color:#854d0e; font-size:12px; text-align:center; padding:8px; border-radius:6px; margin-top:8px;"></div>

        <div class="flex justify-between mt-5">
            <button type="button" class="btn prev-btn" onclick="prevStepForgot(3)">&lt; Prev</button>
            <button type="button" class="btn next-btn" onclick="nextStepForgot(3)">Verify Code &gt;</button>
        </div>
    </div>

    <!-- Step 4: New Password -->
    <div class="step step-4">
        <div style="position: relative; margin-bottom: 15px; "> <!-- Password container  -->
            <div class="pass-input-field">
                <input type="password" name="new_password" id="newPassword" placeholder=" " />
                <label>Enter New Password </label>
                <i class="fas fa-eye-slash toggle-password"></i>
            </div>

            <!-- Password strength container bar -->
            <div class="password-strength-container">
                <div class="password-strength" id="passwordStrengthBar"></div>
            </div>
            <!-- Password Strength Message -->
            <div id="passwordStrengthMessage" style="visibility: hidden;"></div>
            <div id="passwordMessage"></div>
        </div>
        <div style="position: relative;  " class="forgot-confirm-field">
            <div class="input-field password-field">
                <input type="password" name="confirm_password" id="confirmPassword" placeholder=" " />
                <label>Re-enter Password</label>
                <i class="fas fa-eye-slash toggle-password"></i>
            </div>
            <div class="password-match-message-forgot" id="confirmPasswordRequired" style="font-size: 12px;"></div>
            <p class="message-error" id="passwordError"></p>
        </div>
        <div class="flex justify-between mt-2">
            <button type="button" class="btn prev-btn " style="margin-bottom: 10px;" onclick="prevStepForgot(4)">&lt; Prev</button>
            <button type="submit" class="btn_submit">Change Password</button>
        </div>
    </div>
</form>
<p class="toggle-link" style="margin-top: 15px;">
    <- Back to <a href="index.php?action=login"><b>Login</b></a>
</p>

<!-- Success Modal -->
<div class="success-modal" id="successModal" style="display: none;">
    <div class="success-modal-content" style="width: 350px; height: 300px; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 20px;">
        <div class="success-icon">
            <i class="fas fa-check-circle" style="font-size: 60px;"></i>
        </div>
        <h2 class="success-title" style="font-size: 24px; margin: 10px 0;">SUCCESS</h2>
        <p class="success-message" style="font-size: 14px; margin: 5px 0;">Your password has been<br>successfully changed.</p>
        <p class="success-id" style="font-size: 12px; margin: 5px 0;">Click button below to login</p>
        <button class="success-btn" onclick="goToLogin()">Go to Login Now</button>
    </div>
</div>

<script>
    function goToLogin() {
        window.location.href = "index.php?action=login";
    }
</script>
<script src="../../public/js/forgot_password.js"></script>
