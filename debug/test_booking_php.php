<?php
require 'config.php';

echo "<h2>🧪 Test Booking.php Logic</h2>";

try {
    // 1. Kiểm tra account có sẵn
    echo "<h3>1. Kiểm tra Account có sẵn</h3>";
    
    $sql = "SELECT AccountID, FullName, Email FROM Account ORDER BY AccountID LIMIT 5";
    $stmt = $pdo->query($sql);
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($accounts)) {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
        echo "<h4>❌ Error</h4>";
        echo "<p>Không có account nào trong database. Vui lòng tạo account trước khi test booking.</p>";
        echo "</div>";
        exit;
    }
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Account ID</th><th>Full Name</th><th>Email</th>";
    echo "</tr>";
    foreach ($accounts as $account) {
        echo "<tr>";
        echo "<td>{$account['AccountID']}</td>";
        echo "<td>{$account['FullName']}</td>";
        echo "<td>{$account['Email']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Sử dụng account đầu tiên
    $test_account_id = $accounts[0]['AccountID'];
    echo "<p style='color: green;'>✅ Sử dụng Account ID: $test_account_id</p>";
    
    // 2. Hiển thị trạng thái phòng hiện tại
    echo "<h3>2. Trạng thái phòng hiện tại</h3>";
    
    $sql = "SELECT r.RoomID, r.RoomName, r.RoomNumber, r.Status, rt.TypeName, r.PricePerNight
            FROM Room r
            JOIN RoomType rt ON r.RoomTypeID = rt.RoomTypeID
            ORDER BY r.RoomID";
    
    $stmt = $pdo->query($sql);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Room</th><th>Type</th><th>Status</th><th>Price</th><th>Action</th>";
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
        echo "<td>" . number_format($room['PricePerNight']) . " VNĐ</td>";
        echo "<td>";
        if ($room['Status'] === 'Available') {
            echo "<form method='post' style='display: inline;'>";
            echo "<input type='hidden' name='test_booking_php' value='{$room['RoomID']}'>";
            echo "<button type='submit' style='background: #28a745; color: white; border: none; padding: 5px 10px; border-radius: 3px;'>Test Booking.php</button>";
            echo "</form>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 3. Test logic booking.php
    if (isset($_POST['test_booking_php'])) {
        $room_id = (int)$_POST['test_booking_php'];
        
        try {
            // Lấy thông tin phòng
            $stmt = $pdo->prepare("SELECT PricePerNight, Status FROM Room WHERE RoomID = ?");
            $stmt->execute([$room_id]);
            $room_info = $stmt->fetch();
            
            if (!$room_info) {
                throw new Exception("Room does not exist.");
            }
            
            if ($room_info['Status'] !== 'Available') {
                throw new Exception("Room is not available. Current status: " . $room_info['Status']);
            }
            
            echo "<div style='background: #e2e3e5; border: 1px solid #d6d8db; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
            echo "<h4>📋 Test Booking.php Logic:</h4>";
            echo "<p><strong>Room ID:</strong> $room_id</p>";
            echo "<p><strong>Account ID:</strong> $test_account_id</p>";
            echo "<p><strong>Current Status:</strong> {$room_info['Status']}</p>";
            echo "<p><strong>Price per night:</strong> " . number_format($room_info['PricePerNight']) . " VNĐ</p>";
            echo "</div>";
            
            // Bắt đầu transaction
            $pdo->beginTransaction();
            
            // 1. Tạo reservation
            $checkin = date('Y-m-d', strtotime('+1 day'));
            $checkout = date('Y-m-d', strtotime('+3 days'));
            $days = (strtotime($checkout) - strtotime($checkin)) / (60 * 60 * 24);
            if ($days <= 0) $days = 1;
            $total_amount = $room_info['PricePerNight'] * $days;
            
            $stmt = $pdo->prepare("INSERT INTO Reservation (CheckInDate, CheckOutDate, TotalAmount, AccountID, RoomID) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$checkin, $checkout, $total_amount, $test_account_id, $room_id]);
            $reservation_id = $pdo->lastInsertId();
            
            echo "<div style='background: #e2e3e5; border: 1px solid #d6d8db; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
            echo "<h4>✅ Step 1: Reservation Created</h4>";
            echo "<p><strong>Reservation ID:</strong> $reservation_id</p>";
            echo "<p><strong>Check-in:</strong> $checkin</p>";
            echo "<p><strong>Check-out:</strong> $checkout</p>";
            echo "<p><strong>Total Amount:</strong> " . number_format($total_amount) . " VNĐ</p>";
            echo "</div>";
            
            // 2. Tạo payment
            $payment_method = 'Cash';
            $payment_status = 'Pending';
            
            $stmt = $pdo->prepare("INSERT INTO Payment (PaymentMethod, PaymentStatus, ReservationID) VALUES (?, ?, ?)");
            $stmt->execute([$payment_method, $payment_status, $reservation_id]);
            
            echo "<div style='background: #e2e3e5; border: 1px solid #d6d8db; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
            echo "<h4>💰 Step 2: Payment Created</h4>";
            echo "<p><strong>Payment Method:</strong> $payment_method</p>";
            echo "<p><strong>Payment Status:</strong> $payment_status</p>";
            echo "</div>";
            
            // 3. Cập nhật trạng thái phòng thành Reserved
            $stmt = $pdo->prepare("UPDATE Room SET Status = 'Reserved' WHERE RoomID = ?");
            $stmt->execute([$room_id]);
            
            echo "<div style='background: #e2e3e5; border: 1px solid #d6d8db; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
            echo "<h4>🔄 Step 3: Room Status Updated</h4>";
            echo "<p><strong>Room ID:</strong> $room_id</p>";
            echo "<p><strong>New Status:</strong> Reserved</p>";
            echo "</div>";
            
            // Commit transaction
            $pdo->commit();
            
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
            echo "<h4>✅ Booking.php Logic Test Completed!</h4>";
            echo "<p>Room has been booked and status changed to 'Reserved'</p>";
            echo "</div>";
            
            echo "<script>setTimeout(function(){location.reload();}, 3000);</script>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
            echo "<h4>❌ Error</h4>";
            echo "<p>" . $e->getMessage() . "</p>";
            echo "</div>";
        }
    }
    
    // 4. Kiểm tra booking vừa tạo
    echo "<h3>3. Booking hiện tại</h3>";
    
    $sql = "SELECT r.ReservationID, r.RoomID, rm.RoomName, r.CheckInDate, r.CheckOutDate, 
                   r.ActualCheckOutDate, a.FullName, p.PaymentStatus, rm.Status as RoomStatus
            FROM Reservation r
            JOIN Room rm ON r.RoomID = rm.RoomID
            LEFT JOIN Account a ON r.AccountID = a.AccountID
            LEFT JOIN Payment p ON p.ReservationID = r.ReservationID
            WHERE r.CheckInDate <= CURDATE() 
              AND r.CheckOutDate > CURDATE()
              AND r.ActualCheckOutDate IS NULL
            ORDER BY r.CheckInDate DESC";
    
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
    echo "<p><a href='test_booking_direct.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>→ Test Booking Direct</a></p>";
    
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