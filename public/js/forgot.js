document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('.forgot-password');
  if (!form) return;

  const step1 = form.querySelector('.step-1');
  const step2 = form.querySelector('.step-2');
  const step3 = form.querySelector('.step-3');

  const idInput = document.getElementById('fp-id');
  const questionSelect = document.getElementById('fp-question');
  const answerInput = document.getElementById('fp-answer');
  const answerConfirmInput = document.getElementById('fp-answer-confirm');
  const pwdInput = document.getElementById('fp-new-password');
  const pwdConfirmInput = document.getElementById('fp-new-password-confirm');

  const next1 = document.getElementById('fp-next-1');
  const prev2 = document.getElementById('fp-prev-2');
  const next2 = document.getElementById('fp-next-2');
  const prev3 = document.getElementById('fp-prev-3');
  const submitPwd = document.getElementById('fp-submit-password');

  // Password strength elements
  const strengthBar = document.getElementById('fp-strength-bar');
  const strengthMsg = document.getElementById('fp-strength-msg');
  const guide = document.getElementById('fp-password-guide');

  // -----------------------------
  // Error helpers
  // -----------------------------
  function clearErrors(step) {
    const container = step.querySelector('.step-error-container');
    if (container) container.remove();
    step.querySelectorAll('input, select').forEach(input => input.style.borderBottom = '');
  }

  function showErrors(step, errors, firstInvalidInput = null) {
    clearErrors(step);
    if (!errors.length) return;

    if (firstInvalidInput) {
      firstInvalidInput.style.borderBottom = "1px solid rgba(255,0,0,0.5)";
      firstInvalidInput.scrollIntoView({ behavior: "smooth", block: "center" });
    }

    const container = document.createElement('div');
    container.className = 'step-error-container';
    errors.forEach(err => {
      const div = document.createElement('div');
      div.innerText = err;
      container.appendChild(div);
    });
    step.insertBefore(container, step.firstChild);
  }

  // -----------------------------
  // Step navigation
  // -----------------------------
  function showStep(step) {
    [step1, step2, step3].forEach((el, idx) => {
      const active = idx === (step - 1);
      el.style.display = active ? '' : 'none';
      el.classList.toggle('active', active);
    });
    clearErrors(step1);
    clearErrors(step2);
    clearErrors(step3);
  }

  prev2.addEventListener('click', () => showStep(1));
  prev3.addEventListener('click', () => showStep(2));

  // -----------------------------
  // Step 1: ID validation
  // -----------------------------
  next1.addEventListener('click', async () => {
    const id = idInput.value.trim();
    const errors = [];

    if (!id) errors.push('ID Number is required.');

    if (errors.length) {
      showErrors(step1, errors, idInput);
      return;
    }

    try {
      const resp = await fetch('index.php?action=checkId', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id_number=' + encodeURIComponent(id)
      });
      const text = await resp.text();
      if (text === 'missing') {
        showErrors(step1, ['ID not found.'], idInput);
        return;
      }
      showStep(2);
    } catch (e) {
      showErrors(step1, ['Network error. Please try again.'], idInput);
    }
  });

  // -----------------------------
  // Step 2: Security answer validation
  // -----------------------------
  next2.addEventListener('click', async () => {
    const id = idInput.value.trim();
    const qid = parseInt(questionSelect.value, 10);
    const ans = answerInput.value.trim();
    const ans2 = answerConfirmInput.value.trim();
    const errors = [];

    // Individual input errors
    if (!qid) {
      showErrors(step2, ['Please choose a question.'], questionSelect);
      return;
    }
    if (!ans) {
      showErrors(step2, ['Please enter your answer.'], answerInput);
      return;
    }
    if (!ans2) {
      showErrors(step2, ['Please re-enter your answer.'], answerConfirmInput);
      return;
    }
    if (ans !== ans2) {
      showErrors(step2, ['Answers do not match.'], answerConfirmInput);
      return;
    }

    try {
      const resp = await fetch('index.php?action=verifySecurityAnswer', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id_number=${encodeURIComponent(id)}&question_id=${encodeURIComponent(qid)}&answer=${encodeURIComponent(ans)}`
      });
      const data = await resp.json();
      if (data && data.success) {
        showStep(3);
      } else {
        showErrors(step2, [(data && data.message) ? data.message : 'Incorrect answer. Please try again.'], answerInput);
      }
    } catch (e) {
      showErrors(step2, ['Network error. Please try again.'], answerInput);
    }
  });

  // -----------------------------
  // Step 3: Password reset
  // -----------------------------
  pwdInput.addEventListener('input', () => {
    let strength = 0;
    const value = pwdInput.value;
    if (value.length >= 8) strength++;
    if (/[a-z]/.test(value)) strength++;
    if (/[A-Z]/.test(value)) strength++;
    if (/\d/.test(value)) strength++;
    if (/[@$!%*?&]/.test(value)) strength++;

    strengthBar.style.display = 'block';
    strengthMsg.style.display = 'block';
    guide.style.display = 'block';

    if (!value) {
      strengthBar.style.width = '0%';
      strengthMsg.textContent = '';
      guide.textContent = '';
      strengthBar.style.backgroundColor = 'transparent';
      return;
    }

    if (strength <= 2) {
      strengthBar.style.width = '33%';
      strengthBar.style.backgroundColor = '#e74c3c';
      strengthMsg.style.color = '#e74c3c';
      guide.textContent = 'Poor: Add 8+ chars, mix upper, lower, number & symbol.';
      guide.style.color = '#e74c3c';
    } else if (strength <= 4) {
      strengthBar.style.width = '66%';
      strengthBar.style.backgroundColor = '#f1c40f';
      strengthMsg.style.color = '#f1c40f';
      guide.textContent = 'Fair: Almost there! Add 1–2 more types for strong.';
      guide.style.color = '#f1c40f';
    } else {
      strengthBar.style.width = '100%';
      strengthBar.style.backgroundColor = '#2ecc71';
      strengthMsg.style.color = '#2ecc71';
      guide.textContent = 'Secure: Nice! Strong password.';
      guide.style.color = '#2ecc71';
    }
  });

submitPwd.addEventListener('click', async () => {
  const id = idInput.value.trim();
  const p1 = pwdInput.value;
  const p2 = pwdConfirmInput.value;
  const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/;

  // -----------------------------
  // Required field checks
  // -----------------------------
  if (!p1) {
    showErrors(step3, ['Password is required.'], pwdInput);
    return;
  }
  if (!p2) {
    showErrors(step3, ['Re-enter password is required.'], pwdConfirmInput);
    return;
  }

  // -----------------------------
  // Password validation
  // -----------------------------
  if (!regex.test(p1)) {
    showErrors(step3, ['Password must be 8+ and include upper, lower, number, symbol.'], pwdInput);
    return;
  }
  if (p1 !== p2) {
    showErrors(step3, ['Passwords do not match.'], pwdConfirmInput);
    return;
  }

  try {
    const resp = await fetch('index.php?action=updatePassword', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `id_number=${encodeURIComponent(id)}&new_password=${encodeURIComponent(p1)}`
    });
    const data = await resp.json();
    if (data && data.success) {
      alert('Password updated. You can now log in.');
      window.location.href = 'index.php?action=login';
    } else {
      showErrors(step3, [(data && data.message) ? data.message : 'Failed to update password.'], pwdInput);
    }
  } catch (e) {
    showErrors(step3, ['Network error. Please try again.'], pwdInput);
  }
}
);
});
