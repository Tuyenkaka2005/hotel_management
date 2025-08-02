<?php
require 'config.php';

echo "<h2>🔍 Debug Check-out Issue</h2>";

try {
    // 1. Kiểm tra tất cả reservation có ActualCheckOutDate
    echo "<h3>1. Tất cả Reservation có ActualCheckOutDate</h3>";
    
    $sql = "SELECT r.ReservationID, r.RoomID, rm.RoomName, rm.Status as RoomStatus,
            r.CheckInDate, r.CheckOutDate, r.ActualCheckOutDate,
            a.FullName, p.PaymentStatus
            FROM Reservation r
            JOIN Room rm ON r.RoomID = rm.RoomID
            LEFT JOIN Account a ON r.AccountID = a.AccountID
            LEFT JOIN Payment p ON p.ReservationID = r.ReservationID
            WHERE r.ActualCheckOutDate IS NOT NULL
            ORDER BY r.ActualCheckOutDate DESC";
    
    $stmt = $pdo->query($sql);
    $checkouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($checkouts)) {
        echo "<p style='color: orange;'>⚠️ Không có reservation nào đã check-out</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>Reservation ID</th><th>Room</th><th>Room Status</th><th>Guest</th><th>Check-in</th><th>Check-out</th><th>Actual Check-out</th><th>Payment</th>";
        echo "</tr>";
        
        foreach ($checkouts as $checkout) {
            $statusColor = '';
            switch($checkout['RoomStatus']) {
                case 'Available': $statusColor = 'green'; break;
                case 'Occupied': $statusColor = 'red'; break;
                case 'Maintenance': $statusColor = 'gray'; break;
                default: $statusColor = 'blue';
            }
            
            echo "<tr>";
            echo "<td>{$checkout['ReservationID']}</td>";
            echo "<td>{$checkout['RoomName']}</td>";
            echo "<td style='color: $statusColor; font-weight: bold;'>{$checkout['RoomStatus']}</td>";
            echo "<td>{$checkout['FullName']}</td>";
            echo "<td>" . date('d/m/Y', strtotime($checkout['CheckInDate'])) . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($checkout['CheckOutDate'])) . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($checkout['ActualCheckOutDate'])) . "</td>";
            echo "<td>{$checkout['PaymentStatus']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 2. Kiểm tra phòng có ActualCheckOutDate nhưng Status vẫn không phải Available
    echo "<h3>2. Phòng đã check-out nhưng Status không phải Available</h3>";
    
    $sql = "SELECT r.RoomID, r.RoomName, r.RoomNumber, r.Status,
            res.ReservationID, res.ActualCheckOutDate, res.CheckOutDate,
            a.FullName
            FROM Room r
            JOIN Reservation res ON r.RoomID = res.RoomID
            LEFT JOIN Account a ON res.AccountID = a.AccountID
            WHERE res.ActualCheckOutDate IS NOT NULL
              AND res.ActualCheckOutDate <= CURDATE()
              AND r.Status != 'Available'
              AND r.Status != 'Maintenance'
            ORDER BY res.ActualCheckOutDate DESC";
    
    $stmt = $pdo->query($sql);
    $problem_rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($problem_rooms)) {
        echo "<p style='color: green;'>✅ Không có phòng nào có vấn đề</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>Room</th><th>Current Status</th><th>Guest</th><th>Actual Check-out</th><th>Action</th>";
        echo "</tr>";
        
        foreach ($problem_rooms as $room) {
            echo "<tr>";
            echo "<td>{$room['RoomName']} ({$room['RoomNumber']})</td>";
            echo "<td style='color: red; font-weight: bold;'>{$room['Status']}</td>";
            echo "<td>{$room['FullName']}</td>";
            echo "<td>" . date('d/m/Y', strtotime($room['ActualCheckOutDate'])) . "</td>";
            echo "<td>";
            echo "<form method='post' style='display: inline;'>";
            echo "<input type='hidden' name='fix_room' value='{$room['RoomID']}'>";
            echo "<button type='submit' style='background: #28a745; color: white; border: none; padding: 5px 10px; border-radius: 3px;'>Fix Status</button>";
            echo "</form>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 3. Xử lý sửa lỗi
    if (isset($_POST['fix_room'])) {
        $room_id = (int)$_POST['fix_room'];
        
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
    }
    
    // 4. Auto fix tất cả
    echo "<h3>3. Auto Fix All Checked-out Rooms</h3>";
    echo "<form method='post'>";
    echo "<button type='submit' name='auto_fix_all' style='background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px;'>";
    echo "🔧 Auto Fix All Checked-out Rooms";
    echo "</button>";
    echo "</form>";
    
    if (isset($_POST['auto_fix_all'])) {
        // Cập nhật tất cả phòng đã check-out thành Available
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
    
    // 5. Kiểm tra logic booking hiện tại
    echo "<h3>4. Test Booking Logic</h3>";
    
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
            END AS DisplayStatus,
            (
                SELECT COUNT(*) FROM Reservation res
                WHERE res.RoomID = r.RoomID 
                  AND res.CheckInDate <= CURDATE() 
                  AND res.CheckOutDate > CURDATE()
                  AND res.ActualCheckOutDate IS NULL
            ) AS ActiveBookings
            FROM Room r
            ORDER BY r.RoomID";
    
    $stmt = $pdo->query($sql);
    $rooms_logic = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Room</th><th>DB Status</th><th>Display Status</th><th>Active Bookings</th><th>Can Book?</th>";
    echo "</tr>";
    
    foreach ($rooms_logic as $room) {
        $canBook = ($room['DisplayStatus'] === 'Available') ? 'Yes' : 'No';
        $canBookColor = ($canBook === 'Yes') ? 'green' : 'red';
        
        echo "<tr>";
        echo "<td>{$room['RoomName']} ({$room['RoomNumber']})</td>";
        echo "<td>{$room['Status']}</td>";
        echo "<td>{$room['DisplayStatus']}</td>";
        echo "<td>{$room['ActiveBookings']}</td>";
        echo "<td style='color: $canBookColor; font-weight: bold;'>$canBook</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<p><a href='room.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>→ Test Room Page</a></p>";
    echo "<p><a href='fix_room_booking_issue.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>→ Fix Room Booking Issue</a></p>";
    
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