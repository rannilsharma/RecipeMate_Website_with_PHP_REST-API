<?php
session_start();

// Database connection
require_once __DIR__ . '/../dbConnection.php';

// Load configuration / BASE_URL
require_once __DIR__ . '/../config/config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$userId = (int)$_SESSION['user_id'];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $ingredients = trim($_POST['ingredients'] ?? '');
    $steps = trim($_POST['steps'] ?? '');
    $tags = trim($_POST['tags'] ?? '');

    // Handle image upload
    $imagePath = "";

    if (!empty($_FILES["image"]["name"])) {

        // Physical server directory:
        // recipe_meal_planner/uploads/
        $targetDir = __DIR__ . '/../uploads/';

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = time() . "_" . basename($_FILES["image"]["name"]);
        $targetFilePath = $targetDir . $fileName;

        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

        $allowedTypes = [
            "jpg",
            "jpeg",
            "png",
            "gif",
            "webp"
        ];

        if (in_array($fileType, $allowedTypes, true)) {

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {

                /*
                 * IMPORTANT:
                 * Store only the relative path in the database.
                 *
                 * Database:
                 * uploads/imagefile.jpg
                 *
                 * The website/API will then prepend BASE_URL.
                 */
                $imagePath = "uploads/" . $fileName;
            }
        }
    }

    // Insert recipe into database using prepared statement
    $stmt = $conn->prepare("
        INSERT INTO recipes
        (
            user_id,
            title,
            category,
            description,
            ingredients,
            steps,
            tags,
            image_path
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        die("❌ Database prepare failed: " . $conn->error);
    }

    $stmt->bind_param(
        "isssssss",
        $userId,
        $title,
        $category,
        $description,
        $ingredients,
        $steps,
        $tags,
        $imagePath
    );

    if ($stmt->execute()) {

        echo "
        <script>
            alert('✅ Recipe added successfully!');
            window.location.href = '" . BASE_URL . "recipes/recipe_index.php';
        </script>
        ";

        exit();

    } else {

        echo "❌ Error: " . htmlspecialchars($stmt->error);
    }

    $stmt->close();
}

$conn->close();
?>