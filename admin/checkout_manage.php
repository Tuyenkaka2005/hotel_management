<?php
require_once '../config.php';
session_start();

// Kiểm tra quyền admin
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Xử lý check-out
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout_room'])) {
    $reservation_id = (int)$_POST['reservation_id'];
    $room_id = (int)$_POST['room_id'];
    
    try {
        // 1. Cập nhật ngày check-out thực tế trước
        $stmt = $pdo->prepare("UPDATE Reservation SET ActualCheckOutDate = CURDATE() WHERE ReservationID = ?");
        $stmt->execute([$reservation_id]);
        
        // 2. Cập nhật trạng thái phòng thành Available
        $stmt = $pdo->prepare("UPDATE Room SET Status = 'Available' WHERE RoomID = ?");
        $stmt->execute([$room_id]);
        
        $success_message = "Room checked out successfully! Room is now available for new bookings.";
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Lấy danh sách phòng đang có khách
$sql = "
SELECT 
    r.ReservationID,
    r.RoomID,
    rm.RoomName,
    rm.RoomNumber,
    rm.Status,
    rt.TypeName,
    a.FullName,
    a.Email,
    a.PhoneNumber,
    r.CheckInDate,
    r.CheckOutDate,
    r.ActualCheckOutDate,
    p.PaymentStatus
FROM Reservation r
JOIN Room rm ON r.RoomID = rm.RoomID
LEFT JOIN RoomType rt ON rm.RoomTypeID = rt.RoomTypeID
LEFT JOIN Account a ON r.AccountID = a.AccountID
LEFT JOIN Payment p ON p.ReservationID = r.ReservationID
WHERE r.CheckOutDate >= CURDATE() 
  AND r.ActualCheckOutDate IS NULL
ORDER BY r.CheckOutDate ASC
";

$stmt = $pdo->query($sql);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Check-out Management</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .table th {
            background: #f8f9fa;
            border-top: none;
            font-weight: 600;
        }
        .btn-checkout {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            transition: all 0.3s ease;
        }
        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,107,107,0.4);
            color: white;
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-occupied {
            background: #ff6b6b;
            color: white;
        }
        .status-available {
            background: #51cf66;
            color: white;
        }
        .status-pending {
            background: #ffd43b;
            color: #333;
        }
        
        /* Modal Styling */
        .modal-content {
            border-radius: 18px;
            border: none;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
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
        
        .checkout-details {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .checkout-details h6 {
            color: #ff6b6b;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #495057;
        }
        
        .detail-value {
            color: #6c757d;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            border: none;
            border-radius: 25px;
            font-weight: 600;
            padding: 10px 20px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.3);
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
            
            .checkout-details {
                padding: 15px;
            }
            
            .detail-row {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-sign-out-alt"></i> Check-out Management</h2>
                <a href="../admin_dashboard.php" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?= $success_message ?>
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?= $error_message ?>
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-list"></i> Active Reservations</h4>
            </div>
            <div class="card-body">
                <?php if (empty($reservations)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-bed fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No active reservations</h5>
                        <p class="text-muted">All guests have checked out.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Room</th>
                                    <th>Guest</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reservations as $reservation): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($reservation['RoomName']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($reservation['RoomNumber']) ?> - <?= htmlspecialchars($reservation['TypeName']) ?></small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($reservation['FullName']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($reservation['Email']) ?></small>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($reservation['CheckInDate'])) ?></td>
                                    <td><?= date('d/m/Y', strtotime($reservation['CheckOutDate'])) ?></td>
                                    <td>
                                        <?php if ($reservation['PaymentStatus'] === 'Completed'): ?>
                                            <span class="status-badge status-available">Paid</span>
                                        <?php else: ?>
                                            <span class="status-badge status-pending">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-occupied">Occupied</span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-checkout btn-sm" onclick="confirmCheckout(<?= $reservation['ReservationID'] ?>, <?= $reservation['RoomID'] ?>, '<?= htmlspecialchars($reservation['RoomName'], ENT_QUOTES) ?>', '<?= htmlspecialchars($reservation['FullName'], ENT_QUOTES) ?>', '<?= htmlspecialchars($reservation['Email'], ENT_QUOTES) ?>', '<?= $reservation['CheckInDate'] ?>', '<?= $reservation['CheckOutDate'] ?>', '<?= $reservation['PaymentStatus'] ?>')">
                                            <i class="fas fa-sign-out-alt"></i> Check-out
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Auto Update Section -->
        <div class="card mt-4">
            <div class="card-header bg-info text-white">
                <h4 class="mb-0"><i class="fas fa-sync-alt"></i> Auto Update Room Status</h4>
            </div>
            <div class="card-body">
                <p>Click the button below to automatically update room statuses based on check-in/check-out dates:</p>
                <a href="../update_room_status_auto.php" class="btn btn-info" target="_blank">
                    <i class="fas fa-magic"></i> Run Auto Update
                </a>
            </div>
        </div>
    </div>

    </div>

    <!-- Modal Confirm Check-out -->
    <div class="modal fade" id="confirmCheckoutModal" tabindex="-1" role="dialog" aria-labelledby="confirmCheckoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmCheckoutModalLabel">
                        <i class="fas fa-sign-out-alt"></i> Confirm Check-out
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <p>Are you sure you want to check out this guest?</p>
                    <div class="checkout-details">
                        <h6><i class="fas fa-hotel"></i> Room Information</h6>
                        <div class="detail-row">
                            <span class="detail-label">Room:</span>
                            <span class="detail-value" id="checkoutRoomName"></span>
                        </div>
                    </div>
                    
                    <div class="checkout-details">
                        <h6><i class="fas fa-user"></i> Guest Information</h6>
                        <div class="detail-row">
                            <span class="detail-label">Guest Name:</span>
                            <span class="detail-value" id="checkoutGuestName"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Email:</span>
                            <span class="detail-value" id="checkoutGuestEmail"></span>
                        </div>
                    </div>
                    
                    <div class="checkout-details">
                        <h6><i class="fas fa-calendar"></i> Stay Information</h6>
                        <div class="detail-row">
                            <span class="detail-label">Check-in Date:</span>
                            <span class="detail-value" id="checkoutCheckIn"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Check-out Date:</span>
                            <span class="detail-value" id="checkoutCheckOut"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Payment Status:</span>
                            <span class="detail-value" id="checkoutPaymentStatus"></span>
                        </div>
                    </div>
                    
                    <p class="text-warning font-weight-bold">
                        <i class="fas fa-exclamation-triangle"></i> 
                        This will mark the guest as checked out and make the room available for new bookings.
                    </p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="reservation_id" id="checkoutReservationId">
                        <input type="hidden" name="room_id" id="checkoutRoomId">
                        <button type="submit" name="checkout_room" class="btn btn-danger">
                            <i class="fas fa-sign-out-alt"></i> Confirm Check-out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
    function confirmCheckout(reservationId, roomId, roomName, guestName, guestEmail, checkIn, checkOut, paymentStatus) {
        // Populate modal with guest information
        $('#checkoutReservationId').val(reservationId);
        $('#checkoutRoomId').val(roomId);
        $('#checkoutRoomName').text(roomName);
        $('#checkoutGuestName').text(guestName);
        $('#checkoutGuestEmail').text(guestEmail);
        $('#checkoutCheckIn').text(new Date(checkIn).toLocaleDateString('vi-VN'));
        $('#checkoutCheckOut').text(new Date(checkOut).toLocaleDateString('vi-VN'));
        
        // Set payment status with appropriate styling
        const paymentStatusText = paymentStatus === 'Completed' ? 'Paid' : 'Pending';
        const paymentStatusClass = paymentStatus === 'Completed' ? 'text-success' : 'text-warning';
        $('#checkoutPaymentStatus').html(`<span class="${paymentStatusClass}"><i class="fas fa-${paymentStatus === 'Completed' ? 'check-circle' : 'clock'}"></i> ${paymentStatusText}</span>`);
        
        // Show modal
        $('#confirmCheckoutModal').modal('show');
    }
    </script>
</body>
</html> 