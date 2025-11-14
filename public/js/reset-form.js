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
    if (isError) {
      container.style.color = "red";
      input.style.borderBottom = "1px solid rgba(255,0,0,0.5)";
    } else {
      container.style.color = "green";
      input.style.borderBottom = "1px solid rgba(0,255,0,0.5)";
    }
  }

  function clearFieldError(input) {
    const container = findErrorContainer(input);
    if (container) container.innerText = "";
    input.style.borderBottom = "";
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
            return capitalizeMessage(`${fieldName}: No 3 same letters in a row`);
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
          if (previousWasSpace) return `${label}: Cannot contain double spaces.`;
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
          if (!isStreet)
            return `${label}: Cannot include numbers.`;
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
        return `${label}: Only period (.) and dash (-) allowed special characters.`;
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
          "Name Extension must be Jr., Sr., or Roman numerals I to X."
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

    if (!allValid) {
      alert("Please fix the errors before submitting.");
      return false;
    }
    return true;
  };

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
});
// This is your actual JS file

