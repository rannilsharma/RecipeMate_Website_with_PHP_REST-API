<?php
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/api_config.php';

header('Content-Type: application/json');

$user_id = getAuthenticatedUserId();

if (!$user_id) {
    echo json_encode([
        "success" => false,
        "recipes" => []
    ]);
    exit();
}

$stmt = $conn->prepare("
    SELECT
        id,
        user_id,
        title,
        category,
        description,
        ingredients,
        steps,
        image_path,
        likes_count
    FROM recipes
    WHERE user_id = ?
    ORDER BY id DESC
");


$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$recipes = [];

while ($row = $result->fetch_assoc()) {

    // Build image URL using BASE_URL.
    // If image_path is already a full URL, keep it as-is.
    if (!empty($row['image_path'])) {
        if (preg_match('/^https?:\/\//i', $row['image_path'])) {
            $imageUrl = $row['image_path'];
        } else {
            $imageUrl = rtrim(BASE_URL, '/') . '/' . ltrim($row['image_path'], '/');
        }
    } else {
        $imageUrl = null;
    }

    $recipes[] = [
        "id" => (int)$row["id"],
        "user_id" => (int)$row["user_id"],
        "title" => $row["title"],
        "category" => $row["category"],
        "description" => $row["description"], 
        "ingredients" => $row["ingredients"],
        "steps" => $row["steps"],
        "image_url" => $imageUrl,
        "likes_count" => (int)$row["likes_count"],
        "avg_rating" => 0,
        "total_reviews" => 0,
        "has_liked" => false,
        "is_favorite" => false
    ];

}

echo json_encode([
    "success" => true,
    "recipes" => $recipes
]);
exit();