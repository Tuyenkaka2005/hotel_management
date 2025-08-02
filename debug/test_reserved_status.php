<?php
require 'config.php';

echo "<h2>🧪 Test Reserved Status Display</h2>";

try {
    // 1. Kiểm tra cấu trúc bảng Room
    echo "<h3>1. Cấu trúc bảng Room</h3>";
    $stmt = $pdo->query("DESCRIBE Room");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'Status') {
            echo "<p style='color: green;'>✅ Status column: {$column['Type']}</p>";
            break;
        }
    }
    
    // 2. Kiểm tra trạng thái phòng hiện tại
    echo "<h3>2. Trạng thái phòng hiện tại</h3>";
    $sql = "SELECT r.RoomID, r.RoomName, r.RoomNumber, r.Status, rt.TypeName
            FROM Room r
            JOIN RoomType rt ON r.RoomTypeID = rt.RoomTypeID
            ORDER BY r.RoomID";
    
    $stmt = $pdo->query($sql);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Room</th><th>Type</th><th>Status</th><th>Color</th>";
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
        echo "<td style='background-color: $statusColor; color: white;'>$statusColor</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 3. Test tạo booking với trạng thái Reserved
    echo "<h3>3. Test Tạo Booking với Status Reserved</h3>";
    echo "<form method='post'>";
    echo "<select name='test_room_id'>";
    foreach ($rooms as $room) {
        if ($room['Status'] === 'Available') {
            echo "<option value='{$room['RoomID']}'>{$room['RoomName']} ({$room['RoomNumber']})</option>";
        }
    }
    echo "</select>";
    echo "<button type='submit' name='create_reserved_booking' style='background: #ffc107; color: black; border: none; padding: 10px 20px; border-radius: 5px;'>";
    echo "Create Reserved Booking";
    echo "</button>";
    echo "</form>";
    
    if (isset($_POST['create_reserved_booking'])) {
        $test_room_id = (int)$_POST['test_room_id'];
        
        // Tạo reservation test
        $checkin = date('Y-m-d', strtotime('+1 day'));
        $checkout = date('Y-m-d', strtotime('+3 days'));
        $total_amount = 1000000;
        
        try {
            // Bắt đầu transaction
            $pdo->beginTransaction();
            
            // Tạo reservation
            $stmt = $pdo->prepare("INSERT INTO Reservation (CheckInDate, CheckOutDate, TotalAmount, AccountID, RoomID) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$checkin, $checkout, $total_amount, 1, $test_room_id]); // Giả sử AccountID = 1
            $reservation_id = $pdo->lastInsertId();
            
            // Tạo payment pending (Bank Transfer)
            $stmt = $pdo->prepare("INSERT INTO Payment (PaymentMethod, PaymentStatus, ReservationID) VALUES (?, ?, ?)");
            $stmt->execute(['Bank Transfer', 'Pending', $reservation_id]);
            
            // Cập nhật trạng thái phòng thành Reserved
            $stmt = $pdo->prepare("UPDATE Room SET Status = 'Reserved' WHERE RoomID = ?");
            $stmt->execute([$test_room_id]);
            
            // Commit transaction
            $pdo->commit();
            
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
            echo "<h4>✅ Reserved Booking Created!</h4>";
            echo "<p>Created test booking for Room ID $test_room_id with status 'Reserved'</p>";
            echo "<p>Reservation ID: $reservation_id</p>";
            echo "</div>";
            echo "<script>setTimeout(function(){location.reload();}, 2000);</script>";
            
        } catch (Exception $e) {
            // Rollback transaction
            $pdo->rollBack();
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
            echo "<h4>❌ Error</h4>";
            echo "<p>" . $e->getMessage() . "</p>";
            echo "</div>";
        }
    }
    
    // 4. Kiểm tra booking hiện tại
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
    
    // 5. Test hiển thị trạng thái
    echo "<h3>5. Test Hiển thị Trạng thái</h3>";
    
    $sql = "SELECT r.RoomID, r.RoomName, r.RoomNumber, r.Status,
            CASE 
                WHEN r.Status = 'Maintenance' THEN 'Maintenance'
                WHEN r.Status = 'Occupied' THEN 'Occupied'
                WHEN r.Status = 'Reserved' THEN 'Reserved'
                ELSE 'Available'
            END AS DisplayStatus
            FROM Room r
            ORDER BY r.RoomID";
    
    $stmt = $pdo->query($sql);
    $rooms_display = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Room</th><th>DB Status</th><th>Display Status</th><th>Can Book?</th>";
    echo "</tr>";
    
    foreach ($rooms_display as $room) {
        $canBook = ($room['DisplayStatus'] === 'Available') ? 'Yes' : 'No';
        $canBookColor = ($canBook === 'Yes') ? 'green' : 'red';
        
        $statusColor = '';
        switch($room['DisplayStatus']) {
            case 'Available': $statusColor = 'green'; break;
            case 'Reserved': $statusColor = 'orange'; break;
            case 'Occupied': $statusColor = 'red'; break;
            case 'Maintenance': $statusColor = 'gray'; break;
            default: $statusColor = 'blue';
        }
        
        echo "<tr>";
        echo "<td>{$room['RoomName']} ({$room['RoomNumber']})</td>";
        echo "<td style='color: $statusColor; font-weight: bold;'>{$room['Status']}</td>";
        echo "<td style='color: $statusColor; font-weight: bold;'>{$room['DisplayStatus']}</td>";
        echo "<td style='color: $canBookColor; font-weight: bold;'>$canBook</td>";
        echo "</tr>";
    }
    echo "</table>";
    
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