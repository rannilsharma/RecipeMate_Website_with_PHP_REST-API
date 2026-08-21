<?php
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../dbConnection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$recipe_id = $_POST['recipe_id'] ?? null;

if ($recipe_id) {
    $stmt = $conn->prepare("
        DELETE FROM favorite_recipes
        WHERE user_id = ? AND recipe_id = ?
    ");

    $stmt->bind_param("ii", $user_id, $recipe_id);
    $stmt->execute();

    $stmt->close();
}

header("Location: " . BASE_URL . "favorites/favorites.php");
exit();
?>