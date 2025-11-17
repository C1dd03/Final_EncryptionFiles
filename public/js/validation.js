/* <=================== PASSWORD VALIDATION ====================> */

let password,
  message,
  passInputField,
  passwordStrength,
  registerConfirmPasswordInput;

// Wait for DOM to load
document.addEventListener("DOMContentLoaded", function () {
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
});

// Initialize password toggle functionality
function initPasswordToggle() {
  const toggleIcons = document.querySelectorAll(".toggle-password");

  toggleIcons.forEach((icon) => {
    // Check if we've already attached an event listener to prevent duplicates
    if (icon.dataset.listenerAttached === "true") {
      return;
    }

    icon.addEventListener("click", function () {
      // Find the input field within the same parent container
      const container =
        this.closest(".pass-input-field") || this.closest(".password-field");
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

// Function to set field error
function setFieldError(field, message) {
  // Find the field container
  const fieldContainer = field.closest(".input-field") || field.closest(".pass-input-field");
  
  if (!fieldContainer) return;

  // Get or create error container
  let errorContainer = fieldContainer.querySelector(".input-error-container");
  if (!errorContainer) {
    errorContainer = document.createElement("div");
    errorContainer.className = "input-error-container";
    errorContainer.style.fontSize = "11px";
    errorContainer.style.color = "red";
    errorContainer.style.minHeight = "1.4em";
    errorContainer.style.margin = "4px 0px 10px 0px";
    errorContainer.style.lineHeight = "1.4";
    errorContainer.style.paddingLeft = "4px";
    errorContainer.style.visibility = "hidden";
    errorContainer.style.textAlign = "center";
    
    // Insert after the input field
    fieldContainer.parentNode.insertBefore(errorContainer, fieldContainer.nextSibling);
  }

  // Set error message and make visible
  errorContainer.textContent = message;
  errorContainer.style.visibility = "visible";
  
  // Add invalid class to field
  field.classList.add("invalid");
}

// Function to clear field error
function clearFieldError(field) {
  // Find the field container
  const fieldContainer = field.closest(".input-field") || field.closest(".pass-input-field");
  
  if (!fieldContainer) return;

  // Get error container
  const errorContainer = fieldContainer.querySelector(".input-error-container");
  if (errorContainer) {
    errorContainer.textContent = "";
    errorContainer.style.visibility = "hidden";
  }
  
  // Remove invalid class from field
  field.classList.remove("invalid");
}

// Function to find error container
function findErrorContainer(field) {
  // Find the field container
  const fieldContainer = field.closest(".input-field") || field.closest(".pass-input-field");
  
  if (!fieldContainer) return null;

  // Return error container
  return fieldContainer.querySelector(".input-error-container");
}