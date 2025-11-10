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

    .btn_submit {
        background-color: #22c55e;
        /* bg-green-500 */
        width: 100%;
        /* w-full */
        padding: 0.5rem 0;
        /* py-2 */
        border-radius: 0.375rem;
        /* rounded-md */
        font-weight: 700;
        /* font-bold */
        color: #ffffff;
    }

    .btn_submit:hover {
        background-color: #16a34a;
        /* hover:bg-green-600 */
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


<script>
    const form = document.getElementById('forgotForm');
    const msgError = document.querySelector('.message-error');
    const msgSuccess = document.querySelector('.message-success');

    function nextStepForgot(step) {
        const current = document.querySelector(`.step-${step}`);
        const next = document.querySelector(`.step-${step + 1}`);

        // Step 1: Verify ID via AJAX
        if (step === 1) {
            const idInput = current.querySelector('[name="id_number"]');

            // Clear previous message
            msgError.textContent = '';
            msgError.style.display = 'none';

            if (!idInput.value.trim()) {
                msgError.textContent = "Please enter your ID Number";
                msgError.style.display = 'flex'; // or 'block' depende sa imong CSS
                return;
            }

            fetch('index.php?action=verifyId', {
                method: 'POST',
                body: new URLSearchParams({ id_number: idInput.value.trim() })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Show success popup
                        msgSuccess.classList.add('show');

                        // Automatically hide popup and go to next step
                        setTimeout(() => {
                            msgSuccess.classList.remove('show');
                            document.querySelector('.step-1').classList.remove('active');
                            document.querySelector('.step-2').classList.add('active');
                        }, 2000);
                    } else {
                        msgError.textContent = data.message; // Invalid ID Number
                        msgError.style.display = 'flex';
                    }
                })
                .catch(err => {
                    console.error("AJAX error:", err);
                    msgError.textContent = "An error occurred. Please try again.";
                    msgError.style.display = 'flex';
                });

            return;
        }

        // Step 2: Verify security answers before proceeding
        // Step 2: Verify security answers via AJAX
        if (step === 2) {
            const id_number = document.querySelector('[name="id_number"]').value.trim();
            const ans1 = current.querySelector('[name="security_answer_1"]').value.trim();
            const ans2 = current.querySelector('[name="security_answer_2"]').value.trim();
            const ans3 = current.querySelector('[name="security_answer_3"]').value.trim();
            const securityError = document.getElementById('securityError');

            // Clear previous error
            if (securityError) {
                securityError.textContent = '';
                securityError.style.display = 'none';
            }

            // Empty input validation
            if (!ans1 || !ans2 || !ans3) {
                if (securityError) {
                    securityError.textContent = "Please answer all security questions.";
                    securityError.style.display = 'flex';
                } else {
                    alert("Please answer all security questions.");
                }
                return;
            }

            fetch('index.php?action=verifySecurityAnswers', {
                method: 'POST',
                body: new URLSearchParams({
                    id_number: id_number,
                    security_answer_1: ans1,
                    security_answer_2: ans2,
                    security_answer_3: ans3
                })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (securityError) {
                            securityError.textContent = '';
                            securityError.style.display = 'none';
                        }
                        current.classList.remove('active');
                        next.classList.add('active');
                    } else {
                        if (securityError) {
                            securityError.textContent = data.message || 'Verification failed. Please check your answers.';
                            securityError.style.display = 'flex';
                        } else {
                            alert(data.message);
                        }
                    }
                })
                .catch(err => {
                    console.error("AJAX error:", err);
                    if (securityError) {
                        securityError.textContent = "An error occurred. Please try again.";
                        securityError.style.display = 'flex';
                    } else {
                        alert("An error occurred. Please try again.");
                    }
                });

            return; // stop here to wait for AJAX
        }

        // current.classList.remove('active');
        // next.classList.add('active');
    }

    function prevStepForgot(step) {
        const current = document.querySelector(`.step-${step}`);
        const prev = document.querySelector(`.step-${step - 1}`);
        current.classList.remove('active');
        prev.classList.add('active');
    }

    // Password strength checker for Step 3
    const passwordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const strengthBar = document.getElementById('passwordStrengthBar');
    const strengthMessage = document.getElementById('passwordStrengthMessage');
    const passwordError = document.getElementById('passwordError');
    const passwordSuccess = document.getElementById('passwordSuccess');

    function checkPasswordStrength(password) {
        let strength = 0;
        let message = '';
        let color = '';
        let width = '0%';

        if (password.length === 0) {
            strengthBar.style.display = 'none';
            strengthMessage.style.display = 'none';
            return;
        }

        strengthBar.style.display = 'block';
        strengthMessage.style.display = 'block';

        // Check for lowercase
        if (/[a-z]/.test(password)) strength++;
        // Check for uppercase
        if (/[A-Z]/.test(password)) strength++;
        // Check for numbers
        if (/[0-9]/.test(password)) strength++;
        // Check for special characters
        if (/[^a-zA-Z0-9]/.test(password)) strength++;
        // Check length
        if (password.length >= 8) strength++;

        // Determine strength level
        if (strength <= 2) {
            message = 'Weak';
            color = '#f80000'; // Red
            width = '33%';
        } else if (strength === 3 || strength === 4) {
            message = 'Medium';
            color = '#ffa500'; // Orange
            width = '66%';
        } else {
            message = 'Strong';
            color = '#00ff00'; // Green
            width = '100%';
        }

        strengthBar.style.width = width;
        strengthBar.style.backgroundColor = color;
        strengthMessage.textContent = `Password Strength: ${message}`;
        strengthMessage.style.color = color;
        strengthMessage.style.marginLeft = '5px';
        strengthMessage.style.fontSize = '12px';
    }

    // Password match checker
    function checkPasswordMatch() {
        const password = passwordInput.value;
        const confirm = confirmPasswordInput.value;

        if (confirm.length > 0 && password !== confirm) {
            confirmPasswordInput.style.borderBottom = '1px solid rgba(255,0,0,0.5)';
            return false;
        } else {
            confirmPasswordInput.style.borderBottom = '';
            return true;
        }
    }

    // Event listeners for password strength
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            checkPasswordStrength(this.value);
            if (confirmPasswordInput.value.length > 0) {
                checkPasswordMatch();
            }
        });
    }

    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', function() {
            checkPasswordMatch();
        });
    }

    // Handle final submit (Step 3)
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const step3 = document.querySelector('.step-3');

        const id_number = document.querySelector('[name="id_number"]').value.trim();
        const new_password = step3.querySelector('[name="new_password"]').value;
        const confirm_password = step3.querySelector('[name="confirm_password"]').value;

        // Clear previous messages
        passwordError.textContent = '';
        passwordError.style.display = 'none';
        passwordSuccess.style.display = 'none';

        // Check password match
        if (new_password !== confirm_password) {
            passwordError.textContent = 'Mismatch Password';
            passwordError.style.display = 'flex';
            return;
        }

        // Check password strength
        let strength = 0;
        if (/[a-z]/.test(new_password)) strength++;
        if (/[A-Z]/.test(new_password)) strength++;
        if (/[0-9]/.test(new_password)) strength++;
        if (/[^a-zA-Z0-9]/.test(new_password)) strength++;
        if (new_password.length >= 8) strength++;

        if (strength <= 2) {
            passwordError.textContent = 'Password is too weak. Please use a stronger password.';
            passwordError.style.display = 'flex';
            return;
        }

        // Get security answers from step 2
        const step2 = document.querySelector('.step-2');
        const ans1 = step2.querySelector('[name="security_answer_1"]').value.trim();
        const ans2 = step2.querySelector('[name="security_answer_2"]').value.trim();
        const ans3 = step2.querySelector('[name="security_answer_3"]').value.trim();

        // Use the first answer as the security answer for the reset (as per original logic)
        fetch('index.php?action=resetPassword', {
            method: 'POST',
            body: new URLSearchParams({
                id_number: id_number,
                security_question: 1, // Using question 1
                answer: ans1,
                new_password: new_password,
                confirm_password: confirm_password
            })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    passwordSuccess.textContent = 'Successfully Change Password';
                    passwordSuccess.style.display = 'block';
                    passwordSuccess.style.borderLeft = '3px solid #22c55e';
                    passwordSuccess.style.backgroundColor = '#f0fdf4';
                    passwordSuccess.style.color = '#16a34a';
                    
                    // Redirect to login after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'index.php?action=login';
                    }, 2000);
                } else {
                    passwordError.textContent = data.message || 'Failed to reset password.';
                    passwordError.style.display = 'flex';
                }
            })
            .catch(err => {
                console.error("AJAX error:", err);
                passwordError.textContent = 'An error occurred. Please try again.';
                passwordError.style.display = 'flex';
            });
    });

</script>