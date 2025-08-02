<?php
require 'config.php';

echo "<h2>🧪 Test Check-out System</h2>";

try {
    // 1. Kiểm tra cấu trúc bảng Reservation
    echo "<h3>1. Kiểm tra cấu trúc bảng Reservation</h3>";
    $stmt = $pdo->query("DESCRIBE Reservation");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
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
               AND res.ActualCheckOutDate IS NULL) AS IsBookedToday
            FROM Room r
            JOIN RoomType rt ON r.RoomTypeID = rt.RoomTypeID
            ORDER BY r.RoomID";
    
    $stmt = $pdo->query($sql);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>Room</th><th>Type</th><th>Status</th><th>Booked Today</th></tr>";
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
        echo "<td>" . ($room['IsBookedToday'] > 0 ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 3. Kiểm tra booking hiện tại
    echo "<h3>3. Booking hiện tại</h3>";
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
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($reservations)) {
        echo "<p style='color: green;'>✅ Không có booking nào đang hoạt động</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>Room</th><th>Guest</th><th>Check-in</th><th>Check-out</th><th>Payment</th></tr>";
        foreach ($reservations as $reservation) {
            echo "<tr>";
            echo "<td>{$reservation['RoomName']}</td>";
            echo "<td>{$reservation['FullName']}</td>";
            echo "<td>" . date('d/m/Y', strtotime($reservation['CheckInDate'])) . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($reservation['CheckOutDate'])) . "</td>";
            echo "<td>{$reservation['PaymentStatus']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 4. Test cập nhật trạng thái
    echo "<h3>4. Test cập nhật trạng thái</h3>";
    echo "<form method='post'>";
    echo "<select name='room_id'>";
    foreach ($rooms as $room) {
        echo "<option value='{$room['RoomID']}'>{$room['RoomName']} ({$room['RoomNumber']})</option>";
    }
    echo "</select>";
    
    echo "<select name='new_status'>";
    echo "<option value='Available'>Available</option>";
    echo "<option value='Occupied'>Occupied</option>";
    echo "<option value='Maintenance'>Maintenance</option>";
    echo "</select>";
    
    echo "<input type='submit' name='test_update' value='Test Update'>";
    echo "</form>";
    
    if (isset($_POST['test_update'])) {
        $room_id = (int)$_POST['room_id'];
        $new_status = $_POST['new_status'];
        
        $stmt = $pdo->prepare("UPDATE Room SET Status = ? WHERE RoomID = ?");
        $result = $stmt->execute([$new_status, $room_id]);
        
        if ($result) {
            echo "<p style='color: green;'>✅ Cập nhật thành công! Reload trang để xem thay đổi.</p>";
            echo "<script>setTimeout(function(){location.reload();}, 2000);</script>";
        } else {
            echo "<p style='color: red;'>❌ Lỗi cập nhật</p>";
        }
    }
    
    echo "<hr>";
    echo "<p><a href='admin/checkout_manage.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>→ Check-out Management</a></p>";
    echo "<p><a href='update_room_status_auto.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>→ Auto Update</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
h2, h3 { color: #333; }
table { background: white; border-radius: 5px; }
th { background: #f8f9fa !important; }
td, th { padding: 8px; text-align: left; }
form { margin: 20px 0; }
select, input { margin: 5px; padding: 5px; }
</style> 