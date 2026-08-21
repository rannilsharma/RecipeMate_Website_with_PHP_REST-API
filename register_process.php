<?php
// register_process.php

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/dbConnection.php';

session_start();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'register.php');
    exit();
}

// === 1️⃣ Collect and sanitize inputs ===
$name             = trim($_POST['name'] ?? '');
$email            = trim($_POST['email'] ?? '');
$password         = trim($_POST['password'] ?? '');
$confirm_password = trim($_POST['confirm_password'] ?? '');
$is_admin         = isset($_POST['is_admin']) && ($_POST['is_admin'] == '1' || $_POST['is_admin'] === 'on') ? 1 : 0;

// === 2️⃣ Basic validation ===
if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
    echo "<script>
            alert('Please fill in all required fields.');
            window.history.back();
          </script>";
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "<script>
            alert('Please enter a valid email address.');
            window.history.back();
          </script>";
    exit();
}

if ($password !== $confirm_password) {
    echo "<script>
            alert('Passwords do not match.');
            window.history.back();
          </script>";
    exit();
}

if (strlen($password) < 6) {
    echo "<script>
            alert('Password must be at least 6 characters long.');
            window.history.back();
          </script>";
    exit();
}

// === 3️⃣ Check if email or username already exists ===
$checkSql = "SELECT user_id FROM users WHERE email = ? OR userName = ?";

$stmt = $conn->prepare($checkSql);
$stmt->bind_param("ss", $email, $name);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<script>
            alert('An account with that email or username already exists.');
            window.history.back();
          </script>";

    $stmt->close();
    $conn->close();
    exit();
}

$stmt->close();

// === 4️⃣ Hash password ===
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// === 5️⃣ Insert into database ===
$insertSql = "INSERT INTO users (userName, email, password, is_admin) VALUES (?, ?, ?, ?)";

$stmt = $conn->prepare($insertSql);

if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    die("An error occurred. Try again later.");
}

$stmt->bind_param("sssi", $name, $email, $hashedPassword, $is_admin);

if ($stmt->execute()) {

    // Optional: auto-login after registration
    $_SESSION['user_id']  = $stmt->insert_id;
    $_SESSION['userName'] = $name;
    $_SESSION['email']    = $email;
    $_SESSION['is_admin'] = $is_admin;

    echo "<script>
            alert('Registration successful! Redirecting to login...');
            window.location.href = '" . BASE_URL . "index.php';
          </script>";

} else {

    error_log("Insert failed: " . $stmt->error);

    echo "<script>
            alert('Something went wrong while creating your account.');
            window.history.back();
          </script>";
}

$stmt->close();
$conn->close();

exit();
?>