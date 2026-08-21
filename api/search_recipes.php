<?php
header('Content-Type: application/json');

require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/api_config.php';

$q = trim($_GET['q'] ?? '');

if ($q === '') {
    echo json_encode([
        "success" => false,
        "message" => "Search query is required",
        "recipes" => []
    ]);
    exit;
}

$sql = "
    SELECT 
        r.id,
        r.title,
        r.description,
        r.image_path,
        r.created_at,
        u.username
    FROM recipes r
    JOIN users u ON r.user_id = u.user_id
    WHERE 
        r.title LIKE ?
        OR r.description LIKE ?
        OR r.ingredients LIKE ?
        OR r.tags LIKE ?
    ORDER BY r.created_at DESC
";

$stmt = $conn->prepare($sql);

$like = "%{$q}%";
$stmt->bind_param("ssss", $like, $like, $like, $like);
$stmt->execute();

$result = $stmt->get_result();
$recipes = [];

while ($row = $result->fetch_assoc()) {

    $imageUrl = null;

    if (!empty($row['image_path'])) {

        // If image_path is already a complete URL,
        // use it directly.
        if (preg_match('/^https?:\/\//i', $row['image_path'])) {
            $imageUrl = $row['image_path'];
        } else {

            // Otherwise, build the URL using BASE_URL.
            $imageUrl = rtrim(BASE_URL, '/') . '/' . ltrim($row['image_path'], '/');
        }
    }

    $recipes[] = [
        "id" => (int)$row['id'],
        "title" => $row['title'],
        "description" => $row['description'],
        "image_url" => $imageUrl,
        "username" => $row['username'],
        "created_at" => $row['created_at']
    ];
}

$stmt->close();
$conn->close();

echo json_encode([
    "success" => true,
    "count" => count($recipes),
    "recipes" => $recipes
]);