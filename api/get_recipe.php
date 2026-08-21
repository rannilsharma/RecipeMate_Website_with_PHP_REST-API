<?php
header('Content-Type: application/json');

require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/api_config.php';
require_once __DIR__ . '/auth.php';

$user_id = getAuthenticatedUserId();


$recipeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($recipeId <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid recipe ID"
    ]);
    exit;
}

$sql = "
SELECT 
    r.id,
    r.user_id,
    r.title,
    r.category,
    r.ingredients,
    r.steps,
    r.image_path,

    COUNT(DISTINCT rl.like_id) AS likes_count,
    COALESCE(AVG(rv.rating), 0) AS avg_rating,
    COUNT(DISTINCT rv.review_id) AS total_reviews,

    EXISTS (
        SELECT 1 FROM favorite_recipes f
        WHERE f.recipe_id = r.id AND f.user_id = ?
    ) AS is_favorite,

    EXISTS (
        SELECT 1 FROM recipe_likes l
        WHERE l.recipe_id = r.id AND l.user_id = ?
    ) AS has_liked

FROM recipes r
LEFT JOIN recipe_likes rl ON r.id = rl.recipe_id
LEFT JOIN reviews rv ON r.id = rv.recipe_id

WHERE r.id = ?

GROUP BY 
    r.id,
    r.user_id,
    r.title,
    r.category,
    r.ingredients,
    r.steps,
    r.image_path

LIMIT 1;

";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $user_id, $recipeId);

$stmt->execute();

$result = $stmt->get_result();
$recipe = $result->fetch_assoc();

if (!$recipe) {
    echo json_encode([
        "success" => false,
        "message" => "Recipe not found"
    ]);
    exit;
}

$recipe['is_favorite'] = (bool)$recipe['is_favorite'];
$recipe['has_liked'] = (bool)$recipe['has_liked'];

// Build the image URL using BASE_URL.
// If image_path is already a full URL, keep it as-is.
if (!empty($recipe['image_path'])) {
    if (preg_match('/^https?:\/\//i', $recipe['image_path'])) {
        $recipe['image_url'] = $recipe['image_path'];
    } else {
        $recipe['image_url'] = rtrim(BASE_URL, '/') . '/' . ltrim($recipe['image_path'], '/');
    }
} else {
    $recipe['image_url'] = null;
}

echo json_encode([
    "success" => true,
    "recipe" => $recipe
]);