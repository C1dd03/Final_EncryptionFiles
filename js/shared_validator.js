/**
 * shared_validator.js
 * Comprehensive real-time validation engine shared across:
 * - Personal Details
 * - Create Account
 * - Edit Account
 */

(function () {
  "use strict";

  const addressLabels = {
    street: "Purok/Street",
    barangay: "Barangay",
    city: "Municipal/City",
    province: "Province",
    country: "Country",
  };

  const addressNoSpecialCharFields = new Set(["city", "province", "country"]);

  function capitalizeFirst(str) {
    if (!str) return "";
    return str.charAt(0).toUpperCase() + str.slice(1);
  }

  function normalizeExtension(rawValue) {
    const value = rawValue.trim();
    if (value === "") return "";
    const upperValue = value.toUpperCase();
    if (["JR", "JR."].includes(upperValue)) return "Jr.";
    if (["SR", "SR."].includes(upperValue)) return "Sr.";
    const romanValue = upperValue.replace(/\./g, "");
    const validRomans = ["I", "II", "III", "IV", "V", "VI", "VII", "VIII", "IX", "X"];
    if (validRomans.includes(romanValue)) return romanValue;
    return value;
  }

  function validateFieldValue(fieldName, rawValue, options = {}) {
    const isRequired = options.required ?? false;
    const value = (rawValue || "").trim();

    // Required check
    if (isRequired && value === "") {
      if (addressLabels[fieldName]) {
        return `${addressLabels[fieldName]}: This field is required.`;
      }
      if (fieldName === "zip") return "Zip Code: This field is required.";
      if (fieldName === "birthdate") return "Birthdate is required.";
      if (fieldName === "gender") return "Gender is required.";
      if (fieldName === "full_name") return "Full Name is required.";
      if (fieldName === "id_number") return "ID Number is required.";
      if (fieldName === "username") return "Username is required.";
      if (fieldName === "email") return "Email is required.";
      return `${capitalizeFirst(fieldName.replace(/_/g, " "))} is required.`;
    }

    // Skip validation for optional empty fields
    if (!isRequired && value === "") {
      return null;
    }

    // --- Name field rules (first_name, middle_name, last_name, full_name) ---
    if (["first_name", "middle_name", "last_name", "full_name"].includes(fieldName)) {
      const label = fieldName === "full_name" ? "Full Name" : capitalizeFirst(fieldName.replace(/_/g, " "));

      if (rawValue.length > 0 && rawValue.charAt(0) === " ") {
        return `${label} cannot start with a space.`;
      }
      if (rawValue.length > 0 && !/^[A-Za-z]/.test(rawValue.charAt(0))) {
        return `${label} must start with a letter only.`;
      }
      if (/\s{2,}/.test(rawValue)) {
        return `${label} cannot contain double spaces.`;
      }

      let positionInWord = -1;
      let lastLetterLower = null;
      let repeatCount = 0;
      let wordsCount = 0;

      for (let i = 0; i < value.length; i++) {
        const char = value[i];
        if (char === " ") {
          positionInWord = -1;
          lastLetterLower = null;
          repeatCount = 0;
          continue;
        }

        positionInWord += 1;
        if (positionInWord === 0) wordsCount++;

        if (!/[A-Za-z]/.test(char)) {
          if (/\d/.test(char)) return `${label} cannot include numbers.`;
          return `${label} cannot include special characters.`;
        }

        const charLower = char.toLowerCase();
        if (charLower === lastLetterLower) {
          repeatCount += 1;
          if (repeatCount === 3) return `${label}: No 3 same letters in a row`;
        } else {
          lastLetterLower = charLower;
          repeatCount = 1;
        }

        if (positionInWord === 0 && char !== char.toUpperCase()) {
          return i === 0
            ? `${label} must start with a capital letter.`
            : `${label} requires each name to start with a capital letter.`;
        }

        if (positionInWord > 0 && char === char.toUpperCase()) {
          if (value === value.toUpperCase() && value.length > 1) {
            return `${label} should avoid all caps`;
          }
          return `${label} cannot contain capital letters after the first letter of each name.`;
        }
      }

      if (value === value.toUpperCase() && value.length > 1) {
        return `${label} should avoid all caps`;
      }

      if (fieldName === "full_name" && wordsCount < 2) {
        return "Full Name must include at least a first and last name.";
      }

      return null;
    }

    // --- Extension rules ---
    if (fieldName === "extension") {
      const normalized = normalizeExtension(value);
      const validExtensions = ["Jr.", "Sr.", "I", "II", "III", "IV", "V", "VI", "VII", "VIII", "IX", "X"];
      if (/[ivx]/.test(value)) {
        return "Name Extension must use uppercase Roman numerals.";
      }
      if (!validExtensions.includes(normalized)) {
        return "Name Extension must be Jr, Sr, or Roman numerals I–X";
      }
      return null;
    }

    // --- Address rules (street, barangay, city, province, country) ---
    if (addressLabels[fieldName]) {
      const label = addressLabels[fieldName];
      const isStreet = fieldName === "street";

      if (rawValue.length > 0 && rawValue.charAt(0) === " ") {
        return `${label} cannot start with a space.`;
      }

      if (!isStreet && rawValue.length > 0 && !/^[A-Za-z]/.test(rawValue.charAt(0))) {
        return `${label} must start with a letter only.`;
      }

      if (isStreet && value.length < 3) {
        return `${label}: Must be at least 3 characters long.`;
      }

      if (isStreet && /^[\d\s]+$/.test(value)) {
        return `${label}: It must contain letters.`;
      }

      if (isStreet) {
        const firstWord = rawValue.trim().split(/\s+/)[0] || "";
        if (/^[A-Za-z]$/.test(firstWord)) {
          return `${label}: Invalid street format.`;
        }
      }

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
          if (addressNoSpecialCharFields.has(fieldName)) {
            return `${label}: Special characters are not allowed.`;
          }
          lastLetterLower = null;
          repeatCount = 0;
          if (char === ".") {
            inWord = false;
            seenLetterInWord = false;
            wordHasDigit = false;
          }
          continue;
        }

        if (/\d/.test(char)) {
          if (!isStreet) return `${label}: Cannot include numbers.`;

          const prevChar = i > 0 ? rawValue[i - 1] : "";
          const nextChar = i < rawValue.length - 1 ? rawValue[i + 1] : "";

          if (/[A-Za-z]/.test(prevChar) && prevChar !== " " && prevChar !== "-" && prevChar !== ".") {
            return `${label}: Numbers must be separated from letters by a space, dash, or period.`;
          }

          if (/[A-Za-z]/.test(nextChar)) {
            const nextNextChar = i + 2 < rawValue.length ? rawValue[i + 2] : "";
            if (/[A-Za-z]/.test(nextNextChar)) {
              return `${label}: Only a single letter may follow a number directly.`;
            }
          }

          lastLetterLower = null;
          repeatCount = 0;
          if (!inWord) {
            inWord = true;
            seenLetterInWord = false;
            wordHasDigit = true;
            wordIndex += 1;
          } else if (seenLetterInWord) {
            const prev = rawValue[i - 1];
            if (prev !== " " && prev !== "-" && prev !== "." && /[A-Za-z]/.test(prev)) {
              return `${label}: Numbers must be separated from letters by a space, dash, or period.`;
            }
            wordHasDigit = true;
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
            const prevChar = i > 0 ? rawValue[i - 1] : "";
            const prevPrevChar = i > 1 ? rawValue[i - 2] : "";
            const isSuffixAfterDigit =
              isStreet && (/\d/.test(prevChar) || (prevChar === "-" && /\d/.test(prevPrevChar)));

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

            if (char !== char.toUpperCase() && !isSuffixAfterDigit) return capitalMessage;

            seenLetterInWord = true;
            wordHasDigit = false;
          } else if (char === char.toUpperCase()) {
            const prevChar = i > 0 ? rawValue[i - 1] : "";
            const prevPrevChar = i > 1 ? rawValue[i - 2] : "";
            const isAfterDigit =
              /\d/.test(prevChar) || prevChar === "." || (prevChar === "-" && /\d/.test(prevPrevChar));

            if (!isStreet || (isStreet && !isAfterDigit)) {
              return `${label}: Cannot contain capital letters after the first letter of each name.`;
            }
          }

          const charLower = char.toLowerCase();
          if (charLower === lastLetterLower) {
            repeatCount += 1;
            if (repeatCount === 3) return `${label}: No 3 same letters in a row`;
          } else {
            lastLetterLower = charLower;
            repeatCount = 1;
          }
          continue;
        }

        if (addressNoSpecialCharFields.has(fieldName)) {
          return `${label}: Special characters are not allowed.`;
        }
        return `${label}: Only period (.) and dash (-) allowed`;
      }

      if (hasLetter && !isStreet) {
        const lettersOnly = value.replace(/[^A-Za-z]/g, "");
        if (lettersOnly.length > 1 && lettersOnly === lettersOnly.toUpperCase() && !hasLowercaseLetter) {
          return `${label}: Should avoid all caps`;
        }
      }

      return null;
    }

    // --- Zip Code ---
    if (fieldName === "zip") {
      if (value.length < 4 || value.length > 6) {
        return "Zip Code: Must be 4 to 6 digits.";
      }
      if (!/^\d+$/.test(value)) {
        return "Zip Code: Only numbers are allowed.";
      }
      return null;
    }

    // --- Birthdate ---
    if (fieldName === "birthdate") {
      const birthDate = new Date(`${value}T00:00:00`);
      const today = new Date();
      if (isNaN(birthDate.getTime()) || birthDate > today) {
        return "Enter a valid Birthdate.";
      }
      let age = today.getFullYear() - birthDate.getFullYear();
      const m = today.getMonth() - birthDate.getMonth();
      if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) age--;
      if (age < 18) {
        return "You must be at least 18 years old.";
      }
      return null;
    }

    // --- Username ---
    if (fieldName === "username") {
      if (/\s/.test(rawValue)) {
        return "Username cannot contain spaces.";
      }
      if (/\s{2,}/.test(rawValue)) {
        return "Username cannot contain double spaces.";
      }
      if (!/^[A-Za-z0-9._@-]{3,50}$/.test(value)) {
        return "Username must be 3-50 characters and contain no spaces.";
      }
      return null;
    }

    // --- Email ---
    if (fieldName === "email") {
      if (/\s/.test(rawValue)) {
        return "Email cannot contain spaces.";
      }
      if (/\s{2,}/.test(rawValue)) {
        return "Email cannot contain double spaces.";
      }
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(value)) {
        return "Invalid email format.";
      }
      return null;
    }

    // --- ID Number (Create Account) ---
    if (fieldName === "id_number") {
      if (!/^[A-Za-z0-9-]{4,20}$/.test(value)) {
        return "ID Number must be 4-20 letters, numbers, or hyphens.";
      }
      return null;
    }

    // --- Password (Edit Account) ---
    if (fieldName === "password") {
      if (/\s/.test(rawValue)) {
        return "Password cannot contain spaces.";
      }
      if (value.length < 8) {
        return "Password must be at least 8 characters.";
      }
      const missing = [];
      if (!/[a-z]/.test(value)) missing.push("lowercase letter");
      if (!/[A-Z]/.test(value)) missing.push("uppercase letter");
      if (!/\d/.test(value)) missing.push("number");
      if (!/[!@#$%^&*(),.?":{}|<>_\-]/.test(value)) missing.push("special character");
      if (missing.length > 0) {
        return `Missing: ${missing.join(", ")}`;
      }
      return null;
    }

    // --- Security Questions (select) ---
    if (fieldName.startsWith("security_question_")) {
      // Only required when a paired answer has content - checked contextually, not here
      return null;
    }

    // --- Security Answers ---
    if (fieldName.startsWith("security_answer_")) {
      if (/^\s+$/.test(rawValue)) {
        return "Answer cannot contain only spaces.";
      }
      if (/\s/.test(rawValue)) {
        return "Answer cannot contain spaces.";
      }
      return null;
    }

    return null;
  }

  function getOrCreateErrorElement(input) {
    const parentLabel = input.closest("label") || input.parentElement;
    if (!parentLabel) return null;

    let errorEl = parentLabel.querySelector(`.field-error[data-for="${input.name}"]`);
    if (!errorEl) {
      errorEl = parentLabel.querySelector(".field-error");
    }
    if (!errorEl) {
      errorEl = document.createElement("span");
      errorEl.className = "field-error";
      errorEl.dataset.for = input.name;
      if (input.nextSibling) {
        parentLabel.insertBefore(errorEl, input.nextSibling);
      } else {
        parentLabel.appendChild(errorEl);
      }
    }
    return errorEl;
  }

  function setFieldError(input, message) {
    if (!input) return;
    const errorEl = getOrCreateErrorElement(input);
    if (message) {
      if (errorEl) {
        errorEl.textContent = message;
        errorEl.style.display = "block";
      }
      input.classList.add("has-error");
      input.style.borderColor = "#ef4444";
    } else {
      clearFieldError(input);
    }
  }

  function clearFieldError(input) {
    if (!input) return;
    const errorEl = getOrCreateErrorElement(input);
    if (errorEl) {
      errorEl.textContent = "";
      errorEl.style.display = "none";
    }
    input.classList.remove("has-error");
    input.style.borderColor = "";
  }

  /**
   * Attaches real-time validation to a form.
   */
  function attachRealtimeValidation(form, options = {}) {
    if (!form) return null;
    const initialDataGetter = options.getInitialData || (() => ({}));
    const ajaxCheckUrl = options.ajaxCheckUrl || "../auth/index.php";

    let debounceTimers = {};

    function validateInput(input, trigger = "input") {
      if (input.readOnly || input.disabled || input.type === "hidden" || input.type === "submit" || input.type === "reset") {
        return null;
      }

      const name = input.name;
      if (!name) return null;

      // Special handling for birthdate -> calculate age
      if (name === "birthdate" && input.value) {
        const birthDate = new Date(`${input.value}T00:00:00`);
        const today = new Date();
        if (!isNaN(birthDate.getTime())) {
          let age = today.getFullYear() - birthDate.getFullYear();
          const m = today.getMonth() - birthDate.getMonth();
          if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) age--;
          const ageInput = form.elements.age;
          if (ageInput) ageInput.value = Math.max(0, age);
        }
      }

      // Extension normalization on blur
      if (name === "extension" && trigger === "blur" && input.value) {
        input.value = normalizeExtension(input.value);
      }

      const isRequired = input.required || ["first_name", "last_name", "birthdate", "gender", "street", "barangay", "city", "province", "country", "zip"].includes(name);
      const error = validateFieldValue(name, input.value, { required: isRequired });

      if (error) {
        setFieldError(input, error);
        return error;
      }

      clearFieldError(input);

      // Debounced AJAX check for username / email
      if ((name === "username" || name === "email") && input.value.trim() !== "") {
        const initial = initialDataGetter()[name];
        if (input.value.trim() === (initial || "").trim()) {
          clearFieldError(input);
          return null;
        }

        clearTimeout(debounceTimers[name]);
        debounceTimers[name] = setTimeout(async () => {
          try {
            const action = name === "username" ? "checkUsername" : "checkEmail";
            const body = new URLSearchParams({ [name]: input.value.trim() });
            const response = await fetch(`${ajaxCheckUrl}?action=${action}`, {
              method: "POST",
              headers: { "Content-Type": "application/x-www-form-urlencoded" },
              body: body,
            });
            const data = await response.json();
            if (!data.available) {
              setFieldError(input, data.message || `This ${name} is already registered.`);
            } else {
              clearFieldError(input);
            }
          } catch (e) {
            // Ignore connection errors during real-time typing
          }
        }, 400);
      }

      return null;
    }

    // Attach listeners
    const inputs = form.querySelectorAll("input, select, textarea");
    inputs.forEach((input) => {
      if (input.tagName === "SELECT") {
        input.addEventListener("change", () => {
          validateInput(input, "change");
          // Cross-validate paired security question/answer
          if (input.name && input.name.startsWith("security_question_")) {
            const num = input.name.split("_").pop();
            const answerInput = form.elements[`security_answer_${num}`];
            if (answerInput && answerInput.value.trim() !== "") {
              if (!input.value || input.value === "") {
                setFieldError(input, `Please select a question for Answer ${num}.`);
              } else {
                clearFieldError(input);
              }
            } else {
              clearFieldError(input);
            }
          }
        });
        input.addEventListener("blur", () => validateInput(input, "blur"));
      } else {
        input.addEventListener("input", () => {
          validateInput(input, "input");
          // Cross-validate: if answer typed, ensure paired question is selected
          if (input.name && input.name.startsWith("security_answer_")) {
            const num = input.name.split("_").pop();
            const selectInput = form.elements[`security_question_${num}`];
            if (selectInput) {
              if (input.value.trim() !== "" && (!selectInput.value || selectInput.value === "")) {
                setFieldError(selectInput, `Please select a question for Answer ${num}.`);
              } else if (selectInput.value && selectInput.value !== "") {
                clearFieldError(selectInput);
              }
            }
          }
        });
        input.addEventListener("blur", () => validateInput(input, "blur"));
        if (input.type === "date") {
          input.addEventListener("change", () => validateInput(input, "change"));
        }
      }
    });

    return {
      validateAll: () => {
        let firstInvalid = null;
        let hasError = false;
        inputs.forEach((input) => {
          const err = validateInput(input, "blur");
          if (err) {
            hasError = true;
            if (!firstInvalid) firstInvalid = input;
          }
        });
        if (firstInvalid) {
          firstInvalid.focus();
        }
        return !hasError;
      },
      clearAll: () => {
        inputs.forEach((input) => clearFieldError(input));
        // Also clear any general security_questions error div
        const generalErrDiv = form.querySelector('[data-for="security_questions"]');
        if (generalErrDiv) { generalErrDiv.textContent = ""; generalErrDiv.style.display = "none"; }
      },
      setFieldError: (fieldName, message) => {
        const input = form.elements[fieldName];
        if (input) {
          setFieldError(input, message);
          return;
        }
        // Fallback: find a [data-for="fieldName"] element in the form
        const fallbackEl = form.querySelector(`[data-for="${fieldName}"]`);
        if (fallbackEl) {
          fallbackEl.textContent = message;
          fallbackEl.style.display = "block";
        }
      },
      validateField: (fieldName) => {
        const input = form.elements[fieldName];
        if (input) return validateInput(input, "blur");
        return null;
      }
    };
  }

  // Export globally
  window.SharedValidator = {
    validateFieldValue,
    setFieldError,
    clearFieldError,
    attachRealtimeValidation,
    normalizeExtension,
    capitalizeFirst,
  };
})();
