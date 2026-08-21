<?php
session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/dbConnection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

// Get and trim inputs
$identifier = isset($_POST['email']) ? trim($_POST['email']) : '';
$password   = isset($_POST['password']) ? trim($_POST['password']) : '';
$is_admin   = isset($_POST['is_admin']) && ($_POST['is_admin'] == '1' || $_POST['is_admin'] === 'on') ? 1 : 0;

// Basic validation
if (empty($identifier) || empty($password)) {
    echo "<script>alert('Please provide both email/username and password.'); window.history.back();</script>";
    exit();
}

// Query that checks both email OR username
$sql = "SELECT user_id, userName, email, password, is_admin, status 
        FROM users 
        WHERE email = ? OR userName = ? 
        LIMIT 1";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    die("An error occurred. Try again later.");
}

$stmt->bind_param("ss", $identifier, $identifier);
$stmt->execute();

$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {

    $user = $result->fetch_assoc();

    // Check account status before verifying password
    if (isset($user['status']) && strtolower($user['status']) === 'disabled') {
        echo "<script>
                alert('Your account has been suspended for violating our terms of use. Please contact support.');
                window.history.back();
              </script>";
        exit();
    }

    // Verify password
    if (password_verify($password, $user['password'])) {

        session_regenerate_id(true);

        // Save session information
        $_SESSION['user_id']  = (int)$user['user_id'];
        $_SESSION['userName'] = $user['userName'];
        $_SESSION['email']    = $user['email'];
        $_SESSION['is_admin'] = (int)$user['is_admin'];

        // Redirect based on user role
        if ($_SESSION['is_admin'] === 1) {
            header('Location: ' . BASE_URL . 'admin/dashboard.php');
        } else {
            header('Location: ' . BASE_URL . 'dashboard.php');
        }

        $stmt->close();
        $conn->close();
        exit();

    } else {

        echo "<script>
                alert('Invalid password.');
                window.history.back();
              </script>";
    }

} else {

    echo "<script>
            alert('No account found with that email or username.');
            window.history.back();
          </script>";
}

$stmt->close();
$conn->close();
?>