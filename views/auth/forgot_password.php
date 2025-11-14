<!------------------------------- ADD STEP 1, 2, 3 for forgot password -------------------------------------->
<form class="forgot-password" id="forgotForm">
    <h2 class="text-xl font-semibold mb-4 text-center">Forgot Password</h2>

    <!-- Step 1: Verify ID -->

    <div class="step step-1 active">
        <label class=" text-gray-600 text-sm">Enter your ID Number</label>
        <div class="message-success" id="idVerifiedBox">
            <i class="fa-solid fa-circle-check"></i>
            <p>ID Verified Successfully!</p>
        </div>
        <div class="input-field">
            <input type="text" name="id_number" required placeholder=" " />
            <label>User ID</label>
        </div>
        <p class="message-error"></p>
        <div class="flex justify-between items-center mt-2 mb-2">
            <button type="button" class="btn next-btn" onclick="nextStepForgot(1)">Next &gt;</button>
        </div>
      
    </div>

    <!-- Step 2: Security Question -->
    <div class="step step-2">
        <!-- User Info Display -->
        <div style="background: #f5f5f5; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center;">
            <p style="margin: 0; color: #555; font-size: 13px;">ID Number: <strong id="displayIdNumber"></strong></p>
            <p style="margin: 5px 0 0 0; color: #555; font-size: 13px;">Username: <strong id="displayUsername"></strong></p>
        </div>

        <div style="margin-bottom: 10px;">
            <div class="input-field password-field">
                <input type="password" name="security_answer_1" required placeholder=" " />
                <label id="question1Label">Security Question 1</label>
                <i class="fas fa-eye toggle-password"></i>
            </div>
            <div class="answer-feedback" id="feedback1" style="display: none; margin-top: 5px; font-size: 14px;"></div>
            <p class="message-error" id="error1"></p>
        </div>

        <div style="margin-bottom: 10px;">
            <div class="input-field password-field">
                <input type="password" name="security_answer_2" required placeholder=" " />
                <label id="question2Label">Security Question 2</label>
                <i class="fas fa-eye toggle-password"></i>
            </div>
            <div class="answer-feedback" id="feedback2" style="display: none; margin-top: 5px; font-size: 14px;"></div>
            <p class="message-error" id="error2"></p>
        </div>

        <div style="margin-bottom: 10px;">
            <div class="input-field password-field">
                <input type="password" name="security_answer_3" required placeholder=" " />
                <label id="question3Label">Security Question 3</label>
                <i class="fas fa-eye toggle-password"></i>
            </div>
            <div class="answer-feedback" id="feedback3" style="display: none; margin-top: 5px; font-size: 14px;"></div>
            <p class="message-error" id="error3"></p>
        </div>
        <p class="message-error" id="securityError"></p>

        <div class="flex justify-between mt-2">
            <button type="button" class="btn prev-btn" onclick="prevStepForgot(2)">&lt; Prev</button>
            <button type="button" class="btn next-btn" onclick="nextStepForgot(2)">Next &gt;</button>
        </div>
    </div>

    <!-- Step 3: New Password -->
    <div class="step step-3">
         <!-- User Info Display -->
         <div style="background: #f5f5f5; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center;">
            <p style="margin: 0; color: #555; font-size: 13px;">ID Number: <strong id="displayIdNumber"></strong></p>
            <p style="margin: 5px 0 0 0; color: #555; font-size: 13px;">Username: <strong id="displayUsername"></strong></p>
        </div>
        <p class="message-success" id="passwordSuccess" style="position: relative; top: 0; left: 0; transform: none; width: 100%; height: auto; padding: 10px; margin-bottom: 15px; display: none;"></p>
        
        <div> <!-- Password container  -->
            <div class="pass-input-field">
                <input type="password" name="new_password" id="newPassword" required placeholder=" " />
                <label>Enter New Password </label>
                <i class="fas fa-eye toggle-password"></i>
            </div>
            <!-- Password strength container bar -->
            <div class="password-strenght-container">
                <div class="password-strenght" id="passwordStrengthBar"></div>
            </div>
            <!-- Password Strength Message -->
            <div id="passwordStrengthMessage"></div>
        </div>

        <div class="input-field password-field">
            <input type="password" name="confirm_password" id="confirmPassword" required placeholder=" " />
            <label>Re-enter Password</label>
            <i class="fas fa-eye toggle-password"></i>
        </div>
        <p class="message-error" id="passwordError"></p>

        <div class="flex justify-between mt-2">

            <button type="submit" class="btn_submit">Submit</button>
        </div>
    </div>
</form>
<p class="toggle-link" style="margin-top: 15px;">
           <- Back to <a href="index.php?action=login" ><b>Login</b></a>
        </p>

