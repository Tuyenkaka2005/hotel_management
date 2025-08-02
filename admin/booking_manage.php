<?php
require_once '../config.php';

// Xử lý hủy booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $reservation_id = (int)$_POST['reservation_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Lấy thông tin booking
        $stmt = $pdo->prepare("SELECT r.*, rm.RoomID, rm.RoomName, p.PaymentStatus 
                               FROM Reservation r 
                               JOIN Room rm ON r.RoomID = rm.RoomID 
                               LEFT JOIN Payment p ON p.ReservationID = r.ReservationID
                               WHERE r.ReservationID = ?");
        $stmt->execute([$reservation_id]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            throw new Exception("Booking not found.");
        }
        
        // Kiểm tra xem có thể hủy không (chỉ hủy được booking chưa check-in và chưa thanh toán)
        $today = date('Y-m-d');
        if ($booking['CheckInDate'] <= $today) {
            throw new Exception("Cannot cancel booking that has already started.");
        }
        
        if ($booking['PaymentStatus'] === 'Completed') {
            throw new Exception("Cannot cancel booking that has been paid.");
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
        
        $success_message = "Booking cancelled successfully!";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = $e->getMessage();
    }
}

// Truy vấn thông tin đặt phòng kết hợp với trạng thái thanh toán
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
    r.TotalAmount,
    p.PaymentMethod,
    p.PaymentStatus

FROM Reservation r
JOIN Room rm ON r.RoomID = rm.RoomID
LEFT JOIN RoomType rt ON rm.RoomTypeID = rt.RoomTypeID
LEFT JOIN Account a ON r.AccountID = a.AccountID
LEFT JOIN Payment p ON p.ReservationID = r.ReservationID
ORDER BY r.CheckInDate DESC
";

