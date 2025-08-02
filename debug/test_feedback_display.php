<?php
require_once 'config.php';

echo "<h2>Test Feedback Display</h2>";

// Test 1: Check if Feedback table exists and has data
echo "<h3>1. Checking Feedback table:</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Feedback");
    $result = $stmt->fetch();
    echo "Total feedback records: " . $result['total'] . "<br>";
    
    if ($result['total'] > 0) {
        $stmt = $pdo->query("SELECT * FROM Feedback LIMIT 5");
        $feedbacks = $stmt->fetchAll();
        echo "Sample feedback data:<br>";
        foreach ($feedbacks as $feedback) {
            echo "- RoomID: {$feedback['RoomID']}, Rating: {$feedback['Rating']}, Comment: " . substr($feedback['Comment'], 0, 50) . "...<br>";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

// Test 2: Check room data
echo "<h3>2. Checking Room data:</h3>";
try {
    $stmt = $pdo->query("SELECT RoomID, RoomName FROM Room LIMIT 5");
    $rooms = $stmt->fetchAll();
    echo "Available rooms:<br>";
    foreach ($rooms as $room) {
        echo "- RoomID: {$room['RoomID']}, Name: {$room['RoomName']}<br>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

// Test 3: Test get_feedback.php with a specific room
echo "<h3>3. Testing get_feedback.php:</h3>";
if (!empty($rooms)) {
    $testRoomId = $rooms[0]['RoomID'];
    echo "Testing with RoomID: $testRoomId<br>";
    
    $url = "get_feedback.php?room_id=$testRoomId";
    echo "URL: $url<br>";
    
    $response = file_get_contents($url);
    echo "Response: <pre>" . htmlspecialchars($response) . "</pre>";
    
    $data = json_decode($response, true);
    if ($data && isset($data['stats'])) {
        echo "Parsed stats:<br>";
        echo "- Total reviews: " . $data['stats']['total_reviews'] . "<br>";
        echo "- Average rating: " . $data['stats']['average_rating'] . "<br>";
        echo "- 5 stars: " . $data['stats']['five_star'] . "<br>";
        echo "- 4 stars: " . $data['stats']['four_star'] . "<br>";
        echo "- 3 stars: " . $data['stats']['three_star'] . "<br>";
        echo "- 2 stars: " . $data['stats']['two_star'] . "<br>";
        echo "- 1 star: " . $data['stats']['one_star'] . "<br>";
    }
}

// Test 4: Create sample feedback if none exists
echo "<h3>4. Creating sample feedback:</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Feedback");
    $result = $stmt->fetch();
    
    if ($result['total'] == 0) {
        echo "No feedback found. Creating sample data...<br>";
        
        // Get first room and first account
        $roomStmt = $pdo->query("SELECT RoomID FROM Room LIMIT 1");
        $room = $roomStmt->fetch();
        
        $accountStmt = $pdo->query("SELECT AccountID FROM Account WHERE Role = 'Customer' LIMIT 1");
        $account = $accountStmt->fetch();
        
        if ($room && $account) {
            $insertStmt = $pdo->prepare("INSERT INTO Feedback (RoomID, AccountID, Rating, Comment, FeedbackDate) VALUES (?, ?, ?, ?, CURDATE())");
            $insertStmt->execute([$room['RoomID'], $account['AccountID'], 5, 'Great room! Very comfortable and clean.']);
            echo "Sample feedback created successfully!<br>";
        } else {
            echo "Could not find room or account for sample data.<br>";
        }
    } else {
        echo "Feedback already exists.<br>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

echo "<h3>5. Test Links:</h3>";
if (!empty($rooms)) {
    $testRoomId = $rooms[0]['RoomID'];
    echo "<a href='room_detail.php?room_id=$testRoomId' target='_blank'>Test Room Detail Page</a><br>";
    echo "<a href='get_feedback.php?room_id=$testRoomId' target='_blank'>Test Feedback API</a><br>";
}
?> 