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

$planData = $_POST['plan_data'] ?? '';

if ($planData === '') {
    echo json_encode([
        'success' => false,
        'message' => 'No meal plan data provided'
    ]);
    exit();
}

$stmt = $conn->prepare("
    INSERT INTO meal_plans (user_id, plan_name, plan_date, meals)
    VALUES (?, 'My Meal Plan', CURDATE(), ?)
");
$stmt->bind_param("is", $userId, $planData);


if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Meal plan saved successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save meal plan'
    ]);
}
