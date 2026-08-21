<?php
header('Content-Type: application/json');
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';

$userId = getAuthenticatedUserId();
$recipeId = (int) $_POST['recipe_id'];
$rating = (int) $_POST['rating'];
$comment = trim($_POST['comment']);

if (!$userId || $recipeId <= 0) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(["success" => false, "message" => "Invalid rating"]);
    exit;
}

// ✅ Check if already reviewed
$check = $conn->prepare("
    SELECT 1 FROM reviews 
    WHERE recipe_id = ? AND user_id = ?
    LIMIT 1
");
$check->bind_param("ii", $recipeId, $userId);
$check->execute();
$exists = $check->get_result()->num_rows > 0;

if ($exists) {
    echo json_encode([
        "success" => false,
        "message" => "You have already reviewed this recipe"
    ]);
    exit;
}

// ✅ Insert review
$stmt = $conn->prepare("
    INSERT INTO reviews (recipe_id, user_id, rating, comment)
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param("iiis", $recipeId, $userId, $rating, $comment);
$stmt->execute();

echo json_encode(["success" => true]);
