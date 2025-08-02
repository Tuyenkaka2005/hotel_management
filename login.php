<?php
require 'config.php';
session_start();
// Xây dựng action cho form để giữ lại các tham số GET khi submit
$action = 'login.php';
$params = [];
if (isset($_GET['redirect'])) $params[] = 'redirect=' . urlencode($_GET['redirect']);
if (isset($_GET['checkin'])) $params[] = 'checkin=' . urlencode($_GET['checkin']);
if (isset($_GET['checkout'])) $params[] = 'checkout=' . urlencode($_GET['checkout']);
if ($params) $action .= '?' . implode('&', $params);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $stmt = $pdo->prepare('SELECT * FROM Account WHERE Username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['Password'])) {
        $_SESSION['account_id'] = $user['AccountID'];
        $_SESSION['username'] = $user['Username'];
        $_SESSION['role'] = $user['Role'];
        // Phân quyền chuyển hướng
        if (strtolower($user['Role']) === 'admin') {
            header('Location: admin_dashboard.php');
            exit;
        } else {
            // Xử lý chuyển hướng nếu có redirect (giữ nguyên logic cũ)
            if (isset($_GET['redirect'])) {
                $redirect = $_GET['redirect'];
                $params = [];
                if (isset($_GET['checkin'])) $params[] = 'checkin=' . urlencode($_GET['checkin']);
                if (isset($_GET['checkout'])) $params[] = 'checkout=' . urlencode($_GET['checkout']);
                $query = $params ? ('?' . implode('&', $params)) : '';
                header('Location: ' . $redirect . $query);
                exit;
            } else {
                header('Location: index.php');
                exit;
            }
        }
    } else {
        $error = 'Username or password is incorrect!';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
<div class="login-container">
    <h2>Login</h2>
    <?php if (!empty($error)) echo '<div class="error">'.$error.'</div>'; ?>
    <?php if (isset($_GET['register']) && $_GET['register'] === 'success') echo '<div class="success">Register success! Please login.</div>'; ?>
    <form method="post" action="<?= htmlspecialchars($action) ?>">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
    <p>No account? <a href="register.php">Register</a></p>
</div>
</body>
</html> 