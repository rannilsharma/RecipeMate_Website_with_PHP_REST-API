<?php
header("Content-Type: application/json");
require_once __DIR__ . "/dbConnection.php";

$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    echo json_encode(["success" => false, "message" => "Invalid JSON"]);
    exit;
}

$name             = trim($input['name'] ?? '');
$email            = trim($input['email'] ?? '');
$password         = trim($input['password'] ?? '');
$confirm_password = trim($input['confirm_password'] ?? '');
$is_admin         = isset($input['is_admin']) && $input['is_admin'] == 1 ? 1 : 0;

/* Validation */
if ($name === '' || $email === '' || $password === '' || $confirm_password === '') {
    echo json_encode(["success" => false, "message" => "All fields are required"]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email address"]);
    exit;
}

if ($password !== $confirm_password) {
    echo json_encode(["success" => false, "message" => "Passwords do not match"]);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(["success" => false, "message" => "Password must be at least 6 characters"]);
    exit;
}

/* Check existing user */
$checkSql = "SELECT user_id FROM users WHERE email = ? OR userName = ?";
$stmt = $conn->prepare($checkSql);
$stmt->bind_param("ss", $email, $name);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Username or email already exists"]);
    exit;
}
$stmt->close();

/* Insert user */
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$insertSql = "INSERT INTO users (userName, email, password, is_admin)
              VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($insertSql);
$stmt->bind_param("sssi", $name, $email, $hashedPassword, $is_admin);

if (!$stmt->execute()) {
    echo json_encode(["success" => false, "message" => "Registration failed"]);
    exit;
}

/* Optional token (future auto-login) */
$token = bin2hex(random_bytes(32));

echo json_encode([
    "success" => true,
    "message" => "Registration successful",
    "token" => $token
]);

$stmt->close();
$conn->close();
