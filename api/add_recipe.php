<?php
header('Content-Type: application/json');

require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';

$userId = getAuthenticatedUserId();

if (!$userId) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

$title       = $_POST['title'] ?? '';
$category    = $_POST['category'] ?? '';
$description = $_POST['description'] ?? '';
$ingredients = $_POST['ingredients'] ?? '';
$steps       = $_POST['steps'] ?? '';
$tags        = $_POST['tags'] ?? '';

if (!$title || !$category || !$description || !$ingredients || !$steps) {
    echo json_encode([
        "success" => false,
        "message" => "Missing required fields"
    ]);
    exit;
}

/* IMAGE UPLOAD */
$imagePath = null;

if (!empty($_FILES['image']['name'])) {
    $uploadDir = dirname(__DIR__) . '/uploads/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = time() . "_" . basename($_FILES['image']['name']);
    $targetPath = $uploadDir . $fileName;

    $ext = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($ext, $allowed)) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid image type"
        ]);
        exit;
    }

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
        echo json_encode([
            "success" => false,
            "message" => "Image upload failed"
        ]);
        exit;
    }

    /*
     * Store only the relative path in the database.
     *
     * Example:
     * uploads/1723456789_recipe.jpg
     *
     * BASE_URL will be added later by the API
     * when returning the image URL.
     */
    $imagePath = "uploads/" . $fileName;
}

$stmt = $conn->prepare("
    INSERT INTO recipes 
    (user_id, title, category, description, ingredients, steps, tags, image_path)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "isssssss",
    $userId,
    $title,
    $category,
    $description,
    $ingredients,
    $steps,
    $tags,
    $imagePath
);

$stmt->execute();

echo json_encode([
    "success" => true,
    "recipe_id" => $stmt->insert_id
]);