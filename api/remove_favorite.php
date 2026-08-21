<?php
header('Content-Type: application/json');

require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';

$user_id = getAuthenticatedUserId();

if (!$user_id) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

$recipe_id = (int)($_POST['recipe_id'] ?? 0);

if ($recipe_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid recipe ID"
    ]);
    exit;
}

$stmt = $conn->prepare(
    "DELETE FROM favorite_recipes WHERE user_id = ? AND recipe_id = ?"
);
$stmt->bind_param("ii", $user_id, $recipe_id);
$stmt->execute();

echo json_encode([
    "success" => true
]);
