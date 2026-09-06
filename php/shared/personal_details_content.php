<section class="personal-details" id="personalDetails">
    <div class="pd-heading">
      <div><h2>Personal Details</h2><p>Review and edit the information collected during registration. Passwords are never displayed.</p></div>
    </div>
    <form id="personalDetailsForm" novalidate>
      <fieldset><legend>Account</legend><div class="pd-grid">
        <label>ID Number<input name="id_number" readonly /></label>
        <label>Username<input name="username" required /><span class="field-error"></span></label>
        <label>Email Address<input type="email" name="email" required /><span class="field-error"></span></label>
        <label>Role<input name="role" readonly /></label>
        <label>Account Status<input name="status" readonly /></label>
      </div></fieldset>
      <fieldset><legend>Personal Information</legend><div class="pd-grid">
        <label>First Name<input name="first_name" required /><span class="field-error"></span></label>
        <label>Middle Name<input name="middle_name" /><span class="field-error"></span></label>
        <label>Last Name<input name="last_name" required /><span class="field-error"></span></label>
        <label>Name Extension<input name="extension" placeholder="Jr., Sr., III" /><span class="field-error"></span></label>
        <label>Birthdate<input type="date" name="birthdate" required /><span class="field-error"></span></label>
        <label>Age<input name="age" readonly /></label>
        <label>Gender<select name="gender" required><option value="">Select</option><option value="male">Male</option><option value="female">Female</option></select><span class="field-error"></span></label>
      </div></fieldset>
      <fieldset><legend>Address Information</legend><div class="pd-grid">
        <label>Purok / Street<input name="street" required /><span class="field-error"></span></label>
        <label>Barangay<input name="barangay" required /><span class="field-error"></span></label>
        <label>Municipal / City<input name="city" required /><span class="field-error"></span></label>
        <label>Province<input name="province" required /><span class="field-error"></span></label>
        <label>Country<input name="country" required /><span class="field-error"></span></label>
        <label>Zip Code<input name="zip" required /><span class="field-error"></span></label>
      </div></fieldset>
      <fieldset><legend>Security Questions</legend>
        <p class="pd-help">Your selected questions are shown. To configure or change them, choose all three questions and enter three new answers. Stored answers are never displayed.</p>
        <div class="pd-security-grid">
          <?php
          $questionGroups = [
            1 => [1 => 'Who is your best friend in elementary?', 2 => 'What is the name of your favorite pet?', 3 => 'Who is your favorite teacher in high school?'],
            2 => [4 => "What is your mother's maiden name?", 5 => 'What city were you born in?', 6 => 'What is your favorite color?'],
            3 => [7 => 'What is your favorite food?', 8 => 'What was the name of your first school?', 9 => "What is your father's middle name?"]
          ];
          foreach ($questionGroups as $number => $questions): ?>
            <label>Security Question <?= $number ?>
              <select name="security_question_<?= $number ?>"><option value="">Not configured</option>
                <?php foreach ($questions as $id => $text): ?><option value="<?= $id ?>"><?= htmlspecialchars($text) ?></option><?php endforeach; ?>
              </select>
              <span class="field-error"></span>
            </label>
            <label>New Answer <?= $number ?><input type="password" name="security_answer_<?= $number ?>" autocomplete="off" placeholder="Leave blank to keep saved answer" /><span class="field-error"></span></label>
          <?php endforeach; ?>
          <div class="field-error security-questions-error" data-for="security_questions" style="grid-column: 1 / -1; margin-top: 4px;"></div>
        </div>
      </fieldset>
      <p class="pd-message" id="personalDetailsMessage" role="alert"></p>
      <div class="pd-actions"><button type="reset" class="pd-btn">Reset</button><button type="submit" class="pd-btn primary">Save Personal Details</button></div>
    </form>
</section>
