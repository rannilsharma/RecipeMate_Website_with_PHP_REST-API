<?php
header('Content-Type: application/json');
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';

$userId = getAuthenticatedUserId();

if (!$userId) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

// Check if the current user is an admin
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$adminCheck = $stmt->get_result()->fetch_assoc();

if (!$adminCheck || $adminCheck['is_admin'] != 1) {
    echo json_encode(["success" => false, "message" => "Admin access required"]);
    exit;
}

$query = "SELECT user_id, userName, email, status, created_at 
          FROM users 
          ORDER BY user_id ASC";
$result = $conn->query($query);

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode([
    "success" => true,
    "users" => $users
]);