<?php
session_start();
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

if (!isset($_POST['image_id']) || !is_numeric($_POST['image_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid image ID']);
    exit;
}

$image_id = $_POST['image_id'];

try {
    // Lấy thông tin ảnh trước khi xóa
    $stmt = $pdo->prepare("SELECT ImagePath, RoomID FROM RoomImage WHERE ImageID = ?");
    $stmt->execute([$image_id]);
    $image = $stmt->fetch();
    
    if (!$image) {
        echo json_encode(['success' => false, 'error' => 'Image not found']);
        exit;
    }
    
    // Kiểm tra xem phòng có ít nhất 2 ảnh không (để đảm bảo không xóa hết ảnh)
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM RoomImage WHERE RoomID = ?");
    $countStmt->execute([$image['RoomID']]);
    $imageCount = $countStmt->fetchColumn();
    
    // Xóa file vật lý
    $imagePath = 'uploads/' . $image['ImagePath'];
    if (file_exists($imagePath)) {
        unlink($imagePath);
    }
    
    // Xóa bản ghi trong database
    $deleteStmt = $pdo->prepare("DELETE FROM RoomImage WHERE ImageID = ?");
    $deleteStmt->execute([$image_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Image deleted successfully',
        'remaining_count' => $imageCount - 1
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>