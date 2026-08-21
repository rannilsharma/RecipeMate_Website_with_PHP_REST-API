<?php
session_start();

require_once __DIR__ . '/../dbConnection.php';

header('Content-Type: text/plain');

if (!isset($_SESSION['user_id'])) {
    echo "unauthorized";
    exit();
}

$user_id = $_SESSION['user_id'];

$email = trim($_POST['email'] ?? '');

if ($email === '') {
    echo "invalid_input";
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "invalid_email";
    exit();
}

// Check if email is already used by another account
$query = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    echo "prepare_failed: " . $conn->error;
    exit();
}

$stmt->bind_param("si", $email, $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "email_exists";
    $stmt->close();
    $conn->close();
    exit();
}

$stmt->close();

// Update email
$query = "UPDATE users SET email = ? WHERE user_id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    echo "prepare_failed: " . $conn->error;
    exit();
}

$stmt->bind_param("si", $email, $user_id);

if ($stmt->execute()) {
    echo "success";
} else {
    echo "execute_failed: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>