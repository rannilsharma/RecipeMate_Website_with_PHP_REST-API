<?php
header('Content-Type: application/json');

require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';

/* 🔐 Get user from token */
$userId = getAuthenticatedUserId();
$recipeId = (int) ($_POST['recipe_id'] ?? 0);

if ($userId <= 0 || $recipeId <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized or invalid data"
    ]);
    exit;
}

/* Prevent duplicate likes */
$check = $conn->prepare(
    "SELECT 1 FROM recipe_likes WHERE recipe_id = ? AND user_id = ?"
);
$check->bind_param("ii", $recipeId, $userId);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode([
        "success" => false,
        "message" => "Already liked"
    ]);
    exit;
}

/* Insert like */
$stmt = $conn->prepare(
    "INSERT INTO recipe_likes (recipe_id, user_id) VALUES (?, ?)"
);
$stmt->bind_param("ii", $recipeId, $userId);
$stmt->execute();

echo json_encode(["success" => true]);
