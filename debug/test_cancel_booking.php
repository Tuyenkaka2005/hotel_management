<?php
require 'config.php';

echo "<h2>🧪 Test Cancel Booking Function</h2>";

try {
    // 1. Kiểm tra account có sẵn
    echo "<h3>1. Kiểm tra Account có sẵn</h3>";
    
    $sql = "SELECT AccountID, FullName, Email FROM Account ORDER BY AccountID LIMIT 5";
    $stmt = $pdo->query($sql);
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($accounts)) {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
        echo "<h4>❌ Error</h4>";
        echo "<p>Không có account nào trong database. Vui lòng tạo account trước khi test.</p>";
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
    
    // 2. Hiển thị booking hiện tại
    echo "<h3>2. Booking hiện tại</h3>";
    
    $sql = "SELECT r.ReservationID, r.RoomID, rm.RoomName, r.CheckInDate, r.CheckOutDate, 
                   r.ActualCheckOutDate, a.FullName, p.PaymentStatus, rm.Status as RoomStatus
            FROM Reservation r
            JOIN Room rm ON r.RoomID = rm.RoomID
            LEFT JOIN Account a ON r.AccountID = a.AccountID
            LEFT JOIN Payment p ON p.ReservationID = r.ReservationID
            ORDER BY r.CheckInDate DESC";
    
    $stmt = $pdo->query($sql);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($bookings)) {
        echo "<p style='color: orange;'>⚠️ Không có booking nào để test</p>";
        echo "<p><a href='test_booking_direct.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>→ Tạo Booking Test</a></p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>Reservation ID</th><th>Room</th><th>Guest</th><th>Check-in</th><th>Check-out</th><th>Payment Status</th><th>Room Status</th><th>Can Cancel?</th><th>Action</th>";
        echo "</tr>";
        
        foreach ($bookings as $booking) {
            $today = date('Y-m-d');
            $checkin_date = $booking['CheckInDate'];
            $can_cancel = ($checkin_date > $today && !$booking['ActualCheckOutDate']);
            
            $statusColor = '';
            switch($booking['RoomStatus']) {
                case 'Available': $statusColor = 'green'; break;
                case 'Reserved': $statusColor = 'orange'; break;
                case 'Occupied': $statusColor = 'red'; break;
                case 'Maintenance': $statusColor = 'gray'; break;
                default: $statusColor = 'blue';
            }
            
            echo "<tr>";
            echo "<td>{$booking['ReservationID']}</td>";
            echo "<td>{$booking['RoomName']}</td>";
            echo "<td>{$booking['FullName']}</td>";
            echo "<td>" . date('d/m/Y', strtotime($booking['CheckInDate'])) . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($booking['CheckOutDate'])) . "</td>";
            echo "<td>{$booking['PaymentStatus']}</td>";
            echo "<td style='color: $statusColor; font-weight: bold;'>{$booking['RoomStatus']}</td>";
            echo "<td>" . ($can_cancel ? 'Yes' : 'No') . "</td>";
            echo "<td>";
            if ($can_cancel) {
                echo "<form method='post' style='display: inline;'>";
                echo "<input type='hidden' name='test_cancel_booking' value='{$booking['ReservationID']}'>";
                echo "<button type='submit' style='background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px;'>Test Cancel</button>";
                echo "</form>";
            } else {
                echo "<span style='color: #6c757d;'>Cannot Cancel</span>";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 3. Test hủy booking
    if (isset($_POST['test_cancel_booking'])) {
        $reservation_id = (int)$_POST['test_cancel_booking'];
        
        try {
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
            
            echo "<div style='background: #e2e3e5; border: 1px solid #d6d8db; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
            echo "<h4>📋 Test Cancel Booking:</h4>";
            echo "<p><strong>Reservation ID:</strong> $reservation_id</p>";
            echo "<p><strong>Room:</strong> {$booking['RoomName']}</p>";
            echo "<p><strong>Check-in:</strong> {$booking['CheckInDate']}</p>";
            echo "<p><strong>Check-out:</strong> {$booking['CheckOutDate']}</p>";
            echo "<p><strong>Payment Status:</strong> {$booking['PaymentStatus']}</p>";
            echo "</div>";
            
            // Kiểm tra xem có thể hủy không
            $today = date('Y-m-d');
            if ($booking['CheckInDate'] <= $today) {
                throw new Exception("Cannot cancel booking that has already started.");
            }
            
            // Bắt đầu transaction
            $pdo->beginTransaction();
            
            // 1. Cập nhật trạng thái phòng về Available
            $stmt = $pdo->prepare("UPDATE Room SET Status = 'Available' WHERE RoomID = ?");
            $stmt->execute([$booking['RoomID']]);
            
            echo "<div style='background: #e2e3e5; border: 1px solid #d6d8db; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
            echo "<h4>🔄 Step 1: Room Status Updated</h4>";
            echo "<p><strong>Room ID:</strong> {$booking['RoomID']}</p>";
            echo "<p><strong>New Status:</strong> Available</p>";
            echo "</div>";
            
            // 2. Xóa payment record
            $stmt = $pdo->prepare("DELETE FROM Payment WHERE ReservationID = ?");
            $stmt->execute([$reservation_id]);
            
            echo "<div style='background: #e2e3e5; border: 1px solid #d6d8db; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
            echo "<h4>💰 Step 2: Payment Record Deleted</h4>";
            echo "<p><strong>Reservation ID:</strong> $reservation_id</p>";
            echo "</div>";
            
            // 3. Xóa reservation
            $stmt = $pdo->prepare("DELETE FROM Reservation WHERE ReservationID = ?");
            $stmt->execute([$reservation_id]);
            
            echo "<div style='background: #e2e3e5; border: 1px solid #d6d8db; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
            echo "<h4>🗑️ Step 3: Reservation Deleted</h4>";
            echo "<p><strong>Reservation ID:</strong> $reservation_id</p>";
            echo "</div>";
            
            // Commit transaction
            $pdo->commit();
            
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
            echo "<h4>✅ Booking Cancelled Successfully!</h4>";
            echo "<p>Room has been freed and is now available for booking</p>";
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
    
    // 4. Kiểm tra trạng thái phòng sau khi hủy
    echo "<h3>3. Trạng thái phòng sau khi hủy</h3>";
    
    $sql = "SELECT r.RoomID, r.RoomName, r.RoomNumber, r.Status, rt.TypeName
            FROM Room r
            JOIN RoomType rt ON r.RoomTypeID = rt.RoomTypeID
            ORDER BY r.RoomID";
    
    $stmt = $pdo->query($sql);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Room</th><th>Type</th><th>Status</th>";
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
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<p><a href='my_bookings.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>→ Test My Bookings Page</a></p>";
    echo "<p><a href='admin/booking_manage.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>→ Test Admin Booking Management</a></p>";
    
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