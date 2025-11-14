document.addEventListener("DOMContentLoaded", () => {
  /*** DOM Elements & Variables ***/
  const steps = document.querySelectorAll(".register-form .step");
  const form = document.querySelector(".register-form");
  let currentStep = 0;

  const extensionSelect = document.getElementById("extension");
  const otherExtensionInput = document.getElementById("other_extension");
  const passwordInput = form.querySelector("input[name='password']");
  const confirmInput = form.querySelector("input[name='confirm_password']");
  const strengthBar = document.querySelector('.password-strenght');
  const message = document.getElementById('message');
  const guide = document.getElementById('password-guide');

  /*** Utility Functions ***/
  function showStep(stepIndex) {
    steps.forEach((step, idx) => step.classList.toggle("active", idx === stepIndex));
    clearStepErrors();
  }

  function clearStepErrors() {
    const errorContainer = form.querySelector(".step-error-container");
    if (errorContainer) errorContainer.remove();
    steps[currentStep].querySelectorAll("input, select").forEach(input => input.style.border = "");
  }

  function showStepErrors(errors, firstInvalidInput = null) {
    clearStepErrors();
    if (errors.length === 0) return;

    if (firstInvalidInput) {
      firstInvalidInput.style.borderBottom = "1px solid rgba(255, 0, 0, 0.5)";
      firstInvalidInput.style.borderLeft = "1px solid rgba(255, 0, 0, 0.5)";
      firstInvalidInput.style.borderRight= "1px solid rgba(255, 0, 0, 0.5)";
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
  extensionSelect.addEventListener("change", () => {
    if (extensionSelect.value === "Other") {
      extensionSelect.style.display = "none";
      otherExtensionInput.style.display = "block";
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

  otherExtensionInput.addEventListener("input", () => {
    otherExtensionInput.value = otherExtensionInput.value.toUpperCase();
  });

  function validateExtension(input) {
    const value = input.value.trim().toUpperCase();
    const validRoman = /^(I|II|III|IV|V|VI|VII|VIII|IX|X)$/;

    if (!value) return "Other Extension: This field is required.";
    if (!validRoman.test(value)) return "Other Extension: Must be a valid Roman numeral (I–X).";
    return null;
  }

  /*** Field Validation ***/
  function validateField(input) {
    const fieldName = input.name.replace(/_/g, " ");
    let value = input.value.trim();
    const tripleLetterPattern = /(.)\1\1/;

    /*** Name Fields ***/
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

      const formattedFieldName = fieldName.charAt(0).toUpperCase() + fieldName.slice(1);

      if (!/^[A-Za-z\s]+$/.test(value)) return `${formattedFieldName}: Only letters and spaces allowed.`;
      if (/\s{2,}/.test(value)) return `${formattedFieldName}: No double spaces.`;
      if (tripleLetterPattern.test(value.toLowerCase())) return `${formattedFieldName}: No three identical letters.`;
      if (input.name === "middle_name" && value.length === 1 && /^[A-Za-z]$/.test(value)) return null;
      if (["first_name", "last_name"].includes(input.name) && value.length < 2) return `${formattedFieldName}: Must be at least 2 letters long.`;
      if (["first_name", "last_name", "middle_name"].includes(input.name) && value.length > 50) return `${formattedFieldName}: Cannot exceed 50 characters.`;

      return null;
    }

    /*** Extension Validation ***/
    if (input.name === "extension" && value === "Other") {
      const err = validateExtension(otherExtensionInput);
      if (err) {
        showStepErrors([err], otherExtensionInput);
        return err;
      }
    }

    /*** Birthdate Validation ***/
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

/*** Street Validation ***/
if (input.name === "street") {
  value = value.trim();

  // Start with letter
  if (!/^[A-Za-zñÑ]/.test(value)) return "Purok/Street: Must start with a letter.";

  // Basic length
  if (value.length < 3) return "Purok/Street: Must be at least 3 characters long.";
  if (value.length > 100) return "Purok/Street: Cannot exceed 100 characters.";

  // Allowed characters: letters, numbers, space, period, dash, comma, apostrophe, forward slash
  const invalidCharMatch = value.match(/[^A-Za-z0-9ñÑ\s.\-,'/]/);
  if (invalidCharMatch) {
    return `Purok/Street: Invalid character '${invalidCharMatch[0]}' used. Allowed symbols are: . - , ' /`;
  }


  // Cannot end with symbol
  if (!/[A-Za-z0-9ñÑ]$/.test(value)) return "Purok/Street: Cannot end with a symbol.";

  // No double symbols
  if (/([.\-\/,'])(\s*[\-\/,'])/.test(value)) return "Purok/Street: No double symbols allowed.";

  // No extra spaces
  if (/\s{2,}/.test(value)) return "Purok/Street: Extra spaces are not allowed.";

  // No three identical letters in a row
  if (/(.)\1\1/i.test(value)) return "Purok/Street: No three identical letters.";

  /*** NEW — Reject hyphen only for Purok + number ***/
  if (/Purok\s*-\s*\d+/i.test(value)) return "Purok: Do not use hyphens between 'Purok' and a number. Use a space instead (e.g., 'Purok 1').";

  const words = value.split(/\s+/);

  for (let word of words) {

  /*** 1. Allow valid ordinals only (1st, 2nd, 3rd, 4th) ***/
  if (/^\d{1,2}(st|nd|rd|th)$/i.test(word)) continue;

    /*** 2. Reject any letters+numbers inside the same segment ***/
    // Split word by allowed symbols to check each segment separately
    const segments = word.split(/[\-.,'/]/); 
    for (let seg of segments) {
      if (/^(?=.*[A-Za-zñÑ])(?=.*\d)[A-Za-zñÑ0-9]+$/.test(seg)) {
        return `Barangay: Invalid number in '${seg}'. Add a space.`;
      }
    }

    /*** 3. Reject letter→number or number→letter without space (e.g., Purok1, 1Purok) ***/
    if (/^[A-Za-zñÑ]+\d+$/.test(word) || /^\d+[A-Za-zñÑ]+$/.test(word)) return `Purok/Street: Add a space between letters and numbers ('${word}').`;

    /*** 4. Standalone numbers with optional valid ordinal suffix ***/
    if (/^\d{1,2}[A-Za-z]{0,2}$/i.test(word)) {
      const num = parseInt(word, 10);
      const suffix = word.slice(String(num).length).toLowerCase();

      if (suffix) {
        const isValidOrdinal =
          (num % 10 === 1 && suffix === "st" && num % 100 !== 11) ||
          (num % 10 === 2 && suffix === "nd" && num % 100 !== 12) ||
          (num % 10 === 3 && suffix === "rd" && num % 100 !== 13) ||
          ((num % 10 > 3 || num % 10 === 0 || (num % 100 >= 11 && num % 100 <= 13)) && suffix === "th");

        if (!isValidOrdinal)
          return `Purok/Street: Invalid ordinal number ('${word}') (e.g., 1st, 2nd, 3rd, 4th).`;
      }
    }
  }



  return null;
}


    /*** Barangay Validation ***/
    if (input.name === "barangay") {
      value = value.trim();
      if (value.length < 3) return "Barangay: Must be at least 3 characters long.";
      if (value.length > 50) return "Barangay: Cannot exceed 50 characters.";
      if (!/^[A-Za-zñÑ]/.test(value)) return "Barangay: Must start with a letter.";
      if (!/^[A-Za-z0-9ñÑ\s.\-]+$/.test(value)) return "Barangay: Only letters, numbers, spaces, . and - are allowed.";
      if (!/[A-Za-z0-9ñÑ]$/.test(value)) return "Barangay: Cannot end with a symbol.";
      if (/([.\-])(\s*[\.-])/.test(value)) return "Barangay: No double symbols or symbols stuck together allowed.";
      if (/\s{2,}/.test(value)) return "Barangay: Extra spaces not allowed.";
      if (/(.)\1\1/i.test(value)) return "Barangay: No three consecutive identical letters.";

      const words = value.split(/\s+/);
        for (let word of words) {

          // Split each word by allowed symbols to check segments separately
          const segments = word.split(/[\-.,'/]/); 

          for (let seg of segments) {
            // Reject any letters+numbers inside the same segment
            if (/^(?=.*[A-Za-zñÑ])(?=.*\d)[A-Za-zñÑ0-9]+$/.test(seg)) {
              return `Barangay: Invalid number in '${seg}'. Add a space.`;
            }
          }

          // Optional: reject number→letter without space (like 1Purok)
          if (/^\d+[A-Za-zñÑ]+$/.test(word) && !/^\d+[A-Za-zñÑ]{2}$/.test(word)) {
            return `Barangay: Add a space between numbers and letters ('${word}').`;
          }
        }


      input.value = value.replace(/\s+$/, "");
      return null;
    }

    /*** Location Fields (City, Province, Country) ***/
    function validateLocationField(input, label) {
      let value = input.value.trim();
      if (value.length < 3) return `${label}: Must be at least 3 characters long.`;
      if (value.length > 56) return `${label}: Cannot exceed 56 characters.`;
      if (/\d/.test(value)) return `${label}: Numbers are not allowed.`;
      if (/[^A-Za-zÀ-ÿñÑ\s]/.test(value)) return `${label}: Special characters are not allowed.`;
      if (/\s{2,}/.test(value)) return `${label}: Extra spaces are not allowed.`;
      if (/([A-Za-zñÑ])\1{2,}/i.test(value)) return `${label}: No three consecutive identical letters.`;
      if (!/^[A-Za-zñÑ]/.test(value) || !/[A-Za-zñÑ]$/.test(value)) return `${label}: Must start and end with a letter.`;
      input.value = value.trimEnd();
      return null;
    }

    if (input.name === "city") return validateLocationField(input, "City/Municipality");
    if (input.name === "province") return validateLocationField(input, "Province");
    if (input.name === "country") return validateLocationField(input, "Country");

    /*** ZIP Validation ***/
    if (input.name === "zip") {
      input.addEventListener("input", () => {
        input.value = input.value.replace(/\D/g, ""); 
        if (input.value.length > 4) input.value = input.value.slice(0, 4);
      });

      const zipPattern = /^[0-9]{4}$/;
      if (value === "") return "ZIP code is required.";
      if (!zipPattern.test(value)) return "ZIP must be exactly 4 digits.";
      return null;
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
    let firstInvalidInput = null;
    let errorMessage = "";

    for (const step of steps) {
      const inputs = step.querySelectorAll("input, select");
      for (const input of inputs) {
        const value = input.value.trim();
        const name = input.name.replace(/_/g, " ");

        if (input.required && value === "") {
          firstInvalidInput = input;
          errorMessage = name.charAt(0).toUpperCase() + name.slice(1) + " is required.";
          break;
        }

        if (input.name === "password" && value) {
          const pwdRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/;
          if (!pwdRegex.test(value)) {
            firstInvalidInput = input;
            errorMessage = "Password must be 8+ characters with uppercase, lowercase, number & symbol.";
            break;
          }
        }

        if (input.name === "confirm_password") {
          const passwordInput = form.querySelector("input[name='password']");
          if (passwordInput && value !== passwordInput.value) {
            firstInvalidInput = input;
            errorMessage = "Password and Confirm Password do not match.";
            break;
          }
        }
      }

      if (firstInvalidInput) break;
    }

    if (firstInvalidInput) {
      showStepErrors([errorMessage], firstInvalidInput);
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

  /*** Prevent Enter unless all required fields in step are filled ***/
  form.addEventListener("keydown", e => {
    if (e.key === "Enter") {
      e.preventDefault();
      const current = steps[currentStep];
      const requiredInputs = current.querySelectorAll("input[required], select[required]");
      let allFilled = true;
      requiredInputs.forEach(input => { if (!input.value.trim()) allFilled = false; });
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

  /*** Password Strength Indicator ***/
  if (passwordInput && strengthBar && message && guide) {
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
        message.style.color = '#e74c3c';
        // guide.textContent = 'Poor: Add 8+ chars, mix upper, lower, number & symbol.';
        guide.style.color = '#e74c3c';
      } else if (strength === 3 || strength === 4) {
        strengthBar.style.width = '66%';
        strengthBar.style.backgroundColor = '#f1c40f';
        message.style.color = '#f1c40f';
        // guide.textContent = 'Fair: Almost there! Add 1–2 more types for strong.';
        guide.style.color = '#f1c40f';
      } else if (strength >= 5) {
        strengthBar.style.width = '100%';
        strengthBar.style.backgroundColor = '#2ecc71';
        message.style.color = '#2ecc71';
        // guide.textContent = 'Secure: Nice! Strong password.';
        guide.style.color = '#2ecc71';
      }
    });

    passwordInput.addEventListener('blur', () => {
      strengthBar.style.display = 'none';
      message.style.display = 'none';
      guide.style.display = 'none';
    });

    passwordInput.addEventListener('focus', () => {
      if (passwordInput.value.length > 0) {
        strengthBar.style.display = 'block';
        message.style.display = 'block';
        guide.style.display = 'block';
      }
    });
  }


  
  
  /*** Initialize Step ***/
  showStep(currentStep);
});


// Toggle password visibility
function toggleVisibility(inputId, icon) {
  const input = document.getElementById(inputId);
  if (!input) return;

  const isHidden = input.type === "password";
  input.type = isHidden ? "text" : "password";

  icon.classList.toggle("bi-eye-slash", isHidden);
  icon.classList.toggle("bi-eye", !isHidden);
}

// Initialize all toggle-eye icons
document.querySelectorAll(".toggle-eye").forEach(icon => {
  const inputIdMatch = icon.getAttribute("onclick").match(/'(.+?)'/);
  if (!inputIdMatch) return;

  const inputId = inputIdMatch[1];
  const input = document.getElementById(inputId);
  if (!input) return;

  // Initially hidden
  icon.classList.remove("visible");

  // Show icon when input is focused
  input.addEventListener("focus", () => {
    icon.classList.add("visible");
  });

  // Hide icon on blur only if focus moved away from icon
  input.addEventListener("blur", () => {
    setTimeout(() => {
      if (document.activeElement !== icon) {
        icon.classList.remove("visible");
        input.type = "password";           
        icon.classList.remove("bi-eye-slash");
        icon.classList.add("bi-eye");
      }
    }, 0);
  });

  // Prevent input blur when clicking the icon
  icon.addEventListener("mousedown", (e) => {
    e.preventDefault(); // keep input focused
  });
});

// Convert username input to lowercase automatically
const usernameInput = document.querySelector("input[name='username']");
if (usernameInput) {
  usernameInput.addEventListener("input", () => {
    usernameInput.value = usernameInput.value.toLowerCase();
  });
}
