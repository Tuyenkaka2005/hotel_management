<?php
require 'config.php';

echo "<h2>🔄 Test Booking Workflow</h2>";

try {
    // 1. Hiển thị trạng thái hiện tại
    echo "<h3>1. Trạng thái phòng hiện tại</h3>";
    
    $sql = "SELECT r.RoomID, r.RoomName, r.RoomNumber, r.Status, rt.TypeName
            FROM Room r
            JOIN RoomType rt ON r.RoomTypeID = rt.RoomTypeID
            ORDER BY r.RoomID";
    
    $stmt = $pdo->query($sql);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Room</th><th>Type</th><th>Status</th><th>Action</th>";
    echo "</tr>";
    
    foreach ($rooms as $room) {
        $statusColor = '';
        switch($room['Status']) {
            case 'Available': $statusColor = 'green'; break;
            case 'Reserved': $statusColor = 'orange'; break;
            case 'Occupied': $statusColor = 'red'; break;
            case 'Maintenance': $statusColor = 'gray'; break;
            default: $statusColor = 'blue';
        }
        
        echo "<tr>";
        echo "<td>{$room['RoomName']} ({$room['RoomNumber']})</td>";
        echo "<td>{$room['TypeName']}</td>";
        echo "<td style='color: $statusColor; font-weight: bold;'>{$room['Status']}</td>";
        echo "<td>";
        if ($room['Status'] === 'Available') {
            echo "<form method='post' style='display: inline;'>";
            echo "<input type='hidden' name='test_booking_room' value='{$room['RoomID']}'>";
            echo "<button type='submit' style='background: #28a745; color: white; border: none; padding: 5px 10px; border-radius: 3px;'>Test Booking</button>";
            echo "</form>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Test tạo booking
    if (isset($_POST['test_booking_room'])) {
        $test_room_id = (int)$_POST['test_booking_room'];
        
        try {
            $pdo->beginTransaction();
            
            // Tạo reservation test
            $checkin = date('Y-m-d', strtotime('+1 day'));
            $checkout = date('Y-m-d', strtotime('+3 days'));
            $total_amount = 1000000;
            
            $stmt = $pdo->prepare("INSERT INTO Reservation (CheckInDate, CheckOutDate, TotalAmount, AccountID, RoomID) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$checkin, $checkout, $total_amount, 1, $test_room_id]);
            $reservation_id = $pdo->lastInsertId();
            
            // Tạo payment pending
            $stmt = $pdo->prepare("INSERT INTO Payment (PaymentMethod, PaymentStatus, ReservationID) VALUES (?, ?, ?)");
            $stmt->execute(['Cash', 'Pending', $reservation_id]);
            
            // Cập nhật trạng thái phòng thành Reserved
            $stmt = $pdo->prepare("UPDATE Room SET Status = 'Reserved' WHERE RoomID = ?");
            $stmt->execute([$test_room_id]);
            
            $pdo->commit();
            
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
            echo "<h4>✅ Test Booking Created!</h4>";
            echo "<p>Room ID: $test_room_id</p>";
            echo "<p>Reservation ID: $reservation_id</p>";
            echo "<p>Status: Reserved</p>";
            echo "</div>";
            
            // Lưu reservation_id để test confirm payment
            $_SESSION['test_reservation_id'] = $reservation_id;
            $_SESSION['test_room_id'] = $test_room_id;
            
            echo "<script>setTimeout(function(){location.reload();}, 2000);</script>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
            echo "<h4>❌ Error</h4>";
            echo "<p>" . $e->getMessage() . "</p>";
            echo "</div>";
        }
    }
    
    // 3. Test confirm payment
    if (isset($_SESSION['test_reservation_id'])) {
        echo "<h3>2. Test Confirm Payment</h3>";
        echo "<form method='post'>";
        echo "<button type='submit' name='test_confirm_payment' style='background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px;'>";
        echo "💰 Confirm Payment (Reserved → Occupied)";
        echo "</button>";
        echo "</form>";
    }
    
    if (isset($_POST['test_confirm_payment']) && isset($_SESSION['test_reservation_id'])) {
        $reservation_id = $_SESSION['test_reservation_id'];
        $room_id = $_SESSION['test_room_id'];
        
        try {
            $pdo->beginTransaction();
            
            // 1. Cập nhật trạng thái thanh toán
            $stmt = $pdo->prepare("UPDATE Payment SET PaymentStatus = 'Completed' WHERE ReservationID = ?");
            $stmt->execute([$reservation_id]);
            
            // 2. Cập nhật trạng thái phòng thành Occupied
            $stmt = $pdo->prepare("UPDATE Room SET Status = 'Occupied' WHERE RoomID = ?");
            $stmt->execute([$room_id]);
            
            $pdo->commit();
            
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
            echo "<h4>✅ Payment Confirmed!</h4>";
            echo "<p>Reservation ID: $reservation_id</p>";
            echo "<p>Room ID: $room_id</p>";
            echo "<p>Status: Reserved → Occupied</p>";
            echo "</div>";
            
            // Xóa session
            unset($_SESSION['test_reservation_id']);
            unset($_SESSION['test_room_id']);
            
            echo "<script>setTimeout(function(){location.reload();}, 2000);</script>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
            echo "<h4>❌ Error</h4>";
            echo "<p>" . $e->getMessage() . "</p>";
            echo "</div>";
        }
    }
    
    // 4. Test check-out
    if (isset($_SESSION['test_reservation_id'])) {
        echo "<h3>3. Test Check-out</h3>";
        echo "<form method='post'>";
        echo "<button type='submit' name='test_checkout' style='background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 5px;'>";
        echo "🚪 Check-out (Occupied → Available)";
        echo "</button>";
        echo "</form>";
    }
    
    if (isset($_POST['test_checkout']) && isset($_SESSION['test_reservation_id'])) {
        $reservation_id = $_SESSION['test_reservation_id'];
        $room_id = $_SESSION['test_room_id'];
        
        try {
            $pdo->beginTransaction();
            
            // 1. Cập nhật ngày check-out thực tế
            $stmt = $pdo->prepare("UPDATE Reservation SET ActualCheckOutDate = CURDATE() WHERE ReservationID = ?");
            $stmt->execute([$reservation_id]);
            
            // 2. Cập nhật trạng thái phòng thành Available
            $stmt = $pdo->prepare("UPDATE Room SET Status = 'Available' WHERE RoomID = ?");
            $stmt->execute([$room_id]);
            
            $pdo->commit();
            
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
            echo "<h4>✅ Check-out Completed!</h4>";
            echo "<p>Reservation ID: $reservation_id</p>";
            echo "<p>Room ID: $room_id</p>";
            echo "<p>Status: Occupied → Available</p>";
            echo "</div>";
            
            // Xóa session
            unset($_SESSION['test_reservation_id']);
            unset($_SESSION['test_room_id']);
            
            echo "<script>setTimeout(function(){location.reload();}, 2000);</script>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
            echo "<h4>❌ Error</h4>";
            echo "<p>" . $e->getMessage() . "</p>";
            echo "</div>";
        }
    }
    
    // 5. Hiển thị booking hiện tại
    echo "<h3>4. Booking hiện tại</h3>";
    
    $sql = "SELECT r.ReservationID, r.RoomID, rm.RoomName, r.CheckInDate, r.CheckOutDate, 
                   r.ActualCheckOutDate, a.FullName, p.PaymentStatus, rm.Status as RoomStatus
            FROM Reservation r
            JOIN Room rm ON r.RoomID = rm.RoomID
            LEFT JOIN Account a ON r.AccountID = a.AccountID
            LEFT JOIN Payment p ON p.ReservationID = r.ReservationID
            WHERE r.CheckInDate <= CURDATE() 
              AND r.CheckOutDate > CURDATE()
              AND r.ActualCheckOutDate IS NULL
            ORDER BY r.CheckInDate";
    
    $stmt = $pdo->query($sql);
    $active_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($active_bookings)) {
        echo "<p style='color: green;'>✅ Không có booking nào đang hoạt động</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>Room</th><th>Guest</th><th>Check-in</th><th>Check-out</th><th>Payment Status</th><th>Room Status</th>";
        echo "</tr>";
        foreach ($active_bookings as $booking) {
            $statusColor = '';
            switch($booking['RoomStatus']) {
                case 'Available': $statusColor = 'green'; break;
                case 'Reserved': $statusColor = 'orange'; break;
                case 'Occupied': $statusColor = 'red'; break;
                case 'Maintenance': $statusColor = 'gray'; break;
                default: $statusColor = 'blue';
            }
            
            echo "<tr>";
            echo "<td>{$booking['RoomName']}</td>";
            echo "<td>{$booking['FullName']}</td>";
            echo "<td>" . date('d/m/Y', strtotime($booking['CheckInDate'])) . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($booking['CheckOutDate'])) . "</td>";
            echo "<td>{$booking['PaymentStatus']}</td>";
            echo "<td style='color: $statusColor; font-weight: bold;'>{$booking['RoomStatus']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<hr>";
    echo "<p><a href='room.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>→ Test Room Page</a></p>";
    echo "<p><a href='debug_reserved_status.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>→ Debug Reserved Status</a></p>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
    echo "<h4>❌ Error</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
h2, h3 { color: #333; }
table { background: white; border-radius: 5px; }
th { background: #f8f9fa !important; }
td, th { padding: 8px; text-align: left; }
form { margin: 20px 0; }
button { cursor: pointer; }
</style> 