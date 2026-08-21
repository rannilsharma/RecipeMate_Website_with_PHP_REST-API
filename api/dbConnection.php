<?php
header("Content-Type: application/json");

$isLocal = !getenv('APP_ENV') || getenv('APP_ENV') === 'local';

if ($isLocal) {
    $DB_HOST = "localhost";
    $DB_USER = "root";
    $DB_PASS = "";
    $DB_NAME = "recipe_portal";
    $DB_PORT = 3309;
} else {
    $DB_HOST = getenv('DB_HOST');
    $DB_USER = getenv('DB_USER');
    $DB_PASS = getenv('DB_PASS');
    $DB_NAME = getenv('DB_NAME');
    $DB_PORT = getenv('DB_PORT') ?: 3306;
}

if (!$isLocal) {
    $conn = mysqli_init();
    mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
    mysqli_real_connect($conn, $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, (int)$DB_PORT, NULL, MYSQLI_CLIENT_SSL);
} else {
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, (int)$DB_PORT);
}

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed",
        "error" => $conn->connect_error
    ]);
    exit;
}

$conn->set_charset("utf8mb4");