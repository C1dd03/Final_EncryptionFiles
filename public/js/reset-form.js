document.addEventListener("DOMContentLoaded", () => {
  const steps = document.querySelectorAll(".register-form .step");
  const form = document.querySelector(".register-form");
  let currentStep = 0;

  /*** Utility Functions ***/
  function showStep(stepIndex) {
    steps.forEach((step, idx) =>
      step.classList.toggle("active", idx === stepIndex)
    );
    clearStepErrors(steps[stepIndex]);
  }

  function capitalizeFirst(str) {
    if (!str) return "";
    return str.charAt(0).toUpperCase() + str.slice(1);
  }

  function findErrorContainer(input) {
    const fieldWrapper = input.closest(".form-field");
    if (fieldWrapper) {
      const existing = fieldWrapper.querySelector(".input-error-container");
      if (existing) return existing;
    }

    const parent =
      input.closest(".input-field, .pass-input-field") || input.parentElement;
    if (!parent) return null;

    const internal = Array.from(parent.children).find((child) =>
      child.classList?.contains("input-error-container")
    );
    if (internal) return internal;

    const sibling = parent.nextElementSibling;
    if (sibling && sibling.classList.contains("input-error-container"))
      return sibling;

    if (fieldWrapper) {
      const fallback = fieldWrapper.querySelector(".input-error-container");
      if (fallback) return fallback;
    }

    return null;
  }

  function setFieldError(input, message, isError = true) {
    const parent =
      input.closest(".input-field, .pass-input-field") || input.parentElement;
    const fieldWrapper = input.closest(".form-field");
    if (!parent) return;

    let container = findErrorContainer(input);
    if (!container) {
      container = document.createElement("div");
      container.className = "input-error-container";
      if (fieldWrapper) {
        fieldWrapper.appendChild(container);
      } else {
        parent.insertAdjacentElement("afterend", container);
      }
    }
    container.innerText = message;
    // Set font size to 12px as requested
    container.style.fontSize = "11px";
    // Make sure the container is visible when it has content
    container.style.visibility = message ? "visible" : "hidden";
    if (isError) {
      container.style.color = "red";
      input.style.borderBottom = "1px solid rgba(255,0,0,0.5)";
    } else {
      container.style.color = "green";
      input.style.borderBottom = "1px solid rgba(0,255,0,0.5)";
    }

    // Hide password match message when showing error for confirm password field
    if (input.name === "confirm_password") {
      const matchMessage = document.querySelector(".password-match-message");
      if (matchMessage) {
        matchMessage.style.visibility = "hidden";
      }
    }
  }

  function clearFieldError(input) {
    const container = findErrorContainer(input);
    if (container) {
      container.innerText = "";
      container.style.visibility = "hidden"; // Hide the container when cleared
    }
    input.style.borderBottom = "";

    // Show password match message when clearing error for confirm password field
    if (input.name === "confirm_password") {
      const matchMessage = document.querySelector(".password-match-message");
      if (matchMessage) {
        matchMessage.style.visibility = "visible";
      }
    }
  }

  function clearStepErrors(step = steps[currentStep]) {
    if (!step) return;

    step.querySelectorAll("input, select").forEach((input) => {
      clearFieldError(input);
    });
  }

  /*** Extension Input Handling ***/
  const normalizeExtension = (rawValue) => {
    const value = rawValue.trim();
    if (value === "") return "";

    const upperValue = value.toUpperCase();

    if (["JR", "JR."].includes(upperValue)) {
      return "Jr.";
    }

    if (["SR", "SR."].includes(upperValue)) {
      return "Sr.";
    }

    const romanValue = upperValue.replace(/\./g, "");
    const validRomans = [
      "I",
      "II",
      "III",
      "IV",
      "V",
      "VI",
      "VII",
      "VIII",
      "IX",
      "X",
    ];

    if (validRomans.includes(romanValue)) {
      return romanValue;
    }

    return value;
  };

  const extensionInput = document.getElementById("extension_input");
  if (extensionInput) {
    extensionInput.addEventListener("blur", () => {
      extensionInput.value = normalizeExtension(extensionInput.value);
    });
  }

  /*** Validation Functions ***/
  function validateField(input, options = {}) {
    const { normalize = true } = options;
    const namePattern = /^[A-Za-z\s]*$/;
    const fieldName = input.name.replace(/_/g, " ");
    const rawValue = input.value;
    let value = rawValue.trim();
    const addressLabels = {
      street: "Purok/Street",
      barangay: "Barangay",
      city: "Municipal/City",
      province: "Province",
      country: "Country",
    };
    const addressNoSpecialCharFields = new Set(["city", "province", "country"]);
    const customRequiredMessages = {
      street: `${addressLabels.street}: This field is required.`,
      barangay: `${addressLabels.barangay}: This field is required.`,
      city: `${addressLabels.city}: This field is required.`,
      province: `${addressLabels.province}: This field is required.`,
      country: `${addressLabels.country}: This field is required.`,
      zip: "Zip Code: This field is required.",
    };

    function capitalizeMessage(msg) {
      if (!msg) return "";
      return msg.charAt(0).toUpperCase() + msg.slice(1);
    }

    // Auto-capitalize name fields (except "Other Extension")
    // Required check
    if (input.required && value === "") {
      if (customRequiredMessages[input.name])
        return customRequiredMessages[input.name];
      return capitalizeMessage(`${fieldName} is required.`);
    }

    // Name field rules
    if (["first_name", "middle_name", "last_name"].includes(input.name)) {
      const capitalizedFieldName = capitalizeFirst(fieldName);

      // Check if name starts with a space
      if (rawValue.length > 0 && rawValue.charAt(0) === " ") {
        return `${capitalizedFieldName} cannot start with a space.`;
      }

      // Check if name starts with a number or special character
      if (rawValue.length > 0 && !/^[A-Za-z]/.test(rawValue.charAt(0))) {
        return `${capitalizedFieldName} must start with a letter only.`;
      }

      // Check for double spaces
      if (/\s{2,}/.test(rawValue))
        return `${capitalizedFieldName} cannot contain double spaces.`;
      let positionInWord = -1;
      let lastLetterLower = null;
      let repeatCount = 0;

      for (let i = 0; i < value.length; i++) {
        const char = value[i];

        if (char === " ") {
          positionInWord = -1;
          lastLetterLower = null;
          repeatCount = 0;
          continue;
        }

        positionInWord += 1;

        if (!/[A-Za-z]/.test(char)) {
          if (/\d/.test(char))
            return `${capitalizedFieldName} cannot include numbers.`;
          return `${capitalizedFieldName} cannot include special characters.`;
        }

        const charLower = char.toLowerCase();
        if (charLower === lastLetterLower) {
          repeatCount += 1;
          if (repeatCount === 3)
            return capitalizeMessage(
              `${fieldName}: No 3 same letters in a row`
            );
        } else {
          lastLetterLower = charLower;
          repeatCount = 1;
        }

        if (positionInWord === 0 && char !== char.toUpperCase()) {
          return i === 0
            ? `${capitalizedFieldName} must start with a capital letter.`
            : `${capitalizedFieldName} requires each name to start with a capital letter.`;
        }

        if (positionInWord > 0 && char === char.toUpperCase()) {
          if (value === value.toUpperCase() && value.length > 1) {
            return `${capitalizedFieldName} should avoid all caps (e.g., SRRSS).`;
          }
          return `${capitalizedFieldName} cannot contain capital letters after the first letter of each name.`;
        }
      }

      if (value === value.toUpperCase() && value.length > 1)
        return `${capitalizedFieldName} should avoid all caps (e.g., SRRSS).`;

      if (!namePattern.test(value))
        return `${capitalizedFieldName} can only contain letters and spaces.`;
    }

    if (addressLabels[input.name]) {
      const label = addressLabels[input.name];

      // Check if address field (except street) starts with a space
      if (
        input.name !== "street" &&
        rawValue.length > 0 &&
        rawValue.charAt(0) === " "
      ) {
        return `${label} cannot start with a space.`;
      }

      // Check if address field (except street) starts with a number or special character
      if (
        input.name !== "street" &&
        rawValue.length > 0 &&
        !/^[A-Za-z]/.test(rawValue.charAt(0))
      ) {
        return `${label} must start with a letter only.`;
      }

      if (input.name === "street" && value.length < 3)
        return `${label}: Must be at least 3 characters long.`;

      // Check if street contains only numbers (with or without spaces)
      if (input.name === "street" && /^[\d\s]+$/.test(value))
        return `${label}: It must contain letters.`;

      const isStreet = input.name === "street";

      let previousWasSpace = false;
      let lastLetterLower = null;
      let repeatCount = 0;
      let hasLowercaseLetter = false;
      let hasLetter = false;
      let inWord = false;
      let seenLetterInWord = false;
      let wordIndex = -1;
      let wordHasDigit = false;

      for (let i = 0; i < rawValue.length; i++) {
        const char = rawValue[i];
        if (char === " ") {
          if (previousWasSpace)
            return `${label}: Cannot contain double spaces.`;
          previousWasSpace = true;
          lastLetterLower = null;
          repeatCount = 0;
          inWord = false;
          seenLetterInWord = false;
          wordHasDigit = false;
          continue;
        }
        previousWasSpace = false;
        if (char === "." || char === "-") {
          if (addressNoSpecialCharFields.has(input.name)) {
            return `${label}: Special characters are not allowed.`;
          }
          lastLetterLower = null;
          repeatCount = 0;
          // Don't reset inWord or seenLetterInWord for dashes - they're part of the same word
          // Only reset for periods (which typically end words)
          if (char === ".") {
            inWord = false;
            seenLetterInWord = false;
            wordHasDigit = false;
          }
          continue;
        }
        if (/\d/.test(char)) {
          if (!isStreet) return `${label}: Cannot include numbers.`;
          lastLetterLower = null;
          repeatCount = 0;
          if (!inWord) {
            inWord = true;
            seenLetterInWord = false;
            wordHasDigit = true;
            wordIndex += 1;
          } else if (seenLetterInWord) {
            return `${label}: Cannot include numbers.`;
          } else {
            wordHasDigit = true;
          }
          continue;
        }
        if (/[A-Za-z]/.test(char)) {
          hasLetter = true;
          if (char === char.toLowerCase()) hasLowercaseLetter = true;
          if (!inWord) {
            inWord = true;
            seenLetterInWord = false;
            wordHasDigit = false;
            wordIndex += 1;
          }
          if (!seenLetterInWord) {
            let capitalMessage;
            if (isStreet) {
              capitalMessage =
                wordHasDigit || wordIndex > 0
                  ? `${label}: Each word must start with a capital letter.`
                  : `${label}: Must start with a capital letter.`;
            } else if (wordIndex > 0) {
              capitalMessage = `${label}: Each word must start with a capital letter.`;
            } else {
              capitalMessage = `${label}: Must start with a capital letter.`;
            }
            if (char !== char.toUpperCase()) return capitalMessage;
            seenLetterInWord = true;
            wordHasDigit = false;
          } else if (char === char.toUpperCase()) {
            return `${label}: Cannot contain capital letters after the first letter of each name.`;
          }

          const charLower = char.toLowerCase();
          if (charLower === lastLetterLower) {
            repeatCount += 1;
            if (repeatCount === 3)
              return capitalizeMessage(`${label}: No 3 same letters in a row`);
          } else {
            lastLetterLower = charLower;
            repeatCount = 1;
          }
          continue;
        }
        if (addressNoSpecialCharFields.has(input.name)) {
          return `${label}: Special characters are not allowed.`;
        }
        return `${label}: Only period (.) and dash (-) allowed`;
      }

      // Check for all caps (only for non-street fields: barangay, city, province, country)
      if (hasLetter && !isStreet) {
        const lettersOnly = value.replace(/[^A-Za-z]/g, "");
        // Only trigger if there are multiple letters and all are uppercase (like "SRRSS")
        if (
          lettersOnly.length > 1 &&
          lettersOnly === lettersOnly.toUpperCase() &&
          !hasLowercaseLetter
        )
          return `${label}: Should avoid all caps (e.g., SRRSS).`;
      }
    }

    if (input.name === "zip") {
      if (value.length < 4 || value.length > 6)
        return "Zip Code: Must be 4 to 6 digits.";
      if (!/^\d+$/.test(value)) return "Zip Code: Only numbers are allowed.";
    }

    // Extension-specific validation
    if (input.name === "extension" && value !== "") {
      const normalized = normalizeExtension(value);
      const validExtensions = [
        "Jr.",
        "Sr.",
        "I",
        "II",
        "III",
        "IV",
        "V",
        "VI",
        "VII",
        "VIII",
        "IX",
        "X",
      ];

      if (!normalize) {
        const hasLowercaseRoman = /[ivx]/.test(value);
        if (hasLowercaseRoman) {
          return capitalizeMessage(
            "Name Extension must use uppercase Roman numerals."
          );
        }
      }

      if (!validExtensions.includes(normalized)) {
        return capitalizeMessage(
          "Name Extension must be Jr, Sr, or Roman numerals I–X"
        );
      }

      // Update the field with the normalized value for consistency
      if (normalize) {
        input.value = normalized;
      }
    }

    // Birthdate / age
    if (input.name === "birthdate" && value) {
      const birthDate = new Date(value);
      const today = new Date();
      let age = today.getFullYear() - birthDate.getFullYear();
      const m = today.getMonth() - birthDate.getMonth();
      if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) age--;
      const ageInput = input
        .closest(".step")
        .querySelector('input[name="age"]');
      if (ageInput) ageInput.value = age;
      if (age < 18) return "You must be at least 18 years old to register.";
    }

    return null; // valid
  }

  function validateStep(step) {
    const inputs = step.querySelectorAll("input, select");
    let firstInvalidInput = null;
    let allValid = true;

    for (const input of inputs) {
      const error = validateField(input);
      if (error) {
        setFieldError(input, error);
        if (!firstInvalidInput) firstInvalidInput = input;
        allValid = false;
      } else {
        clearFieldError(input);
      }
    }

    if (firstInvalidInput) {
      firstInvalidInput.scrollIntoView({ behavior: "smooth", block: "center" });
    }

    return allValid;
  }

  /*** Step Navigation ***/
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

  /*** Form Submission ***/
  window.handleSubmit = function (form) {
    let allValid = true;
    steps.forEach((step) => {
      if (!validateStep(step)) allValid = false;
    });

    // Check for password validation errors
    const passwordInput = document.querySelector(".password");
    const confirmPasswordInput = document.querySelector(
      'input[name="confirm_password"]'
    );
    const passwordMessage = document.querySelector("#message");
    const passwordMatchMessage = document.querySelector(
      ".password-match-message"
    );

    // Check if password field is empty
    if (passwordInput && passwordInput.value.trim() === "") {
      // If password is empty, show "Password is required" error
      allValid = false;
      setFieldError(passwordInput, "Password is required.", true);
      // Hide the password strength message when showing required error
      if (passwordMessage) {
        passwordMessage.style.display = "none";
      }
    } else {
      // Check password strength
      if (passwordMessage && passwordMessage.textContent) {
        const passwordText = passwordMessage.textContent;
        if (
          passwordText.includes("Missing:") ||
          passwordText.includes("too weak")
        ) {
          allValid = false;
          // Display the password error with detailed feedback
          if (passwordInput) {
            setFieldError(passwordInput, passwordText, true);
            // Set error container color to red to match password strength
            const errorContainer = findErrorContainer(passwordInput);
            if (errorContainer) {
              errorContainer.style.color = "red";
            }
            // Hide the password strength message when showing error
            if (passwordMessage) {
              passwordMessage.style.display = "none";
            }
          }
        } else if (passwordText.includes("Add ")) {
          // For medium strength passwords, we now prevent submission
          // This has been updated to require strong passwords only
          allValid = false;
          if (passwordInput) {
            setFieldError(passwordInput, passwordText, true);
            // Set error container color to red to match password strength
            const errorContainer = findErrorContainer(passwordInput);
            if (errorContainer) {
              errorContainer.style.color = "orange";
            }
            // Hide the password strength message when showing error
            if (passwordMessage) {
              passwordMessage.style.display = "none";
            }
          }
        } else {
          // Clear the error if password is strong
          if (passwordInput) {
            clearFieldError(passwordInput);
            // Show the password strength message when clearing error
            if (passwordMessage) {
              passwordMessage.style.display = "block";
            }
          }
        }
      }
    }

    // Check if passwords don't match
    if (passwordMatchMessage && passwordMatchMessage.textContent) {
      const matchText = passwordMatchMessage.textContent;
      if (matchText.includes("does not match")) {
        allValid = false;
        // Display the password match error in the appropriate error container
        if (confirmPasswordInput) {
          setFieldError(confirmPasswordInput, "Password does not match.", true);
          // Hide the password match message when showing error
          if (passwordMatchMessage) {
            passwordMatchMessage.style.visibility = "hidden";
          }
        }
      } else {
        // Clear the error if passwords match
        if (confirmPasswordInput) {
          clearFieldError(confirmPasswordInput);
          // Show the password match message when clearing error
          if (passwordMatchMessage) {
            passwordMatchMessage.style.visibility = "visible";
          }
        }
      }
    }

    if (!allValid) {
      // Don't use alert, just prevent submission
      return false;
    }
    return true;
  };

  // Make functions globally accessible
  window.setFieldError = setFieldError;
  window.clearFieldError = clearFieldError;
  window.findErrorContainer = findErrorContainer;

  /*** Auto-capitalize on blur ***/
  steps.forEach((step) => {
    const inputs = step.querySelectorAll("input, select");
    inputs.forEach((input) => {
      // Skip username and email - they have their own real-time validation
      if (input.name === "username" || input.name === "email") {
        return;
      }

      const validateAndDisplay = (event) => {
        const shouldNormalize =
          event.type !== "input" || input.name !== "extension";
        const error = validateField(input, { normalize: shouldNormalize });
        if (error) {
          setFieldError(input, error);
        } else {
          clearFieldError(input);
        }
      };

      if (input.tagName === "SELECT") {
        input.addEventListener("change", validateAndDisplay);
      } else {
        input.addEventListener("input", validateAndDisplay);
      }

      input.addEventListener("blur", validateAndDisplay);
    });
  });

  /*** Username and Email Real-time Validation ***/
  let usernameTimeout;
  let emailTimeout;

  const usernameInput = form?.querySelector('input[name="username"]');
  const emailInput = form?.querySelector('input[name="email"]');

  // Username validation
  if (usernameInput) {
    usernameInput.addEventListener("input", function () {
      clearTimeout(usernameTimeout);
      const username = this.value.trim();

      // Clear message if empty
      if (username === "") {
        clearFieldError(this);
        return;
      }

      // Debounce the API call
      usernameTimeout = setTimeout(() => {
        fetch("index.php?action=checkUsername", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: new URLSearchParams({ username: username }),
        })
          .then((res) => res.json())
          .then((data) => {
            if (data.available) {
              // Only show success message when username is available
              setFieldError(this, data.message, false);
            } else {
              // Show error message in red when username is taken
              setFieldError(this, data.message, true);
            }
          })
          .catch((err) => {
            console.error("Error checking username:", err);
          });
      }, 500);
    });

    usernameInput.addEventListener("blur", function () {
      const username = this.value.trim();
      if (username === "") {
        clearFieldError(this);
      }
    });
  }

  // Email validation
  if (emailInput) {
    emailInput.addEventListener("input", function () {
      clearTimeout(emailTimeout);
      const email = this.value.trim();

      // Clear message if empty
      if (email === "") {
        clearFieldError(this);
        return;
      }

      // Basic email format check before API call
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email)) {
        setFieldError(this, "Invalid email format.", true);
        return;
      }

      // Debounce the API call
      emailTimeout = setTimeout(() => {
        fetch("index.php?action=checkEmail", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: new URLSearchParams({ email: email }),
        })
          .then((res) => res.json())
          .then((data) => {
            if (data.available) {
              // Only show success message when email is available
              setFieldError(this, data.message, false);
            } else {
              // Show error message in red when email is already registered
              setFieldError(this, data.message, true);
            }
          })
          .catch((err) => {
            console.error("Error checking email:", err);
          });
      }, 500);
    });

    emailInput.addEventListener("blur", function () {
      const email = this.value.trim();
      if (email === "") {
        clearFieldError(this);
      }
    });
  }

  /*** Initial Display ***/
  showStep(currentStep);

  // Password strength elements
  password = document.querySelector(".password");
  message = document.querySelector("#message");
  passInputField = document.querySelector(".pass-input-field");
  passwordStrength = document.querySelector(".password-strength");
  registerConfirmPasswordInput = document.querySelector(
    'input[name="confirm_password"]'
  );

  // Only attach listeners if elements exist
  if (password) {
    password.addEventListener("input", handlePasswordInput);
    password.addEventListener("input", checkRegisterPasswordMatch); // Also check match when password changes
  }

  if (registerConfirmPasswordInput) {
    registerConfirmPasswordInput.addEventListener(
      "input",
      checkRegisterPasswordMatch
    );
  }

  // Toggle password visibility for all password fields
  initPasswordToggle();

  // Regex patterns
  let regExpLower = /[a-z]/; // lowercase letters
  let regExpUpper = /[A-Z]/; // uppercase letters
  let regExpNumber = /\d/; // numbers
  let regExpSpecial = /[!@#$%^&*(),.?":{}|<>_\-]/; // special characters

  function handlePasswordInput() {
    if (!password || !message || !passInputField || !passwordStrength) return;

    let val = password.value;
    let strength = 0;

    if (val != "") {
      message.style.display = "block";
      passwordStrength.style.display = "block";

      // Count how many conditions are satisfied
      let hasLower = regExpLower.test(val);
      let hasUpper = regExpUpper.test(val);
      let hasNumber = regExpNumber.test(val);
      let hasSpecial = regExpSpecial.test(val);
      let hasLength = val.length >= 8;

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

      // Apply styling based on strength
      if (strength < 4) {
        passInputField.style.borderColor = "red";
        message.style.display = "block";
        if (missing.length > 0) {
          message.textContent = "Missing: " + missing.join(", ");
        } else {
          message.textContent = "Your password is too weak";
        }
        message.style.color = "red";
        passwordStrength.style.width = "25%";
        passwordStrength.style.backgroundColor = "red";
      } else if (strength >= 4 && strength <= 4) {
        passInputField.style.borderColor = "orange";
        message.style.display = "block";
        if (missing.length > 0) {
          message.textContent =
            "Add " + missing.join(", ") + " for stronger password";
        } else {
          message.textContent = "Your password is medium";
        }
        message.style.color = "orange";
        passwordStrength.style.width = "75%";
        passwordStrength.style.backgroundColor = "orange";
      } else {
        passInputField.style.borderColor = "green";
        message.style.display = "block";
        message.textContent = "Your password is strong";
        message.style.color = "#23ad5c";
        passwordStrength.style.width = "100%";
        passwordStrength.style.backgroundColor = "#23ad5c";
      }
    } else {
      passwordStrength.style.display = "none";
      message.style.display = "none";
      passInputField.style.borderColor = "#ccc";
    }
  }

  // Helper function to show password error container
  function showPasswordErrorContainer(passwordInput) {
    if (passwordInput) {
      const errorContainer = findErrorContainer(passwordInput);
      if (errorContainer) {
        errorContainer.style.visibility = "visible";
      }
    }
  }

  // Helper function to hide password error container
  function hidePasswordErrorContainer(passwordInput) {
    if (passwordInput) {
      const errorContainer = findErrorContainer(passwordInput);
      if (errorContainer) {
        errorContainer.style.visibility = "hidden";
      }
    }
  }

  // Check if password and confirm password match for registration form
  function checkRegisterPasswordMatch() {
    if (!password || !registerConfirmPasswordInput) return;

    const passwordValue = password.value;
    const confirmValue = registerConfirmPasswordInput.value;

    // Find the confirm password field container
    const confirmField = registerConfirmPasswordInput.closest(".form-field");
    const confirmInputField =
      registerConfirmPasswordInput.closest(".input-field");

    if (!confirmField || !confirmInputField) return;

    // Get or create the message container
    let matchMessage = document.querySelector(".password-match-message");
    if (!matchMessage) {
      matchMessage = document.createElement("div");
      matchMessage.className = "password-match-message";
      matchMessage.style.fontSize = "11px";
      matchMessage.style.position = "absolute";
      matchMessage.style.marginTop = "2px";

      // Insert after the input field but before error container
      const errorContainer = confirmField.querySelector(
        ".input-error-container"
      );
      if (errorContainer) {
        confirmField.insertBefore(matchMessage, errorContainer);
      } else {
        confirmField.appendChild(matchMessage);
      }
    }

    // Only show validation if confirm password field has content
    if (confirmValue === "") {
      matchMessage.style.visibility = "hidden";
      confirmInputField.style.borderColor = "";
      return;
    }

    // Check if passwords match
    if (passwordValue === confirmValue && passwordValue !== "") {
      // Passwords match
      matchMessage.textContent = "Password match.";
      matchMessage.style.color = "#23ad5c";
      matchMessage.style.visibility = "visible";
      confirmInputField.style.borderColor = "#23ad5c";
      // Hide error container when showing match message
      hideConfirmPasswordErrorContainer(registerConfirmPasswordInput);
    } else {
      // Passwords don't match
      matchMessage.textContent = "Password does not match.";
      matchMessage.style.color = "red";
      matchMessage.style.visibility = "visible";
      confirmInputField.style.borderColor = "red";
      // Hide error container when showing match message
      hideConfirmPasswordErrorContainer(registerConfirmPasswordInput);
    }
  }

  // Helper function to show confirm password error container
  function showConfirmPasswordErrorContainer(confirmPasswordInput) {
    if (confirmPasswordInput) {
      const errorContainer = findErrorContainer(confirmPasswordInput);
      if (errorContainer) {
        errorContainer.style.visibility = "visible";
      }
    }
  }

  // Helper function to hide confirm password error container
  function hideConfirmPasswordErrorContainer(confirmPasswordInput) {
    if (confirmPasswordInput) {
      const errorContainer = findErrorContainer(confirmPasswordInput);
      if (errorContainer) {
        errorContainer.style.visibility = "hidden";
      }
    }
  }

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
});
// This is your actual JS file
