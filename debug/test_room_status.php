<?php
require 'config.php';

echo "<h2>Test Room Status System</h2>";

// Lấy danh sách tất cả phòng và trạng thái
$sql = "SELECT r.RoomID, r.RoomName, r.RoomNumber, r.Status, rt.TypeName,
        (
            SELECT COUNT(*) FROM Reservation res
            WHERE res.RoomID = r.RoomID 
              AND res.CheckInDate <= CURDATE() 
              AND res.CheckOutDate > CURDATE()
        ) AS IsBookedToday
        FROM Room r
        JOIN RoomType rt ON r.RoomTypeID = rt.RoomTypeID
        ORDER BY r.RoomID";

$stmt = $pdo->query($sql);
$rooms = $stmt->fetchAll();

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>Room ID</th>";
echo "<th>Room Name</th>";
echo "<th>Room Number</th>";
echo "<th>Type</th>";
echo "<th>Database Status</th>";
echo "<th>Is Booked Today</th>";
echo "<th>Should Display As</th>";
echo "</tr>";

foreach ($rooms as $room) {
    $displayStatus = 'Available';
    if ($room['Status'] === 'Maintenance') {
        $displayStatus = 'Maintenance';
    } elseif ($room['Status'] === 'Occupied') {
        $displayStatus = 'Occupied';
    } elseif ($room['IsBookedToday'] > 0) {
        $displayStatus = 'Booked';
    }
    
    $statusColor = '';
    switch($displayStatus) {
        case 'Available': $statusColor = 'green'; break;
        case 'Booked': $statusColor = 'orange'; break;
        case 'Occupied': $statusColor = 'red'; break;
        case 'Maintenance': $statusColor = 'gray'; break;
    }
    
    echo "<tr>";
    echo "<td>{$room['RoomID']}</td>";
    echo "<td>{$room['RoomName']}</td>";
    echo "<td>{$room['RoomNumber']}</td>";
    echo "<td>{$room['TypeName']}</td>";
    echo "<td>{$room['Status']}</td>";
    echo "<td>" . ($room['IsBookedToday'] > 0 ? 'Yes' : 'No') . "</td>";
    echo "<td style='color: $statusColor; font-weight: bold;'>$displayStatus</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>Test Update Room Status</h3>";
echo "<form method='post'>";
echo "<select name='room_id'>";
foreach ($rooms as $room) {
    echo "<option value='{$room['RoomID']}'>{$room['RoomName']} ({$room['RoomNumber']})</option>";
}
echo "</select>";

echo "<select name='new_status'>";
echo "<option value='Available'>Available</option>";
echo "<option value='Occupied'>Occupied</option>";
echo "<option value='Maintenance'>Maintenance</option>";
echo "</select>";

echo "<input type='submit' value='Update Status'>";
echo "</form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id = $_POST['room_id'];
    $new_status = $_POST['new_status'];
    
    try {
        $stmt = $pdo->prepare("UPDATE Room SET Status = ? WHERE RoomID = ?");
        $result = $stmt->execute([$new_status, $room_id]);
        
        if ($result) {
            echo "<p style='color: green;'>Status updated successfully!</p>";
            echo "<script>location.reload();</script>";
        } else {
            echo "<p style='color: red;'>Failed to update status.</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 20px 0; }
th, td { padding: 8px; text-align: left; }
form { margin: 20px 0; }
select, input { margin: 5px; padding: 5px; }
</style> 