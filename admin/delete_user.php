<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../dbConnection.php';

if (isset($_GET['id'])) {

    $user_id = intval($_GET['id']);

    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {

        header(
            'Location: ' . BASE_URL . 'admin/users.php?msg=' .
            urlencode('User deleted successfully')
        );

        exit();

    } else {

        echo "Error deleting user: " . $conn->error;
    }

    $stmt->close();

} else {

    echo "Invalid request.";
}

$conn->close();

?>