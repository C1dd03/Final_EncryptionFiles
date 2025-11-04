<form class="forgot-password" id="forgotForm">
  <h2>Forgot Password</h2>

  <div class="input-field">
    <select name="security_question" required>
      <option value="">Select a question</option>
      <option value="q1">Who was your best friend in elementary school?</option>
      <option value="q2">What was the name of your favorite pet?</option>
      <option value="q3">Who was your favorite high school teacher?</option>
    </select>
    <label>Select Question</label>
  </div>

  <div class="input-field">
    <input type="text" name="answer" required placeholder=" " />
    <label>Answer</label>
  </div>

  <div class="input-field">
    <input type="password" name="new_password" required placeholder=" " />
    <label>New Password</label>
  </div>

  <div class="input-field">
    <input type="password" name="confirm_password" required placeholder=" " />
    <label>Confirm Password</label>
  </div>

  <button type="submit" class="btn_submit">Submit</button>

  <p class="toggle-link">
    Remembered your password? <a href="index.php?action=login"><b>Login</b></a>
  </p>
</form>
