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

$recipeId = intval($_GET['recipe_id'] ?? 0);
if ($recipeId <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid recipe ID"]);
    exit;
}

$stmt = $conn->prepare("
    SELECT rv.review_id, rv.comment, rv.rating, u.userName, rv.created_at
    FROM reviews rv
    JOIN users u ON rv.user_id = u.user_id
    WHERE rv.recipe_id = ?
      AND rv.comment != 'Content removed by admin due to violation'
    ORDER BY rv.created_at DESC
");
$stmt->bind_param("i", $recipeId);
$stmt->execute();
$result = $stmt->get_result();

$comments = [];
while ($row = $result->fetch_assoc()) {
    $comments[] = $row;
}

echo json_encode([
    "success" => true,
    "comments" => $comments
]);