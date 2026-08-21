<?php
header('Content-Type: application/json');
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';

$userId = getAuthenticatedUserId();
if (!$userId) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$recipeId    = intval($_POST['recipe_id'] ?? 0);
$title       = $_POST['title'] ?? '';
$category    = $_POST['category'] ?? '';
$description = $_POST['description'] ?? '';
$ingredients = $_POST['ingredients'] ?? '';
$steps       = $_POST['steps'] ?? '';
$tags        = $_POST['tags'] ?? '';

if ($recipeId <= 0 || !$title || !$category || !$description || !$ingredients || !$steps) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

// Ownership check
$stmt = $conn->prepare("SELECT user_id, image_path FROM recipes WHERE id = ?");
$stmt->bind_param("i", $recipeId);
$stmt->execute();
$recipe = $stmt->get_result()->fetch_assoc();

if (!$recipe) {
    echo json_encode(["success" => false, "message" => "Recipe not found"]);
    exit;
}

if ($recipe['user_id'] != $userId) {
    echo json_encode(["success" => false, "message" => "You can only edit your own recipes"]);
    exit;
}

$imagePath = $recipe['image_path'];

// New image upload (optional)
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
        echo json_encode(["success" => false, "message" => "Invalid image type"]);
        exit;
    }

    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
        // Delete old image
        if (!empty($recipe['image_path'])) {
            $oldPath = dirname(__DIR__) . $recipe['image_path'];
            if (file_exists($oldPath)) @unlink($oldPath);
        }
        $imagePath = "/recipe_meal_planner/uploads/" . $fileName;
    }
}

$stmt = $conn->prepare("
    UPDATE recipes 
    SET title = ?, category = ?, description = ?, ingredients = ?, steps = ?, tags = ?, image_path = ?
    WHERE id = ?
");
$stmt->bind_param(
    "sssssssi",
    $title, $category, $description, $ingredients, $steps, $tags, $imagePath, $recipeId
);
$stmt->execute();

echo json_encode([
    "success" => true,
    "message" => "Recipe updated successfully"
]);