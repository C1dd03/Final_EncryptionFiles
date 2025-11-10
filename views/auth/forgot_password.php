<style>
    .step {
        display: none;
    }

    .step-1 {
        position: relative;
    }

    .step.active {
        display: block;
    }

    .input-field {
        position: relative;
        /* relative */
        width: 100%;
        /* w-full */
        margin-bottom: 1rem;
        /* mb-4 */
    }

    .btn {
        padding: 0.5rem 1rem;
        /* px-4 py-2 */
        border-radius: 0.375rem;
        /* rounded-md */
        font-weight: 600;
        /* font-semibold */
        color: #ffffff;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        /* transition-all duration-200 */
        border: none;
    }

    .next-btn {
        background-color: #ff692a;
        /* bg-green-500 */
    }

    .next-btn:hover {
        background-color: #e64f0f;
        color: white;
        /* hover:bg-green-600 */
    }

    .prev-btn {
        background-color: #6b7280;
        /* bg-gray-500 */
    }

    .prev-btn:hover {
        background-color: #4b5563;
        /* hover:bg-gray-600 */
    }

    

    .message-error {
        border-left: 3px solid #e74c3c;
        background-color: #fdecea;
        color: #c0392b;
        padding: 10px;
        border-radius: 3px;
        font-size: 0.9em;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        display: none;
        justify-content: center;
        margin-bottom: 15px;
    }

    .message-success {
        position: absolute;
        top: -25%;
        left: 50%;
        transform: translate(-50%, -20px);
        /* center horizontally + slide effect */
        width: 280px;
        height: 160px;
        background-color: #fff;
        border: 2px solid #16a34a;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);

        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;

        opacity: 0;
        pointer-events: none;
        transition: opacity 0.5s ease, transform 0.5s ease;
        z-index: 1000;
        padding: 20px;
    }

    /* When active (visible) */
    .message-success.show {
        opacity: 1;
        pointer-events: auto;
        transform: translate(-50%, 0);
        /* slide down into place */
    }

    .message-success i {
        color: #22c55e;
        font-size: 50px;
        margin-bottom: 10px;
    }

    .message-success p {
        font-size: 16px;
        color: #16a34a;
        font-weight: 600;
        text-align: center;
    }
</style>

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


