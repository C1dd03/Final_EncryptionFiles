document.addEventListener("DOMContentLoaded", function () {
  const loginForm = document.querySelector(".login-form");
  const messageDiv = document.getElementById("login-message");
  const usernameInput = loginForm?.querySelector('input[name="username"]');
  const passwordInput = loginForm?.querySelector('input[name="password"]');
  const usernameError = document.getElementById("username-error");
  const passwordError = document.getElementById("password-error");
  const formError = document.getElementById("login-form-error");
  const forgotLink =
    document.querySelector(".login-forgot-password a") ||
    document.querySelector(".login-forgot-password");
  const registerLink =
    document.querySelector(".register-link") ||
    document.querySelector(".register-link a");
  const navRegisterLink = document.querySelector(".nav-register-link");

  const submitBtn =
    loginForm &&
    (loginForm.querySelector("button[type='submit']") ||
      loginForm.querySelector("button") ||
      loginForm.querySelector("input[type='submit']"));

  const FAILS_PER_STAGE = 3;
  const LOCK_DURATIONS = [15, 30, 60]; // seconds

  const savedForgotLinkVisible =
    localStorage.getItem("forgotLinkVisible") === "true";
  setForgotLinkVisible(savedForgotLinkVisible);

  let consecutiveFails =
    parseInt(localStorage.getItem("consecutiveFails")) || 0;
  let stageIndex = parseInt(localStorage.getItem("stageIndex")) || 0;
  let isLocked = localStorage.getItem("isLocked") === "true";
  let lockEndTimestamp =
    parseInt(localStorage.getItem("lockEndTimestamp")) || null;
  let lockTimerId = null;

  function saveState() {
    localStorage.setItem("consecutiveFails", String(consecutiveFails));
    localStorage.setItem("stageIndex", String(stageIndex));
    localStorage.setItem("isLocked", isLocked ? "true" : "false");
    localStorage.setItem(
      "lockEndTimestamp",
      lockEndTimestamp ? String(lockEndTimestamp) : ""
    );
  }

  function clearState() {
    consecutiveFails = 0;
    stageIndex = 0;
    isLocked = false;
    lockEndTimestamp = null;
    saveState();
  }

  function setMessage(text, type) {
    if (!messageDiv) return;
    messageDiv.textContent = text;
    messageDiv.classList.remove("error", "success");
    if (type === "error") messageDiv.classList.add("error");
    if (type === "success") messageDiv.classList.add("success");

    // Handle visibility
    if (text) {
      messageDiv.style.visibility = "visible";
    } else {
      messageDiv.style.visibility = "hidden";
    }
  }

  function clearInlineErrors() {
    if (usernameError) {
      usernameError.textContent = "";
      usernameError.style.display = "none";
    }

    if (passwordError) {
      passwordError.textContent = "";
      passwordError.style.display = "none";
    }

    if (formError) {
      formError.textContent = "";
      formError.style.display = "none";
    }
  }

  function setFieldError(type, text) {
    if (type === "username") {
      if (usernameError) {
        usernameError.textContent = text;
        usernameError.style.display = text ? "block" : "none";
      }
    } else if (type === "password") {
      if (passwordError) {
        passwordError.textContent = text;
        passwordError.style.display = text ? "block" : "none";
      }
    } else if (type === "form") {
      if (formError) {
        formError.textContent = text;
        formError.style.display = text ? "block" : "none";
      }
    }
  }

  function showInlineError(type, text) {
    clearInlineErrors();
    setFieldError(type, text);
  }

  function setForgotLinkVisible(visible) {
    if (!forgotLink) return;
    forgotLink.style.display = visible ? "flex" : "none";
    localStorage.setItem("forgotLinkVisible", visible ? "true" : "false");
  }

  function disableFormElements(disabled) {
    if (!loginForm) return;

    const inputs = loginForm.querySelectorAll("input, button");
    inputs.forEach((input) => (input.disabled = disabled));

    [forgotLink, registerLink, navRegisterLink].forEach((link) => {
      if (link && link.parentElement) {
        if (disabled) {
          link.classList.add("disabled-link");
          link.style.pointerEvents = "none";
          link.style.opacity = "0.5";
        } else {
          link.classList.remove("disabled-link");
          link.style.pointerEvents = "auto";
          link.style.opacity = "1";
        }
      }
    });
  }

  function startLock(seconds) {
    isLocked = true;
    lockEndTimestamp = Date.now() + seconds * 1000;
    saveState();
    disableFormElements(true);
    startCountdown(seconds);
  }

  function showThirdAttemptMessage(data) {
    if (data.errorType === "usernameWrong") {
      showInlineError("username", "Username does not exist.");
      return;
    }

    if (data.errorType === "passwordWrong") {
      showInlineError("password", "Password is incorrect.");
      return;
    }

    if (data.errorType === "bothWrong") {
      showInlineError("form", "Invalid username and password.");
      return;
    }

    setMessage(
      data.message || `Invalid credentials. Attempt ${consecutiveFails}/${FAILS_PER_STAGE}`,
      "error"
    );
  }

  function startCountdown(initialSeconds = null) {
    if (lockTimerId) clearInterval(lockTimerId);

    lockTimerId = setInterval(() => {
      const remaining = Math.max(
        0,
        Math.ceil((lockEndTimestamp - Date.now()) / 1000)
      );
      setMessage(
        `Too many failed attempts. Locked for ${remaining} seconds.`,
        "error"
      );

      if (remaining <= 0) {
        clearInterval(lockTimerId);
        lockTimerId = null;
        isLocked = false;
        lockEndTimestamp = null;
        consecutiveFails = 0;
        saveState();
        setMessage("You can try logging in again.", "success");
        disableFormElements(false);
        setTimeout(() => setMessage(""), 4000);
      }
    }, 500);
  }

  // Restore lock state on page load
  if (isLocked && lockEndTimestamp) {
    const remaining = Math.ceil((lockEndTimestamp - Date.now()) / 1000);
    if (remaining > 0) {
      disableFormElements(true);
      startCountdown(remaining);
    } else {
      // Lock expired
      isLocked = false;
      lockEndTimestamp = null;
      consecutiveFails = 0;
      saveState();
      disableFormElements(false);
    }
  }

  if (!loginForm) return;

  [usernameInput, passwordInput].forEach((input) => {
    if (input) {
      input.addEventListener("input", clearInlineErrors);
    }
  });

  loginForm.addEventListener("submit", function (e) {
    e.preventDefault();

    if (isLocked) {
      // Remove this message: "Login temporarily disabled. Wait for the countdown."
      const remaining = Math.max(
        0,
        Math.ceil((lockEndTimestamp - Date.now()) / 1000)
      );
      setMessage(
        `Too many failed attempts. Locked for ${remaining} seconds.`,
        "error"
      );
      return;
    }

    const formData = new FormData(this);
    const username = formData.get("username");
    const password = formData.get("password");

    clearInlineErrors();
    setMessage("", "");

    if (!username && !password) {
      clearInlineErrors();
      setFieldError("username", "Username is required.");
      setFieldError("password", "Password is required.");
      return;
    } else if (!username) {
      showInlineError("username", "Username is required.");
      return;
    } else if (!password) {
      showInlineError("password", "Password is required.");
      return;
    }

    fetch("index.php?action=loginUser", {
      method: "POST",
      body: formData,
      credentials: "same-origin",
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          clearState();
          setForgotLinkVisible(false); // hide the link
          const target = data.redirect || "/Final_EncryptionFiles/public/dashboard.php";
          window.location.href = target;
        } else {
          // Ignore validation errors from backend
          if (
            data.message &&
            (data.message.includes("required") ||
              data.message.includes("Email and password are required"))
          ) {
            setMessage(data.message, "error");
            return;
          }

          consecutiveFails += 1;
          if (consecutiveFails === 2) {
            setForgotLinkVisible(true);
          }
          saveState();

          if (consecutiveFails >= FAILS_PER_STAGE) {
            const seconds =
              LOCK_DURATIONS[Math.min(stageIndex, LOCK_DURATIONS.length - 1)];
            if (stageIndex < LOCK_DURATIONS.length - 1) stageIndex += 1;
            showThirdAttemptMessage(data);
            setTimeout(() => {
              startLock(seconds);
            }, 1200);
            consecutiveFails = 0;
            saveState();
            return;
          }

          if (data.errorType === "usernameWrong") {
            showInlineError("username", "Username does not exist.");
            return;
          }

          if (data.errorType === "passwordWrong") {
            showInlineError("password", "Password is incorrect.");
            return;
          }

          if (data.errorType === "bothWrong") {
            showInlineError("form", "Invalid username and password.");
            return;
          }

          setMessage(
            data.message ||
              `Invalid credentials. Attempt ${consecutiveFails}/${FAILS_PER_STAGE}`,
            "error"
          );
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        setMessage("An error occurred. Please try again.", "error");
      });
  });
});
