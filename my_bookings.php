<?php
require 'config.php';
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['account_id'])) {
    header('Location: login.php');
    exit;
}

$account_id = $_SESSION['account_id'];

// Xử lý hủy booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $reservation_id = (int)$_POST['reservation_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Kiểm tra booking có thuộc về user này không
        $stmt = $pdo->prepare("SELECT r.*, rm.RoomID, rm.RoomName, p.PaymentStatus 
                               FROM Reservation r 
                               JOIN Room rm ON r.RoomID = rm.RoomID 
                               LEFT JOIN Payment p ON p.ReservationID = r.ReservationID
                               WHERE r.ReservationID = ? AND r.AccountID = ?");
        $stmt->execute([$reservation_id, $account_id]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            throw new Exception("Booking not found or you don't have permission to cancel it.");
        }
        
        // Kiểm tra xem có thể hủy không (chỉ hủy được booking chưa check-in)
        $today = date('Y-m-d');
        if ($booking['CheckInDate'] <= $today) {
            throw new Exception("Cannot cancel booking that has already started.");
        }
        
        // Cập nhật trạng thái phòng về Available
        $stmt = $pdo->prepare("UPDATE Room SET Status = 'Available' WHERE RoomID = ?");
        $stmt->execute([$booking['RoomID']]);
        
        // Xóa payment record
        $stmt = $pdo->prepare("DELETE FROM Payment WHERE ReservationID = ?");
        $stmt->execute([$reservation_id]);
        
        // Xóa reservation
        $stmt = $pdo->prepare("DELETE FROM Reservation WHERE ReservationID = ?");
        $stmt->execute([$reservation_id]);
        
        $pdo->commit();
        
        $_SESSION['success_message'] = "Booking cancelled successfully!";
        header('Location: my_bookings.php');
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = $e->getMessage();
        header('Location: my_bookings.php');
        exit;
    }
}

