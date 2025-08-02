<?php
require 'config.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajax'])) {
    $account_id = $_SESSION['account_id'] ?? null;
    if (!$account_id) {
        echo json_encode(['success' => false, 'message' => 'You need to login to book a room.']);
        exit;
    }
    $room_id = $_POST['room_id'];
    $check_in = $_POST['checkin'];
    $check_out = $_POST['checkout'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $promotion_code = $_POST['promotion_code'] ?? '';
    $payment_method = $_POST['payment_method'];
    
    // Kiểm tra trạng thái phòng trước khi đặt
    $sql_check = "SELECT PricePerNight, Status FROM Room WHERE RoomID = ?";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$room_id]);
    $room = $stmt_check->fetch();
    
    if (!$room) {
        echo json_encode(['success' => false, 'message' => 'Room does not exist.']);
        exit;
    }
    
    if ($room['Status'] !== 'Available') {
        echo json_encode(['success' => false, 'message' => 'Room is not available. Current status: ' . $room['Status']]);
        exit;
    }
    
    $room_price = $room['PricePerNight'];
    // Tính tổng tiền
    $days = (strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24);
    if ($days <= 0) $days = 1;
    $total_amount = $room_price * $days;
    
    try {
        // Bắt đầu transaction
        $pdo->beginTransaction();
        
        // 1. Thêm bản ghi đặt phòng
    $sql_reservation = "INSERT INTO Reservation (CheckInDate, CheckOutDate, TotalAmount, AccountID, RoomID)
                    VALUES (?, ?, ?, ?, ?)";
$stmt = $pdo->prepare($sql_reservation);
$stmt->execute([$check_in, $check_out, $total_amount, $account_id, $room_id]);
    $reservation_id = $pdo->lastInsertId();
    
    // 2. Ghi thanh toán (chưa thanh toán ngay)
    $status = 'Pending';
$sql_payment = "INSERT INTO Payment (PaymentMethod, PaymentStatus, ReservationID)
                VALUES (?, ?, ?)";
$stmt2 = $pdo->prepare($sql_payment);
$stmt2->execute([$payment_method, $status, $reservation_id]);
    
    // 3. Cập nhật trạng thái phòng thành 'Reserved'
    $sql_update_room = "UPDATE Room SET Status = 'Reserved' WHERE RoomID = ?";
    $stmt3 = $pdo->prepare($sql_update_room);
    $stmt3->execute([$room_id]);
    
    // Commit transaction
    $pdo->commit();
    
    // Trả về JSON thành công
    echo json_encode(['success' => true]);
    exit;
    
    } catch (Exception $e) {
        // Rollback transaction nếu có lỗi
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
    }

