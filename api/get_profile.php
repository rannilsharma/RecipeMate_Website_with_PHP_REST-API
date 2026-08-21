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

/* USER INFO */
$userQuery = "SELECT userName, email, created_at FROM users WHERE user_id = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

/* FAVORITES COUNT */
$favQuery = "SELECT COUNT(*) AS favCount FROM favorite_recipes WHERE user_id = ?";
$stmtFav = $conn->prepare($favQuery);
$stmtFav->bind_param("i", $userId);
$stmtFav->execute();
$favCount = $stmtFav->get_result()->fetch_assoc()['favCount'];

/* LIKES COUNT */
$likeQuery = "
    SELECT COUNT(rl.like_id) AS likesCount
    FROM recipe_likes rl
    JOIN recipes r ON rl.recipe_id = r.id
    WHERE r.user_id = ?
";
$stmtLike = $conn->prepare($likeQuery);
$stmtLike->bind_param("i", $userId);
$stmtLike->execute();
$likesCount = $stmtLike->get_result()->fetch_assoc()['likesCount'];

echo json_encode([
    "success" => true,
    "data" => [
        "username" => $user['userName'],
        "email" => $user['email'],
        "member_since" => date("Y", strtotime($user['created_at'])),
        "favorite_count" => (int)$favCount,
        "likes_count" => (int)$likesCount
    ]
]);
