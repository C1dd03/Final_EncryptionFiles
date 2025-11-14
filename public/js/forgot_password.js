const form = document.getElementById("forgotForm");
const msgError = document.querySelector(".message-error");
const msgSuccess = document.querySelector(".message-success");

function nextStepForgot(step) {
  const current = document.querySelector(`.step-${step}`);
  const next = document.querySelector(`.step-${step + 1}`);

  // Step 1: Verify ID via AJAX
  if (step === 1) {
    const idInput = current.querySelector('[name="id_number"]');

    // Clear previous message
    msgError.textContent = "";
    msgError.style.display = "none";

    if (!idInput.value.trim()) {
      msgError.textContent = "Please enter your ID Number";
      msgError.style.display = "block";
      return;
    }

    // Check if input contains only numbers and hyphens
    if (!/^[0-9-]+$/.test(idInput.value.trim())) {
      msgError.textContent = "ID Number must contain only numbers";
      msgError.style.display = "block";
      return;
    }

    fetch("index.php?action=verifyId", {
      method: "POST",
      body: new URLSearchParams({ id_number: idInput.value.trim() }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          // Store user data and questions for later use
          window.userData = data.user;
          window.userQuestions = data.questions;

          // Populate Step 2 with user info and questions
          document.getElementById('displayIdNumber').textContent = data.user.id_number;
          document.getElementById('displayUsername').textContent = data.user.username;
          
          // Populate question labels
          if (data.questions && data.questions.length === 3) {
            document.getElementById('question1Label').textContent = data.questions[0].question_text;
            document.getElementById('question2Label').textContent = data.questions[1].question_text;
            document.getElementById('question3Label').textContent = data.questions[2].question_text;
          }

          // Show success popup
          msgSuccess.classList.add("show");

          // Automatically hide popup and go to next step
          setTimeout(() => {
            msgSuccess.classList.remove("show");
            document.querySelector(".step-1").classList.remove("active");
            document.querySelector(".step-2").classList.add("active");
          }, 2000);
        } else {
          msgError.textContent = data.message; // Invalid ID Number
          msgError.style.display = "block";
        }
      })
      .catch((err) => {
        console.error("AJAX error:", err);
        msgError.textContent = "An error occurred. Please try again.";
        msgError.style.display = "block";
      });

    return;
  }

  // Step 2: Verify security answers before proceeding
  // Step 2: Verify security answers via AJAX
  if (step === 2) {
    const id_number = document.querySelector('[name="id_number"]').value.trim();
    const ans1 = current
      .querySelector('[name="security_answer_1"]')
      .value.trim();
    const ans2 = current
      .querySelector('[name="security_answer_2"]')
      .value.trim();
    const ans3 = current
      .querySelector('[name="security_answer_3"]')
      .value.trim();
    const securityError = document.getElementById("securityError");
    const error1 = document.getElementById("error1");
    const error2 = document.getElementById("error2");
    const error3 = document.getElementById("error3");

    // Clear previous errors
    if (securityError) {
      securityError.textContent = "";
      securityError.style.display = "none";
    }
    if (error1) {
      error1.textContent = "";
      error1.style.display = "none";
    }
    if (error2) {
      error2.textContent = "";
      error2.style.display = "none";
    }
    if (error3) {
      error3.textContent = "";
      error3.style.display = "none";
    }

    // Individual input validation
    let hasError = false;
    if (!ans1) {
      error1.textContent = "Please answer this question";
      error1.style.display = "block";
      hasError = true;
    }
    if (!ans2) {
      error2.textContent = "Please answer this question";
      error2.style.display = "block";
      hasError = true;
    }
    if (!ans3) {
      error3.textContent = "Please answer this question";
      error3.style.display = "block";
      hasError = true;
    }

    if (hasError) {
      return;
    }

    fetch("index.php?action=verifySecurityAnswers", {
      method: "POST",
      body: new URLSearchParams({
        id_number: id_number,
        security_answer_1: ans1,
        security_answer_2: ans2,
        security_answer_3: ans3,
      }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          if (securityError) {
            securityError.textContent = "";
            securityError.style.display = "none";
          }
          // Copy user info to Step 3 before moving
          const step3DisplayId = document.querySelector('.step-3 #displayIdNumber');
          const step3DisplayUsername = document.querySelector('.step-3 #displayUsername');
          if (step3DisplayId && window.userData) {
            step3DisplayId.textContent = window.userData.id_number;
          }
          if (step3DisplayUsername && window.userData) {
            step3DisplayUsername.textContent = window.userData.username;
          }
          current.classList.remove("active");
          next.classList.add("active");
        } else {
          if (securityError) {
            securityError.textContent =
              data.message || "Verification failed. Please check your answers.";
            securityError.style.display = "block";
          } else {
            alert(data.message);
          }
        }
      })
      .catch((err) => {
        console.error("AJAX error:", err);
        if (securityError) {
          securityError.textContent = "An error occurred. Please try again.";
          securityError.style.display = "block";
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
  current.classList.remove("active");
  prev.classList.add("active");
}

// Password strength checker for Step 3
let passwordInput, confirmPasswordInput, strengthBar, strengthMessage, passwordError, passwordSuccess;

// Initialize password elements when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
  // Small delay to ensure DOM is fully loaded
  setTimeout(function() {
    passwordInput = document.getElementById("newPassword");
    confirmPasswordInput = document.getElementById("confirmPassword");
    strengthBar = document.getElementById("passwordStrengthBar");
    strengthMessage = document.getElementById("passwordStrengthMessage");
    passwordError = document.getElementById("passwordError");
    passwordSuccess = document.getElementById("passwordSuccess");

    // Event listeners for password strength
    if (passwordInput) {
      passwordInput.addEventListener("input", function () {
        checkPasswordStrength(this.value);
        if (confirmPasswordInput && confirmPasswordInput.value.length > 0) {
          checkPasswordMatch();
        }
      });
    }

    if (confirmPasswordInput) {
      confirmPasswordInput.addEventListener("input", function () {
        checkPasswordMatch();
      });
    }
    
    // Add real-time validation for security answers
    const securityAnswer1 = document.querySelector('[name="security_answer_1"]');
    const securityAnswer2 = document.querySelector('[name="security_answer_2"]');
    const securityAnswer3 = document.querySelector('[name="security_answer_3"]');
    
    if (securityAnswer1) {
      securityAnswer1.addEventListener('input', function() {
        validateSecurityAnswer(1, this.value);
      });
    }
    
    if (securityAnswer2) {
      securityAnswer2.addEventListener('input', function() {
        validateSecurityAnswer(2, this.value);
      });
    }
    
    if (securityAnswer3) {
      securityAnswer3.addEventListener('input', function() {
        validateSecurityAnswer(3, this.value);
      });
    }
    
    // Initialize password toggle functionality
    if (typeof initPasswordToggle === 'function') {
      initPasswordToggle();
    } else {
      // Fallback: initialize password toggle directly
      const toggleIcons = document.querySelectorAll('.toggle-password');
      toggleIcons.forEach(icon => {
        icon.addEventListener('click', function() {
          // Find the input field within the same parent container
          const container = this.closest('.pass-input-field') || this.closest('.password-field');
          if (!container) return;
          
          const input = container.querySelector('input');
          if (!input) return;
          
          // Toggle password visibility
          if (input.type === 'password') {
            input.type = 'text';
            this.classList.remove('fa-eye');
            this.classList.add('fa-eye-slash');
          } else {
            input.type = 'password';
            this.classList.remove('fa-eye-slash');
            this.classList.add('fa-eye');
          }
        });
      });
    }
  }, 100); // 100ms delay
});

// Function to validate security answers in real-time
function validateSecurityAnswer(questionId, answer) {
  // Only validate if we have user data and answer is not empty
  if (!window.userData || answer.trim() === '') {
    hideFeedback(questionId);
    return;
  }
  
  // Get the feedback element
  const feedbackElement = document.getElementById(`feedback${questionId}`);
  if (!feedbackElement) return;
  
  // Show that we're checking
  feedbackElement.textContent = 'Checking...';
  feedbackElement.style.color = '#ffa500'; // Orange
  feedbackElement.style.display = 'block';
  
  // Send AJAX request to validate the answer
  fetch("index.php?action=validateSecurityAnswer", {
    method: "POST",
    body: new URLSearchParams({
      id_number: window.userData.id_number,
      question_id: questionId,
      answer: answer
    }),
  })
  .then((res) => res.json())
  .then((data) => {
    if (data.valid !== undefined) {
      if (data.valid) {
        // Correct answer
        feedbackElement.innerHTML = `<i class="fas fa-check-circle" style="color: #23ad5c;"></i> Correct! Your answer: <strong>${escapeHtml(answer)}</strong>`;
        feedbackElement.style.color = '#23ad5c'; // Green
      } else {
        // Incorrect answer
        feedbackElement.innerHTML = `<i class="fas fa-times-circle" style="color: #e74c3c;"></i> Incorrect. Your answer: <strong>${escapeHtml(answer)}</strong>`;
        feedbackElement.style.color = '#e74c3c'; // Red
      }
      feedbackElement.style.display = 'block';
    } else {
      // Error or no data
      hideFeedback(questionId);
    }
  })
  .catch((err) => {
    console.error("AJAX error:", err);
    hideFeedback(questionId);
  });
}

// Helper function to hide feedback
function hideFeedback(questionId) {
  const feedbackElement = document.getElementById(`feedback${questionId}`);
  if (feedbackElement) {
    feedbackElement.style.display = 'none';
  }
}

// Helper function to escape HTML
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

function checkPasswordStrength(password) {
  if (!strengthBar || !strengthMessage) return;

  let strength = 0;
  let message = "";
  let color = "";
  let width = "0%";

  if (password.length === 0) {
    strengthBar.style.display = "none";
    strengthMessage.style.display = "none";
    return;
  }

  strengthBar.style.display = "block";
  strengthMessage.style.display = "block";

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
    message = "Weak";
    color = "#f80000"; // Red
    width = "33%";
  } else if (strength === 3 || strength === 4) {
    message = "Medium";
    color = "#ffa500"; // Orange
    width = "66%";
  } else {
    message = "Strong";
    color = "#00ff00"; // Green
    width = "100%";
  }

  strengthBar.style.width = width;
  strengthBar.style.backgroundColor = color;
  strengthMessage.textContent = `Password Strength: ${message}`;
  strengthMessage.style.color = color;
  strengthMessage.style.marginLeft = "5px";
  strengthMessage.style.fontSize = "12px";
}

// Password match checker
function checkPasswordMatch() {
  if (!passwordInput || !confirmPasswordInput) return;

  const password = passwordInput.value;
  const confirm = confirmPasswordInput.value;

  if (confirm.length > 0 && password !== confirm) {
    confirmPasswordInput.style.borderBottom = "1px solid rgba(255,0,0,0.5)";
    return false;
  } else {
    confirmPasswordInput.style.borderBottom = "";
    return true;
  }
}

// Handle final submit (Step 3) - removed duplicate event listeners
form.addEventListener("submit", function (e) {
  e.preventDefault();
  const step3 = document.querySelector(".step-3");

  const id_number = document.querySelector('[name="id_number"]').value.trim();
  const new_password = step3.querySelector('[name="new_password"]').value;
  const confirm_password = step3.querySelector(
    '[name="confirm_password"]'
  ).value;

  // Clear previous messages
  passwordError.textContent = "";
  passwordError.style.display = "none";
  passwordSuccess.style.display = "none";

  // Check password match
  if (new_password !== confirm_password) {
    passwordError.textContent = "Mismatch Password";
    passwordError.style.display = "block";
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
    passwordError.textContent =
      "Password is too weak. Please use a stronger password.";
    passwordError.style.display = "block";
    return;
  }

  // Get security answers from step 2
  const step2 = document.querySelector(".step-2");
  const ans1 = step2.querySelector('[name="security_answer_1"]').value.trim();
  const ans2 = step2.querySelector('[name="security_answer_2"]').value.trim();
  const ans3 = step2.querySelector('[name="security_answer_3"]').value.trim();

  // Use the first answer as the security answer for the reset
  fetch("index.php?action=resetPassword", {
    method: "POST",
    body: new URLSearchParams({
      id_number: id_number,
      security_question: 1, // Using question 1 (index 1)
      answer: ans1,
      new_password: new_password,
      confirm_password: confirm_password,
    }),
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        passwordSuccess.textContent = "Successfully Change Password";
        passwordSuccess.style.display = "block";
        passwordSuccess.style.borderLeft = "3px solid #22c55e";
        passwordSuccess.style.backgroundColor = "#f0fdf4";
        passwordSuccess.style.color = "#16a34a";

        // Redirect to login after 2 seconds
        setTimeout(() => {
          window.location.href = "index.php?action=login";
        }, 2000);
      } else {
        passwordError.textContent = data.message || "Failed to reset password.";
        passwordError.style.display = "flex";
      }
    })
    .catch((err) => {
      console.error("AJAX error:", err);
      passwordError.textContent = "An error occurred. Please try again.";
      passwordError.style.display = "flex";
    });
});
