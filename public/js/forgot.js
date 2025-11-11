document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('.forgot-password');
  if (!form) return;

  const step1 = form.querySelector('.step-1');
  const step2 = form.querySelector('.step-2');
  const step3 = form.querySelector('.step-3');

  const idInput = document.getElementById('fp-id');
  const idError = document.getElementById('fp-id-error');

  const questionSelect = document.getElementById('fp-question');
  const answerInput = document.getElementById('fp-answer');
  const answerConfirmInput = document.getElementById('fp-answer-confirm');
  const answerError = document.getElementById('fp-answer-error');

  const pwdInput = document.getElementById('fp-new-password');
  const pwdConfirmInput = document.getElementById('fp-new-password-confirm');
  const pwdError = document.getElementById('fp-password-error');
  const strengthBar = document.getElementById('fp-strength-bar');
  const strengthMsg = document.getElementById('fp-strength-msg');
  const guide = document.getElementById('fp-password-guide');

  const next1 = document.getElementById('fp-next-1');
  const prev2 = document.getElementById('fp-prev-2');
  const next2 = document.getElementById('fp-next-2');
  const prev3 = document.getElementById('fp-prev-3');
  const submitPwd = document.getElementById('fp-submit-password');

  // Make eye icons visible when inputs have content
  const fpInputs = form.querySelectorAll(".input-field input[type='password'], .pass-input-field input[type='password']");
  fpInputs.forEach(input => {
    const icon = input.parentElement.querySelector('.toggle-eye');
    if (!icon) return;
    input.addEventListener('input', () => {
      if (input.value.trim() !== '') {
        icon.classList.add('visible');
      } else {
        icon.classList.remove('visible');
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
        input.type = 'password';
      }
    });
    input.addEventListener('blur', () => {
      if (input.value.trim() === '') {
        icon.classList.remove('visible');
      }
    });
  });

  function showStep(step) {
    [step1, step2, step3].forEach((el, idx) => {
      const active = idx === (step - 1);
      el.style.display = active ? '' : 'none';
      el.classList.toggle('active', active);
    });
  }

  function validatePasswordStrength(value) {
    let strength = 0;
    if (value.length >= 8) strength++;
    if (/[a-z]/.test(value)) strength++;
    if (/[A-Z]/.test(value)) strength++;
    if (/\d/.test(value)) strength++;
    if (/[@$!%*?&]/.test(value)) strength++;

    strengthBar.style.display = 'block';
    strengthMsg.style.display = 'block';
    guide.style.display = 'block';

    if (value.length === 0) {
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
    } else if (strength === 3 || strength === 4) {
      strengthBar.style.width = '66%';
      strengthBar.style.backgroundColor = '#f1c40f';
      strengthMsg.style.color = '#f1c40f';
      guide.textContent = 'Fair: Almost there! Add 1–2 more types for strong.';
      guide.style.color = '#f1c40f';
    } else if (strength >= 5) {
      strengthBar.style.width = '100%';
      strengthBar.style.backgroundColor = '#2ecc71';
      strengthMsg.style.color = '#2ecc71';
      guide.textContent = 'Secure: Nice! Strong password.';
      guide.style.color = '#2ecc71';
    }
  }

  // Step 1: proceed if username is non-empty
  next1.addEventListener('click', async () => {
    idError.style.display = 'none';
    const id = idInput.value.trim();
    if (!id) {
      idError.textContent = 'ID Number is required.';
      idError.style.display = 'block';
      return;
    }

    // Optional: check if ID exists via AJAX
    try {
      const resp = await fetch('index.php?action=checkId', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id_number=' + encodeURIComponent(id)
      });
      const text = await resp.text();
      if (text === 'exists' || text === 'missing') {
        if (text === 'missing') {
          idError.textContent = 'ID not found.';
          idError.style.display = 'block';
          return;
        }
        showStep(2);
      } else {
        showStep(2);
      }
    } catch (e) {
      showStep(2);
    }
  });

  prev2.addEventListener('click', () => showStep(1));

  // Step 2: verify answer
  next2.addEventListener('click', async () => {
    answerError.style.display = 'none';
    const id = idInput.value.trim();
    const qid = parseInt(questionSelect.value, 10);
    const ans = answerInput.value.trim();
    const ans2 = answerConfirmInput.value.trim();
    if (!qid) {
      answerError.textContent = 'Please choose a question.';
      answerError.style.display = 'block';
      return;
    }
    if (!ans || !ans2) {
      answerError.textContent = 'Please enter and re-enter your answer.';
      answerError.style.display = 'block';
      return;
    }
    if (ans !== ans2) {
      answerError.textContent = 'Answers do not match.';
      answerError.style.display = 'block';
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
        answerError.textContent = (data && data.message) ? data.message : 'Incorrect answer. Please try again.';
        answerError.style.display = 'block';
      }
    } catch (e) {
      answerError.textContent = 'Network error. Please try again.';
      answerError.style.display = 'block';
    }
  });

  prev3.addEventListener('click', () => showStep(2));

  // Password strength real-time
  pwdInput.addEventListener('input', () => validatePasswordStrength(pwdInput.value));
  pwdInput.addEventListener('blur', () => {
    strengthBar.style.display = 'none';
    strengthMsg.style.display = 'none';
    guide.style.display = 'none';
  });
  pwdInput.addEventListener('focus', () => {
    if (pwdInput.value.length > 0) {
      strengthBar.style.display = 'block';
      strengthMsg.style.display = 'block';
      guide.style.display = 'block';
    }
  });

  // Step 3: update password
  submitPwd.addEventListener('click', async () => {
    pwdError.style.display = 'none';
    const id = idInput.value.trim();
    const p1 = pwdInput.value;
    const p2 = pwdConfirmInput.value;
    const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/;
    if (!regex.test(p1)) {
      pwdError.textContent = 'Password must be 8+ and include upper, lower, number, symbol.';
      pwdError.style.display = 'block';
      return;
    }
    if (p1 !== p2) {
      pwdError.textContent = 'Passwords do not match.';
      pwdError.style.display = 'block';
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
        pwdError.textContent = (data && data.message) ? data.message : 'Failed to update password.';
        pwdError.style.display = 'block';
      }
    } catch (e) {
      pwdError.textContent = 'Network error. Please try again.';
      pwdError.style.display = 'block';
    }
  });
});

// Global helper for toggling visibility used by inline icons
window.toggleVisibility = function(id, icon) {
  const input = document.getElementById(id);
  if (!input) return;
  if (input.type === 'password') {
    input.type = 'text';
    if (icon && icon.classList) icon.classList.replace('bi-eye', 'bi-eye-slash');
  } else {
    input.type = 'password';
    if (icon && icon.classList) icon.classList.replace('bi-eye-slash', 'bi-eye');
  }
};