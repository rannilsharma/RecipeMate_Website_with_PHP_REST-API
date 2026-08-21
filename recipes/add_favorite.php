<?php

session_start();

require_once __DIR__ . '/../dbConnection.php';
require_once __DIR__ . '/../config/config.php';

// ======================
// CHECK IF USER IS LOGGED IN
// ======================
if (!isset($_SESSION['user_id'])) {

    echo "
    <script>
        alert('⚠️ Please log in to add recipes to favorites.');
        window.location.href = '" . BASE_URL . "index.php';
    </script>
    ";

    exit();
}

$user_id = (int) $_SESSION['user_id'];
$recipe_id = (int) ($_POST['recipe_id'] ?? 0);


// ======================
// VALIDATE RECIPE ID
// ======================
if ($recipe_id <= 0) {

    echo "
    <script>
        alert('❌ Invalid recipe.');
        window.history.back();
    </script>
    ";

    exit();
}


// ======================
// CHECK IF RECIPE EXISTS
// ======================
$recipeCheck = $conn->prepare("
    SELECT id
    FROM recipes
    WHERE id = ?
");

if (!$recipeCheck) {
    die("❌ Database query failed: " . htmlspecialchars($conn->error));
}

$recipeCheck->bind_param("i", $recipe_id);
$recipeCheck->execute();

$recipeResult = $recipeCheck->get_result();

if ($recipeResult->num_rows === 0) {

    $recipeCheck->close();
    $conn->close();

    echo "
    <script>
        alert('❌ Recipe not found.');
        window.history.back();
    </script>
    ";

    exit();
}

$recipeCheck->close();


// ======================
// CHECK IF ALREADY FAVORITED
// ======================
$check = $conn->prepare("
    SELECT id
    FROM favorite_recipes
    WHERE user_id = ?
      AND recipe_id = ?
");

if (!$check) {
    die("❌ Database query failed: " . htmlspecialchars($conn->error));
}

$check->bind_param(
    "ii",
    $user_id,
    $recipe_id
);

$check->execute();

$result = $check->get_result();

if ($result->num_rows > 0) {

    $check->close();
    $conn->close();

    echo "
    <script>
        alert('💖 This recipe is already in your favorites!');
        window.history.back();
    </script>
    ";

    exit();
}

$check->close();


// ======================
// ADD RECIPE TO FAVORITES
// ======================
$stmt = $conn->prepare("
    INSERT INTO favorite_recipes
    (
        user_id,
        recipe_id
    )
    VALUES (?, ?)
");

if (!$stmt) {
    die("❌ Database query failed: " . htmlspecialchars($conn->error));
}

$stmt->bind_param(
    "ii",
    $user_id,
    $recipe_id
);


// ======================
// EXECUTE INSERT
// ======================
if ($stmt->execute()) {

    echo "
    <script>
        alert('✅ Recipe added to favorites!');
        window.history.back();
    </script>
    ";

} else {

    // Duplicate entry protection
    if ($stmt->errno == 1062) {

        echo "
        <script>
            alert('❤️ This recipe is already in your favorites.');
            window.history.back();
        </script>
        ";

    } else {

        echo "
        <script>
            alert('❌ Error adding to favorites. Please try again.');
            window.history.back();
        </script>
        ";
    }
}


// ======================
// CLOSE CONNECTIONS
// ======================
$stmt->close();
$conn->close();

?>