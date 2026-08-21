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

$action  = $_POST['action'] ?? '';
$targetId = intval($_POST['user_id'] ?? 0);

if ($targetId <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid user ID"]);
    exit;
}

// Prevent admin from deleting/disabling themselves
if ($targetId == $userId) {
    echo json_encode(["success" => false, "message" => "You cannot modify your own account"]);
    exit;
}

if ($action === 'update') {
    $status = $_POST['status'] ?? '';
    if (!in_array($status, ['active', 'disabled'])) {
        echo json_encode(["success" => false, "message" => "Invalid status"]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
    $stmt->bind_param("si", $status, $targetId);
    $stmt->execute();

    echo json_encode([
        "success" => true,
        "message" => "User status updated to $status"
    ]);
} 
elseif ($action === 'delete') {
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $targetId);
    $stmt->execute();

    echo json_encode([
        "success" => true,
        "message" => "User deleted successfully"
    ]);
} 
else {
    echo json_encode(["success" => false, "message" => "Invalid action"]);
}