<?php
require_once 'config.php';
header('Content-Type: application/json');
$field = $_GET['field'] ?? '';
$value = $_GET['value'] ?? '';
$map = [
    'username' => 'Username',
    'email' => 'Email',
    'phone' => 'PhoneNumber'
];
if (!isset($map[$field]) || !$value) {
    echo json_encode(['exists'=>false]);
    exit;
}
$sql = "SELECT COUNT(*) FROM Account WHERE {$map[$field]} = :value";
$stmt = $pdo->prepare($sql);
$stmt->execute(['value'=>$value]);
$count = $stmt->fetchColumn();
echo json_encode(['exists'=>($count>0)]); 