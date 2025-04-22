<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require './database/database.php';
$pdo = Database::connect();

if (isset($_GET['email']) && isset($_GET['token'])) {
    $email = filter_var($_GET['email'], FILTER_SANITIZE_EMAIL);
    $token = filter_var($_GET['token'], FILTER_SANITIZE_STRING);

    $stmt = $pdo->prepare("SELECT * FROM iss_persons WHERE email = :email AND verify_token = :token AND verified = 0");
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':token', $token);
    $stmt->execute();

    if ($stmt->rowCount() === 1) {
        $update = $pdo->prepare("UPDATE iss_persons SET verified = 1, verify_token = NULL WHERE email = :email");
        $update->bindParam(':email', $email);
        $update->execute();
        $success = "Email verified successfully! You can now log in.";
    } else {
        $success = "Invalid or expired verification link.";
    }
} else {
    $success = "Missing parameters.";
}

Database::disconnect();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container">
    <h2>Email Verification</h2>
    <p><?php echo htmlspecialchars($success); ?></p>
    <?php if ($success === "Email verified successfully! You can now log in."): ?>
        <a href="login.php" class="btn btn-primary">Login</a>
    <?php endif; ?>
</div>
</body>
</html>
