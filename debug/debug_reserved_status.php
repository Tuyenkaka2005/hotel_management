<?php
require 'config.php';

echo "<h2>🔍 Debug Reserved Status Issue</h2>";

try {
    // 1. Kiểm tra tất cả phòng và trạng thái hiện tại
    echo "<h3>1. Trạng thái phòng hiện tại</h3>";
    
    $sql = "SELECT r.RoomID, r.RoomName, r.RoomNumber, r.Status, rt.TypeName,
            (SELECT COUNT(*) FROM Reservation res 
             WHERE res.RoomID = r.RoomID 
               AND res.CheckInDate <= CURDATE() 
               AND res.CheckOutDate > CURDATE()
               AND res.ActualCheckOutDate IS NULL) AS ActiveBookings,
            (SELECT p.PaymentStatus FROM Reservation res 
             LEFT JOIN Payment p ON p.ReservationID = res.ReservationID
             WHERE res.RoomID = r.RoomID 
               AND res.CheckInDate <= CURDATE() 
               AND res.CheckOutDate > CURDATE()
               AND res.ActualCheckOutDate IS NULL
             LIMIT 1) AS PaymentStatus
            FROM Room r
            JOIN RoomType rt ON r.RoomTypeID = rt.RoomTypeID
            ORDER BY r.RoomID";
    
    $stmt = $pdo->query($sql);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Room</th><th>Type</th><th>DB Status</th><th>Active Bookings</th><th>Payment Status</th><th>Should Be</th><th>Action</th>";
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
        
        // Xác định trạng thái nên có
        $shouldBe = 'Available';
        if ($room['ActiveBookings'] > 0) {
            if ($room['PaymentStatus'] === 'Completed') {
                $shouldBe = 'Occupied';
            } else {
                $shouldBe = 'Reserved';
            }
        }
        
        $shouldBeColor = ($shouldBe === $room['Status']) ? 'green' : 'red';
        
        echo "<tr>";
        echo "<td>{$room['RoomName']} ({$room['RoomNumber']})</td>";
        echo "<td>{$room['TypeName']}</td>";
        echo "<td style='color: $statusColor; font-weight: bold;'>{$room['Status']}</td>";
        echo "<td>{$room['ActiveBookings']}</td>";
        echo "<td>{$room['PaymentStatus']}</td>";
        echo "<td style='color: $shouldBeColor; font-weight: bold;'>$shouldBe</td>";
        echo "<td>";
        if ($shouldBe !== $room['Status']) {
            echo "<form method='post' style='display: inline;'>";
            echo "<input type='hidden' name='fix_room' value='{$room['RoomID']}'>";
            echo "<input type='hidden' name='correct_status' value='$shouldBe'>";
            echo "<button type='submit' style='background: #28a745; color: white; border: none; padding: 5px 10px; border-radius: 3px;'>Fix</button>";
            echo "</form>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Xử lý sửa lỗi
    if (isset($_POST['fix_room'])) {
        $room_id = (int)$_POST['fix_room'];
        $correct_status = $_POST['correct_status'];
        
        $stmt = $pdo->prepare("UPDATE Room SET Status = ? WHERE RoomID = ?");
        $result = $stmt->execute([$correct_status, $room_id]);
        
        if ($result) {
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
            echo "<h4>✅ Fixed Successfully!</h4>";
            echo "<p>Room ID {$room_id} has been set to {$correct_status}.</p>";
            echo "</div>";
            echo "<script>setTimeout(function(){location.reload();}, 2000);</script>";
        } else {
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
            echo "<h4>❌ Error</h4>";
            echo "<p>Failed to update room status.</p>";
            echo "</div>";
        }
    }
    
    // 3. Auto fix tất cả
    echo "<h3>2. Auto Fix All Room Statuses</h3>";
    echo "<form method='post'>";
    echo "<button type='submit' name='auto_fix_all' style='background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px;'>";
    echo "🔧 Auto Fix All Room Statuses";
    echo "</button>";
    echo "</form>";
    
    if (isset($_POST['auto_fix_all'])) {
        // Cập nhật phòng có booking đã thanh toán thành 'Occupied'
        $sql = "UPDATE Room r 
                SET r.Status = 'Occupied'
                WHERE r.RoomID IN (
                    SELECT DISTINCT res.RoomID 
                    FROM Reservation res
                    JOIN Payment p ON p.ReservationID = res.ReservationID
                    WHERE res.CheckInDate <= CURDATE() 
                      AND res.CheckOutDate > CURDATE()
                      AND res.ActualCheckOutDate IS NULL
                      AND p.PaymentStatus = 'Completed'
                )
                AND r.Status != 'Maintenance'";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $occupied_updated = $stmt->rowCount();
        
        // Cập nhật phòng có booking chưa thanh toán thành 'Reserved'
        $sql = "UPDATE Room r 
                SET r.Status = 'Reserved'
                WHERE r.RoomID IN (
                    SELECT DISTINCT res.RoomID 
                    FROM Reservation res
                    LEFT JOIN Payment p ON p.ReservationID = res.ReservationID
                    WHERE res.CheckInDate <= CURDATE() 
                      AND res.CheckOutDate > CURDATE()
                      AND res.ActualCheckOutDate IS NULL
                      AND (p.PaymentStatus IS NULL OR p.PaymentStatus != 'Completed')
                )
                AND r.Status != 'Maintenance'
                AND r.Status != 'Occupied'";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $reserved_updated = $stmt->rowCount();
        
        // Cập nhật phòng đã check-out thành 'Available'
        $sql = "UPDATE Room r 
                SET r.Status = 'Available'
                WHERE r.RoomID IN (
                    SELECT DISTINCT res.RoomID 
                    FROM Reservation res
                    WHERE res.ActualCheckOutDate IS NOT NULL 
                      AND res.ActualCheckOutDate <= CURDATE()
                )
                AND r.Status != 'Maintenance'";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $available_updated = $stmt->rowCount();
        
        // Cập nhật phòng không có booking thành 'Available'
        $sql = "UPDATE Room r 
                SET r.Status = 'Available'
                WHERE r.RoomID NOT IN (
                    SELECT DISTINCT res.RoomID 
                    FROM Reservation res
                    WHERE res.CheckInDate <= CURDATE() 
                      AND res.CheckOutDate > CURDATE()
                      AND res.ActualCheckOutDate IS NULL
                )
                AND r.Status != 'Maintenance'";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $auto_available_updated = $stmt->rowCount();
        
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
        echo "<h4>✅ Auto Fix Completed!</h4>";
        echo "<ul>";
        echo "<li><strong>Rooms set to Occupied:</strong> $occupied_updated</li>";
        echo "<li><strong>Rooms set to Reserved:</strong> $reserved_updated</li>";
        echo "<li><strong>Rooms set to Available (check-out):</strong> $available_updated</li>";
        echo "<li><strong>Rooms auto-set to Available:</strong> $auto_available_updated</li>";
        echo "</ul>";
        echo "</div>";
        echo "<script>setTimeout(function(){location.reload();}, 2000);</script>";
    }
    
    // 4. Kiểm tra booking hiện tại
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
            ORDER BY r.CheckInDate";
    
    $stmt = $pdo->query($sql);
    $active_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($active_bookings)) {
        echo "<p style='color: green;'>✅ Không có booking nào đang hoạt động</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>Room</th><th>Guest</th><th>Check-in</th><th>Check-out</th><th>Payment Status</th><th>Room Status</th><th>Should Be</th>";
        echo "</tr>";
        foreach ($active_bookings as $booking) {
            $roomShouldBe = ($booking['PaymentStatus'] === 'Completed') ? 'Occupied' : 'Reserved';
            $statusColor = ($booking['RoomStatus'] === $roomShouldBe) ? 'green' : 'red';
            
            echo "<tr>";
            echo "<td>{$booking['RoomName']}</td>";
            echo "<td>{$booking['FullName']}</td>";
            echo "<td>" . date('d/m/Y', strtotime($booking['CheckInDate'])) . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($booking['CheckOutDate'])) . "</td>";
            echo "<td>{$booking['PaymentStatus']}</td>";
            echo "<td style='color: $statusColor; font-weight: bold;'>{$booking['RoomStatus']}</td>";
            echo "<td style='font-weight: bold;'>$roomShouldBe</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 5. Test tạo booking mới
    echo "<h3>4. Test Tạo Booking Mới</h3>";
    echo "<form method='post'>";
    echo "<button type='submit' name='test_booking' style='background: #ffc107; color: black; border: none; padding: 10px 20px; border-radius: 5px;'>";
    echo "🧪 Test Create New Booking";
    echo "</button>";
    echo "</form>";
    
    if (isset($_POST['test_booking'])) {
        // Tạo một booking test
        $test_room_id = 1; // Giả sử có phòng ID 1
        
        // Kiểm tra xem phòng có tồn tại và Available không
        $stmt = $pdo->prepare("SELECT RoomID, Status FROM Room WHERE RoomID = ?");
        $stmt->execute([$test_room_id]);
        $test_room = $stmt->fetch();
        
        if ($test_room && $test_room['Status'] === 'Available') {
            // Tạo reservation test
            $checkin = date('Y-m-d', strtotime('+1 day'));
            $checkout = date('Y-m-d', strtotime('+3 days'));
            $total_amount = 1000000;
            
            $stmt = $pdo->prepare("INSERT INTO Reservation (CheckInDate, CheckOutDate, TotalAmount, AccountID, RoomID) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$checkin, $checkout, $total_amount, 1, $test_room_id]); // Giả sử AccountID = 1
            $reservation_id = $pdo->lastInsertId();
            
            // Cập nhật trạng thái phòng thành Reserved
            $stmt = $pdo->prepare("UPDATE Room SET Status = 'Reserved' WHERE RoomID = ?");
            $stmt->execute([$test_room_id]);
            
            // Tạo payment pending
            $stmt = $pdo->prepare("INSERT INTO Payment (PaymentMethod, PaymentStatus, ReservationID) VALUES (?, ?, ?)");
            $stmt->execute(['Bank Transfer', 'Pending', $reservation_id]);
            
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
            echo "<h4>✅ Test Booking Created!</h4>";
            echo "<p>Created test booking for Room ID $test_room_id with status 'Reserved'</p>";
            echo "</div>";
            echo "<script>setTimeout(function(){location.reload();}, 2000);</script>";
        } else {
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
            echo "<h4>❌ Error</h4>";
            echo "<p>Room ID $test_room_id is not available for testing.</p>";
            echo "</div>";
        }
    }
    
    echo "<hr>";
    echo "<p><a href='room.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>→ Test Room Page</a></p>";
    echo "<p><a href='update_room_status_php.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>→ Update Room Status</a></p>";
    
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