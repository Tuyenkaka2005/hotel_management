<?php
session_start();
if (!isset($_SESSION['account_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "password", "hotel_management");
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

$account_id = $_SESSION['account_id'];
$message = '';

// Xử lý khi form được submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = $conn->real_escape_string($_POST['fullname']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $address = $conn->real_escape_string($_POST['address']);

    $sqlUpdate = "UPDATE Account SET FullName='$fullname', Email='$email', PhoneNumber='$phone', Address='$address' WHERE AccountID=$account_id";
    if ($conn->query($sqlUpdate) === TRUE) {
        $message = "Cập nhật thông tin thành công!";
    } else {
        $message = "Lỗi cập nhật: " . $conn->error;
    }
}

// Lấy thông tin tài khoản hiện tại
$sql = "SELECT Username, FullName, Email, PhoneNumber, Address FROM Account WHERE AccountID=$account_id LIMIT 1";
$result = $conn->query($sql);
if ($result->num_rows === 0) {
    die("Không tìm thấy tài khoản.");
}
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>

    <!-- ✅ Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg,rgb(215, 76, 76) 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', Arial, sans-serif;
            padding-top: 20px;
        }
        
        .profile-container {
            background: #fff;
            padding: 48px 36px 36px 36px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.10), 0 1.5px 8px rgba(118, 75, 162, 0.08);
            max-width: 480px;
            margin: 60px auto 0 auto;
            position: relative;
            border-top: 6px solidrgb(215, 76, 76);
            border-bottom: 2px solid #764ba2;
        }
        
        h2 {
            font-weight: 800;
            margin-bottom: 28px;
            color:rgb(215, 76, 76);
            letter-spacing: 1px;
            text-align: center;
            font-size: 2.1rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #444;
            margin-bottom: 6px;
        }
        
        .form-control {
            border-radius: 12px;
            border: 2px solid #e1e5e9;
            padding: 12px 16px;
            font-size: 1rem;
            background: #fafbfc;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.10);
            background: #fff;
        }
        
        textarea.form-control {
            min-height: 70px;
            resize: vertical;
        }
        
        .btn-primary {
            background: linear-gradient(135deg,rgb(215, 76, 76) 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.1rem;
            padding: 12px 0;
            transition: background 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.10);
        }
        
        .btn-primary:hover, .btn-primary:focus {
            background: linear-gradient(135deg, #764ba2 0%,rgb(215, 76, 76) 100%);
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.18);
        }
        
        .btn-outline-secondary {
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            padding: 12px 0;
            border: 2px solidrgb(215, 76, 76);
            color:rgb(215, 76, 76);
            background: #fff;
            transition: background 0.2s, color 0.2s, border-color 0.2s;
        }
        
        .btn-outline-secondary:hover, .btn-outline-secondary:focus {
                background:rgb(215, 76, 76);
            color: #fff;
            border-color:rgb(215, 76, 76);
        }
        
        .alert-info {
            border-radius: 10px;
            font-size: 1rem;
            background: linear-gradient(90deg,rgb(215, 76, 76) 0%, #f8fafc 100%);
            color: #333;
            border: none;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.07);
        }
        
        @media (max-width: 600px) {
            .profile-container {
                padding: 24px 8px 24px 8px;
                max-width: 98vw;
            }
            h2 {
                font-size: 1.3rem;
            }
        }
    </style>
</head>

<body>
<div class="profile-container">
    <h2 class="text-center">Profile</h2>

    <?php if ($message): ?>
        <div class="alert alert-info text-center"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($user['Username']) ?>" readonly>
        </div>

        <div class="mb-3">
            <label class="form-label" for="fullname">Fullname</label>
            <input type="text" id="fullname" name="fullname" class="form-control" required value="<?= htmlspecialchars($user['FullName']) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control" required value="<?= htmlspecialchars($user['Email']) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label" for="phone">Phone</label>
            <input type="text" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($user['PhoneNumber']) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label" for="address">Address</label>
            <textarea id="address" name="address" class="form-control"><?= htmlspecialchars($user['Address']) ?></textarea>
        </div>

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary">💾 Update</button>
            <a href="room.php" class="btn btn-outline-secondary">🔙 Back to Room</a>
        </div>
    </form>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
