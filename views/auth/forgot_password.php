<style>


.step { 
    display: none; 
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

.message {
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


</style>

<form class="forgot-password" id="forgotForm">
  <h2 class="text-xl font-semibold mb-4 text-center">Forgot Password</h2>

  <!-- Step 1: Verify ID -->
  
  <div class="step step-1 active">
    <label class=" text-gray-600 text-sm">Enter your ID Number</label>
    <p class="message"></p>
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
      <button type="button" class="btn prev-btn" onclick="prevStepForgot(2)">&lt; Prev</button>
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
const msg = document.querySelector('.message');

function nextStepForgot(step) {
    const current = document.querySelector(`.step-${step}`);
    const next = document.querySelector(`.step-${step + 1}`);

    // Step 1: Verify ID via AJAX
    if(step === 1) {
        const idInput = current.querySelector('[name="id_number"]');

        // Clear previous message
        msg.textContent = '';
        msg.style.display = 'none';

        if(!idInput.value.trim()) { 
            msg.textContent = "Please enter your ID Number"; 
            msg.style.display = 'flex'; // or 'block' depende sa imong CSS
            return; 
        }

        fetch('index.php?action=verifyId', {
            method: 'POST',
            body: new URLSearchParams({id_number: idInput.value.trim()})
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                current.classList.remove('active');
                next.classList.add('active');
            } else {
                msg.textContent = data.message; // Invalid ID Number
                msg.style.display = 'flex';
            }
        })
        .catch(err => {
            console.error("AJAX error:", err);
            msg.textContent = "An error occurred. Please try again.";
            msg.style.display = 'flex';
        });

        return;
    }

    // Step 2: Ensure question + answer are filled
    if(step === 2) {
        const question = current.querySelector('[name="security_question"]').value;
        const answer = current.querySelector('[name="answer"]').value.trim();
        if(!question || !answer) { alert("Please select question and provide answer"); return; }
    }

    current.classList.remove('active');
    next.classList.add('active');
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
