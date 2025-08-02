<?php
require_once '../config.php';
session_start();

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Xử lý cập nhật khách hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_customer'])) {
    $id = $_POST['AccountID'];
    $fullName = $_POST['FullName'];
    $email = $_POST['Email'];
    $phone = $_POST['PhoneNumber'];

    $stmt = $pdo->prepare("UPDATE Account SET FullName = ?, Email = ?, PhoneNumber = ? WHERE AccountID = ?");
    $stmt->execute([$fullName, $email, $phone, $id]);

    header("Location: manage_customers.php");
    exit;
}

// Xử lý lấy thông tin chi tiết khách hàng cho modal View
if (isset($_GET['view'])) {
    $id = $_GET['view'];
    $stmt = $pdo->prepare("
        SELECT 
            a.AccountID,
            a.Username,
            a.FullName,
            a.Email,
            a.PhoneNumber,
            a.Role,
            COUNT(res.ReservationID) as TotalBookings,
            SUM(res.TotalAmount) as TotalSpent
        FROM Account a
        LEFT JOIN Reservation res ON a.AccountID = res.AccountID
        WHERE a.AccountID = ? AND a.Role = 'Customer'
        GROUP BY a.AccountID
    ");
    $stmt->execute([$id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($customer) {
        // Lấy danh sách booking chi tiết
        $stmt = $pdo->prepare("
            SELECT 
                res.ReservationID,
                res.CheckInDate,
                res.CheckOutDate,
                res.TotalAmount,
                r.RoomName,
                rt.TypeName,
                p.PaymentMethod,
                p.PaymentStatus
            FROM Reservation res
            JOIN Room r ON res.RoomID = r.RoomID
            JOIN RoomType rt ON r.RoomTypeID = rt.RoomTypeID
            LEFT JOIN Payment p ON res.ReservationID = p.ReservationID
            WHERE res.AccountID = ?
            ORDER BY res.CheckInDate DESC
        ");
        $stmt->execute([$id]);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $customer['bookings'] = $bookings;
    }
}



// Lấy danh sách khách hàng + phòng đã đặt
$sql = "
    SELECT 
        a.AccountID,
        a.Username,
        a.FullName,
        a.Email,
        a.PhoneNumber,
        GROUP_CONCAT(r.RoomName SEPARATOR ', ') AS BookedRooms
    FROM Account a
    LEFT JOIN Reservation res ON a.AccountID = res.AccountID
    LEFT JOIN Room r ON res.RoomID = r.RoomID
    WHERE a.Role = 'Customer'
    GROUP BY a.AccountID
    ORDER BY a.AccountID DESC
";
$stmt = $pdo->query($sql);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Customer Management</title>
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
            color: #764ba2;
            letter-spacing: 1px;
        }
        .btn-danger, .btn-secondary {
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 7px 18px;
            margin-right: 4px;
        }
        .btn-danger:hover, .btn-secondary:hover {
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 8px 25px rgba(118, 75, 162, 0.13);
        }
        .btn-secondary {
            background: #b2bec3;
            color: #fff;
            border: none;
        }
        .table {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(118, 75, 162, 0.07);
        }
        .table thead {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
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
        
        /* Modal Styling */
        .modal-content {
            border-radius: 18px;
            border: none;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
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
        
        .customer-info {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .customer-info h5 {
            color: #764ba2;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #495057;
        }
        
        .info-value {
            color: #6c757d;
        }
        
        .booking-table {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .booking-table th {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            color: #fff;
            font-weight: 600;
            padding: 12px;
        }
        
        .booking-table td {
            padding: 10px 12px;
            vertical-align: middle;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-completed {
            background: #28a745;
            color: #fff;
        }
        
        .status-pending {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            border: none;
            border-radius: 25px;
            font-weight: 600;
            padding: 7px 18px;
            margin-right: 4px;
        }
        
        .btn-info:hover {
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 8px 25px rgba(23, 162, 184, 0.3);
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
            
            .customer-info {
                padding: 15px;
            }
            
            .info-row {
                flex-direction: column;
                gap: 5px;
            }
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
    <h2 class="mb-4">Customer Management</h2>



    <!-- Bảng khách hàng -->
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Username</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Booked Rooms</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($users) > 0): ?>
                <?php foreach ($users as $index => $user): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($user['Username']) ?></td>
                        <td><?= htmlspecialchars($user['FullName']) ?></td>
                        <td><?= htmlspecialchars($user['Email']) ?></td>
                        <td><?= htmlspecialchars($user['PhoneNumber']) ?></td>
                        <td><?= $user['BookedRooms'] ? htmlspecialchars($user['BookedRooms']) : 'No booked rooms' ?></td>
                        <td>
                            <button class="btn btn-info btn-sm" onclick="viewCustomer(<?= $user['AccountID'] ?>)">
                                <i class="fas fa-eye"></i> View
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7">No customer.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <a href="../admin_dashboard.php" class="btn btn-secondary mt-3">← Back to Dashboard</a>
</div>

<!-- Modal View Customer -->
<div class="modal fade" id="viewCustomerModal" tabindex="-1" role="dialog" aria-labelledby="viewCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewCustomerModalLabel">
                    <i class="fas fa-user"></i> Customer Details
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="customerModalBody">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
function viewCustomer(customerId) {
    // Show loading
    $('#customerModalBody').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Loading customer details...</p></div>');
    $('#viewCustomerModal').modal('show');
    
    // Load customer details via AJAX
    $.get('customer_manage.php?view=' + customerId, function(data) {
        // Parse the response to extract customer data
        // This is a simple approach - in production you might want to return JSON
        $('#customerModalBody').html(data);
    }).fail(function() {
        $('#customerModalBody').html('<div class="alert alert-danger">Error loading customer details.</div>');
    });
}

// Handle direct view parameter (when page loads with ?view=id)
$(document).ready(function() {
    <?php if (isset($_GET['view']) && isset($customer)): ?>
        $('#viewCustomerModal').modal('show');
        $('#customerModalBody').html(`
            <div class="customer-info">
                <h5><i class="fas fa-user-circle"></i> Customer Information</h5>
                <div class="info-row">
                    <span class="info-label">Username:</span>
                    <span class="info-value"><?= htmlspecialchars($customer['Username']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Full Name:</span>
                    <span class="info-value"><?= htmlspecialchars($customer['FullName']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?= htmlspecialchars($customer['Email']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Phone:</span>
                    <span class="info-value"><?= htmlspecialchars($customer['PhoneNumber'] ?: 'N/A') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Total Bookings:</span>
                    <span class="info-value"><?= $customer['TotalBookings'] ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Total Spent:</span>
                    <span class="info-value">$<?= number_format($customer['TotalSpent'] ?: 0, 2) ?></span>
                </div>
            </div>
            
            <h5><i class="fas fa-calendar-alt"></i> Booking History</h5>
            <?php if (!empty($customer['bookings'])): ?>
                <div class="table-responsive">
                    <table class="table table-striped booking-table">
                        <thead>
                            <tr>
                                <th>Room</th>
                                <th>Type</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Amount</th>
                                <th>Payment</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customer['bookings'] as $booking): ?>
                                <tr>
                                    <td><?= htmlspecialchars($booking['RoomName']) ?></td>
                                    <td><?= htmlspecialchars($booking['TypeName']) ?></td>
                                    <td><?= date('M d, Y', strtotime($booking['CheckInDate'])) ?></td>
                                    <td><?= date('M d, Y', strtotime($booking['CheckOutDate'])) ?></td>
                                    <td>$<?= number_format($booking['TotalAmount'], 2) ?></td>
                                    <td><?= htmlspecialchars($booking['PaymentMethod'] ?: 'N/A') ?></td>
                                    <td>
                                        <span class="status-badge <?= $booking['PaymentStatus'] === 'Completed' ? 'status-completed' : 'status-pending' ?>">
                                            <?= htmlspecialchars($booking['PaymentStatus'] ?: 'Pending') ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No booking history found for this customer.
                </div>
            <?php endif; ?>
        `);
    <?php endif; ?>
});
</script>
</body>
</html>
