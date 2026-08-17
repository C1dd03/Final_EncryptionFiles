const form = document.getElementById("forgotForm");
const msgError = document.querySelector(".message-error");
const msgSuccess = document.querySelector(".message-success");

let forgotPasswordInput,
  forgotConfirmPasswordInput,
  strengthBar,
  strengthMessage,
  passwordSuccess,
  submitButton;

function nextStepForgot(step) {
  const current = document.querySelector(`.step-${step}`);
  const next = document.querySelector(`.step-${step + 1}`);

  // Step 1: Verify email and load the user's security questions
  if (step === 1) {
    const emailInput = document.getElementById("forgotEmailInput");
    const emailError = document.getElementById("emailError");
    const nextBtn = current.querySelector(".next-btn");

    if (emailError) {
      emailError.textContent = "";
      emailError.style.visibility = "hidden";
    }

    const email = emailInput.value.trim();
    if (!email) {
      if (emailError) {
        emailError.textContent = "Please enter your email address";
        emailError.style.visibility = "visible";
      }
      updateForgotStepIndicators(1);
      return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      if (emailError) {
        emailError.textContent = "Please enter a valid email address";
        emailError.style.visibility = "visible";
      }
      updateForgotStepIndicators(1);
      return;
    }

    nextBtn.disabled = true;
    nextBtn.textContent = "Checking...";

    otpPost("index.php?action=verifyForgotEmail", { email: email })
      .then((data) => {
        nextBtn.disabled = false;
        nextBtn.textContent = "Next >";

        if (data.success) {
          window.userData = data.user;
          window.userQuestions = data.questions;

          document.getElementById("displayEmail").textContent =
            data.user.email;
          document.getElementById("displayUsername").textContent =
            data.user.username;

          if (data.questions && data.questions.length === 3) {
            document.getElementById("question1Label").textContent =
              data.questions[0].question_text;
            document.getElementById("question2Label").textContent =
              data.questions[1].question_text;
            document.getElementById("question3Label").textContent =
              data.questions[2].question_text;
          }

          if (emailError) {
            emailError.textContent = "";
            emailError.style.visibility = "hidden";
          }
          current.classList.remove("active");
          next.classList.add("active");
          updateForgotStepIndicators(2);
          current.querySelector('[name="security_answer_1"]').focus();
        } else {
          if (emailError) {
            emailError.textContent = data.message || "Email not found.";
            emailError.style.visibility = "visible";
          }
          updateForgotStepIndicators(1);
        }
      })
      .catch((err) => {
        console.error("AJAX error:", err);
        nextBtn.disabled = false;
        nextBtn.textContent = "Next >";
        if (emailError) {
          emailError.textContent = "An error occurred. Please try again.";
          emailError.style.visibility = "visible";
        }
        updateForgotStepIndicators(1);
      });

    return;
  }

  // Step 2: Verify security answers (at least 2 of 3), then send the OTP
  if (step === 2) {
    const id_number = window.userData ? window.userData.id_number : "";
    const ans1 = current.querySelector('[name="security_answer_1"]').value.trim();
    const ans2 = current.querySelector('[name="security_answer_2"]').value.trim();
    const ans3 = current.querySelector('[name="security_answer_3"]').value.trim();
    const securityError = document.getElementById("securityError");
    const error1 = document.getElementById("error1");
    const error2 = document.getElementById("error2");
    const error3 = document.getElementById("error3");
    const nextBtn = current.querySelector(".next-btn");

    if (securityError) {
      securityError.textContent = "";
      securityError.style.visibility = "hidden";
    }
    [error1, error2, error3].forEach((el) => {
      if (el) {
        el.textContent = "";
        el.style.visibility = "hidden";
      }
    });

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
      updateForgotStepIndicators(2);
      return;
    }

    nextBtn.disabled = true;
    nextBtn.textContent = "Checking...";

    otpPost("index.php?action=verifySecurityAnswers", {
      id_number: id_number,
      security_answer_1: ans1,
      security_answer_2: ans2,
      security_answer_3: ans3,
    })
      .then((data) => {
        nextBtn.disabled = false;
        nextBtn.textContent = "Next >";

        if (data.success) {
          if (securityError) {
            securityError.textContent = "";
            securityError.style.visibility = "hidden";
          }
          current.classList.remove("active");
          next.classList.add("active");
          updateForgotStepIndicators(3);

          // Send the OTP automatically once the security questions pass
          sendForgotOtp();
        } else {
          if (securityError) {
            securityError.textContent =
              data.message || "Verification failed. Please check your answers.";
            securityError.style.visibility = "visible";
          }
          updateForgotStepIndicators(2);
        }
      })
      .catch((err) => {
        console.error("AJAX error:", err);
        nextBtn.disabled = false;
        nextBtn.textContent = "Next >";
        if (securityError) {
          securityError.textContent = "An error occurred. Please try again.";
          securityError.style.visibility = "visible";
        }
        updateForgotStepIndicators(2);
      });

    return;
  }

  // Step 3: Verify the OTP
  if (step === 3) {
    const otpInput = document.getElementById("forgotOtpInput");
    const otpError = document.getElementById("otpError");
    const verifyBtn = current.querySelector(".next-btn");

    if (otpError) {
      otpError.textContent = "";
      otpError.style.visibility = "hidden";
    }

    const code = otpInput.value.trim();
    if (!/^\d{6}$/.test(code)) {
      if (otpError) {
        otpError.textContent = "Please enter the 6-digit code";
        otpError.style.visibility = "visible";
      }
      updateForgotStepIndicators(3);
      return;
    }

    otpSetButtonLoading(verifyBtn, true, "Verifying...", "Verify Code >");

    otpPost("index.php?action=verifyForgotOtp", { otp: code })
      .then((data) => {
        otpSetButtonLoading(verifyBtn, false, "", "Verify Code >");

        if (data.success) {
          if (otpError) {
            otpError.textContent = "";
            otpError.style.visibility = "hidden";
          }
          current.classList.remove("active");
          next.classList.add("active");
          updateForgotStepIndicators(4);
          document.getElementById("newPassword").focus();
        } else {
          if (otpError) {
            otpError.textContent =
              data.message || "Invalid code. Please try again.";
            otpError.style.visibility = "visible";
          }
          otpInput.value = "";
          otpInput.focus();
          updateForgotStepIndicators(3);
        }
      })
      .catch((err) => {
        console.error("AJAX error:", err);
        otpSetButtonLoading(verifyBtn, false, "", "Verify Code >");
        if (otpError) {
          otpError.textContent = "An error occurred. Please try again.";
          otpError.style.visibility = "visible";
        }
        updateForgotStepIndicators(3);
      });

    return;
  }
}

