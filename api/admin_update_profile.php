<?php
header('Content-Type: application/json');
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';

$userId = getAuthenticatedUserId();
if (!$userId) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$email = trim($_POST['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Valid email is required"]);
    exit;
}

// Check if email is already taken by another user
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
$stmt->bind_param("si", $email, $userId);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Email already in use"]);
    exit;
}

$stmt = $conn->prepare("UPDATE users SET email = ? WHERE user_id = ?");
$stmt->bind_param("si", $email, $userId);
$stmt->execute();

echo json_encode([
    "success" => true,
    "message" => "Profile updated successfully"
]);