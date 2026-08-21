<?php
//for testing
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

require_once __DIR__ . '/dbConnection.php';
header('Content-Type: application/json');


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed"
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$identifier = trim($data['identifier'] ?? '');
$password   = trim($data['password'] ?? '');

if (empty($identifier) || empty($password)) {
    echo json_encode([
        "success" => false,
        "message" => "Email/Username and password are required"
    ]);
    exit;
}

// Same query as your website
$sql = "SELECT user_id, userName, email, password, is_admin, status
        FROM users
        WHERE email = ? OR userName = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $identifier, $identifier);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo json_encode([
        "success" => false,
        "message" => "No account found with that email or username"
    ]);
    exit;
}

$user = $result->fetch_assoc();

// Check account status
if (strtolower($user['status']) === 'disabled') {
    echo json_encode([
        "success" => false,
        "message" => "Your account has been suspended"
    ]);
    exit;
}

// Verify password
if (!password_verify($password, $user['password'])) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid password"
    ]);
    exit;
}

// Generate simple token (industry standard)
$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+7 days'));

$insert = $conn->prepare(
    "INSERT INTO user_tokens (user_id, token, expires_at)
     VALUES (?, ?, ?)"
);
$insert->bind_param("iss", $user['user_id'], $token, $expires);
$insert->execute();


echo json_encode([
    "success" => true,
    "message" => "Login successful",
    "token" => $token,
    "user" => [
        "user_id" => (int)$user['user_id'],
        "userName" => $user['userName'],
        "email" => $user['email'],
        "is_admin" => (int)$user['is_admin']
    ]
]);

$stmt->close();
$conn->close();
