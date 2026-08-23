<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/api_config.php';

require_once __DIR__ . '/auth.php';

$user_id = getAuthenticatedUserId();

if (!$user_id) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

$sql = "
SELECT
    r.id,
    r.user_id,
    r.title,
    r.category,
    r.description,
    r.ingredients,
    r.steps,
    r.image_path,
    r.likes_count,

    COALESCE(AVG(rv.rating), 0) AS avg_rating,
    COUNT(rv.review_id) AS total_reviews,

    1 AS is_favorite,

    EXISTS (
        SELECT 1 FROM recipe_likes l
        WHERE l.recipe_id = r.id AND l.user_id = ?
    ) AS has_liked

FROM favorite_recipes f
JOIN recipes r ON f.recipe_id = r.id
LEFT JOIN reviews rv ON r.id = rv.recipe_id
WHERE f.user_id = ?
GROUP BY r.id, f.added_at
ORDER BY f.added_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$recipes = [];

while ($row = $result->fetch_assoc()) {
    $row['is_favorite'] = true;
    $row['has_liked']   = (bool)$row['has_liked'];

    if (!empty($row['image_path'])) {
        if (preg_match('/^https?:\/\//i', $row['image_path'])) {
            $row['image_url'] = $row['image_path'];
        } else {
            $row['image_url'] = rtrim(BASE_URL, '/') . '/' . ltrim($row['image_path'], '/');
        }
    } else {
        $row['image_url'] = null;
    }

    $recipes[] = $row;
}

echo json_encode([
    "success" => true,
    "recipes" => $recipes
]);