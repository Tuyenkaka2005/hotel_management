<?php
require 'config.php';

// Script tự động cập nhật trạng thái phòng
// Chạy định kỳ để đảm bảo trạng thái phòng chính xác

$today = date('Y-m-d');

try {
    echo "<h2>🔄 Auto Update Room Status</h2>";
    echo "<p><strong>Date:</strong> $today</p>";
    echo "<hr>";
    
                    // 1. Cập nhật phòng có booking đã thanh toán thành 'Occupied'
                $stmt = $pdo->prepare("
                    UPDATE Room r 
                    JOIN Reservation res ON r.RoomID = res.RoomID
                    JOIN Payment p ON p.ReservationID = res.ReservationID
                    SET r.Status = 'Occupied'
                    WHERE res.CheckInDate = ? 
                    AND r.Status != 'Maintenance'
                    AND res.ActualCheckOutDate IS NULL
                    AND p.PaymentStatus = 'Completed'
                ");
                $stmt->execute([$today]);
                $occupied_updated = $stmt->rowCount();
    
                    // 2. Cập nhật phòng có booking chưa thanh toán thành 'Reserved'
                $stmt = $pdo->prepare("
                    UPDATE Room r 
                    JOIN Reservation res ON r.RoomID = res.RoomID
                    LEFT JOIN Payment p ON p.ReservationID = res.ReservationID
                    SET r.Status = 'Reserved'
                    WHERE res.CheckInDate <= ? 
                    AND res.CheckOutDate > ?
                    AND r.Status != 'Maintenance'
                    AND res.ActualCheckOutDate IS NULL
                    AND (p.PaymentStatus IS NULL OR p.PaymentStatus != 'Completed')
                ");
                $stmt->execute([$today, $today]);
                $reserved_updated = $stmt->rowCount();
                
                // 3. Cập nhật phòng có khách check-out hôm nay thành 'Available'
                $stmt = $pdo->prepare("
                    UPDATE Room r 
                    JOIN Reservation res ON r.RoomID = res.RoomID
                    SET r.Status = 'Available'
                    WHERE res.CheckOutDate = ? 
                    AND r.Status != 'Maintenance'
                    AND res.ActualCheckOutDate IS NULL
                ");
                $stmt->execute([$today]);
                $available_updated = $stmt->rowCount();
    
    // 3. Cập nhật phòng đã check-out thực tế thành 'Available'
    $stmt = $pdo->prepare("
        UPDATE Room r 
        JOIN Reservation res ON r.RoomID = res.RoomID
        SET r.Status = 'Available'
        WHERE res.ActualCheckOutDate = ? 
        AND r.Status != 'Maintenance'
    ");
    $stmt->execute([$today]);
    $actual_checkout_updated = $stmt->rowCount();
    
    // 4. Cập nhật phòng không có booking nào thành 'Available'
    $stmt = $pdo->prepare("
        UPDATE Room r 
        SET r.Status = 'Available'
        WHERE r.RoomID NOT IN (
            SELECT DISTINCT RoomID 
            FROM Reservation 
            WHERE CheckInDate <= ? AND CheckOutDate > ?
            AND ActualCheckOutDate IS NULL
        )
        AND r.Status != 'Maintenance'
    ");
    $stmt->execute([$today, $today]);
    $auto_available_updated = $stmt->rowCount();
    
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
    echo "<h4>✅ Auto Update Completed Successfully!</h4>";
                    echo "<ul>";
                echo "<li><strong>Rooms set to Occupied (paid bookings):</strong> $occupied_updated</li>";
                echo "<li><strong>Rooms set to Reserved (unpaid bookings):</strong> $reserved_updated</li>";
                echo "<li><strong>Rooms set to Available (check-out date):</strong> $available_updated</li>";
                echo "<li><strong>Rooms set to Available (actual check-out):</strong> $actual_checkout_updated</li>";
                echo "<li><strong>Rooms auto-set to Available:</strong> $auto_available_updated</li>";
                echo "</ul>";
    echo "</div>";
    
    // Hiển thị danh sách phòng hiện tại
    echo "<h3>📋 Current Room Status</h3>";
    $sql = "SELECT r.RoomID, r.RoomName, r.RoomNumber, r.Status, rt.TypeName,
            (SELECT COUNT(*) FROM Reservation res 
             WHERE res.RoomID = r.RoomID 
               AND res.CheckInDate <= '$today' 
               AND res.CheckOutDate > '$today'
               AND res.ActualCheckOutDate IS NULL) AS IsBookedToday
            FROM Room r
            JOIN RoomType rt ON r.RoomTypeID = rt.RoomTypeID
            ORDER BY r.RoomID";
    
    $stmt = $pdo->query($sql);
    $rooms = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-top: 10px;'>";
    echo "<tr style='background: #f8f9fa;'>";
    echo "<th style='padding: 8px;'>Room</th>";
    echo "<th style='padding: 8px;'>Type</th>";
    echo "<th style='padding: 8px;'>Status</th>";
    echo "<th style='padding: 8px;'>Booked Today</th>";
    echo "</tr>";
    
    foreach ($rooms as $room) {
        $statusColor = '';
        switch($room['Status']) {
            case 'Available': $statusColor = '#28a745'; break;
            case 'Occupied': $statusColor = '#dc3545'; break;
            case 'Maintenance': $statusColor = '#6c757d'; break;
            default: $statusColor = '#007bff';
        }
        
        echo "<tr>";
        echo "<td style='padding: 8px;'>{$room['RoomName']} ({$room['RoomNumber']})</td>";
        echo "<td style='padding: 8px;'>{$room['TypeName']}</td>";
        echo "<td style='padding: 8px; color: $statusColor; font-weight: bold;'>{$room['Status']}</td>";
        echo "<td style='padding: 8px;'>" . ($room['IsBookedToday'] > 0 ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<p><a href='admin_dashboard.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>← Back to Admin Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
    echo "<h4>❌ Error Occurred</h4>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
h2 { color: #333; }
h3 { color: #666; margin-top: 30px; }
table { background: white; border-radius: 5px; }
th { background: #f8f9fa !important; }
</style> 