// Send the OTP for step 3 (called after security questions pass / on resend)
function sendForgotOtp() {
  const email = window.userData ? window.userData.email : "";
  const otpError = document.getElementById("otpError");
  const sendBtn = document.querySelector(".step-3 .next-btn");

  if (otpError) {
    otpError.textContent = "";
    otpError.style.visibility = "hidden";
  }

  // Spinner on the OTP step's button while the code is being emailed
  otpSetButtonLoading(sendBtn, true, "Sending code...", "Verify Code >");

  otpPost("index.php?action=sendForgotOtp", { email: email })
    .then((data) => {
      otpSetButtonLoading(sendBtn, false, "", "Verify Code >");
      otpShowDevBanner(
        document.getElementById("forgotOtpDevBanner"),
        data.dev_otp
      );
      if (data.success) {
        document.getElementById("otpEmailDisplay").textContent =
          data.email || email;
        otpStartExpiryTimer(
          data.expires_in || 300,
          document.getElementById("forgotOtpExpiry")
        );
        otpStartResendTimer(
          data.cooldown || 60,
          document.getElementById("forgotResendOtp"),
          document.getElementById("forgotOtpResendTimer")
        );
        document.getElementById("forgotOtpInput").value = "";
        document.getElementById("forgotOtpInput").focus();
      } else {
        if (otpError) {
          otpError.textContent = data.message || "Could not send the code.";
          otpError.style.visibility = "visible";
        }
      }
    })
    .catch((err) => {
      console.error("OTP send error:", err);
      otpSetButtonLoading(sendBtn, false, "", "Verify Code >");
      if (otpError) {
        otpError.textContent = "An error occurred. Please try again.";
        otpError.style.visibility = "visible";
      }
    });
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
  const numbers = document.querySelectorAll(".forgot-pass .number");
  const lines = document.querySelectorAll(".forgot-pass .line");

  // Reset all indicators
  numbers.forEach((num) => {
    num.classList.remove("active");
  });

  lines.forEach((line) => {
    line.classList.remove("active");
  });

  // Activate indicators based on current step
  for (let i = 0; i < currentStep; i++) {
    if (numbers[i]) numbers[i].classList.add("active");
    if (i > 0 && lines[i - 1]) lines[i - 1].classList.add("active");
  }
}

