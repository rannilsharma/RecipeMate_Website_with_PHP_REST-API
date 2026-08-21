<?php
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../dbConnection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $meals = $_POST['plan_data'] ?? '';

    if ($meals === '') {
        die("No meal plan data to save.");
    }

    // Save meal plan in DB
    $stmt = $conn->prepare("
        INSERT INTO meal_plans (user_id, plan_name, plan_date, meals)
        VALUES (?, 'My Meal Plan', CURDATE(), ?)
    ");

    $stmt->bind_param("is", $user_id, $meals);

    if ($stmt->execute()) {

        echo "<script>
                alert('✅ Meal Plan saved successfully!');
                window.location.href='" . BASE_URL . "planner/meal_planner.php';
              </script>";

    } else {

        echo "<script>
                alert('❌ Failed to save meal plan.');
                window.location.href='" . BASE_URL . "planner/meal_planner.php';
              </script>";
    }

    $stmt->close();
}
?>