// Lấy danh sách booking của user
$sql = "SELECT r.*, rm.RoomName, rm.RoomNumber, rt.TypeName, p.PaymentStatus, p.PaymentMethod
        FROM Reservation r
        JOIN Room rm ON r.RoomID = rm.RoomID
        JOIN RoomType rt ON rm.RoomTypeID = rt.RoomTypeID
        LEFT JOIN Payment p ON p.ReservationID = r.ReservationID
        WHERE r.AccountID = ?
        ORDER BY r.CheckInDate DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$account_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings</title>
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

        .header {
            background: white;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            border-bottom: 3px solid;
            border-image: linear-gradient(90deg,rgb(215, 76, 76) 0%, #764ba2 100%) 1;
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

        .page-header {
            background: linear-gradient(135deg, rgba(217, 112, 112, 0.9) 0%, rgba(118, 75, 162, 0.9) 100%);
            padding: 60px 0;
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .page-header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .bookings-container {
            max-width: 1200px;
            margin: 0 auto 40px;
        }

        .booking-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .booking-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .booking-header {
            background: linear-gradient(135deg,rgb(215, 76, 76) 0%, #764ba2 100%);
            color: white;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .booking-header h3 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .booking-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-upcoming {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }

        .status-active {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
        }

        .status-completed {
            background: rgba(108, 117, 125, 0.2);
            color: #6c757d;
        }

        .status-cancelled {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }

        .booking-body {
            padding: 25px;
        }

        .booking-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid rgb(215, 76, 76);
        }

        .detail-item i {
            color: rgb(215, 76, 76);
            margin-right: 12px;
            font-size: 1.2rem;
            width: 20px;
            text-align: center;
        }

        .detail-content h5 {
            margin: 0;
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 500;
        }

        .detail-content p {
            margin: 0;
            font-size: 1rem;
            color: #333;
            font-weight: 600;
        }

        .booking-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }

        .btn-cancel {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .btn-cancel:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }

        .btn-view {
            background: rgb(215, 76, 76);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-view:hover {
            background: #d63384;
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
        }

        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .empty-state i {
            font-size: 4rem;
            color: #6c757d;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #333;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #6c757d;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }
            
            .booking-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .booking-details {
                grid-template-columns: 1fr;
            }
            
            .booking-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
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
                        <li class="nav-item">
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
                <div class="profile-dropdown">
                    <button class="profile-btn">
                        <img src="images/profile.png" class="profile-avatar" alt="avatar">
                        <span class="d-none d-md-inline"><?= htmlspecialchars($_SESSION['username']) ?></span>
                        <i class="fa fa-caret-down"></i>
                    </button>
                    <div class="profile-menu">
                        <a class="dropdown-item" href="profile_customer.php">
                            <i class="fa fa-user mr-2"></i>Profile
                        </a>
                        <a class="dropdown-item" href="my_bookings.php">
                            <i class="fa fa-calendar mr-2"></i>My Bookings
                        </a>
                        <a class="dropdown-item" href="booked_rooms.php">
                            <i class="fa fa-history mr-2"></i>Booked Rooms
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
            </div>
        </div>
    </header>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1><i class="fas fa-calendar-check"></i> My Bookings</h1>
            <p>Manage your room reservations</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="bookings-container">
            <!-- Alert Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?= htmlspecialchars($_SESSION['success_message']) ?>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <?= htmlspecialchars($_SESSION['error_message']) ?>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <?php if (empty($bookings)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Bookings Found</h3>
                    <p>You haven't made any room reservations yet.</p>
                    <a href="room.php" class="btn btn-primary">
                        <i class="fas fa-search mr-2"></i>Browse Rooms
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($bookings as $booking): ?>
                    <?php
                    $today = date('Y-m-d');
                    $checkin_date = $booking['CheckInDate'];
                    $checkout_date = $booking['CheckOutDate'];
                    
                    // Xác định trạng thái booking
                    if ($booking['ActualCheckOutDate']) {
                        $status = 'completed';
                        $status_text = 'Completed';
                    } elseif ($checkin_date <= $today && $checkout_date > $today) {
                        $status = 'active';
                        $status_text = 'Active';
                    } elseif ($checkin_date > $today) {
                        $status = 'upcoming';
                        $status_text = 'Upcoming';
                    } else {
                        $status = 'completed';
                        $status_text = 'Completed';
                    }
                    
                    // Kiểm tra có thể hủy không
                    $can_cancel = ($checkin_date > $today && $status !== 'completed');
                    ?>
                    
                    <div class="booking-card">
                        <div class="booking-header">
                            <h3>
                                <i class="fas fa-bed mr-2"></i>
                                <?= htmlspecialchars($booking['RoomName']) ?> (<?= htmlspecialchars($booking['RoomNumber']) ?>)
                            </h3>
                            <span class="booking-status status-<?= $status ?>">
                                <?= $status_text ?>
                            </span>
                        </div>
                         
                        <div class="booking-body">
                            <div class="booking-details">
                                <div class="detail-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <div class="detail-content">
                                        <h5>Check-in Date</h5>
                                        <p><?= date('d/m/Y', strtotime($booking['CheckInDate'])) ?></p>
                                    </div>
                                </div>
                                
                                <div class="detail-item">
                                    <i class="fas fa-calendar-check"></i>
                                    <div class="detail-content">
                                        <h5>Check-out Date</h5>
                                        <p><?= date('d/m/Y', strtotime($booking['CheckOutDate'])) ?></p>
                                    </div>
                                </div>
                                
                                <div class="detail-item">
                                    <i class="fas fa-users"></i>
                                    <div class="detail-content">
                                        <h5>Room Type</h5>
                                        <p><?= htmlspecialchars($booking['TypeName']) ?></p>
                                    </div>
                                </div>
                                
                                <div class="detail-item">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <div class="detail-content">
                                        <h5>Total Amount</h5>
                                        <p><?= number_format($booking['TotalAmount'], 0, ',', '.') ?> VNĐ</p>
                                    </div>
                                </div>
                                
                                <div class="detail-item">
                                    <i class="fas fa-credit-card"></i>
                                    <div class="detail-content">
                                        <h5>Payment Method</h5>
                                        <p><?= htmlspecialchars($booking['PaymentMethod'] ?? 'N/A') ?></p>
                                    </div>
                                </div>
                                
                                <div class="detail-item">
                                    <i class="fas fa-info-circle"></i>
                                    <div class="detail-content">
                                        <h5>Payment Status</h5>
                                        <p><?= htmlspecialchars($booking['PaymentStatus'] ?? 'N/A') ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="booking-actions">
                                <a href="room_detail.php?room_id=<?= $booking['RoomID'] ?>" class="btn-view">
                                    <i class="fas fa-eye mr-2"></i>View Room
                                </a>
                                
                                <?php if ($can_cancel): ?>
                                    <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel this booking? This action cannot be undone.')">
                                        <input type="hidden" name="reservation_id" value="<?= $booking['ReservationID'] ?>">
                                        <button type="submit" name="cancel_booking" class="btn-cancel">
                                            <i class="fas fa-times mr-2"></i>Cancel Booking
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn-cancel" disabled>
                                        <i class="fas fa-times mr-2"></i>Cannot Cancel
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Scripts -->
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
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
    </script>
</body>
</html>