// Initialize step indicators on page load
document.addEventListener("DOMContentLoaded", function () {
  setTimeout(function () {
    forgotPasswordInput = document.getElementById("newPassword");
    forgotConfirmPasswordInput = document.getElementById("confirmPassword");
    strengthBar = document.getElementById("passwordStrengthBar");
    strengthMessage = document.getElementById("passwordStrengthMessage");
    passwordSuccess = document.getElementById("passwordSuccess");
    submitButton = document.querySelector(".step-4 .btn_submit");

    // Event listeners for password strength and match validation
    if (forgotPasswordInput) {
      forgotPasswordInput.addEventListener("input", function () {
        if (/\s/.test(this.value)) {
          if (typeof setFieldError === "function") {
            setFieldError(this, "New Password cannot contain spaces");
          }
          if (strengthBar) strengthBar.style.display = "none";
          if (strengthMessage) {
            strengthMessage.style.display = "none";
            strengthMessage.style.visibility = "hidden";
          }
          if (typeof checkForgotPasswordMatch === "function") {
            checkForgotPasswordMatch();
          }
          return;
        }

        checkPasswordStrength(this.value);
        if (
          forgotConfirmPasswordInput &&
          forgotConfirmPasswordInput.value.length > 0
        ) {
          checkForgotPasswordMatch();
        }
        if (typeof clearFieldError === "function") {
          clearFieldError(this);
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
        if (/\s/.test(this.value)) {
          if (typeof setFieldError === "function") {
            setFieldError(this, "Re-enter Password cannot contain spaces");
          }
          if (typeof checkForgotPasswordMatch === "function") {
            checkForgotPasswordMatch();
          }
          return;
        }

        checkForgotPasswordMatch();
        if (typeof clearFieldError === "function") {
          clearFieldError(this);
        }
      });
    }

    // Real-time validation for security answers
    [1, 2, 3].forEach((q) => {
      const input = document.querySelector(
        `[name="security_answer_${q}"]`
      );
      if (input) {
        input.addEventListener("input", function () {
          validateSecurityAnswerField(q, this.value);
          validateSecurityAnswer(q, this.value);
        });
        if (input.value.trim() !== "") {
          validateSecurityAnswer(q, input.value);
        }
      }
    });

    // Resend OTP link (step 3)
    const resendLink = document.getElementById("forgotResendOtp");
    if (resendLink) {
      resendLink.addEventListener("click", function (e) {
        e.preventDefault();
        const otpError = document.getElementById("otpError");
        if (otpError) {
          otpError.textContent = "";
          otpError.style.visibility = "hidden";
        }
        resendLink.style.display = "none";

        otpPost("index.php?action=resendOtp", {})
          .then((data) => {
            otpShowDevBanner(
              document.getElementById("forgotOtpDevBanner"),
              data.dev_otp
            );
            if (data.success) {
              otpStartExpiryTimer(
                data.expires_in || 300,
                document.getElementById("forgotOtpExpiry")
              );
              otpStartResendTimer(
                data.cooldown || 60,
                resendLink,
                document.getElementById("forgotOtpResendTimer")
              );
              document.getElementById("forgotOtpInput").value = "";
              document.getElementById("forgotOtpInput").focus();
            } else {
              otpStartResendTimer(
                data.cooldown || 60,
                resendLink,
                document.getElementById("forgotOtpResendTimer")
              );
              if (otpError) {
                otpError.textContent =
                  data.message || "Could not resend the code.";
                otpError.style.visibility = "visible";
              }
            }
          })
          .catch((err) => {
            console.error("OTP resend error:", err);
            resendLink.style.display = "inline";
          });
      });
    }

    // Initialize password toggle functionality
    setTimeout(function () {
      if (typeof initPasswordToggle === "function") {
        initPasswordToggle();
      } else {
        const toggleIcons = document.querySelectorAll(".toggle-password");
        toggleIcons.forEach((icon) => {
          if (icon.dataset.listenerAttached === "true") return;
          icon.dataset.listenerAttached = "true";
          icon.addEventListener("click", function () {
            const container =
              this.closest(".pass-input-field") ||
              this.closest(".password-field");
            if (!container) return;
            const input = container.querySelector("input");
            if (!input) return;
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
        });
      }
    }, 200);
  }, 100);

  // Initialize step indicators
  updateForgotStepIndicators(1);
});

// Function to validate security answer fields in real-time
function validateSecurityAnswerField(questionId, answer) {
  const errorElement = document.getElementById(`error${questionId}`);
  if (!errorElement) return;

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
    feedbackElement.innerHTML = "";
  }
}

// Function to validate security answers in real-time
function validateSecurityAnswer(questionId, answer) {
  const feedbackElement = document.getElementById(`feedback${questionId}`);
  if (!feedbackElement) return;

  if (answer.trim() === "") {
    hideFeedback(questionId);
    return;
  }

  if (!window.userData) {
    hideFeedback(questionId);
    return;
  }

  feedbackElement.textContent = "Checking...";
  feedbackElement.style.color = "#ffa500";
  feedbackElement.style.display = "block";

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
          feedbackElement.innerHTML =
            '<p style="color: #23ad5c;">Correct answer!</p>';
          feedbackElement.style.color = "#23ad5c";
        } else {
          feedbackElement.innerHTML =
            '<p style="color: #e74c3c;">Incorrect answer!</p>';
          feedbackElement.style.color = "#e74c3c";
        }
      } else {
        hideFeedback(questionId);
      }
    })
    .catch((err) => {
      console.error("AJAX error:", err);
      hideFeedback(questionId);
    });
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
    strengthMessage.style.visibility = "hidden";
    return;
  }

  strengthBar.style.display = "block";
  strengthMessage.style.display = "block";
  strengthMessage.style.visibility = "visible";

  const hasLower = /[a-z]/.test(password);
  const hasUpper = /[A-Z]/.test(password);
  const hasNumber = /[0-9]/.test(password);
  const hasSpecial = /[^a-zA-Z0-9]/.test(password);
  const hasLength = password.length >= 8;

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

  if (strength < 4) {
    if (missing.length > 0) {
      message = "Missing: " + missing.join(", ");
    } else {
      message =
        "Your password is too weak (must be 8+ chars, include upper, lower, number)";
    }
    color = "red";
    width = "25%";
  } else if (strength === 4) {
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
    } else {
      return {
        valid: false,
        message: "Your password is medium. Please make it stronger.",
      };
    }
  }

  return { valid: true };
}

