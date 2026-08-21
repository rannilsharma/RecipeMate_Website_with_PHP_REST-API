<?php
header('Content-Type: application/json');
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';

$userId = getAuthenticatedUserId();
if (!$userId) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

// Optional: force admin check
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$adminCheck = $stmt->get_result()->fetch_assoc();

if (!$adminCheck || $adminCheck['is_admin'] != 1) {
    echo json_encode(["success" => false, "message" => "Admin access required"]);
    exit;
}

$stmt = $conn->prepare("SELECT userName, email, created_at FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    echo json_encode(["success" => false, "message" => "User not found"]);
    exit;
}

echo json_encode([
    "success" => true,
    "user" => [
        "username" => $user['userName'],
        "email" => $user['email'],
        "created_at" => $user['created_at'],
    ]
]);