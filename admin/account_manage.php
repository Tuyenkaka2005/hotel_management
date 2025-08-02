<?php
require_once '../config.php';
session_start();

// Xử lý sửa tài khoản
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $account_id = $_POST['account_id'];
    $username = trim($_POST['username']);
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role = $_POST['role'];

    // Validation
    $errors = [];
    
    // Username không được thay đổi - lấy username hiện tại từ database
    $stmt = $pdo->prepare("SELECT Username FROM Account WHERE AccountID = ?");
    $stmt->execute([$account_id]);
    $current_username = $stmt->fetchColumn();
    $username = $current_username; // Giữ nguyên username cũ
    
    // Kiểm tra fullname
    if (empty($fullname)) {
        $errors[] = "Full name is required.";
    } elseif (strlen($fullname) > 100) {
        $errors[] = "Full name cannot exceed 100 characters.";
    }
    
    // Kiểm tra email
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } elseif (strlen($email) > 100) {
        $errors[] = "Email cannot exceed 100 characters.";
    } else {
        // Kiểm tra trùng email (trừ account hiện tại)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Account WHERE Email = ? AND AccountID != ?");
        $stmt->execute([$email, $account_id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Email '$email' already exists.";
        }
    }
    
    // Kiểm tra phone (optional)
    if (!empty($phone) && strlen($phone) > 20) {
        $errors[] = "Phone number cannot exceed 20 characters.";
    }
    
    // Kiểm tra role
    if (!in_array($role, ['Admin', 'Customer'])) {
        $errors[] = "Invalid role selected.";
    }

    // Nếu có lỗi, redirect với thông báo lỗi
    if (!empty($errors)) {
        $error_message = implode(" ", $errors);
        header("Location: " . $_SERVER['PHP_SELF'] . "?error=" . urlencode($error_message));
        exit;
    }

    try {
        // Update (không bao gồm password)
        $stmt = $pdo->prepare("UPDATE Account SET Username=?, FullName=?, Email=?, PhoneNumber=?, Role=? WHERE AccountID=?");
        $stmt->execute([$username, $fullname, $email, $phone, $role, $account_id]);
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=edit");
        exit;
    } catch (Exception $e) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?error=" . urlencode("Database error: " . $e->getMessage()));
        exit;
    }
}

// Xử lý xóa tài khoản (không cho phép xóa Admin)
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    try {
        // Kiểm tra account có tồn tại không
        $stmt = $pdo->prepare("SELECT Role, Username FROM Account WHERE AccountID=?");
        $stmt->execute([$delete_id]);
        $account = $stmt->fetch();
        
        if (!$account) {
            header('Location: account_manage.php?error=' . urlencode("Account not found."));
            exit;
        }
        
        if (strtolower($account['Role']) === 'admin') {
            header('Location: account_manage.php?error=' . urlencode("Cannot delete admin account."));
            exit;
        }
        
        // Kiểm tra xem account có đặt phòng hiện tại không
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Reservation WHERE AccountID = ? AND CheckOutDate > CURDATE()");
        $stmt->execute([$delete_id]);
        if ($stmt->fetchColumn() > 0) {
            header('Location: account_manage.php?error=' . urlencode("Cannot delete account. It has active reservations."));
            exit;
        }
        
        // Xóa account
        $pdo->prepare("DELETE FROM Account WHERE AccountID=?")->execute([$delete_id]);
        header('Location: account_manage.php?success=delete');
        exit;
        
    } catch (Exception $e) {
        header('Location: account_manage.php?error=' . urlencode("Error deleting account: " . $e->getMessage()));
        exit;
    }
}

