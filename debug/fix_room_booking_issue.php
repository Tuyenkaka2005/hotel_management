<?php
require 'config.php';

echo "<h2>🔧 Fix Room Booking Issue</h2>";

try {
    // 1. Kiểm tra phòng đã check-out nhưng vẫn không thể đặt
    echo "<h3>1. Kiểm tra phòng đã check-out</h3>";
    
    $sql = "SELECT r.RoomID, r.RoomName, r.RoomNumber, r.Status, rt.TypeName,
            res.ReservationID, res.CheckInDate, res.CheckOutDate, res.ActualCheckOutDate,
            a.FullName
            FROM Room r
            JOIN RoomType rt ON r.RoomTypeID = rt.RoomTypeID
            LEFT JOIN Reservation res ON r.RoomID = res.RoomID 
                AND res.CheckInDate <= CURDATE() 
                AND res.CheckOutDate > CURDATE()
                AND res.ActualCheckOutDate IS NULL
            LEFT JOIN Account a ON res.AccountID = a.AccountID
            WHERE r.Status != 'Available'
            ORDER BY r.RoomID";
    
    $stmt = $pdo->query($sql);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Room</th><th>Type</th><th>Status</th><th>Guest</th><th>Check-out Date</th><th>Actual Check-out</th><th>Action</th>";
    echo "</tr>";
    
    foreach ($rooms as $room) {
        $statusColor = '';
        switch($room['Status']) {
            case 'Available': $statusColor = 'green'; break;
            case 'Occupied': $statusColor = 'red'; break;
            case 'Maintenance': $statusColor = 'gray'; break;
            default: $statusColor = 'blue';
        }
        
        echo "<tr>";
        echo "<td>{$room['RoomName']} ({$room['RoomNumber']})</td>";
        echo "<td>{$room['TypeName']}</td>";
        echo "<td style='color: $statusColor; font-weight: bold;'>{$room['Status']}</td>";
        echo "<td>" . ($room['FullName'] ?: 'N/A') . "</td>";
        echo "<td>" . ($room['CheckOutDate'] ? date('d/m/Y', strtotime($room['CheckOutDate'])) : 'N/A') . "</td>";
        echo "<td>" . ($room['ActualCheckOutDate'] ? date('d/m/Y', strtotime($room['ActualCheckOutDate'])) : 'N/A') . "</td>";
        echo "<td>";
        if ($room['ActualCheckOutDate'] && $room['Status'] != 'Available') {
            echo "<form method='post' style='display: inline;'>";
            echo "<input type='hidden' name='fix_room' value='{$room['RoomID']}'>";
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
        
        // Kiểm tra xem phòng này có đã check-out thực tế chưa
        $sql = "SELECT r.RoomID, r.Status, res.ActualCheckOutDate
                FROM Room r
                LEFT JOIN Reservation res ON r.RoomID = res.RoomID 
                    AND res.ActualCheckOutDate IS NOT NULL
                    AND res.ActualCheckOutDate <= CURDATE()
                WHERE r.RoomID = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$room_id]);
        $room_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($room_info && $room_info['ActualCheckOutDate']) {
            // Cập nhật trạng thái phòng thành Available
            $update_stmt = $pdo->prepare("UPDATE Room SET Status = 'Available' WHERE RoomID = ?");
            $result = $update_stmt->execute([$room_id]);
            
            if ($result) {
                echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
                echo "<h4>✅ Fixed Successfully!</h4>";
                echo "<p>Room ID {$room_id} has been set to Available.</p>";
                echo "</div>";
                echo "<script>setTimeout(function(){location.reload();}, 2000);</script>";
            } else {
                echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
                echo "<h4>❌ Error</h4>";
                echo "<p>Failed to update room status.</p>";
                echo "</div>";
            }
        } else {
            echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
            echo "<h4>⚠️ Warning</h4>";
            echo "<p>This room has not been checked out yet.</p>";
            echo "</div>";
        }
    }
    
    // 3. Auto fix tất cả phòng đã check-out
    echo "<h3>2. Auto Fix All Checked-out Rooms</h3>";
    echo "<form method='post'>";
    echo "<button type='submit' name='auto_fix_all' style='background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px;'>";
    echo "🔧 Auto Fix All Checked-out Rooms";
    echo "</button>";
    echo "</form>";
    
    if (isset($_POST['auto_fix_all'])) {
        // Tìm tất cả phòng đã check-out nhưng vẫn có trạng thái không phải Available
        $sql = "UPDATE Room r 
                SET r.Status = 'Available'
                WHERE r.RoomID IN (
                    SELECT DISTINCT res.RoomID 
                    FROM Reservation res
                    WHERE res.ActualCheckOutDate IS NOT NULL 
                      AND res.ActualCheckOutDate <= CURDATE()
                )
                AND r.Status != 'Available'
                AND r.Status != 'Maintenance'";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute();
        $affected_rows = $stmt->rowCount();
        
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
        echo "<h4>✅ Auto Fix Completed!</h4>";
        echo "<p>Updated {$affected_rows} rooms to Available status.</p>";
        echo "</div>";
        echo "<script>setTimeout(function(){location.reload();}, 2000);</script>";
    }
    
    // 4. Kiểm tra logic booking
    echo "<h3>3. Kiểm tra logic booking</h3>";
    
    $sql = "SELECT r.RoomID, r.RoomName, r.RoomNumber, r.Status,
            CASE 
                WHEN r.Status = 'Maintenance' THEN 'Maintenance'
                WHEN r.Status = 'Occupied' THEN 'Occupied'
                WHEN (
                    SELECT COUNT(*) FROM Reservation res
                    WHERE res.RoomID = r.RoomID 
                      AND res.CheckInDate <= CURDATE() 
                      AND res.CheckOutDate > CURDATE()
                      AND res.ActualCheckOutDate IS NULL
                ) > 0 THEN 'Booked'
                ELSE 'Available'
            END AS DisplayStatus
            FROM Room r
            ORDER BY r.RoomID";
    
    $stmt = $pdo->query($sql);
    $rooms_logic = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Room</th><th>Database Status</th><th>Display Status</th><th>Can Book?</th>";
    echo "</tr>";
    
    foreach ($rooms_logic as $room) {
        $canBook = ($room['DisplayStatus'] === 'Available') ? 'Yes' : 'No';
        $canBookColor = ($canBook === 'Yes') ? 'green' : 'red';
        
        echo "<tr>";
        echo "<td>{$room['RoomName']} ({$room['RoomNumber']})</td>";
        echo "<td>{$room['Status']}</td>";
        echo "<td>{$room['DisplayStatus']}</td>";
        echo "<td style='color: $canBookColor; font-weight: bold;'>$canBook</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 5. Kiểm tra booking hiện tại
    echo "<h3>4. Booking hiện tại</h3>";
    
    $sql = "SELECT r.ReservationID, r.RoomID, rm.RoomName, r.CheckInDate, r.CheckOutDate, 
                   r.ActualCheckOutDate, a.FullName, p.PaymentStatus
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
        echo "<th>Room</th><th>Guest</th><th>Check-in</th><th>Check-out</th><th>Payment</th>";
        echo "</tr>";
        foreach ($active_bookings as $booking) {
            echo "<tr>";
            echo "<td>{$booking['RoomName']}</td>";
            echo "<td>{$booking['FullName']}</td>";
            echo "<td>" . date('d/m/Y', strtotime($booking['CheckInDate'])) . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($booking['CheckOutDate'])) . "</td>";
            echo "<td>{$booking['PaymentStatus']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<hr>";
    echo "<p><a href='room.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>→ Test Booking Page</a></p>";
    echo "<p><a href='admin/checkout_manage.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>→ Check-out Management</a></p>";
    
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