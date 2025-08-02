<?php
require 'config.php';

echo "<h2>🔄 Update Room Status System</h2>";

try {
    // 1. Cập nhật cột Status để hỗ trợ 'Reserved'
    echo "<h3>1. Cập nhật cấu trúc bảng Room</h3>";
    $sql = "ALTER TABLE Room MODIFY COLUMN Status ENUM('Available','Occupied','Maintenance','Reserved') DEFAULT 'Available'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    echo "<p style='color: green;'>✅ Cấu trúc bảng Room đã được cập nhật</p>";
    
    // 2. Cập nhật phòng có booking đã thanh toán thành 'Occupied'
    echo "<h3>2. Cập nhật phòng đã thanh toán thành 'Occupied'</h3>";
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
    echo "<p style='color: green;'>✅ Cập nhật $occupied_updated phòng thành 'Occupied'</p>";
    
    // 3. Cập nhật phòng có booking chưa thanh toán thành 'Reserved'
    echo "<h3>3. Cập nhật phòng chưa thanh toán thành 'Reserved'</h3>";
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
    echo "<p style='color: green;'>✅ Cập nhật $reserved_updated phòng thành 'Reserved'</p>";
    
    // 4. Cập nhật phòng đã check-out thành 'Available'
    echo "<h3>4. Cập nhật phòng đã check-out thành 'Available'</h3>";
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
    $checkout_updated = $stmt->rowCount();
    echo "<p style='color: green;'>✅ Cập nhật $checkout_updated phòng đã check-out thành 'Available'</p>";
    
    // 5. Cập nhật phòng không có booking thành 'Available'
    echo "<h3>5. Cập nhật phòng không có booking thành 'Available'</h3>";
    
    // Lấy danh sách phòng có booking hiện tại
    $sql = "SELECT DISTINCT res.RoomID 
            FROM Reservation res
            WHERE res.CheckInDate <= CURDATE() 
              AND res.CheckOutDate > CURDATE()
              AND res.ActualCheckOutDate IS NULL";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $booked_rooms = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($booked_rooms)) {
        // Không có phòng nào được đặt, cập nhật tất cả phòng không phải Maintenance
        $sql = "UPDATE Room SET Status = 'Available' WHERE Status != 'Maintenance'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $available_updated = $stmt->rowCount();
    } else {
        // Cập nhật phòng không có trong danh sách đặt
        $placeholders = str_repeat('?,', count($booked_rooms) - 1) . '?';
        $sql = "UPDATE Room SET Status = 'Available' 
                WHERE RoomID NOT IN ($placeholders) 
                AND Status != 'Maintenance' 
                AND Status != 'Available'";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($booked_rooms);
        $available_updated = $stmt->rowCount();
    }
    
    echo "<p style='color: green;'>✅ Cập nhật $available_updated phòng không có booking thành 'Available'</p>";
    
    // 6. Hiển thị kết quả tổng quan
    echo "<h3>6. Kết quả tổng quan</h3>";
    $sql = "SELECT Status, COUNT(*) as Count FROM Room GROUP BY Status ORDER BY Status";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Status</th><th>Count</th>";
    echo "</tr>";
    
    foreach ($results as $result) {
        $statusColor = '';
        switch($result['Status']) {
            case 'Available': $statusColor = 'green'; break;
            case 'Reserved': $statusColor = 'orange'; break;
            case 'Occupied': $statusColor = 'red'; break;
            case 'Maintenance': $statusColor = 'gray'; break;
            default: $statusColor = 'blue';
        }
        
        echo "<tr>";
        echo "<td style='color: $statusColor; font-weight: bold;'>{$result['Status']}</td>";
        echo "<td>{$result['Count']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 7. Tổng kết
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
    echo "<h4>✅ Cập nhật hoàn tất!</h4>";
    echo "<ul>";
    echo "<li><strong>Phòng Occupied:</strong> $occupied_updated</li>";
    echo "<li><strong>Phòng Reserved:</strong> $reserved_updated</li>";
    echo "<li><strong>Phòng Available (check-out):</strong> $checkout_updated</li>";
    echo "<li><strong>Phòng Available (không booking):</strong> $available_updated</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<hr>";
    echo "<p><a href='room.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>→ Test Room Page</a></p>";
    echo "<p><a href='test_new_status_system.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>→ Test System</a></p>";
    
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
</style> 