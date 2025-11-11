// Login attempt handling with escalating lockouts and UI controls
(function(){
  const form = document.querySelector('.login-form');
  if (!form) return;

  const usernameInput = form.querySelector('input[name="username"]');
  const passwordInput = form.querySelector('input[name="password"]');
  const submitBtn = form.querySelector('.btn_submit');
  const forgotLink = document.getElementById('forgot-link');
  const registerLink = document.querySelector('.register-link a');
  const msgEl = document.getElementById('login-message');

  const DURATIONS = [15, 30, 60]; // seconds for stages 1..3
  const LS_KEYS = {
    failCount: 'loginFailCount',
    stage: 'loginLockStage',
    lockUntil: 'loginLockUntil'
  };

  const now = () => Date.now();
  const getInt = (k) => parseInt(localStorage.getItem(k) || '0', 10);
  const setInt = (k, v) => localStorage.setItem(k, String(v));
  const clearKey = (k) => localStorage.removeItem(k);

  function isLocked(){
    const until = getInt(LS_KEYS.lockUntil);
    return until && now() < until;
  }

  function remainingMs(){
    const until = getInt(LS_KEYS.lockUntil);
    return Math.max(0, until - now());
  }

  function updateCountdown(){
    const ms = remainingMs();
    const s = Math.ceil(ms / 1000);
    msgEl.textContent = s > 0 ? `Access denied. Try again in ${s}s.` : '';
  }

  function disableUI(){
    if (submitBtn) submitBtn.disabled = true;
    if (registerLink) {
      registerLink.style.pointerEvents = 'none';
      registerLink.style.opacity = '0.5';
    }
    if (forgotLink) forgotLink.style.display = 'none';

    // Block reload/back while locked
    window.onbeforeunload = function(){ return 'Login temporarily locked'; };
    window.addEventListener('keydown', blockReloadKeys);
    if (window.history && window.history.pushState) {
      window.history.pushState(null, document.title, window.location.href);
      window.onpopstate = function(){ window.history.pushState(null, document.title, window.location.href); };
    }
  }

  function enableUI(){
    if (submitBtn) submitBtn.disabled = false;
    if (registerLink) {
      registerLink.style.pointerEvents = '';
      registerLink.style.opacity = '';
    }
    if (forgotLink) forgotLink.style.display = '';
    window.onbeforeunload = null;
    window.removeEventListener('keydown', blockReloadKeys);
    if (window.history && window.history.pushState) {
      window.onpopstate = null;
    }
    msgEl.textContent = '';
  }

  function blockReloadKeys(e){
    const key = e.key.toLowerCase();
    if (key === 'f5' || (e.ctrlKey && key === 'r')) {
      e.preventDefault();
      e.stopPropagation();
    }
  }

  function startLock(durationSec){
    const until = now() + durationSec * 1000;
    setInt(LS_KEYS.lockUntil, until);
    disableUI();
    updateCountdown();
    const timer = setInterval(function(){
      updateCountdown();
      if (!isLocked()) {
        clearInterval(timer);
        // Reset only failCount; keep stage for next escalation
        setInt(LS_KEYS.failCount, 0);
        clearKey(LS_KEYS.lockUntil);
        enableUI();
        msgEl.textContent = 'You may try logging in again.';
      }
    }, 250);
  }

  function applyExistingLock(){
    if (isLocked()) {
      disableUI();
      updateCountdown();
      const timer = setInterval(function(){
        updateCountdown();
        if (!isLocked()) { clearInterval(timer); enableUI(); msgEl.textContent = 'You may try logging in again.'; }
      }, 250);
    } else {
      enableUI();
    }
  }

  // On load, apply any lock state
  applyExistingLock();

  function showForgotOnSecondFail(){
    const count = getInt(LS_KEYS.failCount);
    if (count % 3 === 2 && !isLocked()) {
      if (forgotLink) forgotLink.style.display = '';
    } else {
      if (forgotLink) forgotLink.style.display = 'none';
    }
  }

  async function handleSubmit(){
    if (isLocked()) return; // ignore clicks while locked
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
        // success: clear counters and redirect
        setInt(LS_KEYS.failCount, 0);
        clearKey(LS_KEYS.lockUntil);
        window.location.href = data.redirect || 'index.php?action=dashboard';
        return;
      }

      // failure handling
      const newCount = getInt(LS_KEYS.failCount) + 1;
      setInt(LS_KEYS.failCount, newCount);
      msgEl.textContent = (data && data.message) ? data.message : 'Invalid username or password.';

      // Show forgot link on 2nd consecutive fail (per block)
      showForgotOnSecondFail();

      // On every 3rd fail, start a lock with escalating duration
      if (newCount % 3 === 0) {
        let stage = getInt(LS_KEYS.stage);
        const duration = DURATIONS[Math.min(stage, DURATIONS.length - 1)];
        startLock(duration);
        setInt(LS_KEYS.stage, Math.min(stage + 1, DURATIONS.length - 1));
      }

    } catch (e) {
      msgEl.textContent = 'Network error. Please try again.';
    }
  }

  // Bind submit
  form.addEventListener('submit', function(e){ e.preventDefault(); handleSubmit(); });
})();