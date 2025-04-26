<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require './database/database.php'; 
$pdo = Database::connect();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $mobile = $_POST['mobile'];
    $admin = 'N'; 
    $errors = [];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }
    if (!preg_match('/^\d{3}-\d{3}-\d{4}$/', $mobile)) {
        $errors[] = "Mobile number must be in the format 000-000-0000.";
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM iss_persons WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = "Email is already registered.";
    }

    if (empty($errors)) {
        $hashedPassword = md5('learn' . $password);

        $verifyToken = bin2hex(random_bytes(16));

        $sql = "INSERT INTO iss_persons (email, pwd_hash, pwd_salt, fname, lname, admin, mobile, verify_token, verified) 
                VALUES (:email, :pwd_hash, :pwd_salt, :fname, :lname, :admin, :mobile, :verify_token, 0)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'email' => $email,
            'pwd_hash' => $hashedPassword, 
            'pwd_salt' => @$password, 
            'fname' => $fname,
            'lname' => $lname,
            'admin' => $admin,
            'mobile' => $mobile,
            'verify_token' => $verifyToken
        ]);

        $verificationLink = "http://localhost/CIS355/final/verify_email.php?email=$email&token=$verifyToken";
        mail($email, "Verify Your Email", "Click this link to verify your email: $verificationLink");

        $success = "Registration successful! Please check your email to verify your account.";
    } else {
        $error = implode(" ", $errors);
    }
}

Database::disconnect();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container">
    <h2>Register New User</h2>
    <?php if ($error): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p style="color: green;"><?php echo $success; ?></p>
        <a href="verify_email.php?email=<?php echo urlencode($email); ?>&token=<?php echo urlencode($verifyToken); ?>" class="btn btn-primary">Verify Email</a>
    <?php endif; ?>
    <form method="POST" action="register.php" class="px-3 py-4" id="registerForm">
        <div class="mb-3">
            <label for="fname" class="form-label">First Name:</label>
            <input type="text" class="form-control" id="fname" name="fname" required>
            <span id="fnameError" class="text-danger"></span>
        </div>
        <div class="mb-3">
            <label for="lname" class="form-label">Last Name:</label>
            <input type="text" class="form-control" id="lname" name="lname" required>
            <span id="lnameError" class="text-danger"></span>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email:</label>
            <input type="email" class="form-control" id="email" name="email" required onblur="validateEmail()">
            <span id="emailError" class="text-danger"></span>
        </div>
        <div class="mb-3">
            <label for="mobile" class="form-label">Mobile:</label>
            <input type="text" class="form-control" id="mobile" name="mobile" required 
                   pattern="\d{3}-\d{3}-\d{4}" 
                   title="Phone number must be in the format 000-000-0000"
                   oninput="this.value = this.value.replace(/[^0-9\-]/g, '').replace(/(\d{3})(\d{3})(\d{4})/, '$1-$2-$3').slice(0, 12);"
                   onblur="validateMobile()">
            <span id="mobileError" class="text-danger"></span>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password:</label>
            <input type="password" class="form-control" id="password" name="password" required onblur="validatePassword()">
            <span id="passwordError" class="text-danger"></span>
        </div>
        <div class="mb-3">
            <label for="confirm_password" class="form-label">Verify Password:</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required onblur="validateConfirmPassword()">
            <span id="confirmPasswordError" class="text-danger"></span>
        </div>
        <button type="submit" class="btn btn-success">Register</button>
    </form>
    <p>Already have an account? <a href="login.php">Login here</a></p>        
</div>
<script>
    function validateEmail() {
        const email = document.getElementById('email').value;
        const emailError = document.getElementById('emailError');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!emailRegex.test(email)) {
            emailError.textContent = "Invalid email format";
        } else {
            emailError.textContent = "";
        }
    }

    function validateMobile() {
        const mobile = document.getElementById('mobile').value;
        const mobileError = document.getElementById('mobileError');
        const mobileRegex = /^\d{3}-\d{3}-\d{4}$/;

        if (!mobileRegex.test(mobile)) {
            mobileError.textContent = "Invalid mobile number";
        } else {
            mobileError.textContent = "";
        }
    }

    function validatePassword() {
        const password = document.getElementById('password').value;
        const passwordError = document.getElementById('passwordError');

        if (password.length < 8) {
            passwordError.textContent = "Password must be at least 8 characters long.";
        } else {
            passwordError.textContent = "";
        }
    }

    function validateConfirmPassword() {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const confirmPasswordError = document.getElementById('confirmPasswordError');

        if (password !== confirmPassword) {
            confirmPasswordError.textContent = "Passwords do not match.";
        } else {
            confirmPasswordError.textContent = "";
        }
    }
</script>
</body>
</html>
