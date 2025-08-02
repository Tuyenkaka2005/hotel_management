<?php
require 'config.php';

echo "<h2>🧪 Test Feedback System</h2>";

try {
    // 1. Kiểm tra cấu trúc bảng Feedback
    echo "<h3>1. Cấu trúc bảng Feedback</h3>";
    $stmt = $pdo->query("DESCRIBE Feedback");
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
    
    // 2. Kiểm tra dữ liệu feedback hiện tại
    echo "<h3>2. Dữ liệu Feedback hiện tại</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Feedback");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($total == 0) {
        echo "<p style='color: orange;'>⚠️ Chưa có feedback nào trong database</p>";
    } else {
        echo "<p style='color: green;'>✅ Có $total feedback trong database</p>";
        
        // Hiển thị feedback mẫu
        $stmt = $pdo->query("
            SELECT f.*, a.FullName, r.RoomName 
            FROM Feedback f 
            JOIN Account a ON f.AccountID = a.AccountID 
            JOIN Room r ON f.RoomID = r.RoomID 
            ORDER BY f.FeedbackDate DESC 
            LIMIT 5
        ");
        $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Room</th><th>User</th><th>Rating</th><th>Comment</th><th>Reply</th><th>Date</th>";
        echo "</tr>";
        
        foreach ($feedbacks as $feedback) {
            echo "<tr>";
            echo "<td>{$feedback['FeedbackID']}</td>";
            echo "<td>{$feedback['RoomName']}</td>";
            echo "<td>{$feedback['FullName']}</td>";
            echo "<td>" . str_repeat('★', $feedback['Rating']) . str_repeat('☆', 5 - $feedback['Rating']) . "</td>";
            echo "<td>" . substr($feedback['Comment'], 0, 50) . "...</td>";
            echo "<td>" . ($feedback['Reply'] ? 'Có' : 'Chưa có') . "</td>";
            echo "<td>{$feedback['FeedbackDate']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 3. Kiểm tra thống kê feedback
    echo "<h3>3. Thống kê Feedback</h3>";
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_feedback,
            AVG(Rating) as average_rating,
            COUNT(CASE WHEN Reply IS NULL OR Reply = '' THEN 1 END) as pending_replies,
            COUNT(CASE WHEN Rating = 5 THEN 1 END) as five_star,
            COUNT(CASE WHEN Rating = 4 THEN 1 END) as four_star,
            COUNT(CASE WHEN Rating = 3 THEN 1 END) as three_star,
            COUNT(CASE WHEN Rating = 2 THEN 1 END) as two_star,
            COUNT(CASE WHEN Rating = 1 THEN 1 END) as one_star
        FROM Feedback
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Metric</th><th>Value</th>";
    echo "</tr>";
    echo "<tr><td>Tổng đánh giá</td><td>{$stats['total_feedback']}</td></tr>";
    echo "<tr><td>Đánh giá trung bình</td><td>" . round($stats['average_rating'], 1) . "</td></tr>";
    echo "<tr><td>Chờ phản hồi</td><td>{$stats['pending_replies']}</td></tr>";
    echo "<tr><td>5 sao</td><td>{$stats['five_star']}</td></tr>";
    echo "<tr><td>4 sao</td><td>{$stats['four_star']}</td></tr>";
    echo "<tr><td>3 sao</td><td>{$stats['three_star']}</td></tr>";
    echo "<tr><td>2 sao</td><td>{$stats['two_star']}</td></tr>";
    echo "<tr><td>1 sao</td><td>{$stats['one_star']}</td></tr>";
    echo "</table>";
    
    // 4. Kiểm tra account có sẵn để test
    echo "<h3>4. Account có sẵn để test</h3>";
    $stmt = $pdo->query("SELECT AccountID, FullName, Email FROM Account ORDER BY AccountID LIMIT 5");
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($accounts)) {
        echo "<p style='color: red;'>❌ Không có account nào để test</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>Account ID</th><th>Full Name</th><th>Email</th>";
        echo "</tr>";
        foreach ($accounts as $account) {
            echo "<tr>";
            echo "<td>{$account['AccountID']}</td>";
            echo "<td>{$account['FullName']}</td>";
            echo "<td>{$account['Email']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 5. Kiểm tra phòng có sẵn để test
    echo "<h3>5. Phòng có sẵn để test</h3>";
    $stmt = $pdo->query("SELECT RoomID, RoomName, RoomNumber FROM Room ORDER BY RoomID LIMIT 5");
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($rooms)) {
        echo "<p style='color: red;'>❌ Không có phòng nào để test</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>Room ID</th><th>Room Name</th><th>Room Number</th>";
        echo "</tr>";
        foreach ($rooms as $room) {
            echo "<tr>";
            echo "<td>{$room['RoomID']}</td>";
            echo "<td>{$room['RoomName']}</td>";
            echo "<td>{$room['RoomNumber']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 6. Test tạo feedback mẫu
    echo "<h3>6. Test tạo Feedback mẫu</h3>";
    
    if (!empty($accounts) && !empty($rooms)) {
        $test_account_id = $accounts[0]['AccountID'];
        $test_room_id = $rooms[0]['RoomID'];
        
        // Kiểm tra xem đã có feedback cho phòng này chưa
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Feedback WHERE AccountID = ? AND RoomID = ?");
        $stmt->execute([$test_account_id, $test_room_id]);
        $existing = $stmt->fetchColumn();
        
        if ($existing > 0) {
            echo "<p style='color: orange;'>⚠️ Đã có feedback cho phòng này từ account này</p>";
        } else {
            // Tạo feedback test
            $stmt = $pdo->prepare("
                INSERT INTO Feedback (Rating, Comment, FeedbackDate, AccountID, RoomID) 
                VALUES (?, ?, CURDATE(), ?, ?)
            ");
            $stmt->execute([5, 'Phòng rất đẹp và sạch sẽ! Dịch vụ tốt, nhân viên thân thiện.', $test_account_id, $test_room_id]);
            
            echo "<p style='color: green;'>✅ Đã tạo feedback test thành công!</p>";
            echo "<p>Account ID: $test_account_id</p>";
            echo "<p>Room ID: $test_room_id</p>";
            echo "<p>Rating: 5 sao</p>";
            echo "<p>Comment: Phòng rất đẹp và sạch sẽ! Dịch vụ tốt, nhân viên thân thiện.</p>";
        }
    }
    
    // 7. Links để test
    echo "<h3>7. Links để test</h3>";
    echo "<div style='margin: 20px 0;'>";
    echo "<p><strong>Frontend:</strong></p>";
    echo "<p><a href='room.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>→ Room List</a></p>";
    if (!empty($rooms)) {
        echo "<p><a href='room_detail.php?room_id={$rooms[0]['RoomID']}' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>→ Room Detail (với feedback)</a></p>";
    }
    echo "<p><strong>Admin:</strong></p>";
    echo "<p><a href='admin/feedback_manage.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>→ Feedback Management</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
    echo "<h4>❌ Error</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?> 