const form = document.getElementById("forgotForm");
const msgError = document.querySelector(".message-error");
const msgSuccess = document.querySelector(".message-success");

function nextStepForgot(step) {
  const current = document.querySelector(`.step-${step}`);
  const next = document.querySelector(`.step-${step + 1}`);

  // Step 1: Verify ID via AJAX
  if (step === 1) {
    const idInput = current.querySelector('[name="id_number"]');
    const msgError = current.querySelector(".message-error");

    // Clear previous message
    if (msgError) {
      msgError.textContent = "";
      msgError.style.visibility = "hidden";
    }

    if (!idInput.value.trim()) {
      if (msgError) {
        msgError.textContent = "Please enter your ID Number";
        msgError.style.visibility = "visible";
      }
      // Reset step indicators to current step
      updateForgotStepIndicators(1);
      return;
    }

    // Check if input contains only numbers and hyphens
    if (!/^[0-9-]+$/.test(idInput.value.trim())) {
      if (msgError) {
        msgError.textContent =
          "ID Number must contain only numbers and hyphens";
        msgError.style.visibility = "visible";
      }
      // Reset step indicators to current step
      updateForgotStepIndicators(1);
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
          document.getElementById("displayIdNumber").textContent =
            data.user.id_number;
          document.getElementById("displayUsername").textContent =
            data.user.username;

          // Populate question labels
          if (data.questions && data.questions.length === 3) {
            document.getElementById("question1Label").textContent =
              data.questions[0].question_text;
            document.getElementById("question2Label").textContent =
              data.questions[1].question_text;
            document.getElementById("question3Label").textContent =
              data.questions[2].question_text;
          }

          // Show success popup
          msgSuccess.classList.add("show");

          // Automatically hide popup and go to next step
          setTimeout(() => {
            msgSuccess.classList.remove("show");
            document.querySelector(".step-1").classList.remove("active");
            document.querySelector(".step-2").classList.add("active");
            // Update step indicators
            updateForgotStepIndicators(2);
          }, 2000);
        } else {
          if (msgError) {
            msgError.textContent = data.message; // Invalid ID Number
            msgError.style.visibility = "visible";
          }
          // Reset step indicators to current step
          updateForgotStepIndicators(1);
        }
      })
      .catch((err) => {
        console.error("AJAX error:", err);
        if (msgError) {
          msgError.textContent = "An error occurred. Please try again.";
          msgError.style.visibility = "visible";
        }
        // Reset step indicators to current step
        updateForgotStepIndicators(1);
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
      securityError.style.visibility = "hidden";
    }
    if (error1) {
      error1.textContent = "";
      error1.style.visibility = "hidden";
    }
    if (error2) {
      error2.textContent = "";
      error2.style.visibility = "hidden";
    }
    if (error3) {
      error3.textContent = "";
      error3.style.visibility = "hidden";
    }

    // Individual input validation
    let hasError = false;
    if (!ans1) {
      if (error1) {
        error1.textContent = "Please answer this question";
        error1.style.visibility = "visible";
      }
      hasError = true;
    }
    if (!ans2) {
      if (error2) {
        error2.textContent = "Please answer this question";
        error2.style.visibility = "visible";
      }
      hasError = true;
    }
    if (!ans3) {
      if (error3) {
        error3.textContent = "Please answer this question";
        error3.style.visibility = "visible";
      }
      hasError = true;
    }

    if (hasError) {
      // Reset step indicators to current step
      updateForgotStepIndicators(2);
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
            securityError.style.visibility = "hidden";
          }
          // Copy user info to Step 3 before moving
          const step3DisplayId = document.querySelector(
            ".step-3 #displayIdNumber"
          );
          const step3DisplayUsername = document.querySelector(
            ".step-3 #displayUsername"
          );
          if (step3DisplayId && window.userData) {
            step3DisplayId.textContent = window.userData.id_number;
          }
          if (step3DisplayUsername && window.userData) {
            step3DisplayUsername.textContent = window.userData.username;
          }
          current.classList.remove("active");
          next.classList.add("active");
          // Update step indicators
          updateForgotStepIndicators(3);
        } else {
          if (securityError) {
            securityError.textContent =
              data.message || "Verification failed. Please check your answers.";
            securityError.style.visibility = "visible";
          } else {
            alert(data.message);
          }
          // Reset step indicators to current step
          updateForgotStepIndicators(2);
        }
      })
      .catch((err) => {
        console.error("AJAX error:", err);
        if (securityError) {
          securityError.textContent = "An error occurred. Please try again.";
          securityError.style.visibility = "visible";
        } else {
          alert("An error occurred. Please try again.");
        }
        // Reset step indicators to current step
        updateForgotStepIndicators(2);
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
  
  // Update step indicators
  updateForgotStepIndicators(step - 1);
}

// Update step indicators for forgot password form
function updateForgotStepIndicators(currentStep) {
  // Get all number and line elements
  const numbers = document.querySelectorAll('.forgot-pass .number');
  const lines = document.querySelectorAll('.forgot-pass .line');
  
  // Reset all indicators
  numbers.forEach(num => {
    num.classList.remove('active');
  });
  
  lines.forEach(line => {
    line.classList.remove('active');
  });
  
  // Activate indicators based on current step
  switch(currentStep) {
    case 1:
      if (numbers[0]) numbers[0].classList.add('active');
      break;
    case 2:
      if (numbers[0]) numbers[0].classList.add('active');
      if (lines[0]) lines[0].classList.add('active');
      if (numbers[1]) numbers[1].classList.add('active');
      break;
    case 3:
      if (numbers[0]) numbers[0].classList.add('active');
      if (lines[0]) lines[0].classList.add('active');
      if (numbers[1]) numbers[1].classList.add('active');
      if (lines[1]) lines[1].classList.add('active');
      if (numbers[2]) numbers[2].classList.add('active');
      break;
  }
}

// Initialize step indicators on page load
document.addEventListener("DOMContentLoaded", function () {
  // Small delay to ensure DOM is fully loaded
  setTimeout(function () {
    forgotPasswordInput = document.getElementById("newPassword");
    forgotConfirmPasswordInput = document.getElementById("confirmPassword");
    strengthBar = document.getElementById("passwordStrengthBar");
    strengthMessage = document.getElementById("passwordStrengthMessage");
    passwordSuccess = document.getElementById("passwordSuccess");
    submitButton = document.querySelector(".step-3 .btn_submit");

    console.log("Elements initialized:", {
      strengthBar,
      strengthMessage,
      passwordSuccess,
      submitButton,
    });

    // Event listeners for password strength and match validation
    if (forgotPasswordInput) {
      forgotPasswordInput.addEventListener("input", function () {
        // Check if password contains spaces
        if (/\s/.test(this.value)) {
          // Show space error and clear any existing errors
          if (typeof setFieldError === "function") {
            setFieldError(this, "New Password cannot contain spaces");
          }
          // Hide password strength elements
          if (strengthBar) strengthBar.style.display = "none";
          if (strengthMessage) {
            strengthMessage.style.display = "none";
            strengthMessage.style.visibility = "hidden";
          }
          // Clear password match message if it exists
          if (typeof checkForgotPasswordMatch === "function") {
            checkForgotPasswordMatch();
          }
          return;
        }
        
        checkPasswordStrength(this.value);
        // Check password match if confirm password has content
        if (
          forgotConfirmPasswordInput &&
          forgotConfirmPasswordInput.value.length > 0
        ) {
          checkForgotPasswordMatch();
        }
        // Clear any previous error when user starts typing
        if (typeof clearFieldError === "function") {
          clearFieldError(this);
          // Show password strength message again when clearing errors
          const passwordStrengthMessage = document.getElementById(
            "passwordStrengthMessage"
          );
          if (passwordStrengthMessage) {
            passwordStrengthMessage.style.visibility = "visible";
          }
        }
      });
    }

    if (forgotConfirmPasswordInput) {
      forgotConfirmPasswordInput.addEventListener("input", function () {
        // Check if confirm password contains spaces
        if (/\s/.test(this.value)) {
          // Show space error and clear any existing errors
          if (typeof setFieldError === "function") {
            setFieldError(this, "Re-enter Password cannot contain spaces");
          }
          // Clear password match message if it exists
          if (typeof checkForgotPasswordMatch === "function") {
            checkForgotPasswordMatch();
          }
          return;
        }
        
        checkForgotPasswordMatch();
        // Clear any previous error when user starts typing
        if (typeof clearFieldError === "function") {
          clearFieldError(this);
        }
      });
      // Check password match on page load if there's content
      if (forgotConfirmPasswordInput.value.length > 0) {
        checkForgotPasswordMatch();
      }
    }

    // Add real-time validation for ID number
    const idInput = document.querySelector('.step-1 [name="id_number"]');
    if (idInput) {
      idInput.addEventListener("input", function () {
        validateIdNumber(this.value);
      });
    }

    // Add real-time validation for security answers
    const securityAnswer1 = document.querySelector(
      '[name="security_answer_1"]'
    );
    const securityAnswer2 = document.querySelector(
      '[name="security_answer_2"]'
    );
    const securityAnswer3 = document.querySelector(
      '[name="security_answer_3"]'
    );

    if (securityAnswer1) {
      securityAnswer1.addEventListener("input", function () {
        validateSecurityAnswerField(1, this.value);
        validateSecurityAnswer(1, this.value);
      });
      // Show feedback immediately if there's a value
      if (securityAnswer1.value.trim() !== "") {
        validateSecurityAnswer(1, securityAnswer1.value);
      }
    }

    if (securityAnswer2) {
      securityAnswer2.addEventListener("input", function () {
        validateSecurityAnswerField(2, this.value);
        validateSecurityAnswer(2, this.value);
      });
      // Show feedback immediately if there's a value
      if (securityAnswer2.value.trim() !== "") {
        validateSecurityAnswer(2, securityAnswer2.value);
      }
    }

    if (securityAnswer3) {
      securityAnswer3.addEventListener("input", function () {
        validateSecurityAnswerField(3, this.value);
        validateSecurityAnswer(3, this.value);
      });
      // Show feedback immediately if there's a value
      if (securityAnswer3.value.trim() !== "") {
        validateSecurityAnswer(3, securityAnswer3.value);
      }
    }

    // Initialize password toggle functionality
    // Use a small delay to ensure the validation.js initPasswordToggle has run
    setTimeout(function () {
      if (typeof initPasswordToggle === "function") {
        initPasswordToggle();
      } else {
        // Fallback: initialize password toggle directly
        const toggleIcons = document.querySelectorAll(".toggle-password");
        toggleIcons.forEach((icon) => {
          // Check if we've already attached an event listener to prevent duplicates
          if (icon.dataset.listenerAttached === "true") {
            return;
          }

          // Check if we've already attached an event listener to prevent duplicates
          if (icon.dataset.listenerAttached === "true") {
            return;
          }

          icon.addEventListener("click", function () {
            // Find the input field within the same parent container
            const container =
              this.closest(".pass-input-field") ||
              this.closest(".password-field");
            if (!container) return;

            const input = container.querySelector("input");
            if (!input) return;

            // Toggle password visibility
            if (input.type === "password") {
              input.type = "text";
              this.classList.remove("fa-eye");
              this.classList.add("fa-eye-slash");
            } else {
              input.type = "password";
              this.classList.remove("fa-eye-slash");
              this.classList.add("fa-eye");
            }
          });

          // Mark that we've attached a listener to this icon
          icon.dataset.listenerAttached = "true";
        });
      }

      // No need to check form validity on page load since we're not disabling the button
    }, 200); // Slightly longer delay to ensure validation.js has run
  }, 100); // 100ms delay

  // Initialize step indicators
  updateForgotStepIndicators(1);
});

// Function to validate ID number in real-time
function validateIdNumber(idNumber) {
  const msgError = document.querySelector(".step-1 .message-error");
  if (!msgError) return;

  // Clear previous message
  msgError.textContent = "";
  msgError.style.visibility = "hidden";

  if (idNumber.trim() === "") {
    return; // Don't show error for empty field
  }

  // Check if input contains only numbers and hyphens
  if (!/^[0-9-]+$/.test(idNumber.trim())) {
    msgError.textContent = "ID Number must contain only numbers and hyphens";
    msgError.style.visibility = "visible";
  }
}

// Function to validate security answer fields in real-time
function validateSecurityAnswerField(questionId, answer) {
  const errorElement = document.getElementById(`error${questionId}`);
  if (!errorElement) return;

  // Clear previous message
  errorElement.textContent = "";
  errorElement.style.visibility = "hidden";

  if (answer.trim() === "") {
    errorElement.textContent = "Please answer this question";
    errorElement.style.visibility = "visible";
  }
}

// Helper function to hide feedback
function hideFeedback(questionId) {
  const feedbackElement = document.getElementById(`feedback${questionId}`);
  if (feedbackElement) {
    feedbackElement.style.display = "none";
    feedbackElement.innerHTML = ""; // Clear the content as well
  }
}

// Function to validate security answers in real-time
function validateSecurityAnswer(questionId, answer) {
  // Get the feedback element
  const feedbackElement = document.getElementById(`feedback${questionId}`);
  if (!feedbackElement) return;

  // If answer is empty, hide feedback and let the field validation handle the error message
  if (answer.trim() === "") {
    hideFeedback(questionId);
    return;
  }

  // Only validate if we have user data and answer is not empty
  if (!window.userData) {
    hideFeedback(questionId);
    return;
  }

  // Show that we're checking
  feedbackElement.textContent = "Checking...";
  feedbackElement.style.color = "#ffa500"; // Orange
  feedbackElement.style.display = "block";

  // Send AJAX request to validate the answer
  fetch("index.php?action=validateSecurityAnswer", {
    method: "POST",
    body: new URLSearchParams({
      id_number: window.userData.id_number,
      question_id: questionId,
      answer: answer,
    }),
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.valid !== undefined) {
        if (data.valid) {
          // Correct answer
          feedbackElement.innerHTML =
            '<p style="color: #23ad5c;">Correct answer!</p>';
          feedbackElement.style.color = "#23ad5c"; // Green
        } else {
          // Incorrect answer
          feedbackElement.innerHTML =
            '<p style="color: #e74c3c;">Incorrect answer!</p>';
          feedbackElement.style.color = "#e74c3c"; // Red
        }
        // feedbackElement.style.display = "block";
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

// Helper function to escape HTML
function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

function checkPasswordStrength(password) {
  console.log("checkPasswordStrength called with password:", password);
  if (!strengthBar || !strengthMessage) {
    console.log("Strength bar or message not found");
    return;
  }

  let strength = 0;
  let message = "";
  let color = "";
  let width = "0%";

  if (password.length === 0) {
    strengthBar.style.display = "none";
    strengthMessage.style.display = "none";
    strengthMessage.style.visibility = "hidden";
    return;
  }

  strengthBar.style.display = "block";
  strengthMessage.style.display = "block";
  strengthMessage.style.visibility = "visible";

  // Check for lowercase
  const hasLower = /[a-z]/.test(password);
  // Check for uppercase
  const hasUpper = /[A-Z]/.test(password);
  // Check for numbers
  const hasNumber = /[0-9]/.test(password);
  // Check for special characters
  const hasSpecial = /[^a-zA-Z0-9]/.test(password);
  // Check length
  const hasLength = password.length >= 8;

  // Calculate strength (0-5)
  if (hasLower) strength++;
  if (hasUpper) strength++;
  if (hasNumber) strength++;
  if (hasSpecial) strength++;
  if (hasLength) strength++;

  // Create detailed feedback message
  let missing = [];
  if (!hasLower) missing.push("lowercase letter");
  if (!hasUpper) missing.push("uppercase letter");
  if (!hasNumber) missing.push("number");
  if (!hasSpecial) missing.push("special character");
  if (!hasLength) missing.push("8+ characters");

  // Determine strength level with detailed feedback
  if (strength < 4) {
    if (missing.length > 0) {
      message = "Missing: " + missing.join(", ");
    } else {
      message =
        "Your password is too weak (must be 8+ chars, include upper, lower, number)";
    }
    color = "red";
    width = "25%";
  } else if (strength >= 4 && strength <= 4) {
    if (missing.length > 0) {
      message = "Add " + missing.join(", ") + " for stronger password";
    } else {
      message =
        "Your password is medium (add special characters for more strength)";
    }
    color = "orange";
    width = "75%";
  } else {
    message = "Your password is strong";
    color = "#23ad5c";
    width = "100%";
  }

  strengthBar.style.width = width;
  strengthBar.style.backgroundColor = color;
  strengthMessage.textContent = message;
  strengthMessage.style.color = color;
  strengthMessage.style.marginLeft = "5px";
  strengthMessage.style.fontSize = "11px";
  strengthMessage.style.visibility = "visible";

  // Hide the error message when showing strength message
  const newPasswordInput = document.getElementById("newPassword");
  if (typeof clearFieldError === "function" && newPasswordInput) {
    clearFieldError(newPasswordInput);
  }
}

// Password match checker for forgot password
function checkForgotPasswordMatch() {
  if (!forgotPasswordInput || !forgotConfirmPasswordInput) return;

  const password = forgotPasswordInput.value;
  const confirm = forgotConfirmPasswordInput.value;

  const confirmField = forgotConfirmPasswordInput.closest(
    ".forgot-confirm-field"
  );
  if (!confirmField) return;

  let matchMessage = confirmField.querySelector(
    ".password-match-message-forgot"
  );
  if (!matchMessage) return;

  // Clear previous error messages
  const passwordError = confirmField.querySelector(".message-error");
  if (passwordError) passwordError.textContent = "";

  if (confirm === "") {
    matchMessage.style.display = "none";
    forgotConfirmPasswordInput.style.borderBottom = "";
    return false;
  }

  if (password === confirm) {
    matchMessage.textContent = "Passwords match.";
    matchMessage.style.color = "#23ad5c";
    matchMessage.style.display = "block";
    forgotConfirmPasswordInput.style.borderBottom =
      "1px solid rgba(0,255,0,0.5)";
    return true;
  }

  matchMessage.textContent = "Password does not match";
  matchMessage.style.color = "red";
  matchMessage.style.display = "block";
  forgotConfirmPasswordInput.style.borderBottom = "1px solid rgba(255,0,0,0.5)";

  return false;
}

// Function to check if password meets requirements
function isPasswordValid(password, confirmPassword) {
  // Get or create the password message element
  // let passwordMessage = document.querySelector("#passwordMessage");
  // if (!passwordMessage) {
  //   passwordMessage = document.createElement("div");
  //   passwordMessage.id = "passwordMessage";

  //   // Insert below the password input field
  //   const passwordInput = document.getElementById("newPassword");
  //   if (passwordInput && passwordInput.parentNode) {
  //     passwordInput.parentNode.insertBefore(
  //       passwordMessage,
  //       passwordInput.nextSibling
  //     );
  //   }
  // }

  // Style the message
  // passwordMessage.style.textAlign = "center";
  // passwordMessage.style.marginTop = "5px";
  // passwordMessage.style.fontSize = "12px";
  // passwordMessage.style.width = "100%";

  // Check password strength
  const hasLower = /[a-z]/.test(password);
  const hasUpper = /[A-Z]/.test(password);
  const hasNumber = /[0-9]/.test(password);
  const hasSpecial = /[^a-zA-Z0-9]/.test(password);
  const hasLength = password.length >= 8;

  let strength = 0;
  if (hasLower) strength++;
  if (hasUpper) strength++;
  if (hasNumber) strength++;
  if (hasSpecial) strength++;
  if (hasLength) strength++;

  let missing = [];
  if (!hasLower) missing.push("lowercase letter");
  if (!hasUpper) missing.push("uppercase letter");
  if (!hasNumber) missing.push("number");
  if (!hasSpecial) missing.push("special character");
  if (!hasLength) missing.push("8+ characters");

  // Weak password (less than 4 criteria met)
  if (strength < 4) {
    if (missing.length > 0) {
      return { valid: false, message: "Missing: " + missing.join(", ") };
    } else {
      return { valid: false, message: "Your password is too weak" };
    }
  } else if (strength === 4) {
    if (missing.length > 0) {
      return {
        valid: false,
        message: "Add " + missing.join(", ") + " for stronger passwords",
      };
      message.style.textColor = clearInterval;
    } else {
      return {
        valid: false,
        message: "Your password is medium. Please make it stronger.",
      };
    }
  }
  // Medium password (exactly 4 criteria met) - not acceptable per requirements

  // Strong password (all 5 criteria met)
  return { valid: true };
}

// Function to check form validity and show alerts
function checkFormValidity() {
  // This function is kept for compatibility but doesn't disable the button
  return true;
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
  passwordSuccess.style.display = "none";

  // Clear password match message
  if (typeof checkForgotPasswordMatch === "function") {
    checkForgotPasswordMatch();
  }

  // Get the password input fields
  const newPasswordInput = document.getElementById("newPassword");
  const confirmPasswordInput = document.getElementById("confirmPassword");

  // Clear previous errors using clearFieldError function (with safety check)
  if (typeof clearFieldError === "function") {
    if (newPasswordInput) clearFieldError(newPasswordInput);
    if (confirmPasswordInput) clearFieldError(confirmPasswordInput);
  }

  // Check if passwords are provided
  let hasErrors = false;

  // Check if new password contains spaces (prioritize over empty check)
  if (/\s/.test(new_password)) {
    if (typeof setFieldError === "function" && newPasswordInput) {
      setFieldError(newPasswordInput, "New Password cannot contain spaces");
    }
    hasErrors = true;
  } else if (!new_password) {
    if (typeof setFieldError === "function" && newPasswordInput) {
      setFieldError(newPasswordInput, "New Password is required");
    }
    hasErrors = true;
  }

  // Check if confirm password contains spaces (prioritize over empty check)
  if (/\s/.test(confirm_password)) {
    if (typeof setFieldError === "function" && confirmPasswordInput) {
      setFieldError(confirmPasswordInput, "Re-enter Password cannot contain spaces");
    }
    hasErrors = true;
  } else if (!confirm_password) {
    if (typeof setFieldError === "function" && confirmPasswordInput) {
      setFieldError(confirmPasswordInput, "Re-enter Password is required");
    }
    hasErrors = true;
  }

  // If either field has errors, stop submission
  if (hasErrors) {
    // Stay on current step (Step 3), no need to update step indicators
    return;
  }

  // Check for password validation errors before submission
  // Get the password strength message element
  const passwordStrengthMessage = document.getElementById(
    "passwordStrengthMessage"
  );

  // Check password strength using our isPasswordValid function
  if (typeof isPasswordValid === "function") {
    const passwordValidation = isPasswordValid(new_password, confirm_password);
    if (!passwordValidation.valid) {
      if (typeof setFieldError === "function" && newPasswordInput) {
        setFieldError(newPasswordInput, passwordValidation.message);
        // Set the error container color based on password strength
        const errorContainer = findErrorContainer(newPasswordInput);
        if (errorContainer) {
          // Determine color based on message content
          let color = "red"; // Default to red for weak passwords
          if (
            passwordValidation.message.includes("medium") ||
            passwordValidation.message.includes("Add") ||
            passwordValidation.message.includes("stronger")
          ) {
            color = "orange"; // Orange for medium strength passwords
          }
          errorContainer.style.color = color;
          // Center the error message without causing spacing issues
          errorContainer.style.textAlign = "center";
          errorContainer.style.position = "absolute";
          errorContainer.style.left = "50%";
          errorContainer.style.transform = "translateX(-50%)";
          errorContainer.style.width = "100%";
        }
        // Hide the password strength message when showing error
        if (passwordStrengthMessage) {
          passwordStrengthMessage.style.visibility = "hidden";
        }
      }
      hasErrors = true;
    }
  }

  // If there are password strength errors, stop submission
  if (hasErrors) {
    // Stay on current step (Step 3), no need to update step indicators
    return;
  }

  //Check if passwords don't match using our checkForgotPasswordMatch function
  if (typeof checkForgotPasswordMatch === "function") {
    const passwordsMatch = checkForgotPasswordMatch();
    if (!passwordsMatch) {
      if (typeof setFieldError === "function" && confirmPasswordInput) {
        setFieldError(confirmPasswordInput, "");
      }
      hasErrors = true;
    }
  }

  // If there are password mismatch errors, stop submission
  if (hasErrors) {
    // Stay on current step (Step 3), no need to update step indicators
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
        // Show success modal
        showSuccessModal(data.message || "Your password has been successfully changed!");
        
        // Redirect to login after 3 seconds
        setTimeout(() => {
          window.location.href = "index.php?action=login";
        }, 3000);
      } else {
        // Show error under new password field
        if (typeof setFieldError === "function" && newPasswordInput) {
          setFieldError(
            newPasswordInput,
            data.message || "Failed to reset password."
          );
        }
        // Stay on current step (Step 3)
        updateForgotStepIndicators(3);
      }
    })
    .catch((err) => {
      console.error("AJAX error:", err);
      // Show error under new password field
      if (typeof setFieldError === "function" && newPasswordInput) {
        setFieldError(newPasswordInput, "An error occurred. Please try again.");
      }
      // Stay on current step (Step 3)
      updateForgotStepIndicators(3);
    });
});

// Function to show password reset success popup
function showPasswordResetSuccessPopup(message) {
  // Create popup container if it doesn't exist
  let popup = document.getElementById('passwordResetSuccessPopup');
  if (!popup) {
    popup = document.createElement('div');
    popup.id = 'passwordResetSuccessPopup';
    popup.className = 'success-popup centered';
    popup.innerHTML = `
      <div class="success-popup-content">
        <i class="fas fa-check-circle"></i>
        <p>${message}</p>
      </div>
    `;
    document.body.appendChild(popup);
  } else {
    // Update message if popup already exists
    const messageElement = popup.querySelector('p');
    if (messageElement) {
      messageElement.textContent = message;
    }
    // Ensure it's centered
    popup.className = 'success-popup centered';
  }
  
  // Show the popup
  popup.style.display = 'block';
  
  // Hide popup after 3 seconds
  setTimeout(() => {
    popup.style.display = 'none';
  }, 3000);
}

// Function to show success modal
function showSuccessModal(message) {
  const modal = document.getElementById('successModal');
  if (modal) {
    // Update the message
    const messageElement = modal.querySelector('.success-message');
    if (messageElement) {
      messageElement.innerHTML = message.replace(/\n/g, '<br>');
    }
    
    // Show the modal
    modal.style.display = 'flex';
    
    // Add class to trigger animation
    setTimeout(() => {
      modal.classList.add('show');
    }, 10);
  }
}
