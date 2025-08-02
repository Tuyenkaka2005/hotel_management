<?php
require 'config.php';
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['account_id'])) {
    die("You need to login to book a room.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id = intval($_POST['room_id']);
    $account_id = $_SESSION['account_id'];
    $checkin = $_POST['checkin'];
    $checkout = $_POST['checkout'];
    $promotion_code = trim($_POST['promotion_code'] ?? '');
    $payment_method = $_POST['payment_method'];

    // ✅ Kiểm tra trạng thái phòng trước khi đặt
    $stmt = $conn->prepare("SELECT PricePerNight, Status FROM Room WHERE RoomID = ?");
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result || $result->num_rows === 0) {
        die("Room does not exist.");
    }
    $room = $result->fetch_assoc();

    if ($room['Status'] !== 'Available') {
        echo "<script>alert('Room is not available. Please choose a different room.'); window.location.href='room.php';</script>";
        exit();
    }

    $price_per_night = $room['PricePerNight'];

    // Tính số đêm
    $date1 = new DateTime($checkin);
    $date2 = new DateTime($checkout);
    $interval = $date1->diff($date2);
    $nights = $interval->days;
    if ($nights <= 0) {
        die("Check-out date must be after check-in date.");
    }

    $total_amount = $price_per_night * $nights;
    $promotion_id = null;

    // Kiểm tra mã khuyến mãi (nếu có)
    if (!empty($promotion_code)) {
        $stmt = $conn->prepare("SELECT * FROM Promotion WHERE Code = ? AND StartDate <= CURDATE() AND EndDate >= CURDATE()");
        $stmt->bind_param("s", $promotion_code);
        $stmt->execute();
        $promo_result = $stmt->get_result();
        if ($promo_result && $promo_result->num_rows > 0) {
            $promo = $promo_result->fetch_assoc();
            $promotion_id = $promo['PromotionID'];
            if ($promo['DiscountType'] === 'Percentage') {
                $total_amount -= ($total_amount * $promo['DiscountValue'] / 100);
            } else {
                $total_amount -= $promo['DiscountValue'];
            }
            if ($total_amount < 0) $total_amount = 0;
        }
    }

    // Thêm vào bảng Reservation
    $stmt = $conn->prepare("INSERT INTO Reservation (CheckInDate, CheckOutDate, TotalAmount, AccountID, RoomID, PromotionID)
                            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdiii", $checkin, $checkout, $total_amount, $account_id, $room_id, $promotion_id);
    $stmt->execute();
    $reservation_id = $stmt->insert_id;

    // Thêm vào bảng Payment
    $payment_status = ($payment_method === 'Bank Transfer') ? 'Pending' : 'Completed';
    $stmt = $conn->prepare("INSERT INTO Payment (PaymentMethod, PaymentStatus, ReservationID)
                            VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $payment_method, $payment_status, $reservation_id);
    $stmt->execute();

    // ✅ Cập nhật trạng thái phòng thành 'Reserved' cho tất cả phương thức thanh toán
    // (Chỉ admin mới có thể xác nhận thanh toán và chuyển sang 'Occupied')
    $update_stmt = $conn->prepare("UPDATE Room SET Status = 'Reserved' WHERE RoomID = ?");
    $update_stmt->bind_param("i", $room_id);
    $update_stmt->execute();

    // Chuyển hướng
    if ($payment_method === 'Bank Transfer') {
        $_SESSION['pending_payment'] = [
            'reservation_id' => $reservation_id,
            'amount' => $total_amount
        ];
        header("Location: payment_qr.php");
        exit();
    } else {
        header("Location: booking_success.php");
        exit();
    }
} else {
    echo "Invalid method.";
}
?>
