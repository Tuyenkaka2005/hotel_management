<?php
session_start();

if (!isset($_SESSION['account_id'])) {
    header("Location: ../login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "password", "hotel_management");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Tổng số lượt đặt phòng
$totalReservations = $conn->query("SELECT COUNT(*) AS total FROM Reservation")->fetch_assoc()['total'] ?? 0;

// Tổng doanh thu (tất cả đơn đã thanh toán)
$totalRevenueResult = $conn->query("
    SELECT SUM(r.TotalAmount) AS revenue
    FROM Reservation r
    JOIN Payment p ON r.ReservationID = p.ReservationID
    WHERE p.PaymentStatus = 'Completed'
");
$totalRevenue = $totalRevenueResult->fetch_assoc()['revenue'] ?? 0;

// Doanh thu thanh toán bằng chuyển khoản
$bankTransferRevenueResult = $conn->query("
    SELECT SUM(r.TotalAmount) AS total
    FROM Reservation r
    JOIN Payment p ON r.ReservationID = p.ReservationID
    WHERE p.PaymentStatus = 'Completed' AND p.PaymentMethod = 'Bank Transfer'
");
$bankTransferRevenue = $bankTransferRevenueResult->fetch_assoc()['total'] ?? 0;

// Tổng tiền các đơn chưa thanh toán (bất kỳ phương thức nào)
$unpaidRevenueResult = $conn->query("
    SELECT SUM(r.TotalAmount) AS total
    FROM Reservation r
    JOIN Payment p ON r.ReservationID = p.ReservationID
    WHERE p.PaymentStatus != 'Completed'
");
$pendingRevenue = $unpaidRevenueResult->fetch_assoc()['total'] ?? 0;

// Danh sách đặt phòng chi tiết
$reservationList = $conn->query("
    SELECT 
        r.ReservationID,
        a.FullName,
        r.CheckInDate,
        r.CheckOutDate,
        r.TotalAmount,
        p.PaymentMethod,
        p.PaymentStatus
    FROM Reservation r
    JOIN Account a ON r.AccountID = a.AccountID
    LEFT JOIN Payment p ON r.ReservationID = p.ReservationID
    ORDER BY r.ReservationDate DESC
");

function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.') . ' ₫';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Booking Statistics</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            font-family: 'Inter', Arial, sans-serif;
        }
        .container {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.10), 0 1.5px 8px rgba(118, 75, 162, 0.08);
            padding: 36px 28px 28px 28px;
            margin-top: 40px;
            margin-bottom: 40px;
        }
        h2, h4 {
            font-weight: 700;
            color: #007bff;
            letter-spacing: 1px;
        }
        .card {
            border-radius: 16px;
            box-shadow: 0 4px 18px rgba(0,123,255,0.08);
            border: none;
            margin-bottom: 0;
        }
        .card-body {
            padding: 28px 18px 18px 18px;
            text-align: center;
        }
        .card h5 {
            font-size: 1.08rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .card .fs-4 {
            font-size: 2rem !important;
            font-weight: 700;
            color: #222;
        }
        .card.bg-primary { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important; color: #fff; }
        .card.bg-success { background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important; color: #fff; }
        .card.bg-info { background: linear-gradient(135deg, #17a2b8 0%, #138496 100%) !important; color: #fff; }
        .card.bg-warning { background: linear-gradient(135deg, #ffb347 0%, #ffcc33 100%) !important; color: #333; }
        .row.g-4 > [class^='col-'] { margin-bottom: 0; }
        table {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,123,255,0.07);
        }
        .table thead {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: #fff;
        }
        .table th, .table td {
            vertical-align: middle !important;
            text-align: center;
        }
        .table th {
            font-size: 1.05rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .table td {
            font-size: 0.98rem;
        }
        .badge {
            border-radius: 20px;
            padding: 8px 15px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge.bg-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        .badge.bg-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }
        .badge.bg-warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%) !important;
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
            color: #333 !important;
        }
        .badge.bg-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%) !important;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }
        .btn-secondary {
            border-radius: 25px;
            font-weight: 600;
            padding: 8px 22px;
            font-size: 1rem;
            margin-top: 18px;
            background: #b2bec3;
            color: #fff;
            border: none;
            transition: all 0.2s;
        }
        .btn-secondary:hover {
            background: #007bff;
            color: #fff;
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 8px 25px rgba(0,123,255,0.13);
        }
        @media (max-width: 991px) {
            .container {
                padding: 18px 4px 12px 4px;
            }
            .card-body {
                padding: 18px 8px 12px 8px;
            }
            .card .fs-4 {
                font-size: 1.3rem !important;
            }
        }
        @media (max-width: 767px) {
            h2, h4 {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <h2 class="mb-4">📊 Booking Statistics</h2>

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5>Total Bookings</h5>
                    <p class="fs-4"><?= $totalReservations ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5>Total Revenue</h5>
                    <p class="fs-4"><?= formatCurrency($totalRevenue) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5>Paid (Bank Transfer)</h5>
                    <p class="fs-4"><?= formatCurrency($bankTransferRevenue) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h5>Not Paid</h5>
                    <p class="fs-4"><?= formatCurrency($pendingRevenue) ?></p>
                </div>
            </div>
        </div>
    </div>

    <h4>📋 Booking Details</h4>
    <table class="table table-bordered table-hover mt-3">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Check-in</th>
                <th>Check-out</th>
                <th>Amount (VND)</th>
                <th>Payment Method</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $reservationList->fetch_assoc()): ?>
            <tr>
                <td><?= $row['ReservationID'] ?></td>
                <td><?= htmlspecialchars($row['FullName']) ?></td>
                <td><?= $row['CheckInDate'] ?></td>
                <td><?= $row['CheckOutDate'] ?></td>
                <td><?= formatCurrency($row['TotalAmount']) ?></td>
                <td><?= $row['PaymentMethod'] ?? 'N/A' ?></td>
                <td>
                    <?php if ($row['PaymentStatus'] == 'Completed'): ?>
                        <span class="badge bg-success">Paid</span>
                    <?php elseif ($row['PaymentStatus'] == 'Pending'): ?>
                        <span class="badge bg-warning text-dark">Not Paid</span>
                    <?php elseif ($row['PaymentStatus'] == 'Failed'): ?>
                            <span class="badge bg-danger">Failed</span>
                    <?php else: ?>
                        <span class="badge bg-secondary"><?= $row['PaymentStatus'] ?? 'Unknown' ?></span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
        <a href="../admin_dashboard.php" class="btn btn-secondary mt-3">← Back to Dashboard</a>

</div>
</body>
</html>
