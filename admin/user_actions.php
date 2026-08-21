<?php
// admin/user_actions.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../dbConnection.php';

// simple admin check
if (!isset($_SESSION['is_admin']) || intval($_SESSION['is_admin']) !== 1) {
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'invalid_method']);
    exit();
}

$action = $_POST['action'] ?? '';
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

if ($user_id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'invalid_user_id']);
    exit();
}

// Helper: send error
function json_fail($msg) {
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit();
}

// Update status (expects 'active' or 'disabled')
if ($action === 'update') {
    $status = $_POST['status'] ?? '';
    // normalize
    $status = strtolower(trim($status));
    if (!in_array($status, ['active', 'disabled'])) {
        json_fail('invalid_status_value');
    }

    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
    if (!$stmt) json_fail('prepare_failed: ' . $conn->error);
    $stmt->bind_param("si", $status, $user_id);
    $ok = $stmt->execute();
    if ($ok) {
        echo json_encode(['ok' => true, 'msg' => "User status updated to {$status}."]);
        $stmt->close();
        exit();
    } else {
        json_fail('execute_failed: ' . $stmt->error);
    }
}

// Delete user
if ($action === 'delete') {
    // optional protection: prevent deleting yourself
    if (isset($_SESSION['user_id']) && intval($_SESSION['user_id']) === $user_id) {
        json_fail('cannot_delete_self');
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    if (!$stmt) json_fail('prepare_failed: ' . $conn->error);
    $stmt->bind_param("i", $user_id);
    $ok = $stmt->execute();
    if ($ok) {
        echo json_encode(['ok' => true, 'msg' => 'User deleted.']);
        $stmt->close();
        exit();
    } else {
        json_fail('execute_failed: ' . $stmt->error);
    }
}

echo json_encode(['ok' => false, 'error' => 'unknown_action']);
exit();
