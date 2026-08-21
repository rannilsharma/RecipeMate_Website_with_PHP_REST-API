<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../dbConnection.php';

if (isset($_GET['id']) && isset($_GET['status'])) {

    $user_id = intval($_GET['id']);
    $status = $_GET['status'] === 'active' ? 'active' : 'disabled';

    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
    $stmt->bind_param("si", $status, $user_id);

    if ($stmt->execute()) {

        header("Location: " . BASE_URL . "admin/users.php?msg=Status updated successfully");
        exit();

    } else {

        echo "Error updating status: " . $conn->error;
    }

} else {

    echo "Invalid request.";
}

?>