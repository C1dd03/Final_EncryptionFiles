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
    position: relative; /* relative */
    width: 100%; /* w-full */
    margin-bottom: 1rem; /* mb-4 */
}

.btn {
    padding: 0.5rem 1rem; /* px-4 py-2 */
    border-radius: 0.375rem; /* rounded-md */
    font-weight: 600; /* font-semibold */
    color: #ffffff;
    cursor: pointer;
    transition: all 0.2s ease-in-out; /* transition-all duration-200 */
    border: none;
}

.next-btn {
    background-color:   #ff692a; /* bg-green-500 */
}

.next-btn:hover {
    background-color: #e64f0f; 
    color: white;/* hover:bg-green-600 */
}

.prev-btn {
    background-color: #6b7280; /* bg-gray-500 */
}

.prev-btn:hover {
    background-color: #4b5563; /* hover:bg-gray-600 */
}

.btn_submit {
    background-color: #22c55e; /* bg-green-500 */
    width: 100%; /* w-full */
    padding: 0.5rem 0; /* py-2 */
    border-radius: 0.375rem; /* rounded-md */
    font-weight: 700; /* font-bold */
    color: #ffffff;
}

.btn_submit:hover {
    background-color: #16a34a; /* hover:bg-green-600 */
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
    transform: translate(-50%, -20px); /* center horizontally + slide effect */
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
    transform: translate(-50%, 0); /* slide down into place */
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
    <div class="input-field">
    <input type="text" name="security_answer_1" required placeholder=" " />
    <label>Who was your best friend in elementary school?</label>
  </div>

  <div class="input-field">
    <input type="text" name="security_answer_2" required placeholder=" " />
    <label>What was the name of your favorite pet?</label>
  </div>

  <div class="input-field">
    <input type="text" name="security_answer_3" required placeholder=" " />
    <label>Who was your favorite high school teacher?</label>
  </div>

    <div class="flex justify-between mt-2">
      <!-- <button type="button" class="btn prev-btn" onclick="prevStepForgot(2)">&lt; Prev</button> -->
      <button type="button" class="btn next-btn" onclick="nextStepForgot(2)">Next &gt;</button>
    </div>
  </div>

  <!-- Step 3: New Password -->
  <div class="step step-3">
    <div class="input-field">
      <input type="password" name="new_password" required placeholder=" " />
      <label>New Password</label>
    </div>

    <div class="input-field">
      <input type="password" name="confirm_password" required placeholder=" " />
      <label>Confirm Password</label>
    </div>

    <div class="flex justify-between mt-2">
      <button type="button" class="btn prev-btn" onclick="prevStepForgot(3)">&lt; Prev</button>
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
    if(step === 1) {
        const idInput = current.querySelector('[name="id_number"]');

        // Clear previous message
        msgError.textContent = '';
        msgError.style.display = 'none';

        if(!idInput.value.trim()) { 
            msgError.textContent = "Please enter your ID Number"; 
            msgError.style.display = 'flex'; // or 'block' depende sa imong CSS
            return; 
        }

        fetch('index.php?action=verifyId', {
            method: 'POST',
            body: new URLSearchParams({id_number: idInput.value.trim()})
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
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

        // Empty input validation
        if (!ans1 || !ans2 || !ans3) {
            alert("Please answer all security questions.");
            return;
        }

        fetch('../../index.php?action=verifySecurityAnswers', {
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
                alert(data.message);
                current.classList.remove('active');
                next.classList.add('active');
            } else {
                alert(data.message);
            }
        })
        .catch(err => {
            console.error("AJAX error:", err);
            alert("An error occurred. Please try again.");
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

// Handle final submit (Step 3)
form.addEventListener('submit', function(e){
    e.preventDefault();
    const step2 = document.querySelector('.step-2');
    const step3 = document.querySelector('.step-3');

    const id_number = document.querySelector('[name="id_number"]').value.trim();
    const question_id = step2.querySelector('[name="security_question"]').value;
    const answer = step2.querySelector('[name="answer"]').value.trim();
    const new_password = step3.querySelector('[name="new_password"]').value;
    const confirm_password = step3.querySelector('[name="confirm_password"]').value;

    if(new_password !== confirm_password){
        alert("Passwords do not match");
        return;
    }

    fetch('index.php?action=resetPassword', {
        method: 'POST',
        body: new URLSearchParams({
            id_number,
            security_question: question_id,
            answer,
            new_password,
            confirm_password
        })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if(data.success) window.location.href = 'index.php?action=login';
    })
    .catch(err => {
        console.error("AJAX error:", err);
        alert("An error occurred. Please try again.");
    });
});

</script>
