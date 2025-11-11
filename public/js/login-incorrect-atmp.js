// Login attempt handling with full back button lockout
(function(){
  const form = document.querySelector('.login-form');
  if (!form) return;

  const usernameInput = form.querySelector('input[name="username"]');
  const passwordInput = form.querySelector('input[name="password"]');
  const submitBtn = form.querySelector('.btn_submit');
  const forgotLink = document.getElementById('forgot-link');
  const registerLink = document.querySelector('.register-link a');
  const msgEl = document.getElementById('login-message');

  const DURATIONS = [10, 20, 30]; // seconds for stages 1..3
  const LS_KEYS = {
    failCount: 'loginFailCount',
    stage: 'loginLockStage',
    lockUntil: 'loginLockUntil'
  };

  const now = () => Date.now();
  const getInt = (k) => parseInt(localStorage.getItem(k) || '0', 10);
  const setInt = (k, v) => localStorage.setItem(k, String(v));
  const clearKey = (k) => localStorage.removeItem(k);

  // -------------------
  // Lock logic
  // -------------------
  function isLocked(){
    const until = getInt(LS_KEYS.lockUntil);
    return until && now() < until;
  }

  function remainingMs(){
    return Math.max(0, getInt(LS_KEYS.lockUntil) - now());
  }

  function updateCountdown(){
    const s = Math.ceil(remainingMs() / 1000);
    msgEl.textContent = s > 0 ? `Access denied. Please wait ${s}s.` : '';
  }

  // -------------------
  // Block keys (reload/back)
  // -------------------
  function blockKeys(e){
    const key = e.key.toLowerCase();
    const isBackCombo = (e.altKey && key === 'arrowleft') || key === 'browserback' || key === 'goback' || key === 'backspace';
    if (key === 'f5' || (e.ctrlKey && key === 'r') || isBackCombo) {
      e.preventDefault();
      e.stopPropagation();
    }
  }

  // -------------------
  // Fully block back button using dummy states
  // -------------------
  function disableBackCompletely(){
    if (!(window.history && window.history.pushState)) return;

    // Push multiple dummy states
    for (let i = 0; i < 20; i++) {
      window.history.pushState({dummy: i}, document.title, window.location.href);
    }

    window.onpopstate = function(e){
      if (isLocked()) {
        // Go forward immediately to stay on page
        setTimeout(() => window.history.go(1), 0);
      }
    };
  }

  // -------------------
  // UI control
  // -------------------
  function disableUI(){

    // Disable navbar links during lockout
    const navbarLinks = document.querySelectorAll('nav a'); // adjust selector if needed
    navbarLinks.forEach(link => {
        link.style.pointerEvents = 'none'; // prevent click
        link.style.opacity = '0.5';        // visually indicate disabled
        link.dataset.locked = 'true';      // custom attribute to track state
    });

    if (submitBtn) submitBtn.disabled = true;
    if (usernameInput) usernameInput.disabled = true;
    if (passwordInput) passwordInput.disabled = true;
    if (registerLink) {
      registerLink.style.pointerEvents = 'none';
      registerLink.style.opacity = '0.5';
    }
    if (forgotLink) forgotLink.style.display = 'none';

    window.addEventListener('keydown', blockKeys);
    disableBackCompletely();
  }

  function enableUI(){

    // Restore navbar links after lockout
    const navbarLinks = document.querySelectorAll('nav a[data-locked="true"]');
    navbarLinks.forEach(link => {
        link.style.pointerEvents = '';
        link.style.opacity = '';
        link.removeAttribute('data-locked');
    });

    if (submitBtn) submitBtn.disabled = false;
    if (usernameInput) usernameInput.disabled = false;
    if (passwordInput) passwordInput.disabled = false;
    if (registerLink) {
      registerLink.style.pointerEvents = '';
      registerLink.style.opacity = '';
    }
    if (forgotLink) forgotLink.style.display = '';

    window.removeEventListener('keydown', blockKeys);
    window.onpopstate = null;
    msgEl.textContent = '';
  }

  // -------------------
  // Start lockout
  // -------------------
  function startLock(durationSec){
    const until = now() + durationSec * 1000;
    setInt(LS_KEYS.lockUntil, until);
    disableUI();
    updateCountdown();

    const timer = setInterval(() => {
      if (!isLocked()) {
        clearInterval(timer);
        clearKey(LS_KEYS.lockUntil);
        setInt(LS_KEYS.failCount, 0); // reset fail count
        enableUI();
        msgEl.textContent = 'You may try logging in again.';
      } else {
        updateCountdown();
      }
    }, 250);
  }

  function applyExistingLock(){
    if (isLocked()) startLock(Math.ceil(remainingMs() / 1000));
    else enableUI();
  }

  // -------------------
  // Forgot password link logic
  // -------------------
  function showForgotOnSecondFail(){
    const count = getInt(LS_KEYS.failCount);
    if (count % 3 === 2 && !isLocked()) {
      if (forgotLink) forgotLink.style.display = '';
    } else {
      if (forgotLink) forgotLink.style.display = 'none';
    }
  }

  // -------------------
  // Handle submit
  // -------------------
  async function handleSubmit(){
    if (isLocked()) return;

    const uname = usernameInput.value.trim();
    const pass = passwordInput.value;

    if (!uname || !pass) {
      msgEl.textContent = 'Username and password are required.';
      return;
    }

    try {
      const resp = await fetch('index.php?action=loginUser', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `username=${encodeURIComponent(uname)}&password=${encodeURIComponent(pass)}`
      });
      const data = await resp.json();

      if (data && data.success) {
        clearKey(LS_KEYS.lockUntil);
        setInt(LS_KEYS.failCount, 0);
        setInt(LS_KEYS.stage, 0);
        window.location.href = data.redirect || 'index.php?action=dashboard';
        return;
      }

      // Failed login
      const newCount = getInt(LS_KEYS.failCount) + 1;
      setInt(LS_KEYS.failCount, newCount);
      msgEl.textContent = (data && data.message) ? data.message : 'Invalid username or password.';

      showForgotOnSecondFail();

      // Escalate lockout every 3 fails
      if (newCount % 3 === 0) {
        let stage = getInt(LS_KEYS.stage);
        const duration = DURATIONS[Math.min(stage, DURATIONS.length - 1)];
        startLock(duration);
        setInt(LS_KEYS.stage, Math.min(stage + 1, DURATIONS.length - 1));
      }

    } catch (err) {
      msgEl.textContent = 'Network error. Please try again.';
    }
  }

  // -------------------
  // Bind submit & initialize
  // -------------------
  form.addEventListener('submit', e => { e.preventDefault(); handleSubmit(); });
  applyExistingLock();
})();
