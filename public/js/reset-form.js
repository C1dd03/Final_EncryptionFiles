document.addEventListener("DOMContentLoaded", () => {
  const steps = document.querySelectorAll(".register-form .step");
  const form = document.querySelector(".register-form");
  let currentStep = 0;

  /*** Utility Functions ***/
  function showStep(stepIndex) {
    steps.forEach((step, idx) => step.classList.toggle("active", idx === stepIndex));
    clearStepErrors();
  }

  function clearStepErrors() {
    const errorContainer = form.querySelector(".step-error-container");
    if (errorContainer) errorContainer.remove();
    steps[currentStep].querySelectorAll("input, select").forEach(input => {
      input.style.border = "";
    });
  }

  function showStepErrors(errors, firstInvalidInput = null) {
    clearStepErrors();
    if (errors.length === 0) return;

    if (firstInvalidInput) {
      firstInvalidInput.style.borderBottom = "1px solid rgba(255,0,0,0.5)";
      firstInvalidInput.scrollIntoView({ behavior: "smooth", block: "center" });
    }

    const container = document.createElement("div");
    container.className = "step-error-container";
    errors.forEach(err => {
      const div = document.createElement("div");
      div.innerText = err;
      container.appendChild(div);
    });

    form.insertBefore(container, steps[currentStep]);
  }

  /*** Extension Handling ***/
const extensionSelect = document.getElementById("extension");
const otherExtensionInput = document.getElementById("other_extension");

extensionSelect.addEventListener("change", () => {
  if (extensionSelect.value === "Other") {
    extensionSelect.style.display = "none"; // hide dropdown
    otherExtensionInput.style.display = "block"; // show input
    otherExtensionInput.focus();
  }
});

otherExtensionInput.addEventListener("blur", () => {
  if (!otherExtensionInput.value.trim()) {
    otherExtensionInput.style.display = "none";
    extensionSelect.style.display = "block";
    extensionSelect.value = "";
  }
});

/*** ✨ Auto Uppercase Only (No Blocking Letters) ***/
otherExtensionInput.addEventListener("input", () => {
  otherExtensionInput.value = otherExtensionInput.value.toUpperCase();
});

/*** ✅ Validation Function for Other Extension ***/
function validateExtension(input) {
  const value = input.value.trim().toUpperCase();
  const validRoman = /^(I|II|III|IV|V|VI|VII|VIII|IX|X)$/;

  if (!value) {
    return "Other Extension: This field is required.";
  }

  if (!validRoman.test(value)) {
    return "Other Extension: Must be a valid Roman numeral (I–X).";
  }

  return null;
}


  /*** Validation Function ***/
  function validateField(input) {
    const fieldName = input.name.replace(/_/g, " ");
    let value = input.value.trim();
    const tripleLetterPattern = /(.)\1\1/;

    if (["first_name", "middle_name", "last_name"].includes(input.name)) {
      input.addEventListener("blur", () => {
        if (input.value.trim().length > 0) {
          input.value = input.value
            .trim()
            .toLowerCase()
            .replace(/\b\w/g, c => c.toUpperCase())
            .replace(/\s+/g, " ");
        }
      });
    }

    if (input.required && value === "") {
      const formattedFieldName = fieldName.charAt(0).toUpperCase() + fieldName.slice(1);
      return `${formattedFieldName} is required.`;
    }

    if (["first_name", "middle_name", "last_name"].includes(input.name)) {
      if (input.name === "middle_name" && value === "") return null;
      if (!/^[A-Za-z\s]+$/.test(value)) return `${fieldName}: Only letters and spaces allowed.`;
      if (/\s{2,}/.test(value)) return `${fieldName}: No double spaces.`;
      if (tripleLetterPattern.test(value.toLowerCase())) return `${fieldName}: No 3 same letters in a row.`;
      return null;
    }

    /*** ✅ EXTENSION VALIDATION FIXED + IMPROVED ***/
    if (input.name === "extension") {
      if (value === "Other") {
        const err = validateExtension(otherExtensionInput);
        if (err) return err;
      }
    }

    if (input.name === "birthdate" && value) {
      const birthDate = new Date(value);
      const today = new Date();
      let age = today.getFullYear() - birthDate.getFullYear();
      const m = today.getMonth() - birthDate.getMonth();
      if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) age--;
      const ageInput = input.closest(".step").querySelector('input[name="age"]');
      if (ageInput) ageInput.value = age;
      if (age < 18) return "You must be at least 18 years old to register.";
      if (age > 100) return "Age cannot exceed 100 years.";
      return null;
    }

    /*** Address Field Validations ***/
    if (input.name === "barangay") {
      const barangayPattern = /^[A-Za-z0-9ñÑ.\-,'\s]+$/;
      if (!barangayPattern.test(value)) return "Barangay: Invalid characters detected.";
      if (/([.\-\/,])\1/.test(value)) return "Please don’t use double special characters (e.g. --, //, .., ,,)";
      input.value = value.replace(/\s+$/, "");
      return null;
    }

    if (input.name === "street") {
      const streetPattern = /^[A-Za-z0-9ñÑ.\-\/,'\s]+$/;
      if (!streetPattern.test(value)) return "Purok/Street: Invalid characters detected.";
      if (/([.\-\/,])\1/.test(value)) return "Please don’t use double special characters (e.g. --, //, .., ,,)";
      input.value = value.replace(/\s+$/, "");
      return null;
    }

    function validateLocationField(input, label) {
      let value = input.value.trim();

      // ✅ Allow letters (A–Z, Ñ, accented letters), space, hyphen, period, apostrophe
      const pattern = /^[A-Za-zÀ-ÿñÑ.'\-\s]+$/;

      // ❌ Disallow consecutive or misplaced special characters
      const invalidPattern = /(\.\.|--|''|,,|-\s|-\.|\.|-['\s]|['\s]-|['.]{2,}|^[\-\.\',]|[\-\.\',]$)/;

      // ❌ Disallow 3 or more same consecutive letters (e.g., LLL, sss)
      const repeatedLetters = /([A-Za-zñÑ])\1{2,}/;

      if (!pattern.test(value) || invalidPattern.test(value) || repeatedLetters.test(value)) {
        return `${label}: Invalid characters or invalid format detected.`;
      }

      input.value = value.trimEnd();
      return null;
    }

    // ✅ Usage example:
    if (input.name === "city") {
      return validateLocationField(input, "City/Municipality");
    }

    if (input.name === "province") {
      return validateLocationField(input, "Province");
    }

    if (input.name === "country") {
      return validateLocationField(input, "Country");
    }


   const zipInput = document.querySelector('input[name="zip"]');

    if (zipInput) {
      zipInput.addEventListener('input', () => {
        // Remove any non-digit character immediately
        zipInput.value = zipInput.value.replace(/\D/g, '');

        // Limit to exactly 4 digits
        if (zipInput.value.length > 4) {
          zipInput.value = zipInput.value.slice(0, 4);
        }
      });
    }

    return null;
  }

  /*** Step Validation ***/
  function validateStep(step) {
    const inputs = step.querySelectorAll("input, select");
    const errors = [];
    let firstInvalidInput = null;

    for (const input of inputs) {
      const error = validateField(input);
      if (error) {
        errors.push(error);
        if (!firstInvalidInput) firstInvalidInput = input;
        break;
      }
    }

    showStepErrors(errors, firstInvalidInput);
    return errors.length === 0;
  }

  /*** Navigation ***/
  window.nextStep = function (stepNum) {
    const current = steps[stepNum - 1];
    if (validateStep(current)) {
      currentStep = Math.min(currentStep + 1, steps.length - 1);
      showStep(currentStep);
    }
  };

  window.prevStep = function (stepNum) {
    currentStep = Math.max(currentStep - 1, 0);
    showStep(currentStep);
  };

  /*** Submission ***/
  window.handleSubmit = function (form) {
    let allValid = true;
    steps.forEach(step => {
      if (!validateStep(step)) allValid = false;
    });

    if (!allValid) {
      showStepErrors(["Please fix the errors before submitting."]);
      return false;
    }
    return true;
  };

  /*** Auto-format on blur ***/
  steps.forEach(step => {
    const inputs = step.querySelectorAll("input");
    inputs.forEach(input => {
      if ([
        "first_name",
        "middle_name",
        "last_name",
        "barangay",
        "street",
        "country",
        "city",
        "province"
      ].includes(input.name)) {
        input.addEventListener("blur", () => {
          const val = input.value.trim();
          if (val === "") return;
          input.value = val
            .toLowerCase()
            .replace(/\s{2,}/g, " ")
            .replace(/\b\w/g, c => c.toUpperCase())
            .replace(/\s+$/, "");
        });
      }
    });
  });

  /*** 🚫 Prevent Enter unless all required fields in step are filled ***/
  form.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      const current = steps[currentStep];
      const requiredInputs = current.querySelectorAll("input[required], select[required]");
      let allFilled = true;

      requiredInputs.forEach(input => {
        if (!input.value.trim()) allFilled = false;
      });

      if (allFilled && validateStep(current)) {
        if (currentStep < steps.length - 1) {
          currentStep++;
          showStep(currentStep);
        } else {
          form.requestSubmit();
        }
      }
    }
  });

  /*** Initial Display ***/
  showStep(currentStep);
});


/*** 🧠 Password Strength Indicator + Format Guide (Auto-Hide on Blur) ***/
const passwordInput = document.querySelector('.password');
const strengthBar = document.querySelector('.password-strenght');
const message = document.getElementById('message');
const guide = document.getElementById('password-guide');

if (passwordInput && strengthBar && message && guide) {
  // Show password strength as user types
  passwordInput.addEventListener('input', () => {
    const value = passwordInput.value.trim();
    let strength = 0;

    if (value.length >= 8) strength++;
    if (/[a-z]/.test(value)) strength++;
    if (/[A-Z]/.test(value)) strength++;
    if (/\d/.test(value)) strength++;
    if (/[@$!%*?&]/.test(value)) strength++;

    strengthBar.style.display = 'block';
    message.style.display = 'block';
    guide.style.display = 'block';

    if (value.length === 0) {
      strengthBar.style.width = '0%';
      message.textContent = '';
      guide.textContent = '';
      strengthBar.style.backgroundColor = 'transparent';
      return;
    }

    if (strength <= 2) {
  strengthBar.style.width = '33%';
  strengthBar.style.backgroundColor = '#e74c3c';
  // message.textContent = 'Poor';
  message.style.color = '#e74c3c';
  guide.textContent = 'Poor: Add 8+ chars, mix upper, lower, number & symbol.';
  guide.style.color = '#e74c3c';
} else if (strength === 3 || strength === 4) {
  strengthBar.style.width = '66%';
  strengthBar.style.backgroundColor = '#f1c40f';
  // message.textContent = 'Medium';
  message.style.color = '#f1c40f';
  guide.textContent = 'Fair: Almost there! Add 1–2 more types for strong.';
  guide.style.color = '#f1c40f';
} else if (strength >= 5) {
  strengthBar.style.width = '100%';
  strengthBar.style.backgroundColor = '#2ecc71';
  // message.textContent = 'Strong';
  message.style.color = '#2ecc71';
  guide.textContent = 'Secure: Nice! Strong password.';
  guide.style.color = '#2ecc71';
}

  });

  // Hide password strength when clicking away
  passwordInput.addEventListener('blur', () => {
    strengthBar.style.display = 'none';
    message.style.display = 'none';
    guide.style.display = 'none';
  });

  // Show again when user clicks back in
  passwordInput.addEventListener('focus', () => {
    if (passwordInput.value.length > 0) {
      strengthBar.style.display = 'block';
      message.style.display = 'block';
      guide.style.display = 'block';
    }
  });
}


function toggleVisibility(id, icon) {
  const input = document.getElementById(id);
  if (input.type === "password") {
    input.type = "text";
    icon.classList.replace("bi-eye", "bi-eye-slash");
  } else {
    input.type = "password";
    icon.classList.replace("bi-eye-slash", "bi-eye");
  }
}

document.addEventListener("DOMContentLoaded", () => {
  const inputFields = document.querySelectorAll(".input-field input[type='password']");
  
  inputFields.forEach(input => {
    const icon = input.parentElement.querySelector(".toggle-eye");

    input.addEventListener("input", () => {
      if (input.value.trim() !== "") {
        icon.classList.add("visible"); // fade in smoothly
      } else {
        icon.classList.remove("visible"); // fade out
        icon.classList.remove("bi-eye-slash");
        icon.classList.add("bi-eye");
        input.type = "password";
      }
    });

    input.addEventListener("blur", () => {
      if (input.value.trim() === "") {
        icon.classList.remove("visible");
      }
    });
  });
});