// Lấy thông tin phòng để hiển thị
$room_id = $_GET['room_id'] ?? 0;
$room_info = null;
if ($room_id) {
    $sql = "SELECT r.*, rt.TypeName FROM Room r 
            JOIN RoomType rt ON r.RoomTypeID = rt.RoomTypeID 
            WHERE r.RoomID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$room_id]);
    $room_info = $stmt->fetch();
}
?>
<!DOCTYPE html>
    <html lang="vi">
   <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Booking Room</title>
      <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding-top: 80px;
        }

        /* Header Custom Styles */
        .header {
            background: white;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            border-bottom: 3px solid;
            border-image: linear-gradient(90deg,rgb(215, 76, 76) 0%, #764ba2 100%) 1;
        }

        .logo-text {
            display: flex;
            align-items: center;    
            font-weight: 700;
            font-size: 1.8rem;
            color: #333;
            text-decoration: none;
        }

        .logo-dots {
            color: #ff6b35;
            font-size: 1.2rem;
            margin-right: 8px;
            opacity: 0.8;
        }

        .logo-brand {
            color: #ff6b35;
            font-weight: 800;
            letter-spacing: 1px;
        }

        .navigation .navbar-nav .nav-link {
            color: #666 !important;
            font-weight: 500;
            padding: 8px 16px !important;
            margin: 0 4px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .navigation .navbar-nav .nav-link:hover,
        .navigation .navbar-nav .nav-link.active {
            color:rgb(215, 76, 76) !important;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            transform: translateY(-1px);
        }

        .search-btn {
            border: 2px solidrgb(215, 76, 76);
            color:rgb(215, 76, 76);
            font-weight: 600;
            padding: 8px 20px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            background: linear-gradient(135deg,rgb(215, 76, 76) 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .profile-btn {
            background: white;
            border: 2px solid #e1e5e9;
            border-radius: 25px;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .profile-btn:hover {
            border-color:rgb(215, 76, 76);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }

        .profile-avatar {
            font-size: 1.2rem;
            color:rgb(215, 76, 76);
        }

        .auth-buttons .btn {
            border-radius: 25px;
            font-weight: 600;
            padding: 8px 20px;
            margin-left: 10px;
            transition: all 0.3s ease;
        }

        .auth-buttons .btn-outline-primary {
            border: 2px solidrgb(215, 76, 76);
            color:rgb(215, 76, 76);
        }

        .auth-buttons .btn-outline-primary:hover {
            background: linear-gradient(135deg,rgb(215, 76, 76) 0%, #764ba2 100%);
            border-color: transparent;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .auth-buttons .btn-primary {
            background: linear-gradient(135deg,rgb(215, 76, 76) 0%, #764ba2 100%);
            border: none;
        }

        .auth-buttons .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        /* Profile Dropdown Styles */
        .profile-dropdown {
            position: relative;
            display: inline-block;
        }

        .profile-btn {
            background: none;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 16px;
            color: #333;
            padding: 8px 12px;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .profile-btn:hover {
            background: rgba(102, 126, 234, 0.1);
        }

        .profile-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 1.5px solid #eee;
        }

        .profile-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 120%;
            background: #fff;
            min-width: 200px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
            border-radius: 8px;
            z-index: 1000;
            border: 1px solid #e1e5e9;
        }

        .profile-dropdown.open .profile-menu {
            display: block;
        }

        .profile-menu .dropdown-item {
            display: block;
            width: 100%;
            padding: 10px 18px;
            color: #333;
            text-decoration: none;
            background: none;
            border: none;
            text-align: left;
            font-size: 15px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .profile-menu .dropdown-item:hover {
            background: #f4f6fb;
            color: #667eea;
        }

        .profile-menu .dropdown-divider {
            height: 1px;
            background: #e1e5e9;
            margin: 8px 0;
        }

        .search-section .btn {
            border-radius: 20px;
            padding: 8px 12px;
            transition: all 0.3s ease;
        }

        .search-section .btn:hover {
            background:rgb(234, 102, 102);
            border-color:rgb(234, 102, 102);
            color: white;
        }

        /* Modal Success Styling */
        #bookingSuccessModal .modal-content {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        #bookingSuccessModal .modal-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-bottom: none;
            padding: 25px 30px 20px;
        }

        #bookingSuccessModal .modal-title {
            font-size: 1.4rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        #bookingSuccessModal .modal-title i {
            font-size: 1.8rem;
            color: #fff;
            animation: bounce 0.6s ease-in-out;
        }

        #bookingSuccessModal .close {
            color: white;
            opacity: 0.8;
            font-size: 1.5rem;
            transition: opacity 0.3s ease;
        }

        #bookingSuccessModal .close:hover {
            opacity: 1;
            transform: scale(1.1);
        }

        #bookingSuccessModal .modal-body {
            padding: 30px;
            text-align: center;
            background: #f8f9fa;
        }

        #bookingSuccessModal .modal-body p {
            font-size: 1.1rem;
            color: #495057;
            line-height: 1.6;
            margin: 0;
        }

        #bookingSuccessModal .modal-footer {
            border-top: none;
            padding: 20px 30px 30px;
            background: white;
        }

        #bookingSuccessModal .btn {
            border-radius: 12px;
            font-weight: 600;
            padding: 12px 24px;
            font-size: 1rem;
            transition: all 0.3s ease;
            min-width: 140px;
        }

        #bookingSuccessModal .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
        }

        #bookingSuccessModal .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        #bookingSuccessModal .btn-outline-secondary {
            border: 2px solid #6c757d;
            color: #6c757d;
            background: white;
        }

        #bookingSuccessModal .btn-outline-secondary:hover {
            background: #6c757d;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.3);
        }

        /* Modal Animation */
        #bookingSuccessModal.fade .modal-dialog {
            transform: scale(0.7);
            transition: transform 0.3s ease-out;
        }

        #bookingSuccessModal.show .modal-dialog {
            transform: scale(1);
        }

        /* Bounce Animation for Success Icon */
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }

        /* Modal Backdrop */
        .modal-backdrop.show {
            opacity: 0.7;
        }

        /* Success Message Highlight */
        #bookingSuccessModal .modal-body p strong {
            color: #28a745;
            font-weight: 700;
        }

        .booking-hero {
            background: linear-gradient(135deg, rgba(217, 112, 112, 0.9) 0%, rgba(118, 75, 162, 0.9) 100%);
            padding: 60px 0;
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }

        .booking-hero h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .booking-hero p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .booking-container {
            max-width: 800px;
            margin: 0 auto 40px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .booking-header {
            background: linear-gradient(135deg,rgb(215, 76, 76) 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .booking-header h2 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .booking-header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .booking-form {
            padding: 40px;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            align-items: center;
        }

        .section-title i {
            margin-right: 10px;
            color:rgb(234, 102, 102);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
            display: block;
        }

        .form-control {
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #fafbfc;
        }

        .form-control:focus {
            border-color:rgb(234, 102, 102);  
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: white;
            outline: none;
        }

        .form-control.error {
            border-color: #e74c3c;
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
        }

        .error-message {
            color: #e74c3c;
            background: #fdf2f2;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #e74c3c;
            display: none;
        }

        .room-info {
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f2ff 100%);
            border: 2px solid #e8ecff;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .room-info h3 {
            color:rgb(234, 102, 102);
            font-weight: 700;
            margin-bottom: 15px;
        }

        .room-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .room-detail-item {
            display: flex;
            align-items: center;
            padding: 10px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .room-detail-item i {
            color:rgb(234, 102, 102);
            margin-right: 10px;
            font-size: 1.1rem;
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .payment-option {
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }

        .payment-option:hover {
            border-color:rgb(234, 102, 102);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }

        .payment-option.selected {
            border-color:rgb(234, 102, 102);
            background: linear-gradient(135deg,rgb(215, 76, 76) 0%, #764ba2 100%);
            color: white;
        }

        .payment-option i {
            font-size: 2rem;
            margin-bottom: 8px;
            display: block;
        }

        .qr-section {
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f2ff 100%);
            border: 2px solid #e8ecff;
            border-radius: 15px;
            padding: 25px;
            margin-top: 20px;
            text-align: center;
            display: none;
        }

        .qr-section img {
            max-width: 250px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin: 15px 0;
        }

        .submit-btn {
            background: linear-gradient(135deg,rgb(215, 76, 76) 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 20px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .back-btn {
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 30px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }

        .back-btn:hover {
            background: #5a6268;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
        }

        @media (max-width: 768px) {
            .booking-hero h1 {
                font-size: 2rem;
            }
            
            .booking-form {
                padding: 25px;
            }
            
            .room-details {
                grid-template-columns: 1fr;
            }
            
            .payment-methods {
                grid-template-columns: 1fr;
            }
        }
    </style>
   </head>
<body>
      <!-- header -->
      <header>
         <div class="header">
            <div class="logo_section">
                           <div class="logo">
                    <a href="index.php"><img src="images/logo.png" alt="Logo"></a>
                           </div>
                        </div>
            <nav class="navigation navbar navbar-expand-lg navbar-light">
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                        </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav mx-auto">
                        <li class="nav-item">
                                 <a class="nav-link" href="index.php">Home</a>
                              </li>
                              <li class="nav-item">
                            <a class="nav-link" href="about.php">About</a>
                              </li>
                              <li class="nav-item active">
                            <a class="nav-link" href="room.php">Our Room</a>
                              </li>
                              <li class="nav-item">
                            <a class="nav-link" href="gallery.php">Gallery</a>
                              </li>
                              <li class="nav-item">
                            <a class="nav-link" href="blog.php">Blog</a>
                              </li>
                              <li class="nav-item">
                            <a class="nav-link" href="contact.php">Contact Us</a>
                              </li>
                           </ul>
                        </div>
                     </nav>
            <div class="header-actions d-flex align-items-center">
                <div class="search-section mr-3">
                    <button class="btn btn-outline-secondary btn-sm" type="button" data-toggle="modal" data-target="#searchModal">
                        <i class="fa fa-search"></i>
                    </button>
                </div>
                <?php if (isset($_SESSION['account_id'])): ?>
                    <div class="profile-dropdown">
                        <button class="profile-btn">
                            <img src="images/profile.png" class="profile-avatar" alt="avatar">
                            <span class="d-none d-md-inline"><?= htmlspecialchars($_SESSION['username']) ?></span>
                            <i class="fa fa-caret-down"></i>
                        </button>
                        <div class="profile-menu">
                            <a class="dropdown-item" href="profile_customer.php">
                                <i class="fa fa-user mr-2"></i>Personal Information
                            </a>
                            <a class="dropdown-item" href="my_bookings.php">
                                <i class="fa fa-calendar mr-2"></i>My Bookings
                            </a>
                            <a class="dropdown-item" href="booked_rooms.php">
                                <i class="fa fa-history mr-2"></i>Booking History
                            </a>
                            <?php if ($_SESSION['role'] === 'Admin'): ?>
                                <a class="dropdown-item" href="admin_dashboard.php">
                                    <i class="fa fa-cog mr-2"></i>Admin 
                                </a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <form method="post" action="logout.php" style="margin:0;">
                                <button type="submit" class="dropdown-item">
                                    <i class="fa fa-sign-out mr-2"></i>Logout
                                </button>
                            </form>
                  </div>
               </div>
                <?php else: ?>
                    <div class="auth-buttons">
                        <a href="login.php" class="btn btn-outline-primary btn-sm mr-2">
                            <i class="fa fa-sign-in mr-1"></i>Login
                        </a>
                        <a href="register.php" class="btn btn-primary btn-sm">
                            <i class="fa fa-user-plus mr-1"></i>Register
                        </a>
                    </div>
                <?php endif; ?>
            </div>
         </div>
      </header>

    <!-- Hero Section -->
    <div class="booking-hero">
         <div class="container">
            <h1><i class="fas fa-calendar-check"></i> Booking Room</h1>
            <p>Complete the information to book your room</p>
                  </div>
               </div>

    <!-- Main Content -->
    <div class="container">
        <a href="room_detail.php?room_id=<?= htmlspecialchars($_GET['room_id']) ?>" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Room Detail
        </a>

        <div class="booking-container">
            <div class="booking-header">
                <h2><i class="fas fa-hotel"></i> Booking Information</h2>
                <p>Please fill in the information below</p>
            </div>

            <div class="booking-form">
                <!-- Room Information -->
                <?php if ($room_info): ?>
                <div class="room-info">
                    <h3><i class="fas fa-info-circle"></i> Room Information</h3>
                    <div class="room-details">
                        <div class="room-detail-item">
                            <i class="fas fa-bed"></i>
                            <span><strong>Room Type:</strong> <?= htmlspecialchars($room_info['TypeName']) ?></span>
                        </div>
                        <div class="room-detail-item">
                            <i class="fas fa-users"></i>
                            <span><strong>Capacity:</strong> <?= htmlspecialchars($room_info['MaxGuests']) ?> guests</span>
                        </div>
                        <div class="room-detail-item">
                            <i class="fas fa-money-bill-wave"></i>
                            <span><strong>Price:</strong> <?= number_format($room_info['PricePerNight'], 0, ',', '.') ?> VNĐ/night</span>
            </div>
         </div>
      </div>
                <?php endif; ?>

                <form method="post" id="booking-form">
    <input type="hidden" name="room_id" value="<?= htmlspecialchars($_GET['room_id']) ?>">

                    <!-- Error Message -->
                    <div id="booking-error" class="error-message"></div>

                    <!-- Dates Section -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-calendar-alt"></i>
                            Stay Time
                        </div>
                        <div class="row">
                            <div class="col-md-6">
    <div class="form-group">
                                    <label class="form-label" for="checkin">
                                        <i class="fas fa-sign-in-alt"></i> Check-in Date
                                    </label>
        <input type="date" name="checkin" id="checkin" class="form-control" required>
    </div>
                            </div>
                            <div class="col-md-6">
    <div class="form-group">
                                    <label class="form-label" for="checkout">
                                            <i class="fas fa-sign-out-alt"></i> Check-out Date
                                    </label>
        <input type="date" name="checkout" id="checkout" class="form-control" required>
    </div>
                            </div>
                        </div>
    </div>

                    <!-- Personal Information -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-user"></i>
                            Personal Information
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="full_name">
                                        <i class="fas fa-user"></i> Fullname
                                    </label>
                                    <input type="text" name="full_name" id="full_name" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="email">
                                        <i class="fas fa-envelope"></i> Email
                                    </label>
                                    <input type="email" name="email" id="email" class="form-control" required>
                                </div>
                            </div>
                        </div>
    <div class="form-group">
                            <label class="form-label" for="phone">
                                <i class="fas fa-phone"></i> Phone
                            </label>
                            <input type="tel" name="phone" id="phone" class="form-control" required>
                        </div>
    </div>

                    <!-- Promotion Code -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-gift"></i>
                            Promotion Code (optional)
                        </div>
    <div class="form-group">
                            <label class="form-label" for="promotion_code">
                                <i class="fas fa-tag"></i> Promotion Code
                            </label>
                            <input type="text" name="promotion_code" id="promotion_code" class="form-control" placeholder="Enter promotion code if you have">
                        </div>
    </div>

                    <!-- Payment Method -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-credit-card"></i>
                            Payment Method
                        </div>
                        <div class="payment-methods">
                            <div class="payment-option" data-value="Credit Card">
                                <i class="fas fa-credit-card"></i>
                                <div>Credit Card</div>
                            </div>
                            <div class="payment-option" data-value="Cash">
                                <i class="fas fa-money-bill-wave"></i>
                                <div>Cash</div>
                            </div>
                            <div class="payment-option" data-value="Bank Transfer">
                                <i class="fas fa-university"></i>
                                <div>Bank Transfer</div>
                            </div>
                        </div>
                        <input type="hidden" name="payment_method" id="payment_method" required>
    </div>

                    <!-- Credit Card Form -->
                    <div id="credit-card-form" style="display:none; margin-top: 20px;">
                        <div class="form-group">
                            <label for="cc-name">Card Holder Name</label>
                            <input type="text" class="form-control" id="cc-name" name="cc_name" placeholder="VD: NGUYEN VAN A">
                        </div>
    <div class="form-group">
                            <label for="cc-number">Card Number</label>
                            <input type="text" class="form-control" id="cc-number" name="cc_number" maxlength="19" placeholder="1234 5678 9012 3456">
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                        <label for="cc-expiry">Expiry Date</label>
                                <input type="text" class="form-control" id="cc-expiry" name="cc_expiry" maxlength="5" placeholder="MM/YY">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="cc-cvc">CVV</label>
                                <input type="password" class="form-control" id="cc-cvc" name="cc_cvc" maxlength="4" placeholder="123">
                            </div>
                        </div>
                        <div class="alert alert-info mt-2" style="font-size: 0.95rem;">
                            <i class="fas fa-lock mr-1"></i> Your card information will be kept secure and not stored on the system.<br>
                            <span style="color:#e74c3c;">* Demo function, not connected to real payment gateway.</span>
    </div>
</div>

                    <!-- QR Section for Bank Transfer -->
                    <div id="qr-section" class="qr-section">
                        <h4><i class="fas fa-qrcode"></i> Bank Transfer Information</h4>
                        <p><strong>Please scan the QR code or transfer to:</strong></p>
                        <img src="images/qr_bank.jpg" alt="QR Code ngân hàng">
                        <div class="payment-details">
                            <p><strong>Amount:</strong> <span id="payment-amount">calculating...</span></p>
                            <p><strong>Content:</strong> Booking - <?= htmlspecialchars($room_info['TypeName'] ?? 'Room') ?></p>
                            <p><strong>Account Number:</strong> <span id="bank-account">123456789</span> <button type="button" class="btn btn-link btn-sm" onclick="copyBankAccount()">Copy</button></p>
                            <p><strong>Bank Name:</strong> BTEC Bank</p>
                        </div>
                        <button type="button" class="btn btn-success mt-2" id="confirm-transfer-btn">I have transferred</button>
                        <div id="transfer-confirm-msg" class="alert alert-success mt-3" style="display:none;">Thank you! We will check and confirm within 30 minutes.</div>
    </div>

                    <button type="submit" class="submit-btn">
                        <i class="fas fa-check-circle"></i> Confirm Booking
                    </button>
</form>

                <!-- Modal thông báo thành công -->
                <div class="modal fade" id="bookingSuccessModal" tabindex="-1" role="dialog" aria-labelledby="bookingSuccessLabel" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="bookingSuccessLabel"><i class="fas fa-check-circle text-success"></i> Booking Success!</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>
                      <div class="modal-body text-center">
                        <p>You have successfully booked a room.<br>Please pay at the reception when you arrive.</p>
</div>
                      <div class="modal-footer justify-content-center">
                        <a href="index.php" class="btn btn-primary">Back to Home</a>
                        <a href="booked_rooms.php" class="btn btn-outline-secondary">View booked rooms</a>
                  </div>
                  </div>
                  </div>
               </div>
            </div>
        </div>
    </div>

    <!-- Search Modal -->
    <div class="modal fade" id="searchModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-search"></i> Search room</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="room.php" method="GET">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Check-in Date</label>
                                    <input type="date" name="checkin" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Check-out Date</label>
                                    <input type="date" name="checkout" class="form-control" required>
                                </div>
                            </div>
                        </div>
                  <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Guests</label>
                                    <select name="guests" class="form-control">
                                        <option value="">Choose guests</option>
                                        <option value="1">1 guest</option>
                                        <option value="2">2 guest</option>
                                        <option value="3">3 guest</option>
                                        <option value="4">4 guest</option>
                                        <option value="5">5 guest</option>
                                        <option value="6">6 guest</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Room Type</label>
                                    <select name="room_type" class="form-control">
                                        <option value="">All room types</option>
                                        <option value="Standard">Standard</option>
                                        <option value="Deluxe">Deluxe</option>
                                        <option value="Suite">Suite</option>
                                    </select>
                     </div>
                  </div>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-search"></i> Search Room
                            </button>
                        </div>
                    </form>
               </div>
            </div>
         </div>
    </div>

    <!-- Scripts -->
      <script src="js/jquery.min.js"></script>
      <script src="js/bootstrap.bundle.min.js"></script>
      <script>
        // Set minimum dates
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('checkin').min = today;
        document.getElementById('checkout').min = today;

        // Update checkout min date when checkin changes
        document.getElementById('checkin').addEventListener('change', function() {
            document.getElementById('checkout').min = this.value;
        });

        // Payment method selection
        document.querySelectorAll('.payment-option').forEach(option => {
            option.addEventListener('click', function() {
                // Remove selected class from all options
                document.querySelectorAll('.payment-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                
                // Add selected class to clicked option
                this.classList.add('selected');
                
                // Update hidden input
                document.getElementById('payment_method').value = this.dataset.value;
                
                // Show/hide QR section
                const qrSection = document.getElementById('qr-section');
                if (this.dataset.value === 'Bank Transfer') {
                    qrSection.style.display = 'block';
                    // Calculate and display amount
                    const checkin = document.getElementById('checkin').value;
                    const checkout = document.getElementById('checkout').value;
                    if (checkin && checkout) {
                        const days = Math.ceil((new Date(checkout) - new Date(checkin)) / (1000 * 60 * 60 * 24));
                        const price = <?= $room_info['PricePerNight'] ?? 0 ?>;
                        const total = days * price;
                        document.getElementById('payment-amount').textContent = total.toLocaleString('vi-VN') + ' VNĐ';
                    }
                } else {
                    qrSection.style.display = 'none';
                }
            });
        });

        // Form validation
const form = document.getElementById('booking-form');
const errorDiv = document.getElementById('booking-error');
        
form.addEventListener('submit', function(e) {
    errorDiv.style.display = 'none';
    errorDiv.textContent = '';
            
            // Remove error styling
            document.querySelectorAll('.form-control').forEach(input => {
                input.classList.remove('error');
            });
            
            // Validate dates
            const checkin = document.getElementById('checkin').value;
            const checkout = document.getElementById('checkout').value;
            
            if (checkin && checkout) {
                const inDate = new Date(checkin);
                const outDate = new Date(checkout);
                
        if (inDate >= outDate) {
            e.preventDefault();
            errorDiv.textContent = 'Check-out date must be after check-in date!';
            errorDiv.style.display = 'block';
                    document.getElementById('checkin').classList.add('error');
                    document.getElementById('checkout').classList.add('error');
                }
            }
            
            // Validate payment method
            const paymentMethod = document.getElementById('payment_method').value;
            if (!paymentMethod) {
                e.preventDefault();
                        errorDiv.textContent = 'Please select a payment method!';
                errorDiv.style.display = 'block';
            }
        });

        // Update payment amount when dates change
        function updatePaymentAmount() {
            const checkin = document.getElementById('checkin').value;
            const checkout = document.getElementById('checkout').value;
            const paymentMethod = document.getElementById('payment_method').value;
            
            if (checkin && checkout && paymentMethod === 'Bank Transfer') {
                const days = Math.ceil((new Date(checkout) - new Date(checkin)) / (1000 * 60 * 60 * 24));
                const price = <?= $room_info['PricePerNight'] ?? 0 ?>;
                const total = days * price;
                document.getElementById('payment-amount').textContent = total.toLocaleString('vi-VN') + ' VNĐ';
            }
        }

        document.getElementById('checkin').addEventListener('change', updatePaymentAmount);
        document.getElementById('checkout').addEventListener('change', updatePaymentAmount);

        // Profile dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            var profileBtn = document.querySelector('.profile-btn');
            if (profileBtn) {
                profileBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var dropdown = this.closest('.profile-dropdown');
                    dropdown.classList.toggle('open');
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    var dropdown = document.querySelector('.profile-dropdown');
                    if (dropdown && !dropdown.contains(e.target)) {
                        dropdown.classList.remove('open');
                    }
                });
            }
        });

        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
        } else {
                header.classList.remove('scrolled');
            }
        });

        // Search modal dropdown fix
        $('#searchModal').on('shown.bs.modal', function() {
            var selectElements = this.querySelectorAll('select.form-control');
            selectElements.forEach(function(select) {
                if (select.nextElementSibling && select.nextElementSibling.classList.contains('nice-select')) {
                    select.nextElementSibling.style.display = 'none';
                }
                select.style.display = 'block';
                select.style.opacity = '1';
                select.style.visibility = 'visible';
            });
        });

        // Hiển thị form thẻ hoặc QR tuỳ chọn
        function showPaymentDetails(method) {
            document.getElementById('credit-card-form').style.display = (method === 'Credit Card') ? 'block' : 'none';
            document.getElementById('qr-section').style.display = (method === 'Bank Transfer') ? 'block' : 'none';
        }
        document.querySelectorAll('.payment-option').forEach(option => {
            option.addEventListener('click', function() {
                showPaymentDetails(this.dataset.value);
            });
        });
        // Input mask cho số thẻ và ngày hết hạn
        function maskCardNumber(input) {
            input.value = input.value.replace(/\D/g, '').replace(/(.{4})/g, '$1 ').trim();
        }
        document.getElementById('cc-number').addEventListener('input', function() { maskCardNumber(this); });
        document.getElementById('cc-expiry').addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9\/]/g, '').replace(/(\d{2})(\d{1,2})/, '$1/$2').substr(0,5);
        });
        // Copy số tài khoản
        function copyBankAccount() {
            var acc = document.getElementById('bank-account').textContent;
            navigator.clipboard.writeText(acc);
            alert('Account number copied!');
        }
        // Xác nhận chuyển khoản
        var confirmBtn = document.getElementById('confirm-transfer-btn');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function() {
                document.getElementById('transfer-confirm-msg').style.display = 'block';
            });
        }

        // AJAX submit cho form booking
        $(function() {
            $('#booking-form').on('submit', function(e) {
                e.preventDefault();
                var $form = $(this);
                var formData = $form.serialize();
                $('#booking-error').hide().text('');
                $('.submit-btn').prop('disabled', true);
                $.ajax({
                    url: '',
                    type: 'POST',
                    data: formData + '&ajax=1',
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            $('#bookingSuccessModal').modal('show');
                            $form[0].reset();
                        } else {
                            $('#booking-error').show().text(res.message || 'An error occurred.');
                        }
                    },
                    error: function(xhr) {
                        $('#booking-error').show().text('An error occurred.');
                    },
                    complete: function() {
                        $('.submit-btn').prop('disabled', false);                   
                    }
                });     
    });
});
</script>
   </body>
</html>