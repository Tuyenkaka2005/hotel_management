<?php
require_once '../config.php';
session_start();

// Chỉ cho phép admin truy cập
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Lấy ID tài khoản cần sửa
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: account_manage.php');
    exit;
}
$account_id = (int)$_GET['id'];

// Lấy thông tin tài khoản
$stmt = $pdo->prepare("SELECT * FROM Account WHERE AccountID=?");
$stmt->execute([$account_id]);
$acc = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$acc) {
    header('Location: account_manage.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role = $acc['Role'] === 'Admin' ? 'Admin' : trim($_POST['role']); // Không cho phép đổi role admin chính

    $stmt = $pdo->prepare("UPDATE Account SET FullName=?, Email=?, PhoneNumber=?, Role=? WHERE AccountID=?");
    $stmt->execute([$fullname, $email, $phone, $role, $account_id]);
    $message = 'Cập nhật thành công!';
    // Reload lại dữ liệu mới
    $stmt = $pdo->prepare("SELECT * FROM Account WHERE AccountID=?");
    $stmt->execute([$account_id]);
    $acc = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Edit Account</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            font-family: 'Inter', Arial, sans-serif;
        }
        .edit-container {
            background: #fff;
            padding: 48px 36px 36px 36px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(192, 57, 43, 0.10), 0 1.5px 8px rgba(118, 75, 162, 0.08);
            max-width: 480px;
            margin: 60px auto 0 auto;
            position: relative;
            border-top: 6px solid #c0392b;
            border-bottom: 2px solid #764ba2;
        }
        h2 {
            font-weight: 700;
            color: #c0392b;
            letter-spacing: 1px;
            margin-bottom: 28px;
        }
        .form-label {
            font-weight: 500;
            color: #4b3fa7;
        }
        .form-control {
            border-radius: 10px;
            border: 1.5px solid #e0e0e0;
            font-size: 1rem;
            margin-bottom: 18px;
        }
        .btn-primary {
            border-radius: 25px;
            font-weight: 600;
            padding: 10px 28px;
            font-size: 1.05rem;
            background: linear-gradient(135deg, #c0392b 0%, #764ba2 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(192, 57, 43, 0.10);
            transition: all 0.2s;
        }
        .btn-primary:hover {
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 8px 25px rgba(192, 57, 43, 0.18);
        }
        .btn-outline-secondary {
            border-radius: 25px;
            font-weight: 600;
            padding: 10px 28px;
            font-size: 1.05rem;
            border: 2px solid #764ba2;
            color: #764ba2;
            background: #fff;
            transition: all 0.2s;
        }
        .btn-outline-secondary:hover {
            background: #764ba2;
            color: #fff;
            border-color: #764ba2;
        }
        .alert-success {
            border-radius: 12px;
            font-size: 1rem;
            margin-bottom: 18px;
        }
        @media (max-width: 575px) {
            .edit-container {
                padding: 24px 8px 18px 8px;
            }
            h2 {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
<div class="edit-container">
    <h2 class="text-center">Edit Account</h2>
    <?php if ($message): ?>
        <div class="alert alert-success text-center"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($acc['Username']) ?>" readonly>
        </div>
        <div class="mb-3">
            <label class="form-label" for="fullname">Full Name</label>
            <input type="text" id="fullname" name="fullname" class="form-control" required value="<?= htmlspecialchars($acc['FullName']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control" required value="<?= htmlspecialchars($acc['Email']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label" for="phone">Phone</label>
            <input type="text" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($acc['PhoneNumber']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label" for="role">Role</label>
            <?php if ($acc['Role'] === 'Admin'): ?>
                <input type="text" class="form-control" value="Admin" readonly>
            <?php else: ?>
                <select id="role" name="role" class="form-control">
                    <option value="Customer" <?= $acc['Role']==='Customer'?'selected':'' ?>>Customer</option>
                    <option value="Admin" <?= $acc['Role']==='Admin'?'selected':'' ?>>Admin</option>
                </select>
            <?php endif; ?>
        </div>
        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary">💾 Save</button>
            <a href="account_manage.php" class="btn btn-outline-secondary">← Back</a>
        </div>
    </form>
</div>
</body>
</html> 