<?php
if (!isset($invitation, $errors, $completed)) { http_response_code(404); exit; }
$escape = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$questions = [
    [1=>'Who is your best friend in elementary?', 2=>'What is the name of your favorite pet?', 3=>'Who is your favorite teacher in high school?'],
    [4=>'What is your mother’s maiden name?', 5=>'What city were you born in?', 6=>'What is your favorite color?'],
    [7=>'What is your favorite food?', 8=>'What was the name of your first school?', 9=>'What is your father’s middle name?'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Complete Account Setup</title>
  <link rel="stylesheet" href="../../css/invitation_setup.css">
</head>
<body><main class="setup-card">
  <h1>Complete Account Setup</h1>
  <?php if ($completed): ?>
    <p role="status"><?= $escape($message) ?></p>
    <a class="button" href="index.php?action=login">Back to Login</a>
  <?php else: ?>
    <p>Complete all required information and choose a new password before accessing your account.</p>
    <?php if ($errors): ?><div class="errors" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= $escape($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" action="index.php?action=invitationSetup">
      <input type="hidden" name="csrf" value="<?= $escape($_SESSION['invitation_csrf']) ?>">
      <fieldset><legend>Account Information</legend><div class="fields">
        <label>ID Number<input value="<?= $escape($invitation['user_id']) ?>" readonly></label>
        <label>Username<input value="<?= $escape($invitation['username']) ?>" readonly></label>
        <label>Email address<input type="email" name="email" value="<?= $escape($invitation['email']) ?>" readonly required></label>
        <label>Role<input value="<?= $escape($invitation['role'] === 'superadmin' ? 'Super Admin' : ucfirst($invitation['role'])) ?>" readonly></label>
      </div></fieldset>
      <fieldset><legend>Personal Details</legend><div class="fields">
        <?php foreach (['first_name'=>'First Name','middle_name'=>'Middle Name (optional)','last_name'=>'Last Name','extension'=>'Extension (optional)'] as $key=>$label): ?>
          <label><?= $escape($label) ?><input name="<?= $key ?>" maxlength="<?= $key === 'extension' ? 10 : 50 ?>" value="<?= $escape($_POST[$key] ?? '') ?>" <?= in_array($key, ['middle_name','extension']) ? '' : 'required' ?>></label>
        <?php endforeach; ?>
        <label>Birthdate<input type="date" name="birthdate" value="<?= $escape($_POST['birthdate'] ?? '') ?>" required></label>
        <label>Gender<select name="gender" required><option value="">Select gender</option><?php foreach (['male'=>'Male','female'=>'Female'] as $key=>$label): ?><option value="<?= $key ?>" <?= ($_POST['gender'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label>
        <label>Contact Number<input type="tel" name="contact_number" maxlength="20" value="<?= $escape($_POST['contact_number'] ?? '') ?>" required></label>
      </div></fieldset>
      <fieldset><legend>Address</legend><div class="fields">
        <?php foreach (['street'=>'Street / Purok','barangay'=>'Barangay','city'=>'City / Municipality','province'=>'Province','country'=>'Country','zip'=>'ZIP Code'] as $key=>$label): ?>
          <label><?= $label ?><input name="<?= $key ?>" maxlength="<?= $key === 'zip' ? 10 : 100 ?>" value="<?= $escape($_POST[$key] ?? '') ?>" required></label>
        <?php endforeach; ?>
      </div></fieldset>
      <fieldset><legend>Security Questions</legend>
        <p>Choose three questions and keep your answers private. These are used to recover your account.</p>
        <?php foreach ($questions as $index=>$options): $number=$index+1; ?>
          <div class="fields">
            <label>Question <?= $number ?><select name="security_question_<?= $number ?>" required><option value="">Select a question</option>
              <?php foreach ($options as $id=>$label): ?><option value="<?= $id ?>" <?= (int)($_POST["security_question_{$number}"] ?? 0) === $id ? 'selected' : '' ?>><?= $escape($label) ?></option><?php endforeach; ?>
            </select></label>
            <label>Answer <?= $number ?><input type="password" name="security_answer_<?= $number ?>" maxlength="72" autocomplete="off" required></label>
          </div>
        <?php endforeach; ?>
      </fieldset>
      <fieldset><legend>New Password</legend>
        <p>Use at least 8 characters with uppercase and lowercase letters, a number, and a special character.</p>
        <div class="fields">
          <label>New Password<input type="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" required></label>
          <label>Confirm Password<input type="password" name="confirm_password" minlength="8" maxlength="72" autocomplete="new-password" required></label>
        </div>
      </fieldset>
      <div class="actions"><a href="index.php?action=login">Back to Login</a><button type="submit">Complete Setup</button></div>
    </form>
  <?php endif; ?>
</main></body></html>
