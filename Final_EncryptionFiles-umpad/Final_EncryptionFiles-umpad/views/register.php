<!DOCTYPE html>
<html>
<head>
    <title>Register User</title>
        <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        .error { color: red; margin: 5px 0; }
        .success { color: green; margin: 5px 0; }
        form { max-width: 400px; }
        label { display: block; margin-top: 10px; }
        input[type="text"], input[type="password"], input[type="date"] {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            box-sizing: border-box;
        }
        input[type="submit"] {
            margin-top: 15px;
            padding: 10px;
            width: 100%;
            background: #007BFF;
            color: white;
            border: none;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <h2>User Registration</h2>

    <!-- ✅ PHP Error Messages from Controller -->
    <?php if (!empty($errors)) : ?>
        <div class="error">
            <?php foreach ($errors as $err) echo "<p>$err</p>"; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="index.php?action=register" onsubmit="return validateForm();">

        <label>ID Number* </label><br>
        <input type="text" name="id_number" placeholder="xxxx-xxxx" 
               required pattern="^[0-9]{4}-[0-9]{4}$"><br><br>

        <label>First Name* </label><br>
        <input type="text" id="first_name" name="first_name" required 
               onblur="validateName(this, 'first_name')">
        <span class="error" id="first_name_error"></span><br><br>

        <label>Middle Name <span style="color:red;">(optional)</span></label><br>
        <input type="text" id="middle_name" name="middle_name"
               onblur="validateName(this, 'middle_name')">
        <span class="error" id="middle_name_error"></span><br><br>

        <label>Last Name* </label><br>
        <input type="text" id="last_name" name="last_name" required
               onblur="validateName(this, 'last_name')">
        <span class="error" id="last_name_error"></span><br><br>

        <label>Extension <span style="color:red;">(optional)</span></label><br>
        <input type="text" name="extension"><br><br>

        <label>Birthdate* </label><br>
        <input type="date" name="birthdate" required><br><br>

        <label>Username* </label><br>
        <input type="text" name="username" required><br><br>

        <label>Password* </label><br>
        <input type="password" id="password" name="password" required onkeyup="checkPasswordStrength();">
        <span id="password_strength"></span><br><br>

        <label>Confirm Password* </label><br>
        <input type="password" id="confirm_password" name="confirm_password" required onkeyup="confirmPasswordCheck();">
        <span class="error" id="confirm_error"></span><br><br>

        <button type="submit">Register</button>
    </form>

    <!-- ✅ External JS file -->
    <script src="/public/js/script.js"></script>
</body>

</html>
