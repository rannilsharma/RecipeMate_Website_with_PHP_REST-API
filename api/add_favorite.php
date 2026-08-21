<?php
header('Content-Type: application/json');

require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';

$user_id = getAuthenticatedUserId();
$recipe_id = intval($_POST['recipe_id'] ?? 0);

if (!$user_id || $recipe_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized or invalid parameters"
    ]);
    exit();
}

/* Prevent duplicate favorites */
$check = $conn->prepare(
    "SELECT 1 FROM favorite_recipes WHERE user_id = ? AND recipe_id = ?"
);
$check->bind_param("ii", $user_id, $recipe_id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode([
        "success" => true,
        "already_favorite" => true
    ]);
    exit();
}

/* Insert favorite */
$stmt = $conn->prepare(
    "INSERT INTO favorite_recipes (user_id, recipe_id) VALUES (?, ?)"
);
$stmt->bind_param("ii", $user_id, $recipe_id);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Database error"
    ]);
}
