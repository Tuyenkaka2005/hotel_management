<?php
require 'config.php';

echo "<h2>🔄 Reset & Test Complete Workflow</h2>";

try {
    // 1. Reset tất cả phòng về Available
    echo "<h3>1. Reset tất cả phòng về Available</h3>";
    
    if (isset($_POST['reset_all_rooms'])) {
        $stmt = $pdo->prepare("UPDATE Room SET Status = 'Available' WHERE Status != 'Maintenance'");
        $stmt->execute();
        $reset_count = $stmt->rowCount();
        
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
        echo "<h4>✅ Reset Completed!</h4>";
        echo "<p>Reset $reset_count rooms to 'Available' status.</p>";
        echo "</div>";
        echo "<script>setTimeout(function(){location.reload();}, 2000);</script>";
    }
    
    echo "<form method='post'>";
    echo "<button type='submit' name='reset_all_rooms' style='background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 5px;'>";
    echo "🔄 Reset All Rooms to Available";
    echo "</button>";
    echo "</form>";
    
    // 2. Hiển thị trạng thái hiện tại
    echo "<h3>2. Trạng thái phòng hiện tại</h3>";
    
    $sql = "SELECT r.RoomID, r.RoomName, r.RoomNumber, r.Status, rt.TypeName
            FROM Room r
            JOIN RoomType rt ON r.RoomTypeID = rt.RoomTypeID
            ORDER BY r.RoomID";
    
    $stmt = $pdo->query($sql);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Room</th><th>Type</th><th>Status</th><th>Test Action</th>";
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
        echo "<td>";
        if ($room['Status'] === 'Available') {
            echo "<form method='post' style='display: inline;'>";
            echo "<input type='hidden' name='test_full_workflow' value='{$room['RoomID']}'>";
            echo "<button type='submit' style='background: #28a745; color: white; border: none; padding: 5px 10px; border-radius: 3px;'>Test Full Workflow</button>";
            echo "</form>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 3. Test full workflow
    if (isset($_POST['test_full_workflow'])) {
        $test_room_id = (int)$_POST['test_full_workflow'];
        
        try {
            $pdo->beginTransaction();
            
            // Bước 1: Tạo booking (Available → Reserved)
            $checkin = date('Y-m-d', strtotime('+1 day'));
            $checkout = date('Y-m-d', strtotime('+3 days'));
            $total_amount = 1000000;
            
            $stmt = $pdo->prepare("INSERT INTO Reservation (CheckInDate, CheckOutDate, TotalAmount, AccountID, RoomID) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$checkin, $checkout, $total_amount, 1, $test_room_id]);
            $reservation_id = $pdo->lastInsertId();
            
            // Tạo payment pending
            $stmt = $pdo->prepare("INSERT INTO Payment (PaymentMethod, PaymentStatus, ReservationID) VALUES (?, ?, ?)");
            $stmt->execute(['Cash', 'Pending', $reservation_id]);
            
            // Cập nhật trạng thái phòng thành Reserved
            $stmt = $pdo->prepare("UPDATE Room SET Status = 'Reserved' WHERE RoomID = ?");
            $stmt->execute([$test_room_id]);
            
            $pdo->commit();
            
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
            echo "<h4>✅ Step 1: Booking Created (Available → Reserved)</h4>";
            echo "<p>Room ID: $test_room_id</p>";
            echo "<p>Reservation ID: $reservation_id</p>";
            echo "<p>Status: Reserved</p>";
            echo "</div>";
            
            // Lưu thông tin để test tiếp
            $_SESSION['workflow_reservation_id'] = $reservation_id;
            $_SESSION['workflow_room_id'] = $test_room_id;
            $_SESSION['workflow_step'] = 1;
            
            echo "<script>setTimeout(function(){location.reload();}, 2000);</script>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
            echo "<h4>❌ Error</h4>";
            echo "<p>" . $e->getMessage() . "</p>";
            echo "</div>";
        }
    }
    
    // 4. Test confirm payment
    if (isset($_SESSION['workflow_step']) && $_SESSION['workflow_step'] == 1) {
        echo "<h3>3. Test Confirm Payment (Reserved → Occupied)</h3>";
        echo "<form method='post'>";
        echo "<button type='submit' name='test_confirm_payment_workflow' style='background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px;'>";
        echo "💰 Confirm Payment";
        echo "</button>";
        echo "</form>";
    }
    
    if (isset($_POST['test_confirm_payment_workflow']) && isset($_SESSION['workflow_reservation_id'])) {
        $reservation_id = $_SESSION['workflow_reservation_id'];
        $room_id = $_SESSION['workflow_room_id'];
        
        try {
            $pdo->beginTransaction();
            
            // Cập nhật trạng thái thanh toán
            $stmt = $pdo->prepare("UPDATE Payment SET PaymentStatus = 'Completed' WHERE ReservationID = ?");
            $stmt->execute([$reservation_id]);
            
            // Cập nhật trạng thái phòng thành Occupied
            $stmt = $pdo->prepare("UPDATE Room SET Status = 'Occupied' WHERE RoomID = ?");
            $stmt->execute([$room_id]);
            
            $pdo->commit();
            
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
            echo "<h4>✅ Step 2: Payment Confirmed (Reserved → Occupied)</h4>";
            echo "<p>Reservation ID: $reservation_id</p>";
            echo "<p>Room ID: $room_id</p>";
            echo "<p>Status: Occupied</p>";
            echo "</div>";
            
            $_SESSION['workflow_step'] = 2;
            echo "<script>setTimeout(function(){location.reload();}, 2000);</script>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
            echo "<h4>❌ Error</h4>";
            echo "<p>" . $e->getMessage() . "</p>";
            echo "</div>";
        }
    }
    
    // 5. Test check-out
    if (isset($_SESSION['workflow_step']) && $_SESSION['workflow_step'] == 2) {
        echo "<h3>4. Test Check-out (Occupied → Available)</h3>";
        echo "<form method='post'>";
        echo "<button type='submit' name='test_checkout_workflow' style='background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 5px;'>";
        echo "🚪 Check-out";
        echo "</button>";
        echo "</form>";
    }
    
    if (isset($_POST['test_checkout_workflow']) && isset($_SESSION['workflow_reservation_id'])) {
        $reservation_id = $_SESSION['workflow_reservation_id'];
        $room_id = $_SESSION['workflow_room_id'];
        
        try {
            $pdo->beginTransaction();
            
            // Cập nhật ngày check-out thực tế
            $stmt = $pdo->prepare("UPDATE Reservation SET ActualCheckOutDate = CURDATE() WHERE ReservationID = ?");
            $stmt->execute([$reservation_id]);
            
            // Cập nhật trạng thái phòng thành Available
            $stmt = $pdo->prepare("UPDATE Room SET Status = 'Available' WHERE RoomID = ?");
            $stmt->execute([$room_id]);
            
            $pdo->commit();
            
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
            echo "<h4>✅ Step 3: Check-out Completed (Occupied → Available)</h4>";
            echo "<p>Reservation ID: $reservation_id</p>";
            echo "<p>Room ID: $room_id</p>";
            echo "<p>Status: Available</p>";
            echo "</div>";
            
            // Xóa session
            unset($_SESSION['workflow_reservation_id']);
            unset($_SESSION['workflow_room_id']);
            unset($_SESSION['workflow_step']);
            
            echo "<script>setTimeout(function(){location.reload();}, 2000);</script>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
            echo "<h4>❌ Error</h4>";
            echo "<p>" . $e->getMessage() . "</p>";
            echo "</div>";
        }
    }
    
    // 6. Hiển thị workflow summary
    echo "<h3>5. Workflow Summary</h3>";
    echo "<div style='background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
    echo "<h4>🔄 Complete Booking Workflow:</h4>";
    echo "<ol>";
    echo "<li><strong>Available</strong> → Khách đặt phòng (tất cả phương thức thanh toán)</li>";
    echo "<li><strong>Reserved</strong> → Đã đặt, chờ admin xác nhận thanh toán</li>";
    echo "<li><strong>Occupied</strong> → Admin xác nhận thanh toán, khách đang ở</li>";
    echo "<li><strong>Available</strong> → Admin check-out, phòng trống</li>";
    echo "</ol>";
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
</style> 