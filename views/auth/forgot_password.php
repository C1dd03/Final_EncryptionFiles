<?php
// Prevent caching for proper flow
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");
?>

<form class="forgot-password" method="POST" onsubmit="return false;">
  <h2>Forgot Password</h2>

  <!-- Step 1: Identify User -->
  <div class="step step-1 active">
    <span class="span spn">Find your account</span>
    <div class="input-field inputfield">
      <input type="text" name="id_number" id="fp-id" required placeholder=" " />
      <label>ID Number</label>
    </div>
    <div style="display: flex; justify-content: space-between; gap: 10px">
      <label class="message">Enter your ID number to continue</label>
      <button type="button" class="btn next-btn" id="fp-next-1">Next &gt;</button>
    </div>
    <p class="error-message" id="fp-id-error" style="display:none;"></p>
  </div>

  <!-- Step 2: Security Question Verification -->
  <div class="step step-2" style="display:none;">
    <span class="span">Answer your security question</span>

    <div class="input-field inputfield">
      <select name="question_id" id="fp-question" required>
        <option value="" disabled selected hidden></option>
        <option value="1">Who was your best friend in elementary school?</option>
        <option value="2">What was the name of your favorite pet?</option>
        <option value="3">Who was your favorite high school teacher?</option>
      </select>
      <label>Choose a question</label>
    </div>

    <div class="input-field inputfield">
      <input type="password" id="fp-answer" name="answer" required placeholder=" " />
      <label>Your Answer</label>
      <i class="bi bi-eye toggle-eye" onclick="toggleVisibility('fp-answer', this)"></i>
    </div>

    <div class="input-field inputfield">
      <input type="password" id="fp-answer-confirm" name="answer_confirm" required placeholder=" " />
      <label>Re-enter Answer</label>
      <i class="bi bi-eye toggle-eye" onclick="toggleVisibility('fp-answer-confirm', this)"></i>
    </div>

    <div style="display: flex; justify-content: space-between; gap: 10px">
      <button type="button" class="btn prev-btn" id="fp-prev-2">&lt; Prev</button>
      <button type="button" class="btn next-btn" id="fp-next-2">Submit</button>
    </div>
    <p class="error-message" id="fp-answer-error" style="display:none;"></p>
  </div>

  <!-- Step 3: Change Password -->
  <div class="step step-3" style="display:none;">
    <span class="span">Change Password</span>

    <div>
      <div class="pass-input-field inputfield">
        <input type="password" id="fp-new-password" name="new_password" required placeholder=" " />
        <label>Enter New Password</label>
        <i class="bi bi-eye toggle-eye" onclick="toggleVisibility('fp-new-password', this)"></i>
      </div>
      <div class="password-strenght-container">
        <div class="password-strenght" id="fp-strength-bar"></div>
      </div>
      <div id="fp-strength-msg"></div>
      <div id="fp-password-guide" style="font-size: 12px; margin-top: 3px; display: none;"></div>
    </div>

    <div class="input-field inputfield">
      <input type="password" id="fp-new-password-confirm" name="new_password_confirm" required placeholder=" " />
      <label>Re-enter Password</label>
      <i class="bi bi-eye toggle-eye" onclick="toggleVisibility('fp-new-password-confirm', this)"></i>
    </div>

    <div style="display: flex; justify-content: space-between; gap: 10px">
      <button type="button" class="btn prev-btn" id="fp-prev-3">&lt; Prev</button>
      <button type="button" class="btn next-btn" id="fp-submit-password">Update Password</button>
    </div>
    <p class="error-message" id="fp-password-error" style="display:none;"></p>
  </div>

  <p class="toggle-link">
    Remembered your password? <a href="index.php?action=login"><b>Login</b></a>
  </p>
</form>