<?php
session_start();
require_once __DIR__ . '/../dbConnection.php';

if (!isset($_SESSION['user_id'])) {
    echo "unauthorized";
    exit();
}

$user_id = $_SESSION['user_id'];
$userName = trim($_POST['userName'] ?? '');
$email = trim($_POST['email'] ?? '');

if ($userName === '' || $email === '') {
    echo "invalid_input";
    exit();
}

$query = "UPDATE users SET userName = ?, email = ? WHERE user_id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    echo "prepare_failed: " . $conn->error;
    exit();
}

$stmt->bind_param("ssi", $userName, $email, $user_id);

if ($stmt->execute()) {
    echo "success";
} else {
    echo "execute_failed: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
