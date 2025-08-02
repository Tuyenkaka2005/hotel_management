<?php
require 'config.php';
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = 'Customer';
    
    // Kiểm tra password match
    if ($password !== $confirm_password) {
        $error = 'Passwords do not match!';
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        // Chỉ kiểm tra trùng username/email và lỗi hệ thống ở PHP
        $stmt = $pdo->prepare('SELECT * FROM Account WHERE Username = ? OR Email = ?');
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'Username or email already exists!';
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO Account (Username, Password, Role, FullName, Email, PhoneNumber) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$username, $hashed_password, $role, $fullname, $email, $phone]);
                header('Location: login.php?register=success');
                exit;
            } catch (Exception $e) {
                $error = 'System error, please try again later!';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="https://netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css">
</head>
<body>
<div class="login-container">
    <h2>Register</h2>
    <div id="register-error" class="error" style="display:none"></div>
    <?php if (!empty($error)) echo '<div class="error">'.$error.'</div>'; ?>
    <form method="post" id="register-form" autocomplete="off">
        <input type="text" name="fullname" id="fullname" placeholder="Fullname" required value="<?= isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : '' ?>">
        <input type="text" name="username" id="username" placeholder="Username" required value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
        <div class="password-field">
            <input type="password" name="password" id="password" placeholder="Password" required>
            <i class="fa fa-eye-slash toggle-password" onclick="togglePassword('password')"></i>
        </div>
        <div class="password-field">
            <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
            <i class="fa fa-eye-slash toggle-password" onclick="togglePassword('confirm_password')"></i>
        </div>
        <input type="email" name="email" id="email" placeholder="Email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
        <input type="text" name="phone" id="phone" placeholder="Phone" value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>">
        <button type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="login.php">Login</a></p>
</div>

<script>
function togglePassword(fieldId) {
    const passwordField = document.getElementById(fieldId);
    const toggleIcon = passwordField.parentElement.querySelector('.toggle-password');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    } else {
        passwordField.type = 'password';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    }
}
</script>
<script src="js/register-validate.js"></script>
</body>
</html> 