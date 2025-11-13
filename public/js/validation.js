/* <=================== PASSWORD VALIDATION ====================> */

let password, message, passInputField, passwordStrenght, confirmPasswordInput;

// Wait for DOM to load
document.addEventListener('DOMContentLoaded', function() {
  // Birthdate age calculator
  const birthdateInput = document.getElementById("birthdate");
  const ageInput = document.getElementById("age");
  
  if (birthdateInput && ageInput) {
    birthdateInput.addEventListener("change", function () {
      const birthDate = new Date(this.value);
      if (!this.value) return; // if empty, skip

      const today = new Date();
      let age = today.getFullYear() - birthDate.getFullYear();
      const monthDiff = today.getMonth() - birthDate.getMonth();

      // Adjust if birthday hasn't occurred yet this year
      if (
        monthDiff < 0 ||
        (monthDiff === 0 && today.getDate() < birthDate.getDate())
      ) {
        age--;
      }

      // Display age in the input field
      ageInput.value = age;
    });
  }

  // Password strength elements
  password = document.querySelector(".password");
  message = document.querySelector("#message");
  passInputField = document.querySelector(".pass-input-field");
  passwordStrenght = document.querySelector(".password-strenght");
  confirmPasswordInput = document.querySelector('input[name="confirm_password"]');

  // Only attach listeners if elements exist
  if (password) {
    password.addEventListener("input", handlePasswordInput);
  }

  if (confirmPasswordInput) {
    confirmPasswordInput.addEventListener('input', checkPasswordMatch);
  }
});

// Regex patterns
let regExpLower = /[a-z]/; // lowercase letters
let regExpUpper = /[A-Z]/; // uppercase letters
let regExpNumber = /\d/; // numbers
let regExpSpecial = /[!@#$%^&*(),.?":{}|<>_\-]/; // special characters

function handlePasswordInput() {
  if (!password || !message || !passInputField || !passwordStrenght) return;

  let val = password.value;
  let no = 0;

  if (val != "") {
    message.style.display = "flex";
    passwordStrenght.style.display = "flex";

    // Count how many conditions are satisfied
    let hasLower = regExpLower.test(val);
    let hasUpper = regExpUpper.test(val);
    let hasNumber = regExpNumber.test(val);
    let hasSpecial = regExpSpecial.test(val);
    let hasLength = val.length >= 8;

    // Strength rules
    if (!hasLength || !(hasLower && hasUpper && hasNumber)) {
      // Doesn't meet minimum secure requirements
      no = 1;
    } else if (hasLength && hasLower && hasUpper && hasNumber && !hasSpecial) {
      // Meets minimum requirements but no special character
      no = 2;
    } else if (hasLength && hasLower && hasUpper && hasNumber && hasSpecial) {
      // Meets minimum + has special character
      no = 3;
    }

    // Apply styling
    if (no == 1) {
      passInputField.style.borderColor = "red";
      message.style.display = "block";
      message.textContent =
        "Your password is too weak (must be 8+ chars, include upper, lower, number)";
      message.style.color = "red";
      passwordStrenght.style.width = "25%";
      passwordStrenght.style.backgroundColor = "red";
    }
    if (no == 2) {
      passInputField.style.borderColor = "orange";
      message.style.display = "block";
      message.textContent =
        "Your password is medium (add special characters for more strength)";
      message.style.color = "orange";
      passwordStrenght.style.width = "75%";
      passwordStrenght.style.backgroundColor = "orange";
    }
    if (no == 3) {
      passInputField.style.borderColor = "green";
      message.style.display = "block";
      message.textContent = "Your password is strong";
      message.style.color = "#23ad5c";
      passwordStrenght.style.width = "100%";
      passwordStrenght.style.backgroundColor = "#23ad5c";
    }
  } else {
    passwordStrenght.style.display = "none";
    message.style.display = "none";
    passInputField.style.borderColor = "#ccc";
  }
}

function checkPasswordMatch() {
  if (!confirmPasswordInput) return;
  
  const confirmPassword = confirmPasswordInput.value;
  const confirmField = confirmPasswordInput.closest('.input-field');
  const errorContainer = confirmPasswordInput.closest('.form-field').querySelector('.input-error-container');
  
  if (confirmPassword.length > 0) {
    if (password.value === confirmPassword) {
      // Passwords match - show green checkmark
      confirmField.style.borderColor = '#00d100';
      if (errorContainer) {
        errorContainer.innerHTML = '<span style="color: #00d100;">✓ Passwords match</span>';
      }
    } else {
      // Passwords don't match - show red X
      confirmField.style.borderColor = '#f80000';
      if (errorContainer) {
        errorContainer.innerHTML = '<span style="color: #f80000;">❌ Passwords do not match</span>';
      }
    }
  } else {
    // Reset when confirm field is empty
    confirmField.style.borderColor = '#ccc';
    if (errorContainer) {
      errorContainer.innerHTML = '';
    }
  }
}
