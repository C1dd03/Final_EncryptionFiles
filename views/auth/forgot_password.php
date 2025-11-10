<!------------------------------- ADD STEP 1, 2, 3 for forgot password -------------------------------------->
<form class="forgot-password" id="forgotForm">
    <h2 class="text-xl font-semibold mb-4 text-center">Forgot Password</h2>

    <!-- Step 1: Verify ID -->

    <div class="step step-1 active">
        <label class=" text-gray-600 text-sm">Enter your ID Number</label>
        <p class="message-error"></p>
        <div class="message-success" id="idVerifiedBox">
            <i class="fa-solid fa-circle-check"></i>
            <p>ID Verified Successfully!</p>
        </div>
        <div class="input-field">
            <input type="text" name="id_number" required placeholder=" " />
            <label>User ID</label>
        </div>
        <div class="flex justify-between items-center mt-2">
            <button type="button" class="btn next-btn" onclick="nextStepForgot(1)">Next &gt;</button>
        </div>
    </div>

    <!-- Step 2: Security Question -->
    <div class="step step-2">
        <p class="message-error" id="securityError"></p>
        <div class="input-field">
            <input type="password" name="security_answer_1" required placeholder=" " />
            <label>Who was your best friend in elementary school?</label>
        </div>

        <div class="input-field">
            <input type="password" name="security_answer_2" required placeholder=" " />
            <label>What was the name of your favorite pet?</label>
        </div>

        <div class="input-field">
            <input type="password" name="security_answer_3" required placeholder=" " />
            <label>Who was your favorite high school teacher?</label>
        </div>

        <div class="flex justify-between mt-2">
            <!-- <button type="button" class="btn prev-btn" onclick="prevStepForgot(2)">&lt; Prev</button> -->
            <button type="button" class="btn next-btn" onclick="nextStepForgot(2)">Next &gt;</button>
        </div>
    </div>

    <!-- Step 3: New Password -->
    <div class="step step-3">
        <p class="message-error" id="passwordError"></p>
        <p class="message-success" id="passwordSuccess" style="position: relative; top: 0; left: 0; transform: none; width: 100%; height: auto; padding: 10px; margin-bottom: 15px; display: none;"></p>
        
        <div> <!-- Password container  -->
            <div class="pass-input-field">
                <input type="password" name="new_password" id="newPassword" required placeholder=" " />
                <label>Enter New Password </label>
            </div>
            <!-- Password strength container bar -->
            <div class="password-strenght-container">
                <div class="password-strenght" id="passwordStrengthBar"></div>
            </div>
            <!-- Password Strength Message -->
            <div id="passwordStrengthMessage"></div>
        </div>

        <div class="input-field">
            <input type="password" name="confirm_password" id="confirmPassword" required placeholder=" " />
            <label>Re-enter Password</label>
        </div>

        <div class="flex justify-between mt-2">

            <button type="submit" class="btn_submit">Submit</button>
        </div>
    </div>
</form>


