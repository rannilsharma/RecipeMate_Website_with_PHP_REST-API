<?php
header('Content-Type: application/json');
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';

$userId = getAuthenticatedUserId();
if (!$userId) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

// Admin check
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$adminCheck = $stmt->get_result()->fetch_assoc();

if (!$adminCheck || $adminCheck['is_admin'] != 1) {
    echo json_encode(["success" => false, "message" => "Admin access required"]);
    exit;
}

$reviewId = intval($_POST['review_id'] ?? 0);
if ($reviewId <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid review ID"]);
    exit;
}

$msg = "Content removed by admin due to violation";

$stmt = $conn->prepare("UPDATE reviews SET comment = ? WHERE review_id = ?");
$stmt->bind_param("si", $msg, $reviewId);
$stmt->execute();

echo json_encode([
    "success" => true,
    "message" => "Comment removed successfully"
]);