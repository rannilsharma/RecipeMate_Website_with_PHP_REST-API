<?php
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

$userId = getAuthenticatedUserId();

if (!$userId) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit();
}

$stmt = $conn->prepare(
    "SELECT meal_plan_id, plan_name, meals, created_at
     FROM meal_plans
     WHERE user_id = ?
     ORDER BY created_at DESC"
);

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$plans = [];

while ($row = $result->fetch_assoc()) {
    $decodedMeals = json_decode($row['meals'], true);
    //$row['meals'] = json_decode($row['meals'], true);

    $row['meals'] = is_array($decodedMeals) ? $decodedMeals : [];
    $plans[] = $row;
}

echo json_encode([
    'success' => true,
    'meal_plans' => $plans
]);
