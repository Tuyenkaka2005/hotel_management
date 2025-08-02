<?php
require_once '../config.php'; // đường dẫn đúng tới config
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id'])) {
    $reservation_id = (int)$_POST['reservation_id'];
    
    try {
        // Bắt đầu transaction
        $pdo->beginTransaction();
        
        // 1. Cập nhật trạng thái thanh toán
        $stmt = $pdo->prepare("UPDATE Payment SET PaymentStatus = 'Completed' WHERE ReservationID = ?");
        $stmt->execute([$reservation_id]);
        
        // 2. Lấy RoomID từ Reservation
        $stmt = $pdo->prepare("SELECT RoomID FROM Reservation WHERE ReservationID = ?");
        $stmt->execute([$reservation_id]);
        $room_id = $stmt->fetchColumn();
        
        if ($room_id) {
            // 3. Cập nhật trạng thái phòng từ 'Reserved' thành 'Occupied'
            $stmt = $pdo->prepare("UPDATE Room SET Status = 'Occupied' WHERE RoomID = ?");
            $stmt->execute([$room_id]);
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Redirect với thông báo thành công
        header('Location: booking_manage.php?success=payment_confirmed');
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction nếu có lỗi
        $pdo->rollBack();
        header('Location: booking_manage.php?error=payment_confirmation_failed');
        exit;
    }
}

header('Location: booking_manage.php');
exit;
?>
