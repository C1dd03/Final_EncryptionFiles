/* <=================== PASSWORD VALIDATION ====================> */

let password, message, passInputField, passwordStrenght, confirmPasswordInput;

// Wait for DOM to load
document.addEventListener('DOMContentLoaded', function () {
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
    password.addEventListener("input", checkPasswordMatch); // Also check match when password changes
  }

  if (confirmPasswordInput) {
    confirmPasswordInput.addEventListener('input', checkPasswordMatch);
  }

  // Toggle password visibility for all password fields
  initPasswordToggle();
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

// Check if password and confirm password match
function checkPasswordMatch() {
  if (!password || !confirmPasswordInput) return;

  const passwordValue = password.value;
  const confirmValue = confirmPasswordInput.value;
  
  // Find the confirm password field container
  const confirmField = confirmPasswordInput.closest('.form-field');
  const confirmInputField = confirmPasswordInput.closest('.input-field');
  
  if (!confirmField || !confirmInputField) return;
  
  // Get or create the message container
  let matchMessage = confirmField.querySelector('.password-match-message');
  if (!matchMessage) {
    matchMessage = document.createElement('div');
    matchMessage.className = 'password-match-message';
    matchMessage.style.fontSize = '12px';
    matchMessage.style.marginTop = '2px';
    matchMessage.style.fontWeight = '500';
    
    // Insert after the input field but before error container
    const errorContainer = confirmField.querySelector('.input-error-container');
    if (errorContainer) {
      confirmField.insertBefore(matchMessage, errorContainer);
    } else {
      confirmField.appendChild(matchMessage);
    }
  }
  
  // Only show validation if confirm password field has content
  if (confirmValue === '') {
    matchMessage.style.display = 'none';
    confirmInputField.style.borderColor = '';
    return;
  }
  
  // Check if passwords match
  if (passwordValue === confirmValue && passwordValue !== '') {
    // Passwords match
    matchMessage.textContent = 'Password match.';
    matchMessage.style.color = '#23ad5c';
    matchMessage.style.display = 'block';
    confirmInputField.style.borderColor = '#23ad5c';
  } else {
    // Passwords don't match
    matchMessage.textContent = 'Password do not match.';
    matchMessage.style.color = 'red';
    matchMessage.style.display = 'block';
    confirmInputField.style.borderColor = 'red';
  }
}

// Initialize password toggle functionality
function initPasswordToggle() {
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