// Handle final submit (Step 4): OTP + security questions were verified server-side already
form.addEventListener("submit", function (e) {
  e.preventDefault();
  const step4 = document.querySelector(".step-4");

  const new_password = step4.querySelector('[name="new_password"]').value;
  const confirm_password = step4.querySelector(
    '[name="confirm_password"]'
  ).value;

  if (passwordSuccess) passwordSuccess.style.display = "none";

  if (typeof checkForgotPasswordMatch === "function") {
    checkForgotPasswordMatch();
  }

  const newPasswordInput = document.getElementById("newPassword");
  const confirmPasswordInput = document.getElementById("confirmPassword");

  if (typeof clearFieldError === "function") {
    if (newPasswordInput) clearFieldError(newPasswordInput);
    if (confirmPasswordInput) clearFieldError(confirmPasswordInput);
  }

  let hasErrors = false;

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

  if (/\s/.test(confirm_password)) {
    if (typeof setFieldError === "function" && confirmPasswordInput) {
      setFieldError(
        confirmPasswordInput,
        "Re-enter Password cannot contain spaces"
      );
    }
    hasErrors = true;
  } else if (!confirm_password) {
    if (typeof setFieldError === "function" && confirmPasswordInput) {
      setFieldError(confirmPasswordInput, "Re-enter Password is required");
    }
    hasErrors = true;
  }

  if (hasErrors) {
    return;
  }

  const passwordStrengthMessage = document.getElementById(
    "passwordStrengthMessage"
  );

  if (typeof isPasswordValid === "function") {
    const passwordValidation = isPasswordValid(new_password, confirm_password);
    if (!passwordValidation.valid) {
      if (typeof setFieldError === "function" && newPasswordInput) {
        setFieldError(newPasswordInput, passwordValidation.message);
        const errorContainer = findErrorContainer(newPasswordInput);
        if (errorContainer) {
          let color = "red";
          if (
            passwordValidation.message.includes("medium") ||
            passwordValidation.message.includes("Add") ||
            passwordValidation.message.includes("stronger")
          ) {
            color = "orange";
          }
          errorContainer.style.color = color;
          errorContainer.style.textAlign = "center";
          errorContainer.style.position = "absolute";
          errorContainer.style.left = "50%";
          errorContainer.style.transform = "translateX(-50%)";
          errorContainer.style.width = "100%";
        }
        if (passwordStrengthMessage) {
          passwordStrengthMessage.style.visibility = "hidden";
        }
      }
      hasErrors = true;
    }
  }

  if (hasErrors) {
    return;
  }

  if (typeof checkForgotPasswordMatch === "function") {
    const passwordsMatch = checkForgotPasswordMatch();
    if (!passwordsMatch) {
      if (typeof setFieldError === "function" && confirmPasswordInput) {
        setFieldError(confirmPasswordInput, "");
      }
      hasErrors = true;
    }
  }

  if (hasErrors) {
    return;
  }

  otpPost("index.php?action=resetPassword", {
    new_password: new_password,
    confirm_password: confirm_password,
  })
    .then((data) => {
      if (data.success) {
        showSuccessModal(
          data.message || "Your password has been successfully changed!"
        );
        setTimeout(() => {
          window.location.href = "index.php?action=login";
        }, 3000);
      } else {
        if (typeof setFieldError === "function" && newPasswordInput) {
          setFieldError(
            newPasswordInput,
            data.message || "Failed to reset password."
          );
        }
        updateForgotStepIndicators(4);
      }
    })
    .catch((err) => {
      console.error("AJAX error:", err);
      if (typeof setFieldError === "function" && newPasswordInput) {
        setFieldError(newPasswordInput, "An error occurred. Please try again.");
      }
      updateForgotStepIndicators(4);
    });
});

// Function to show success modal
function showSuccessModal(message) {
  const modal = document.getElementById("successModal");
  if (modal) {
    const messageElement = modal.querySelector(".success-message");
    if (messageElement) {
      messageElement.innerHTML = message.replace(/\n/g, "<br>");
    }
    modal.style.display = "flex";
    setTimeout(() => {
      modal.classList.add("show");
    }, 10);
  }
}
