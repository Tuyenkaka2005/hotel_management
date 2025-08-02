<?php
require 'config.php';

echo "<h2>🧪 Test New Room Status System</h2>";

try {
    // 1. Kiểm tra cấu trúc bảng Room
    echo "<h3>1. Cấu trúc bảng Room</h3>";
    $stmt = $pdo->query("DESCRIBE Room");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        if ($column['Field'] === 'Status') {
            echo "<tr style='background: #d4edda;'>";
        } else {
            echo "<tr>";
        }
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Kiểm tra trạng thái phòng hiện tại
    echo "<h3>2. Trạng thái phòng hiện tại</h3>";
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
    echo "<th>Room</th><th>Type</th><th>Status</th><th>Active Bookings</th><th>Payment Status</th><th>Should Be</th>";
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
        echo "</tr>";
    }
    echo "</table>";
    
    // 3. Test cập nhật trạng thái
    echo "<h3>3. Test Cập nhật Trạng thái</h3>";
    echo "<form method='post'>";
    echo "<button type='submit' name='update_status' style='background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px;'>";
    echo "🔄 Update All Room Statuses";
    echo "</button>";
    echo "</form>";
    
    if (isset($_POST['update_status'])) {
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
        echo "<h4>✅ Status Update Completed!</h4>";
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
        echo "<th>Room</th><th>Guest</th><th>Check-in</th><th>Check-out</th><th>Payment Status</th><th>Room Should Be</th>";
        echo "</tr>";
        foreach ($active_bookings as $booking) {
            $roomShouldBe = ($booking['PaymentStatus'] === 'Completed') ? 'Occupied' : 'Reserved';
            echo "<tr>";
            echo "<td>{$booking['RoomName']}</td>";
            echo "<td>{$booking['FullName']}</td>";
            echo "<td>" . date('d/m/Y', strtotime($booking['CheckInDate'])) . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($booking['CheckOutDate'])) . "</td>";
            echo "<td>{$booking['PaymentStatus']}</td>";
            echo "<td style='font-weight: bold;'>$roomShouldBe</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<hr>";
    echo "<p><a href='room.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>→ Test Room Page</a></p>";
    echo "<p><a href='update_room_status_auto.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>→ Auto Update</a></p>";
    
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