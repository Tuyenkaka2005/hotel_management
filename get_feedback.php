<?php
require_once 'config.php';

// Lấy room_id từ request
$room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;

if ($room_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID phòng không hợp lệ']);
    exit;
}

try {
    // Lấy thông tin đánh giá trung bình
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_reviews,
            AVG(Rating) as average_rating,
            COUNT(CASE WHEN Rating = 5 THEN 1 END) as five_star,
            COUNT(CASE WHEN Rating = 4 THEN 1 END) as four_star,
            COUNT(CASE WHEN Rating = 3 THEN 1 END) as three_star,
            COUNT(CASE WHEN Rating = 2 THEN 1 END) as two_star,
            COUNT(CASE WHEN Rating = 1 THEN 1 END) as one_star
        FROM Feedback 
        WHERE RoomID = ?
    ");
    $stmt->execute([$room_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Lấy danh sách feedback chi tiết
    $stmt = $pdo->prepare("
        SELECT 
            f.FeedbackID,
            f.Rating,
            f.Comment,
            f.Reply,
            f.FeedbackDate,
            a.FullName,
            a.Email
        FROM Feedback f
        JOIN Account a ON f.AccountID = a.AccountID
        WHERE f.RoomID = ?
        ORDER BY f.FeedbackDate DESC
        LIMIT 10
    ");
    $stmt->execute([$room_id]);
    $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dữ liệu
    $stats['average_rating'] = round($stats['average_rating'], 1);
    $stats['total_reviews'] = (int)$stats['total_reviews'];
    
    foreach ($feedbacks as &$feedback) {
        $feedback['FeedbackDate'] = date('d/m/Y', strtotime($feedback['FeedbackDate']));
        $feedback['Rating'] = (int)$feedback['Rating'];
        // Ẩn email, chỉ hiển thị tên
        $feedback['Email'] = substr($feedback['Email'], 0, 3) . '***@' . substr(strrchr($feedback['Email'], '@'), 1);
    }
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'feedbacks' => $feedbacks
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}
?> 