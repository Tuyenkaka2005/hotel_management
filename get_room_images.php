<?php
session_start();
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

require_once 'config.php';

if (!isset($_GET['room_id']) || !is_numeric($_GET['room_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid room ID']);
    exit;
}

$room_id = $_GET['room_id'];

try {
    $stmt = $pdo->prepare("SELECT ImageID as id, ImagePath as path FROM RoomImage WHERE RoomID = ? ORDER BY ImageID");
    $stmt->execute([$room_id]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'images' => $images,
        'count' => count($images)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>