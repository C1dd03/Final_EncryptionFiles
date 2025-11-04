document.addEventListener("DOMContentLoaded", () => {
  const steps = document.querySelectorAll(".register-form .step");
  const form = document.querySelector(".register-form");
  let currentStep = 0;

  /*** Utility Functions ***/
  function showStep(stepIndex) {
    steps.forEach((step, idx) => step.classList.toggle("active", idx === stepIndex));
    clearStepErrors();
  }

  function capitalizeFirst(str) {
    if (!str) return "";
    return str.charAt(0).toUpperCase() + str.slice(1);
  }

  function clearStepErrors() {
    const errorContainer = form.querySelector(".step-error-container");
    if (errorContainer) errorContainer.remove();

    // Reset input borders
    steps[currentStep].querySelectorAll("input, select").forEach(input => {
      input.style.border = "";
    });
  }

  function showStepErrors(errors, firstInvalidInput = null) {
    clearStepErrors();
    if (errors.length === 0) return;

    // Highlight first invalid input
    if (firstInvalidInput) {
      firstInvalidInput.style.borderBottom = "1px solid rgba(255,0,0,0.5)";
      firstInvalidInput.scrollIntoView({ behavior: "smooth", block: "center" });
    }

    // Create error container
    const container = document.createElement("div");
    container.className = "step-error-container";

    errors.forEach(err => {
      const div = document.createElement("div");
      div.innerText = err;
      container.appendChild(div);
    });

    form.insertBefore(container, steps[currentStep]);
  }

  /*** Extension "Other" Input Handling ***/
  const extensionSelect = document.getElementById('extension');
  const otherExtensionInput = document.getElementById('other_extension');
  if (otherExtensionInput) otherExtensionInput.style.display = 'none'; // hide initially

  if (extensionSelect && otherExtensionInput) {
    extensionSelect.addEventListener('change', () => {
      if (extensionSelect.value === 'Other') {
        otherExtensionInput.style.display = 'block';
      } else {
        otherExtensionInput.style.display = 'none';
        otherExtensionInput.value = '';
      }
    });

    // Auto-uppercase as user types
    otherExtensionInput.addEventListener('input', () => {
      otherExtensionInput.value = otherExtensionInput.value.toUpperCase();
    });
  }

  /*** Validation Functions ***/
  function validateField(input) {
    const namePattern = /^[A-Za-z\s]*$/;
    const tripleLetterPattern = /(.)\1\1/;
    const fieldName = input.name.replace(/_/g, " ");
    let value = input.value.trim();

    function capitalizeMessage(msg) {
      if (!msg) return "";
      return msg.charAt(0).toUpperCase() + msg.slice(1);
    }

    // Auto-capitalize name fields (except "Other Extension")
    if (['first_name','middle_name','last_name'].includes(input.name) && value.length > 0) {
      value = capitalizeFirst(value);
      input.value = value;
    }

    // Required check
    if (input.required && value === "") return capitalizeMessage(`${fieldName} is required.`);

    // Name field rules
    if (['first_name','middle_name','last_name'].includes(input.name)) {
      if (!namePattern.test(value)) return capitalizeMessage(`${fieldName}: No special characters`);
      if (/^\d+[A-Za-z]/.test(value)) return capitalizeMessage(`${fieldName}: Numbers first not allowed`);
      if (/\s{2,}/.test(value)) return capitalizeMessage(`${fieldName}: No double spaces`);
      if (value === value.toUpperCase() && value.length > 1) return capitalizeMessage(`${fieldName}: Avoid all caps`);
      if (tripleLetterPattern.test(value.toLowerCase())) return capitalizeMessage(`${fieldName}: No 3 same letters in a row`);
      if (value !== capitalizeFirst(value)) return capitalizeMessage(`${fieldName}: Start with capital letter`);
    }

    // Extension-specific validation
    if (input.name === 'extension') {
      if (value === 'Other' && otherExtensionInput && otherExtensionInput.value.trim() !== '') {
        let otherVal = otherExtensionInput.value.trim().toUpperCase();
        otherExtensionInput.value = otherVal;
        const validOtherExtensions = ['I','II','III','IV','V','IX','X'];
        if (!validOtherExtensions.includes(otherVal)) {
          return capitalizeMessage(`Other Extension: Must be I, II, III, IV, V, IX, or X`);
        }
      } else if (value !== '' && value !== 'Other') {
        // Uppercase predefined extension
        input.value = value.toUpperCase();
      }
    }

    // Birthdate / age
    if (input.name === 'birthdate' && value) {
      const birthDate = new Date(value);
      const today = new Date();
      let age = today.getFullYear() - birthDate.getFullYear();
      const m = today.getMonth() - birthDate.getMonth();
      if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) age--;
      const ageInput = input.closest('.step').querySelector('input[name="age"]');
      if (ageInput) ageInput.value = age;
      if (age < 18) return 'You must be at least 18 years old to register.';
    }

    return null; // valid
  }

  function validateStep(step) {
    const inputs = step.querySelectorAll("input, select");
    const errors = [];
    let firstInvalidInput = null;

    for (const input of inputs) {
      const error = validateField(input);
      if (error) {
        errors.push(error);
        if (!firstInvalidInput) firstInvalidInput = input;
        break; // stop at first invalid input
      }
    }

    showStepErrors(errors, firstInvalidInput);
    return errors.length === 0;
  }

  /*** Step Navigation ***/
  window.nextStep = function(stepNum) {
    const current = steps[stepNum - 1];
    if (validateStep(current)) {
      currentStep = Math.min(currentStep + 1, steps.length - 1);
      showStep(currentStep);
    }
  };

  window.prevStep = function(stepNum) {
    currentStep = Math.max(currentStep - 1, 0);
    showStep(currentStep);
  };

  /*** Form Submission ***/
  window.handleSubmit = function(form) {
    let allValid = true;
    steps.forEach(step => {
      if (!validateStep(step)) allValid = false;
    });

    if (!allValid) {
      alert("Please fix the errors before submitting.");
      return false;
    }
    return true;
  };

  /*** Auto-capitalize on blur ***/
  steps.forEach(step => {
    const inputs = step.querySelectorAll("input");
    inputs.forEach(input => {
      if (['first_name','middle_name','last_name'].includes(input.name)) {
        input.addEventListener("blur", () => {
          input.value = capitalizeFirst(input.value.trim());
        });
      }
    });
  });

  /*** Initial Display ***/
  showStep(currentStep);
});
