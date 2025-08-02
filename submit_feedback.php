<?php
require_once 'config.php';
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['account_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login to rate.']);
    exit;
}

// Kiểm tra method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not supported.']);
    exit;
}

// Lấy dữ liệu từ request
$room_id = isset($_POST['room_id']) ? (int)$_POST['room_id'] : 0;
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
$account_id = $_SESSION['account_id'];

// Validation
if ($room_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid room ID.']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5.']);
    exit;
}

if (empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'Please enter your comment.']);
    exit;
}

if (strlen($comment) > 500) {
    echo json_encode(['success' => false, 'message' => 'Comment must be less than 500 characters.']);
    exit;
}

try {
    // Kiểm tra xem người dùng đã đánh giá phòng này chưa
    $stmt = $pdo->prepare("SELECT FeedbackID FROM Feedback WHERE AccountID = ? AND RoomID = ?");
    $stmt->execute([$account_id, $room_id]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You have already rated this room.']);   
        exit;
    }
    
    // Kiểm tra xem người dùng đã từng đặt phòng này chưa
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM Reservation 
        WHERE AccountID = ? AND RoomID = ? AND ActualCheckOutDate IS NOT NULL
    ");
    $stmt->execute([$account_id, $room_id]);
    
    if ($stmt->fetchColumn() == 0) {
        echo json_encode(['success' => false, 'message' => 'You can only rate rooms you have used.']);
        exit;
    }
    
    // Thêm feedback mới
    $stmt = $pdo->prepare("
        INSERT INTO Feedback (Rating, Comment, FeedbackDate, AccountID, RoomID) 
        VALUES (?, ?, CURDATE(), ?, ?)
    ");
    $stmt->execute([$rating, $comment, $account_id, $room_id]);
    
    echo json_encode(['success' => true, 'message' => 'Your rating has been submitted successfully!']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?> 