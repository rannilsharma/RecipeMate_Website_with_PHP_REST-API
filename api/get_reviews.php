<?php
header('Content-Type: application/json');
require_once __DIR__ . '/dbConnection.php';

$recipeId = (int)($_GET['recipe_id'] ?? 0);

if ($recipeId <= 0) {
    echo json_encode(["success" => false]);
    exit;
}

$sql = "
SELECT 
    rv.review_id,
    rv.rating,
    rv.comment,
    u.userName,
    rv.created_at
FROM reviews rv
JOIN users u ON rv.user_id = u.user_id
WHERE rv.recipe_id = ?
ORDER BY rv.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $recipeId);
$stmt->execute();
$result = $stmt->get_result();

$reviews = [];
while ($row = $result->fetch_assoc()) {
    $reviews[] = $row;
}

echo json_encode([
    "success" => true,
    "reviews" => $reviews
]);
