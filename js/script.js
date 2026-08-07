// Utility to show errors without duplication
function showError(field, message) {
  const errorSpan = document.getElementById(field + "_error");
  if (errorSpan) {
    errorSpan.textContent = message || "";
  }
}

// Validate names (first, middle, last)
function validateName(input, field) {
  const value = input.value.trim();
  let error = "";

  if (value === "") return; // skip if optional (middle name)

  if (!/^[A-Z][a-z]*( [A-Z][a-z]*)*$/.test(value)) {
    error = `${field.replace(
      "_",
      " "
    )} must start with uppercase and follow proper format.`;
  } else if (/[^a-zA-Z ]/.test(value)) {
    error = `${field.replace("_", " ")} must not contain special characters.`;
  } else if (/\s{2,}/.test(value)) {
    error = `${field.replace("_", " ")} must not contain double spaces.`;
  } else if (/(.)\1{2,}/i.test(value)) {
    error = `${field.replace(
      "_",
      " "
    )} must not contain 3 consecutive repeating letters.`;
  }

  showError(field, error);
  return error === "";
}

// Password strength check
function checkPasswordStrength() {
  const password = document.getElementById("password").value;
  const strengthSpan = document.getElementById("password_strength");
  let strength = 0;

  if (/[a-z]/.test(password)) strength++;
  if (/[A-Z]/.test(password)) strength++;
  if (/[0-9]/.test(password)) strength++;
  if (/[^a-zA-Z0-9]/.test(password)) strength++;
  
  // Check length separately
  const hasLength = password.length >= 8;
  if (hasLength) strength++;

  const levels = ["Too Weak", "Weak", "Medium", "Strong", "Very Strong"];
  strengthSpan.textContent = password ? levels[strength] : "";
  
  // Updated: Password must meet minimum requirements (at least 4 criteria) - medium strength (4/5) is not acceptable
  // For a password to be considered strong, it must have at least 5 criteria (including length)
  strengthSpan.style.color = strength < 5 ? "red" : "green";

  return strength >= 5;
}

// Confirm password match
function confirmPasswordCheck() {
  const pass = document.getElementById("password").value;
  const confirm = document.getElementById("confirm_password").value;
  let error = "";

  if (confirm && pass !== confirm) {
    error = "Passwords do not match.";
  }

  showError("confirm", error);
  return error === "";
}

// Final form validation before submit
function validateForm() {
  let valid = true;

  valid &= validateName(document.getElementById("first_name"), "first_name");
  valid &= validateName(document.getElementById("middle_name"), "middle_name");
  valid &= validateName(document.getElementById("last_name"), "last_name");
  valid &= checkPasswordStrength();
  valid &= confirmPasswordCheck();

  return !!valid; // cast to true/false
}
// console.log("script.js loaded successfully!");

// function resetForm() {
//   alert("Form reset function executed!");
// }
