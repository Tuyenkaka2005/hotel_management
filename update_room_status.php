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

if (!isset($_POST['room_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

$room_id = $_POST['room_id'];
$status = $_POST['status'];

// Validate status
$validStatuses = ['Available', 'Reserved', 'Occupied', 'Maintenance'];
if (!in_array($status, $validStatuses)) {
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE Room SET Status = ? WHERE RoomID = ?");
    $stmt->execute([$status, $room_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Room status updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Room not found or no changes made'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>