$stmt = $pdo->query($sql);
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Booking Management</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
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
            padding: 36px 20px 28px 20px;
            margin-top: 40px;
            margin-bottom: 40px;
            max-width: 100%;
        }
        h2 {
            font-weight: 700;
            color: #17a2b8;
            letter-spacing: 1px;
        }
        .btn-success, .btn-secondary {
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 7px 18px;
            margin-right: 4px;
        }
        .btn-success:hover, .btn-secondary:hover {
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 8px 25px rgba(23, 162, 184, 0.13);
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
            box-shadow: 0 2px 8px rgba(23, 162, 184, 0.07);
        }
        .table thead {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: #fff;
        }
        .table th, .table td {
            vertical-align: middle !important;
            text-align: center;
            padding: 10px 6px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        .table {
            width: 100%;
            table-layout: fixed;
        }
        
        .table-responsive {
            border-radius: 12px;
        }
        
        /* Column widths - using percentages for responsive layout */
        .table th:nth-child(1), .table td:nth-child(1) { /* # */
            width: 5%;
        }
        
        .table th:nth-child(2), .table td:nth-child(2) { /* Room */
            width: 20%;
        }
        
        .table th:nth-child(3), .table td:nth-child(3) { /* Customer */
            width: 15%;
        }
        
        .table th:nth-child(4), .table td:nth-child(4) { /* Check-in */
            width: 12%;
        }
        
        .table th:nth-child(5), .table td:nth-child(5) { /* Check-out */
            width: 12%;
        }
        
        .table th:nth-child(6),{ /* Total Amount */
            width: 12%;
            font-weight: 600;
            color:rgb(255, 255, 255);
        }
        .table td:nth-child(6) { /* Total Amount */
            width: 12%;
            font-weight: 600;
            color: #28a745;
        }
        
        .table th:nth-child(7), .table td:nth-child(7) { /* Status */
            width: 12%;
        }
        
        .table th:nth-child(8), .table td:nth-child(8) { /* Actions */
            width: 12%;
        }
        .table th {
            font-size: 1.05rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .table td {
            font-size: 0.98rem;
        }
        .badge {
            border-radius: 20px;
            padding: 8px 15px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge.bg-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        .badge.bg-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }
        .badge.bg-warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
        }
        .badge.bg-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }
        a.btn-secondary {
            margin-top: 18px;
            border-radius: 25px;
            font-weight: 600;
            padding: 8px 22px;
            font-size: 1rem;
        }
        @media (max-width: 1200px) {
            .table th, .table td {
                font-size: 0.8rem;
                padding: 8px 4px;
            }
            
            .btn-sm {
                padding: 4px 8px;
                font-size: 0.75rem;
            }
            
            .badge {
                font-size: 0.7rem;
                padding: 4px 8px;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px 10px;
            }
            
            .table-responsive {
                overflow-x: auto;
            }
            
            .table th, .table td {
                font-size: 0.75rem;
                padding: 6px 3px;
                white-space: nowrap;
            }
            
            .btn-sm {
                padding: 3px 6px;
                font-size: 0.7rem;
            }
            
            .badge {
                font-size: 0.65rem;
                padding: 3px 6px;
            }
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
        
        .btn-cancel {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-cancel:hover {
            background: #c82333;
            transform: translateY(-1px);
        }
        
        .btn-cancel:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Modal Styling */
        .modal-content {
            border-radius: 18px;
            border: none;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
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
        
        .booking-details {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .booking-details h6 {
            color: #17a2b8;
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
        
        .btn-view {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            border: none;
            border-radius: 25px;
            font-weight: 600;
            padding: 7px 18px;
            margin-right: 4px;
            color: #fff;
        }
        
        .btn-view:hover {
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 8px 25px rgba(23, 162, 184, 0.3);
            color: #fff;
        }
        
        .btn-confirm {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            border-radius: 25px;
            font-weight: 600;
            padding: 7px 18px;
            margin-right: 4px;
            color: #fff;
        }
        
        .btn-confirm:hover {
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
            color: #fff;
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
            
            .booking-details {
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
<div class="container mt-5">
    <h2 class="mb-4">📋 Booking Management</h2>
    
    <!-- Alert Messages -->
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle mr-2"></i>
            <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>
    
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Room</th>
                <th>Customer</th>
                <th>Check-in</th>
                <th>Check-out</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th><i class="fas fa-cogs"></i> Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rooms as $index => $room): ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td>
                    <div>
                        <strong><?= htmlspecialchars($room['RoomName']) ?></strong><br>
                        <small class="text-muted"><?= htmlspecialchars($room['TypeName']) ?> (<?= htmlspecialchars($room['RoomNumber']) ?>)</small>
                    </div>
                </td>
                <td><?= $room['FullName'] ?? '<em>No customer</em>' ?></td>
                <td><?= date('M d, Y', strtotime($room['CheckInDate'])) ?? '-' ?></td>
                <td><?= date('M d, Y', strtotime($room['CheckOutDate'])) ?? '-' ?></td>
                <td><strong><?= number_format($room['TotalAmount'] ?? 0, 0, ',', '.') ?> VNĐ</strong></td>
                <td style="white-space: nowrap;">
                    <?php
                    // Logic trạng thái phòng: nếu có đặt phòng chưa thanh toán => Đặt trước, đã thanh toán => Có khách, còn lại => Trống
                    if ($room['PaymentStatus'] === 'Completed') {
                        echo '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Paid</span>';
                    } elseif ($room['PaymentStatus'] && $room['PaymentStatus'] !== 'Completed') {
                        echo '<span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Pending</span>';
                    } else {
                        echo '<span class="badge bg-secondary"><i class="fas fa-times"></i> Empty</span>';
                    }
                    ?>
                </td>
                <td style="white-space: nowrap;">
    <div style="display: flex; flex-direction: column; gap: 5px; align-items: center;">
        <button type="button" class="btn btn-view btn-sm" onclick="viewBooking(<?= $room['ReservationID'] ?>, '<?= htmlspecialchars($room['RoomName'], ENT_QUOTES) ?>', '<?= htmlspecialchars($room['FullName'], ENT_QUOTES) ?>', '<?= $room['CheckInDate'] ?>', '<?= $room['CheckOutDate'] ?>', '<?= $room['PaymentMethod'] ?>', '<?= $room['PaymentStatus'] ?>', '<?= $room['TotalAmount'] ?? 0 ?>', '<?= htmlspecialchars($room['Email'], ENT_QUOTES) ?>', '<?= htmlspecialchars($room['PhoneNumber'], ENT_QUOTES) ?>', '<?= htmlspecialchars($room['TypeName'], ENT_QUOTES) ?>', '<?= htmlspecialchars($room['RoomNumber'], ENT_QUOTES) ?>')">
            <i class="fas fa-eye"></i> View
        </button>
        
        <?php if ($room['PaymentStatus'] !== 'Completed'): ?>
            <button type="button" class="btn btn-confirm btn-sm" onclick="confirmPayment(<?= $room['ReservationID'] ?>, '<?= htmlspecialchars($room['RoomName'], ENT_QUOTES) ?>', '<?= htmlspecialchars($room['FullName'], ENT_QUOTES) ?>')">
                <i class="fas fa-check"></i> Confirm
            </button>
        <?php endif; ?>
        
        <?php
        $today = date('Y-m-d');
        $checkin_date = $room['CheckInDate'];
        $can_cancel = ($checkin_date > $today && !$room['ActualCheckOutDate'] && $room['PaymentStatus'] !== 'Completed');
        ?>
        
        <?php if ($can_cancel): ?>
            <button type="button" class="btn-cancel" onclick="cancelBooking(<?= $room['ReservationID'] ?>, '<?= htmlspecialchars($room['RoomName'], ENT_QUOTES) ?>', '<?= htmlspecialchars($room['FullName'], ENT_QUOTES) ?>')" title="Cancel Booking">
                <i class="fas fa-times"></i> Cancel
            </button>
        <?php else: ?>
            <button class="btn-cancel" disabled title="Cannot cancel - booking has started, completed, or payment confirmed">
                <i class="fas fa-ban"></i> Cannot Cancel
            </button>
        <?php endif; ?>
    </div>
</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <a href="../admin_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
</div>

<!-- Modal View Booking -->
<div class="modal fade" id="viewBookingModal" tabindex="-1" role="dialog" aria-labelledby="viewBookingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewBookingModalLabel">
                    <i class="fas fa-calendar-alt"></i> Booking Details
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="bookingModalBody">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Confirm Payment -->
<div class="modal fade" id="confirmPaymentModal" tabindex="-1" role="dialog" aria-labelledby="confirmPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmPaymentModalLabel">
                    <i class="fas fa-check-circle text-success"></i> Confirm Payment
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <p>Are you sure you want to confirm payment for this booking?</p>
                <div class="booking-details">
                    <h6><i class="fas fa-info-circle"></i> Booking Information</h6>
                    <div class="detail-row">
                        <span class="detail-label">Room:</span>
                        <span class="detail-value" id="confirmRoomName"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Customer:</span>
                        <span class="detail-value" id="confirmCustomerName"></span>
                    </div>
                </div>
                <p class="text-success font-weight-bold">This will change the room status to 'Occupied'.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <form method="post" action="confirm_payment.php" style="display: inline;">
                    <input type="hidden" name="reservation_id" id="confirmReservationId">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Confirm Payment
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Cancel Booking -->
<div class="modal fade" id="cancelBookingModal" tabindex="-1" role="dialog" aria-labelledby="cancelBookingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelBookingModalLabel">
                    <i class="fas fa-exclamation-triangle text-danger"></i> Cancel Booking
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <p>Are you sure you want to cancel this booking?</p>
                <div class="booking-details">
                    <h6><i class="fas fa-info-circle"></i> Booking Information</h6>
                    <div class="detail-row">
                        <span class="detail-label">Room:</span>
                        <span class="detail-value" id="cancelRoomName"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Customer:</span>
                        <span class="detail-value" id="cancelCustomerName"></span>
                    </div>
                </div>
                <p class="text-danger font-weight-bold">This action cannot be undone. The room will become available again.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <form method="post" style="display: inline;">
                    <input type="hidden" name="reservation_id" id="cancelReservationId">
                    <button type="submit" name="cancel_booking" class="btn btn-danger">
                        <i class="fas fa-times"></i> Cancel Booking
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
function viewBooking(reservationId, roomName, customerName, checkIn, checkOut, paymentMethod, paymentStatus, totalAmount, email, phone, roomType, roomNumber) {
    const statusText = paymentStatus === 'Completed' ? 'Paid' : 'Not Paid';
    const statusClass = paymentStatus === 'Completed' ? 'text-success' : 'text-warning';
    
    $('#bookingModalBody').html(`
        <div class="booking-details">
            <h6><i class="fas fa-hotel"></i> Room Information</h6>
            <div class="detail-row">
                <span class="detail-label">Room Name:</span>
                <span class="detail-value">${roomName}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Room Type:</span>
                <span class="detail-value">${roomType}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Room Number:</span>
                <span class="detail-value">${roomNumber}</span>
            </div>
        </div>
        
        <div class="booking-details">
            <h6><i class="fas fa-user"></i> Customer Information</h6>
            <div class="detail-row">
                <span class="detail-label">Customer Name:</span>
                <span class="detail-value">${customerName || 'N/A'}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Email:</span>
                <span class="detail-value">${email || 'N/A'}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Phone:</span>
                <span class="detail-value">${phone || 'N/A'}</span>
            </div>
        </div>
        
        <div class="booking-details">
            <h6><i class="fas fa-calendar"></i> Booking Information</h6>
            <div class="detail-row">
                <span class="detail-label">Check-in Date:</span>
                <span class="detail-value">${checkIn}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Check-out Date:</span>
                <span class="detail-value">${checkOut}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Total Amount:</span>
                <span class="detail-value">${parseFloat(totalAmount || 0).toLocaleString('vi-VN')} VNĐ</span>
            </div>
        </div>
        
        <div class="booking-details">
            <h6><i class="fas fa-credit-card"></i> Payment Information</h6>
            <div class="detail-row">
                <span class="detail-label">Payment Method:</span>
                <span class="detail-value">${paymentMethod || 'N/A'}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payment Status:</span>
                <span class="detail-value ${statusClass}"><i class="fas fa-${paymentStatus === 'Completed' ? 'check-circle' : 'clock'}"></i> ${statusText}</span>
            </div>
        </div>
    `);
    
    $('#viewBookingModal').modal('show');
}

function confirmPayment(reservationId, roomName, customerName) {
    $('#confirmReservationId').val(reservationId);
    $('#confirmRoomName').text(roomName);
    $('#confirmCustomerName').text(customerName || 'N/A');
    $('#confirmPaymentModal').modal('show');
}

function cancelBooking(reservationId, roomName, customerName) {
    $('#cancelReservationId').val(reservationId);
    $('#cancelRoomName').text(roomName);
    $('#cancelCustomerName').text(customerName || 'N/A');
    $('#cancelBookingModal').modal('show');
}
</script>
</body>
</html>
