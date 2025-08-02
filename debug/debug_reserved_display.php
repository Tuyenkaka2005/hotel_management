<?php
require 'config.php';

echo "<h2>🔍 Debug Reserved Display Issue</h2>";

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
    
    // 2. Kiểm tra trạng thái phòng trong database
    echo "<h3>2. Trạng thái phòng trong Database</h3>";
    $sql = "SELECT r.RoomID, r.RoomName, r.RoomNumber, r.Status, rt.TypeName
            FROM Room r
            JOIN RoomType rt ON r.RoomTypeID = rt.RoomTypeID
            ORDER BY r.RoomID";
    
    $stmt = $pdo->query($sql);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Room</th><th>Type</th><th>DB Status</th><th>Color</th>";
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
    
    // 3. Kiểm tra logic hiển thị trong room.php
    echo "<h3>3. Logic hiển thị trong room.php</h3>";
    
    $today = date('Y-m-d');
    $sql = "SELECT r.*, rt.TypeName,
            (
                SELECT ImagePath FROM RoomImage 
                WHERE RoomID = r.RoomID LIMIT 1
            ) AS FirstImage,
            (
                SELECT COUNT(*) FROM Reservation res
                WHERE res.RoomID = r.RoomID 
                  AND res.CheckInDate <= '$today' 
                  AND res.CheckOutDate > '$today'
            ) AS IsBookedToday,
            CASE 
                WHEN r.Status = 'Maintenance' THEN 'Maintenance'
                WHEN r.Status = 'Occupied' THEN 'Occupied'
                WHEN r.Status = 'Reserved' THEN 'Reserved'
                ELSE 'Available'
            END AS DisplayStatus
            FROM Room r
            JOIN RoomType rt ON r.RoomTypeID = rt.RoomTypeID
            ORDER BY r.RoomID";
    
    $stmt = $pdo->query($sql);
    $rooms_display = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Room</th><th>DB Status</th><th>Display Status</th><th>IsBookedToday</th><th>Can Book?</th>";
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
        echo "<td style='font-weight: bold;'>{$room['Status']}</td>";
        echo "<td style='color: $statusColor; font-weight: bold;'>{$room['DisplayStatus']}</td>";
        echo "<td>{$room['IsBookedToday']}</td>";
        echo "<td style='color: $canBookColor; font-weight: bold;'>$canBook</td>";
        echo "</tr>";
    }
    echo "</table>";
    
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
    
    // 5. Test tạo booking với trạng thái Reserved
    echo "<h3>5. Test Tạo Booking với Status Reserved</h3>";
    
    // Tìm phòng Available
    $available_rooms = array_filter($rooms, function($room) {
        return $room['Status'] === 'Available';
    });
    
    if (!empty($available_rooms)) {
        echo "<form method='post'>";
        echo "<select name='test_room_id'>";
        foreach ($available_rooms as $room) {
            echo "<option value='{$room['RoomID']}'>{$room['RoomName']} ({$room['RoomNumber']})</option>";
        }
        echo "</select>";
        echo "<button type='submit' name='create_test_booking' style='background: #ffc107; color: black; border: none; padding: 10px 20px; border-radius: 5px;'>";
        echo "Create Test Booking (Should be Reserved)";
        echo "</button>";
        echo "</form>";
    } else {
        echo "<p style='color: orange;'>⚠️ Không có phòng Available để test</p>";
    }
    
    if (isset($_POST['create_test_booking'])) {
        $test_room_id = (int)$_POST['test_room_id'];
        
        // Lấy account đầu tiên
        $stmt = $pdo->prepare("SELECT AccountID FROM Account ORDER BY AccountID LIMIT 1");
        $stmt->execute();
        $account = $stmt->fetch();
        
        if (!$account) {
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
            echo "<h4>❌ Error</h4>";
            echo "<p>Không có account nào trong database. Vui lòng tạo account trước khi test booking.</p>";
            echo "</div>";
        } else {
            try {
                $pdo->beginTransaction();
                
                // Tạo reservation test
                $checkin = date('Y-m-d', strtotime('+1 day'));
                $checkout = date('Y-m-d', strtotime('+3 days'));
                $total_amount = 1000000;
                
                $stmt = $pdo->prepare("INSERT INTO Reservation (CheckInDate, CheckOutDate, TotalAmount, AccountID, RoomID) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$checkin, $checkout, $total_amount, $account['AccountID'], $test_room_id]);
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
            echo "<script>setTimeout(function(){location.reload();}, 2000);</script>";
            
            } catch (Exception $e) {
                $pdo->rollBack();
                echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
                echo "<h4>❌ Error</h4>";
                echo "<p>" . $e->getMessage() . "</p>";
                echo "</div>";
            }
        }
    }
    
    // 6. Kiểm tra logic booking_process.php
    echo "<h3>6. Kiểm tra logic booking_process.php</h3>";
    echo "<div style='background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
    echo "<h4>📝 Logic hiện tại:</h4>";
    echo "<pre style='background: white; padding: 10px; border-radius: 3px;'>";
    echo "// ✅ Cập nhật trạng thái phòng thành 'Reserved' cho tất cả phương thức thanh toán\n";
    echo "// (Chỉ admin mới có thể xác nhận thanh toán và chuyển sang 'Occupied')\n";
    echo "\$update_stmt = \$conn->prepare(\"UPDATE Room SET Status = 'Reserved' WHERE RoomID = ?\");\n";
    echo "\$update_stmt->bind_param(\"i\", \$room_id);\n";
    echo "\$update_stmt->execute();";
    echo "</pre>";
    echo "</div>";
    
    // 7. Kiểm tra logic hiển thị trong room.php
    echo "<h3>7. Kiểm tra logic hiển thị trong room.php</h3>";
    echo "<div style='background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
    echo "<h4>📝 Logic hiển thị:</h4>";
    echo "<pre style='background: white; padding: 10px; border-radius: 3px;'>";
    echo "CASE \n";
    echo "    WHEN r.Status = 'Maintenance' THEN 'Maintenance'\n";
    echo "    WHEN r.Status = 'Occupied' THEN 'Occupied'\n";
    echo "    WHEN r.Status = 'Reserved' THEN 'Reserved'\n";
    echo "    ELSE 'Available'\n";
    echo "END AS DisplayStatus";
    echo "</pre>";
    echo "</div>";
    
    echo "<hr>";
    echo "<p><a href='room.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>→ Test Room Page</a></p>";
    echo "<p><a href='test_booking_workflow.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>→ Test Booking Workflow</a></p>";
    
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
pre { font-family: 'Courier New', monospace; font-size: 12px; }
</style> 