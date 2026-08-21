<?php
header('Content-Type: application/json');
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';

$userId = getAuthenticatedUserId();
if (!$userId) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$recipeId = intval($_POST['recipe_id'] ?? 0);
if ($recipeId <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid recipe ID"]);
    exit;
}

// Get recipe owner
$stmt = $conn->prepare("SELECT user_id, image_path FROM recipes WHERE id = ?");
$stmt->bind_param("i", $recipeId);
$stmt->execute();
$recipe = $stmt->get_result()->fetch_assoc();

if (!$recipe) {
    echo json_encode(["success" => false, "message" => "Recipe not found"]);
    exit;
}

// Check if admin
$stmtAdmin = $conn->prepare("SELECT is_admin FROM users WHERE user_id = ?");
$stmtAdmin->bind_param("i", $userId);
$stmtAdmin->execute();
$admin = $stmtAdmin->get_result()->fetch_assoc();
$isAdmin = $admin && $admin['is_admin'] == 1;

if ($recipe['user_id'] != $userId && !$isAdmin) {
    echo json_encode(["success" => false, "message" => "You can only delete your own recipes"]);
    exit;
}

// Delete image file
if (!empty($recipe['image_path'])) {
    $imageUrlPath = parse_url($recipe['image_path'], PHP_URL_PATH);

    if ($imageUrlPath) {
        $fullPath = dirname(__DIR__) . $imageUrlPath;

        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
    }
}

$stmtDel = $conn->prepare("DELETE FROM recipes WHERE id = ?");
$stmtDel->bind_param("i", $recipeId);
$stmtDel->execute();

echo json_encode([
    "success" => true,
    "message" => "Recipe deleted successfully"
]);