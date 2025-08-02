<?php
require 'config.php';
session_start();

// Kiểm tra quyền admin
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    
    // Validate status
    $valid_statuses = ['Available', 'Occupied', 'Maintenance'];
    if (!in_array($status, $valid_statuses)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid status']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE Room SET Status = ? WHERE RoomID = ?");
        $result = $stmt->execute([$status, $room_id]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Room status updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update room status']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?> 