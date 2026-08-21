<?php
require_once __DIR__ . '/dbConnection.php';

function getAuthenticatedUserId() {
    $headers = getallheaders();

    $authHeader =
        $headers['Authorization']
        ?? $headers['authorization']
        ?? null;

    if (!$authHeader) {
        return null;
    }

    if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        return null;
    }

    $token = $matches[1];

    global $conn;

    $stmt = $conn->prepare(
        "SELECT user_id
         FROM user_tokens
         WHERE token = ?
           AND expires_at > NOW()
         LIMIT 1"
    );
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        return (int)$row['user_id'];
    }

    return null;
}
