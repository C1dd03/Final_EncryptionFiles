document.addEventListener("DOMContentLoaded", function () {
  const loginForm = document.querySelector(".login-form");
  const messageDiv = document.getElementById("login-message");
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

    if (!username && !password) {
      setMessage("Username and password are required.", "error");
      return;
    } else if (!username) {
      setMessage("Username is required.", "error");
      return;
    } else if (!password) {
      setMessage("Password is required.", "error");
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
          const target = data.redirect || "/encryption/public/dashboard.php";
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
            startLock(seconds);
            consecutiveFails = 0;
            saveState();
            return;
          }

          //Show specific error messages
          // if (data.error === "username") {
          //   setMessage("Email is incorrect.", "error");
          // } else if (data.error === "password") {
          //   setMessage("Password is incorrect.", "error");
          // } else {
          //   setMessage(
          //     data.message ||
          //       `Invalid credentials. Attempt ${consecutiveFails}/${FAILS_PER_STAGE}`,
          //     "error"
          //   );
          // }

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
