(function () {
  const FAILS_PER_STAGE = 3;
  const LOCK_DURATIONS = [10, 30, 60]; // seconds for stage 0,1,2

  let consecutiveFails = 0;
  let stageIndex = 0;
  let isLocked = false;
  let lockTimerId = null;
  let lockEndTimestamp = null;

  const form = document.querySelector(".login-form");
  const usernameInput = document.querySelector(".username");
  const passwordInput = document.querySelector(".password");
  const submitBtn = document.querySelector(".btn_submit");
  const messageEl = document.getElementById("login-message");

  // Disable input and button
  function updateUI() {
    usernameInput.disabled = isLocked;
    passwordInput.disabled = isLocked;
    submitBtn.disabled = isLocked;
  }

  // text info
  function setMessage(text, type = "info") {
    const messageEl = document.getElementById("login-message");
    messageEl.textContent = text;

    // reset classes first
    messageEl.className = "msg";

    if (type === "error") {
      messageEl.classList.add("error"); // red
    } else if (type === "success") {
      messageEl.classList.add("success"); // green
    }
  }

  // time duration
  function startLock(seconds) {
    isLocked = true;
    lockEndTimestamp = Date.now() + seconds * 1000;
    updateUI();
    countdownLock();
  }

  //pop up alert and message
  function countdownLock() {
    if (lockTimerId) clearInterval(lockTimerId);
    lockTimerId = setInterval(() => {
      const remainingMs = lockEndTimestamp - Date.now();

      // ✅ Kung tapos na ang lock
      if (remainingMs <= 0) {
        clearInterval(lockTimerId);
        lockTimerId = null;
        isLocked = false;
        consecutiveFails = 0;

        // Pop-up lang imbes na message sa UI
        /* alert("You can try logging in again."); */

        // Option 1
        // messageEl.innerHTML = "You can try logging in again.";

        // setTimeout(() => {
        //   messageEl.textContent = "";
        // }, 5000);

        // Burahin ang message sa UI (para mawala yung countdown text)
        // Option 2
        setMessage("You can try logging in again.");

        setTimeout(() => {
          setMessage("");
        }, 3000);

        updateUI();
        return;
      }

      // ✅ Habang may natitirang oras
      const sec = Math.ceil(remainingMs / 1000);
      setMessage(`Too many attempts. Try again in ${sec}s.`, "error");
    }, 500);
  }

  // Handle form submitz
  form.addEventListener("submit", async function (evt) {
    evt.preventDefault();
    if (isLocked) {
      setMessage("Login temporarily disabled. Wait for countdown.", "error");
      return;
    }

    const enteredUsername = usernameInput.value.trim();
    const enteredPassword = passwordInput.value;

    // PHP connection
    // Send credentials to backend
    // let response = await fetch("login.php", {
    //   method: "POST",
    //   headers: { "Content-Type": "application/json" },
    //   body: JSON.stringify({
    //     username: enteredUsername,
    //     password: enteredPassword,
    //   }),
    // });
    // let result = await response.json();

    // Input value  Condition
    if (enteredUsername === "admin" && enteredPassword === "1234") {
      // this for js example
      // or for php connect if (result.success) {
      consecutiveFails = 0;
      stageIndex = 0;
      isLocked = false;
      if (lockTimerId) clearInterval(lockTimerId);
      setMessage("✅ Login successful!");
      updateUI();
      alert("Welcome " + enteredUsername);
      // window.location.href = "dashboard.php";
    } else {
      consecutiveFails += 1;
      alert(
        `Incorrect Username or Password.  Attemp(${consecutiveFails}/${FAILS_PER_STAGE})`
      );
      updateUI();

      if (consecutiveFails >= FAILS_PER_STAGE) {
        const stageToUse = Math.min(stageIndex, LOCK_DURATIONS.length - 1);
        const seconds = LOCK_DURATIONS[stageToUse];
        alert(`❌ Too many failed attempts. Locked for ${seconds} seconds!`);
        startLock(seconds);
        if (stageIndex <= LOCK_DURATIONS.length) stageIndex += 1;
        consecutiveFails = 0;
        updateUI();
      }
    }
  });

  // setMessage("Please Sign in");
  /*  messageEl.style.display = "none"; */
  updateUI();
})();
