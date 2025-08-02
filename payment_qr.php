<?php
session_start();
$data = $_SESSION['pending_payment'] ?? null;
if (!$data) {
    die("No transaction found.");
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payment</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body class="text-center mt-5">
    <h2>Scan QR to pay</h2>
    <p>Please transfer the exact amount to the system to automatically confirm.</p>
    <img src="images/qr_bank.png" alt="QR Bank" style="max-width: 300px;">
    <p class="mt-3"><strong>Amount:</strong> <?= number_format($data['amount'], 0, ',', '.') ?> VND</p>
    <p><strong>Transfer content:</strong> DATPHONG<?= $data['reservation_id'] ?></p>
</body>
</html>
