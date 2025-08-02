<?php
session_start();
if (!isset($_SESSION['account_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "password", "hotel_management");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$account_id = $_SESSION['account_id']; // lấy ID người đăng nhập

$sql = "SELECT r.*, rm.RoomName, p.PaymentStatus 
        FROM Reservation r 
        JOIN Room rm ON r.RoomID = rm.RoomID 
        LEFT JOIN Payment p ON r.ReservationID = p.ReservationID 
        WHERE r.AccountID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $account_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <title>Booked rooms</title>
    <link rel="stylesheet" href="css/bootstrap.min.css" />
</head>
<body>
<div class="container mt-5">
    <div class="card mb-4">
        <div class="card-body bg-primary text-white">
            <h2 class="mb-0">📅 Booked rooms</h2>
        </div>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Booking ID</th>
                    <th>Room name</th>
                    <th>Check-in date</th>
                    <th>Check-out date</th>
                    <th>Total amount</th>
                    <th>Payment status</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['ReservationID'] ?></td>
                    <td><?= htmlspecialchars($row['RoomName']) ?></td>
                    <td><?= date("d/m/Y", strtotime($row['CheckInDate'])) ?></td>
                    <td><?= date("d/m/Y", strtotime($row['CheckOutDate'])) ?></td>
                    <td><?= number_format($row['TotalAmount'], 0, ',', '.') ?> VND</td>
                    <td>
                        <?php if ($row['PaymentStatus'] === 'Paid' || $row['PaymentStatus'] === 'Completed'): ?>
                            <span class="badge bg-success">Paid</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Pending</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-muted">You have not booked any rooms.</p>
    <?php endif; ?>

    <a href="index.php" class="btn btn-secondary mt-3">← Back to home</a>
</div>
</body>
</html>