// Kiểm tra đăng nhập và phân quyền Admin
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Lấy danh sách tài khoản
$stmt = $pdo->query("SELECT AccountID, Username, Role, FullName, Email, PhoneNumber FROM Account ORDER BY AccountID DESC");
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Account Management</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            font-family: 'Inter', Arial, sans-serif;
        }
        .container {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.10), 0 1.5px 8px rgba(118, 75, 162, 0.08);
            padding: 36px 28px 28px 28px;
            margin-top: 40px;
            margin-bottom: 40px;
        }
        h2 {
            font-weight: 700;
            color: #c0392b;
            letter-spacing: 1px;
        }
        .btn-warning, .btn-danger, .btn-secondary {
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 7px 18px;
            margin-right: 4px;
        }
        .btn-warning:hover, .btn-danger:hover, .btn-secondary:hover {
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 8px 25px rgba(192, 57, 43, 0.13);
        }
        .btn-secondary {
            background: #b2bec3;
            color: #fff;
            border: none;
        }
        .alert {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .alert-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: #fff;
        }
        .alert-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: #fff;
        }
        .alert i {
            margin-right: 8px;
        }
        .form-control[readonly] {
            background-color: #f8f9fa;
            color: #6c757d;
            cursor: not-allowed;
        }
        
        /* Modal Styling */
        .modal-content {
            border-radius: 18px;
            border: none;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border-radius: 18px 18px 0 0;
            border-bottom: none;
            padding: 20px 25px;
        }
        
        .modal-title {
            font-weight: 600;
            font-size: 1.2rem;
            letter-spacing: 0.5px;
        }
        
        .modal-header .close {
            color: #fff;
            opacity: 0.8;
            font-size: 1.5rem;
            text-shadow: none;
        }
        
        .modal-header .close:hover {
            opacity: 1;
            transform: scale(1.1);
        }
        
        .modal-body {
            padding: 25px;
            background: #fff;
        }
        
        .modal-footer {
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            border-radius: 0 0 18px 18px;
            padding: 20px 25px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .form-control[readonly] {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-color: #dee2e6;
        }
        
        .form-text {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .btn {
            border-radius: 25px;
            font-weight: 600;
            padding: 10px 20px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            border: none;
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.3);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
        }
        
        /* Delete Modal Specific */
        .modal-body.text-center {
            padding: 30px 25px;
        }
        
        .modal-body.text-center p {
            margin-bottom: 15px;
            font-size: 1rem;
        }
        
        .modal-body.text-center .text-danger {
            font-size: 1.1rem;
            font-weight: 700;
        }
        
        .modal-body.text-center .text-muted {
            font-size: 0.9rem;
            line-height: 1.5;
        }
        
        /* Modal Animation */
        .modal.fade .modal-dialog {
            transform: scale(0.8);
            transition: transform 0.3s ease-out;
        }
        
        .modal.show .modal-dialog {
            transform: scale(1);
        }
        
        /* Responsive Modal */
        @media (max-width: 576px) {
            .modal-dialog {
                margin: 10px;
            }
            
            .modal-body {
                padding: 20px 15px;
            }
            
            .modal-footer {
                padding: 15px 20px;
            }
            
            .btn {
                padding: 8px 16px;
                font-size: 0.9rem;
            }
        }
        .table {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(192, 57, 43, 0.07);
        }
        .table thead {
            background: linear-gradient(135deg, rgb(215, 76, 76) 0%, #764ba2 100%);
            color: #fff;
        }
        .table th, .table td {
            vertical-align: middle !important;
            text-align: center;
        }
        .table th {
            font-size: 1.05rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .table td {
            font-size: 0.98rem;
        }
        a.btn-secondary {
            margin-top: 18px;
            border-radius: 25px;
            font-weight: 600;
            padding: 8px 22px;
            font-size: 1rem;
        }
        @media (max-width: 767px) {
            .container {
                padding: 18px 4px 12px 4px;
            }
            .table th, .table td {
                font-size: 0.92rem;
            }
            h2 {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">Account Management</h2>
    
    <?php if (isset($_GET['success'])): ?>
        <?php if ($_GET['success'] === 'edit'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> Account updated successfully!
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php elseif ($_GET['success'] === 'delete'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-trash-alt"></i> Account deleted successfully!
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($_GET['error']) ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Full Name</th>
                <th>Role</th>
                <th>Email</th>
                <th>Phone</th>
                
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
    <?php foreach ($accounts as $acc): ?>
        <tr>
            <td><?= htmlspecialchars($acc['AccountID']) ?></td>
            <td><?= htmlspecialchars($acc['Username']) ?></td>
            <td><?= htmlspecialchars($acc['FullName']) ?></td>
            <td><?= htmlspecialchars($acc['Role']) ?></td>
            <td><?= htmlspecialchars($acc['Email']) ?></td>
            <td><?= htmlspecialchars($acc['PhoneNumber']) ?></td>
            
            <td>
                <button class="btn btn-warning btn-sm" onclick="editAccount(<?= $acc['AccountID'] ?>, '<?= htmlspecialchars($acc['Username'], ENT_QUOTES) ?>', '<?= htmlspecialchars($acc['FullName'], ENT_QUOTES) ?>', '<?= htmlspecialchars($acc['Email'], ENT_QUOTES) ?>', '<?= htmlspecialchars($acc['PhoneNumber'], ENT_QUOTES) ?>', '<?= htmlspecialchars($acc['Role'], ENT_QUOTES) ?>')">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <?php if (strtolower($acc['Role']) !== 'admin'): ?>
                    <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $acc['AccountID'] ?>, '<?= htmlspecialchars($acc['Username'], ENT_QUOTES) ?>')">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                <?php else: ?>
                    <button class="btn btn-secondary btn-sm" disabled>
                        <i class="fas fa-ban"></i> Cannot delete
                    </button>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>

    </table>
    <a href="../admin_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>      
</div>

<!-- Modal thêm/sửa tài khoản -->
<div class="modal fade" id="accountModal" tabindex="-1" role="dialog" aria-labelledby="accountModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="post" id="accountForm">
        <div class="modal-header">
          <h5 class="modal-title" id="accountModalLabel">
            <i class="fas fa-user-edit"></i> Edit Account
          </h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="account_id" id="account_id">
          <div class="form-group">
            <label for="username">Username</label>
            <input type="text" class="form-control" name="username" id="username" readonly>
            <small class="form-text text-muted">Username cannot be changed</small>
          </div>
          <div class="form-group">
            <label for="fullname">Full Name</label>
            <input type="text" class="form-control" name="fullname" id="fullname" required>
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" class="form-control" name="email" id="email" required>
          </div>
          <div class="form-group">
            <label for="phone">Phone</label>
            <input type="text" class="form-control" name="phone" id="phone">
          </div>
          <div class="form-group">
            <label for="role">Role</label>
            <select class="form-control" name="role" id="role" required>
              <option value="Admin">Admin</option>
              <option value="Customer">Customer</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Account</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal xác nhận xóa tài khoản -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" role="dialog" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteAccountModalLabel">
          <i class="fas fa-exclamation-triangle text-danger"></i> Confirm Delete Account
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body text-center">
        <p>Are you sure you want to delete this account?</p>
        <p class="text-danger font-weight-bold" id="accountNameToDelete"></p>
        <p class="text-muted">This action cannot be undone. All account data will be permanently deleted.</p>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <a href="#" id="confirmDeleteAccountBtn" class="btn btn-danger">Delete Account</a>
      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
// Function to edit account
function editAccount(accountId, username, fullname, email, phone, role) {
    $('#account_id').val(accountId);
    $('#username').val(username);
    $('#fullname').val(fullname);
    $('#email').val(email);
    $('#phone').val(phone);
    $('#role').val(role);
    $('#accountModal').modal('show');
}

// Function to confirm delete account
function confirmDelete(accountId, username) {
    $('#accountNameToDelete').text(username);
    $('#confirmDeleteAccountBtn').attr('href', '?delete=' + accountId);
    $('#deleteAccountModal').modal('show');
}
</script>
</body>
</html>
                  