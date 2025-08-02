<?php
session_start();
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header('Location: login.php');
    exit;
}
require_once 'config.php';

// Xử lý form thêm/sửa phòng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['room_name'])) {
    $room_id = isset($_POST['room_id']) ? $_POST['room_id'] : null;
    $room_name = $_POST['room_name'];
    $room_number = $_POST['room_number'];
    $room_type = $_POST['room_type'];
    $price = $_POST['price'];
    $max_guests = $_POST['max_guests'];
    $status = $_POST['status'];

    if ($room_id) {
        // Update
        $stmt = $pdo->prepare("UPDATE Room SET RoomName=?, RoomNumber=?, RoomTypeID=?, PricePerNight=?, MaxGuests=?, Status=? WHERE RoomID=?");
        $stmt->execute([$room_name, $room_number, $room_type, $price, $max_guests, $status, $room_id]);
    } else {
        // Insert
        $stmt = $pdo->prepare("INSERT INTO Room (RoomName, RoomNumber, RoomTypeID, PricePerNight, MaxGuests, Status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$room_name, $room_number, $room_type, $price, $max_guests, $status]);
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Lấy số liệu thống kê
$roomCount = $pdo->query('SELECT COUNT(*) FROM Room')->fetchColumn();
$accountCount = $pdo->query('SELECT COUNT(*) FROM Account')->fetchColumn();

$bookingCount = $pdo->query('SELECT COUNT(*) FROM Reservation')->fetchColumn();
$roomTypeCount = $pdo->query('SELECT COUNT(*) FROM RoomType')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="vi">
<head>  
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="css/bootstrap.min.css">

    <style>
        body {
            background: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background: #343a40;
            color: #fff;
            z-index: 1050;
            position: relative;
            padding-top: 32px;
            border-top-right-radius: 18px;
            border-bottom-right-radius: 18px;
            box-shadow: 2px 0 16px rgba(44,62,80,0.08);
        }
        .sidebar-sticky h4 {
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 36px;
            color: #fff;
            letter-spacing: 1px;
        }
        .sidebar a {
            color: #fff;
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 24px;
            text-decoration: none;
            border-radius: 10px;
            font-size: 1.08rem;
            font-weight: 500;
            margin-bottom: 10px;
            transition: background 0.18s, color 0.18s, box-shadow 0.18s;
        }
        .sidebar a.active, .sidebar a:hover {
            background: linear-gradient(90deg, #ff5858 0%, #f857a6 100%);
            color: #fff;
            box-shadow: 0 4px 18px rgba(255,88,88,0.10);
        }
        .sidebar a i {
            font-size: 1.2rem;
            opacity: 0.85;
            min-width: 22px;
            text-align: center;
        }
        .sidebar a:last-child {
            margin-bottom: 0;
        }
        @media (max-width: 991px) {
            .sidebar {
                border-radius: 0;
                padding-top: 18px;
            }
            .sidebar a {
                font-size: 1rem;
                padding: 12px 14px;
            }
        }
        .dashboard-header {
            background: #fff;
            border-bottom: 1px solid #dee2e6;
            padding: 16px 24px;
        }
        .dashboard-content {
            padding: 32px 24px;
            position: relative;
        }
        .dashboard-bg-blur {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            z-index: 0;
            background: url('images/banner1.jpg') center center/cover no-repeat;
            filter: blur(8px) brightness(0.7);
            opacity: 0.25;
            border-radius: 24px;
        }
        .dashboard-content > *:not(.dashboard-bg-blur) {
            position: relative;
            z-index: 1;
        }
        @media (max-width: 767.98px) {
            .sidebar {
                min-height: auto;
            }
        }
        .dashboard-stats-row {
            margin-top: 32px;
            margin-bottom: 32px;
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
            justify-content: flex-start;
        }
        .dashboard-stat-card {
            flex: 1 1 220px;
            min-width: 220px;
            max-width: 260px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.10), 0 1.5px 8px rgba(118, 75, 162, 0.08);
            padding: 32px 24px 24px 24px;
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .dashboard-stat-card:hover {
            transform: translateY(-6px) scale(1.03);
            box-shadow: 0 16px 40px rgba(102, 126, 234, 0.18);
        }
        .dashboard-stat-icon {
            font-size: 2.8rem;
            margin-bottom: 12px;
            opacity: 0.18;
            position: absolute;
            top: 18px;
            right: 18px;
            pointer-events: none;
        }
        .dashboard-stat-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }
        .dashboard-stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0;
            letter-spacing: 1px;
            text-shadow: 0 2px 8px rgba(60,40,120,0.10);
        }
        /* Gradient color variations */
        .dashboard-stat-card.bg-primary {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        }
        .dashboard-stat-card.bg-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        .dashboard-stat-card.bg-warning {
            background: linear-gradient(135deg, #ffb347 0%, #ffcc33 100%);
            color: #333;
        }
        .dashboard-stat-card.bg-danger {
            background: linear-gradient(135deg, #ff5858 0%, #f857a6 100%);
        }
        @media (max-width: 991px) {
            .dashboard-stats-row {
                gap: 16px;
            }
            .dashboard-stat-card {
                min-width: 180px;
                padding: 24px 12px 18px 12px;
            }
        }
        @media (max-width: 767px) {
            .dashboard-stats-row {
                flex-direction: column;
                gap: 18px;
            }
            .dashboard-stat-card {
                max-width: 100%;
                min-width: 0;
            }
        }
        .modal-content {
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(255, 88, 88, 0.13);
            border: none;
            overflow: hidden;
        }
        .modal-header {
            background: linear-gradient(135deg, #ff5858 0%, #f857a6 100%);
            color: #fff;
            border-bottom: none;
            border-top-left-radius: 18px;
            border-top-right-radius: 18px;
            padding-top: 28px;
            padding-bottom: 18px;
        }
        .modal-title {
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .modal-title i {
            font-size: 2rem;
            margin-right: 6px;
        }
        .modal-body {
            font-size: 1.08rem;
            color: #333;
            padding: 28px 18px 18px 18px;
        }
        .modal-footer {
            border-top: none;
            padding-bottom: 24px;
            justify-content: center;
            gap: 18px;
        }
        .modal-footer .btn {
            border-radius: 25px;
            font-weight: 600;
            font-size: 1.05rem;
            padding: 10px 28px;
            transition: all 0.18s;
        }
        .modal-footer .btn-danger {
            background: linear-gradient(135deg, #ff5858 0%, #f857a6 100%);
            border: none;
            color: #fff;
            box-shadow: 0 4px 15px rgba(255,88,88,0.10);
        }
        .modal-footer .btn-danger:hover {
            background: linear-gradient(135deg, #f857a6 0%, #ff5858 100%);
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 8px 25px rgba(255,88,88,0.18);
        }
        .modal-footer .btn-secondary {
            background: #b2bec3;
            color: #fff;
            border: none;
        }
        .modal-footer .btn-secondary:hover {
            background: #ff5858;
            color: #fff;
        }
        @media (max-width: 575px) {
            .modal-content {
                padding: 0 2px;
            }
            .modal-header, .modal-body, .modal-footer {
                padding-left: 8px;
                padding-right: 8px;
            }
            .modal-title {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-2 d-none d-md-block sidebar py-4">
            <div class="sidebar-sticky">
                <h4 class="text-center mb-4">Admin Panel</h4>
                <a href="admin_dashboard.php" class="active" id="dashboard-tab"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
                <a href="room_manage.php" id="room-tab"><i class="fas fa-bed"></i>Room</a> 
                <a href="admin/account_manage.php" id="account-tab"><i class="fas fa-users-cog"></i>Account</a>
                <a href="admin/customer_manage.php" id="customer-tab"><i class="fas fa-user-friends"></i>Customer</a>
                <a href="admin/booking_manage.php" id="booking-tab"><i class="fas fa-calendar-check"></i>Booking</a>
                <a href="admin/checkout_manage.php" id="checkout-tab"><i class="fas fa-door-open"></i>Checkout</a>
                <a href="admin/feedback_manage.php" id="feedback-tab"><i class="fas fa-star"></i>Feedback</a>
                <a href="admin/statistics.php" id="stat-tab"><i class="fas fa-chart-bar"></i>Statistics</a>
                <a href="javascript:void(0)" class="text-white" data-toggle="modal" data-target="#logoutModal" style="display: flex; align-items: center;"><i class="fas fa-sign-out-alt"></i>Logout</a>
            </div>
        </nav>
        <!-- Main content -->
        <main class="col-md-10 ml-sm-auto px-0">
            <div class="dashboard-header d-flex justify-content-between align-items-center">
                <h3>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h3>
                <span class="badge badge-danger" style="font-size: 1rem;">Admin</span>
            </div>
            <div class="dashboard-content">
                <div id="dashboard-section">
                    <div class="dashboard-bg-blur"></div>
                    <h4>Hotel Management System</h4>        
                    <p>Choose the management function in the left menu.</p>
                    <div class="dashboard-stats-row">
                        <div class="dashboard-stat-card bg-primary">
                            <div class="dashboard-stat-icon"><i class="fas fa-bed"></i></div>
                              <div class="dashboard-stat-title">Room</div>
                            <div class="dashboard-stat-value"><?= $roomCount ?></div>
                        </div>
                        <div class="dashboard-stat-card bg-success">
                            <div class="dashboard-stat-icon"><i class="fas fa-users"></i></div>
                            <div class="dashboard-stat-title">Account</div>
                            <div class="dashboard-stat-value"><?= $accountCount ?></div>
                        </div>
                        <div class="dashboard-stat-card bg-warning">
                            <div class="dashboard-stat-icon"><i class="fas fa-calendar-check"></i></div>
                            <div class="dashboard-stat-title">Booking</div>
                            <div class="dashboard-stat-value"><?= $bookingCount ?></div>
                        </div>
                        <div class="dashboard-stat-card bg-danger">
                            <div class="dashboard-stat-icon"><i class="fas fa-layer-group"></i></div>
                            <div class="dashboard-stat-title">Room Type</div>
                            <div class="dashboard-stat-value"><?= $roomTypeCount ?></div>
                        </div>
                    </div>
                </div>
                <!-- Room Management -->
                <div id="room-section" style="display:none;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4>Room Management</h4>
                        <button class="btn btn-success" data-toggle="modal" data-target="#roomModal" onclick="openRoomModal()">Add Room</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Room Name</th>          
                                    <th>Room Number</th>
                                    <th>Room Type</th>
                                    <th>Price/Night</th>
                                    <th>Max Guests</th>
                                    <th>Status</th>
                                    <th>Action</th> 
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                  require_once 'config.php';
                                  $stmt = $pdo->query('SELECT Room.*, RoomType.TypeName FROM Room JOIN RoomType ON Room.RoomTypeID = RoomType.RoomTypeID');
                                while ($room = $stmt->fetch()): ?>
                                <tr>
                                    <td><?= $room['RoomID'] ?></td>
                                    <td><?= htmlspecialchars($room['RoomName']) ?></td>
                                    <td><?= htmlspecialchars($room['RoomNumber']) ?></td>
                                    <td><?= htmlspecialchars($room['TypeName']) ?></td>
                                    <td><?= number_format($room['PricePerNight']) ?> VNĐ</td>
                                    <td><?= $room['MaxGuests'] ?? 'N/A' ?></td>
                                    <td>
                                        <select class="form-control form-control-sm status-select" 
                                                data-room-id="<?= $room['RoomID'] ?>" 
                                                onchange="updateRoomStatus(<?= $room['RoomID'] ?>, this.value)">
                                            <option value="Available" <?= $room['Status'] === 'Available' ? 'selected' : '' ?>>Available</option>
                                            <option value="Reserved" <?= $room['Status'] === 'Reserved' ? 'selected' : '' ?>>Reserved</option>
                                            <option value="Occupied" <?= $room['Status'] === 'Occupied' ? 'selected' : '' ?>>Occupied</option>
                                            <option value="Maintenance" <?= $room['Status'] === 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                        </select>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" onclick="editRoom(<?= $room['RoomID'] ?>)">Edit</button>
                                        <button class="btn btn-danger btn-sm" onclick="deleteRoom(<?= $room['RoomID'] ?>)">Delete</button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Quản lý tài khoản -->
                
                <!-- Modal thêm/sửa phòng -->
                <div class="modal fade" id="roomModal" tabindex="-1" role="dialog" aria-labelledby="roomModalLabel" aria-hidden="true">
                  <div class="modal-dialog" role="document">
                    <div class="modal-content">
                      <form method="post" id="roomForm">
                        <div class="modal-header">
                          <h5 class="modal-title" id="roomModalLabel">Add/Edit Room</h5>
                          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                          </button>
                        </div>
                        <div class="modal-body">
                          <input type="hidden" name="room_id" id="room_id">
                          <div class="form-group">
                            <label for="room_name">Room Name</label>
                            <input type="text" class="form-control" name="room_name" id="room_name" required>
                          </div>
                          <div class="form-group">
                            <label for="room_number">Room Number</label>
                            <input type="text" class="form-control" name="room_number" id="room_number" required>
                          </div>
                          <div class="form-group">
                            <label for="room_type">Room Type</label>
                            <select class="form-control" name="room_type" id="room_type" required>
                              <?php
                              $stmt = $pdo->query('SELECT * FROM RoomType');
                              while ($type = $stmt->fetch()): ?>
                                <option value="<?= $type['RoomTypeID'] ?>"><?= htmlspecialchars($type['TypeName']) ?></option>
                              <?php endwhile; ?>
                            </select>
                          </div>
                          <div class="form-group">
                            <label for="price">Price/Night</label>
                            <input type="number" class="form-control" name="price" id="price" required>
                          </div>
                          <div class="form-group">
                            <label for="max_guests">Max Guests</label>
                            <input type="number" class="form-control" name="max_guests" id="max_guests" min="1" max="10" required>
                          </div>
                          <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control" name="status" id="status" required>
                              <option value="Available">Available</option>
                              <option value="Reserved">Reserved</option>
                              <option value="Occupied">Occupied</option>
                              <option value="Maintenance">Maintenance</option>
                            </select>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                          <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
                <!-- Modal thêm/sửa tài khoản -->
                <div class="modal fade" id="userModal" tabindex="-1" role="dialog" aria-labelledby="userModalLabel" aria-hidden="true">
                  <div class="modal-dialog" role="document">
                    <div class="modal-content">
                      <form method="post" id="userForm">
                        <div class="modal-header">
                          <h5 class="modal-title" id="userModalLabel">Add/Edit Account</h5>
                          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                          </button>
                        </div>
                        <div class="modal-body">
                          <input type="hidden" name="account_id" id="account_id">
                          <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" class="form-control" name="username" id="username" required>
                          </div>
                          <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" class="form-control" name="password" id="password">
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
                          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                          <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>

            </div>
        </main>
    </div>
</div>

<!-- Modal xác nhận đăng xuất -->
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="logoutModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="logoutModalLabel">
          <i class="fas fa-sign-out-alt text-warning"></i> Confirm Logout
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body text-center">
        <p>Are you sure you want to logout?</p>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <a href="logout.php" class="btn btn-danger">Logout</a>
      </div>
    </div>
  </div>
</div>
<!-- Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<!-- Đảm bảo nút đăng xuất có thuộc tính đúng cho Bootstrap 4 -->
<!--
<button type="button" class="btn btn-link text-white p-0 border-0 bg-transparent w-100 text-left"
        data-toggle="modal" data-target="#logoutModal">Đăng xuất</button>
-->

<script>
// Modal open/clear logic
function openRoomModal() {
  $('#roomForm')[0].reset();
  $('#room_id').val('');
  $('#roomModalLabel').text('Add Room');
}
function editRoom(id) {
  // TODO: AJAX lấy dữ liệu phòng và điền vào form, sau đó show modal
  // Tạm thời sử dụng giá trị mặc định
  $('#room_id').val(id);
  $('#room_name').val('Room ' + id);
  $('#room_number').val('10' + id);
  $('#room_type').val('1');
  $('#price').val('1000000');
  $('#max_guests').val('2');
  $('#status').val('Available');
  $('#roomModalLabel').text('Edit Room');
  $('#roomModal').modal('show');
}
function deleteRoom(id) {
  if (confirm('Are you sure you want to delete this room?')) {
    // TODO: Gửi request xóa phòng
  }
}
function openUserModal() {
  $('#userForm')[0].reset();
  $('#account_id').val('');
  $('#userModalLabel').text('Add Account');
}
function editUser(id) {
  // TODO: AJAX lấy dữ liệu user và điền vào form, sau đó show modal
}
function deleteUser(id) {
      if (confirm('Are you sure you want to delete this account?')) {
    // TODO: Send request to delete user
  }
}

// Function to update room status via AJAX
function updateRoomStatus(roomId, status) {
    $.ajax({
        url: 'update_room_status.php',
        type: 'POST',
        data: {
            room_id: roomId,
            status: status
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Show success message
                alert('Room status updated successfully!');
                // Optionally refresh the page to show updated status
                location.reload();
            } else {
                alert('Error: ' + response.error);
            }
        },
        error: function(xhr, status, error) {
            alert('Error updating room status: ' + error);
        }
    });
}
</script>
</body>
</html>
