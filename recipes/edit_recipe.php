<?php
session_start();

require_once __DIR__ . '/../dbConnection.php';
require_once __DIR__ . '/../config/config.php';

// ======================
// ENSURE USER IS LOGGED IN
// ======================
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$loggedUserId = (int) $_SESSION['user_id'];

// ======================
// GET RECIPE ID
// ======================
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("❌ No valid recipe ID provided.");
}

$id = (int) $_GET['id'];

// ======================
// FETCH RECIPE
// ======================
$stmt = $conn->prepare("
    SELECT *
    FROM recipes
    WHERE id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    die("❌ Recipe not found.");
}

$recipe = $result->fetch_assoc();
$stmt->close();

// ======================
// CHECK OWNERSHIP
// ======================
if ((int) $recipe['user_id'] !== $loggedUserId) {
    die("❌ You cannot edit a recipe that is not yours.");
}

// ======================
// HANDLE FORM SUBMISSION
// ======================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $ingredients = trim($_POST['ingredients'] ?? '');
    $steps = trim($_POST['steps'] ?? '');
    $tags = trim($_POST['tags'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // Basic validation
    if (
        $title === '' ||
        $category === '' ||
        $ingredients === '' ||
        $steps === ''
    ) {
        die("❌ Please fill in all required fields.");
    }

    // Keep existing image by default
    $imagePath = $recipe['image_path'];

    // ======================
    // HANDLE NEW IMAGE UPLOAD
    // ======================
    if (
        isset($_FILES['image']) &&
        $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            die("❌ There was an error uploading the image.");
        }

        $allowedTypes = [
            'jpg',
            'jpeg',
            'png',
            'gif',
            'webp'
        ];

        $originalName = $_FILES['image']['name'];

        $fileExtension = strtolower(
            pathinfo($originalName, PATHINFO_EXTENSION)
        );

        if (!in_array($fileExtension, $allowedTypes, true)) {
            die(
                "❌ Invalid image format. Allowed formats: JPG, JPEG, PNG, GIF, WEBP."
            );
        }

        // ======================
        // UPLOAD DIRECTORY
        // ======================

        $targetDir = __DIR__ . '/../uploads/';

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // Generate safer unique filename
        $fileName = uniqid('recipe_', true) . '.' . $fileExtension;

        $targetFilePath = $targetDir . $fileName;

        // Move uploaded image
        if (!move_uploaded_file(
            $_FILES['image']['tmp_name'],
            $targetFilePath
        )) {
            die("❌ Failed to upload the recipe image.");
        }

        // ======================
        // STORE RELATIVE PATH
        // ======================
        //
        // Database stores:
        // uploads/recipe_xxxxx.jpg
        //
        // NOT:
        // /recipe_meal_planner/uploads/recipe_xxxxx.jpg
        //
        // BASE_URL is added only when displaying the image.

        $imagePath = 'uploads/' . $fileName;
    }

    // ======================
    // UPDATE RECIPE
    // ======================
    $stmt = $conn->prepare("
        UPDATE recipes
        SET
            title = ?,
            category = ?,
            ingredients = ?,
            steps = ?,
            tags = ?,
            description = ?,
            image_path = ?
        WHERE id = ?
          AND user_id = ?
    ");

    if (!$stmt) {
        die("❌ Failed to prepare update statement.");
    }

    $stmt->bind_param(
        "sssssssii",
        $title,
        $category,
        $ingredients,
        $steps,
        $tags,
        $description,
        $imagePath,
        $id,
        $loggedUserId
    );

    if ($stmt->execute()) {

        $stmt->close();
        $conn->close();

        echo "
        <script>
            alert('✅ Recipe updated successfully!');
            window.location.href = '" .
            BASE_URL . "recipes/view_recipe.php?id=" . $id .
            "';
        </script>";

        exit();

    } else {

        $errorMessage = htmlspecialchars($stmt->error);

        $stmt->close();
        $conn->close();

        die("❌ Error updating recipe: " . $errorMessage);
    }
}

// ======================
// CATEGORY LIST
// ======================
$categories = [
    'Breakfast',
    'Lunch',
    'Dinner',
    'Dessert',
    'Appetizer',
    'Snack',
    'Beverage',
    'Soup',
    'Salad',
    'Main Course',
    'Side Dish',
    'Seafood',
    'Pasta',
    'Pizza'
];

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit Recipe | RecipeMate</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap"
        rel="stylesheet"
    >

    <style>

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #071524, #0b2238);
            color: #fff;
            overflow-x: hidden;
        }

        header {
            background: transparent;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 60px;
            background: rgba(0, 40, 80, 0.4);
            backdrop-filter: blur(12px) saturate(150%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0 0 20px 20px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.35);
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: all 0.4s ease;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #a7d8ff;
            text-shadow: 0 0 8px rgba(167, 216, 255, 0.5);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-links {
            list-style: none;
            display: flex;
            gap: 28px;
            margin: 0;
            padding: 0;
        }

        .nav-links a {
            color: #e3f2fd;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: #4fc3f7;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: #4fc3f7;
            transition: width 0.3s ease;
        }

        .nav-links a:hover::after,
        .nav-links a.active::after {
            width: 100%;
        }

        /* ======================
           CONTAINER
        ====================== */

        .container {
            max-width: 800px;
            margin: 60px auto;
            padding: 30px 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            backdrop-filter: blur(15px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
        }

        h1 {
            text-align: center;
            font-size: 2rem;
            margin-bottom: 30px;
            color: #ffffff;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        label {
            font-weight: 500;
            color: #dcdcdc;
            display: block;
            margin-bottom: 7px;
        }

        input[type="text"],
        textarea,
        select {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            font-size: 1rem;
            outline: none;
            box-sizing: border-box;
        }

        input[type="text"]:focus,
        textarea:focus,
        select:focus {
            background: rgba(255, 255, 255, 0.25);
            box-shadow: 0 0 10px rgba(108, 184, 255, 0.5);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        select {
            cursor: pointer;
        }

        select option {
            background: #0b2238;
            color: #fff;
        }

        input[type="file"] {
            width: 100%;
            box-sizing: border-box;
            background: rgba(255, 255, 255, 0.2);
            padding: 10px;
            border-radius: 10px;
            color: #ddd;
        }

        /* ======================
           CURRENT IMAGE
        ====================== */

        .current-image {
            margin-top: 12px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
        }

        .current-image p {
            margin: 0 0 10px;
            color: #cdeaff;
        }

        .current-image img {
            width: 180px;
            max-height: 140px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        /* ======================
           BUTTONS
        ====================== */

        .button-container {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 10px;
        }

        .btn {
            padding: 12px 28px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            display: inline-block;
            font-size: 1rem;
        }

        .btn-primary {
            background: linear-gradient(90deg, #007bff, #00bcd4);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 12px rgba(0, 188, 212, 0.6);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.35);
        }

        /* ======================
           FOOTER
        ====================== */

        footer {
            text-align: center;
            padding: 30px;
            color: rgba(255, 255, 255, 0.7);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            background: rgba(0, 0, 30, 0.2);
            margin-top: 80px;
        }

        /* ======================
           RESPONSIVE
        ====================== */

        @media (max-width: 900px) {

            .navbar {
                padding: 18px 25px;
            }

            .nav-links {
                gap: 15px;
                flex-wrap: wrap;
                justify-content: flex-end;
            }

            .container {
                margin: 40px 20px;
                padding: 25px;
            }
        }

    </style>

</head>

<body>

<header>

    <nav class="navbar">

        <div class="logo">
            🍽️ RecipeMate
        </div>

        <ul class="nav-links">

            <li>
                <a href="<?= BASE_URL ?>dashboard.php">
                    Home
                </a>
            </li>

            <li>
                <a href="<?= BASE_URL ?>recipes/search_recipe.php">
                    Search Recipes
                </a>
            </li>

            <li>
                <a
                    href="<?= BASE_URL ?>recipes/recipe_index.php"
                    class="active"
                >
                    Recipes
                </a>
            </li>

            <li>
                <a href="<?= BASE_URL ?>planner/meal_planner.php">
                    Meal Planner
                </a>
            </li>

            <li>
                <a href="<?= BASE_URL ?>favorites/favorites.php">
                    Favorites
                </a>
            </li>

            <li>
                <a href="<?= BASE_URL ?>profile/profile.php">
                    Profile
                </a>
            </li>

            <li>
                <a href="<?= BASE_URL ?>index.php">
                    Logout
                </a>
            </li>

        </ul>

    </nav>

</header>


<div class="container">

    <h1>✏️ Edit Recipe</h1>

    <form
        action=""
        method="POST"
        enctype="multipart/form-data"
    >

        <!-- Recipe Title -->
        <div>

            <label for="title">
                Recipe Title
            </label>

            <input
                type="text"
                id="title"
                name="title"
                value="<?= htmlspecialchars($recipe['title']); ?>"
                required
            >

        </div>


        <!-- Category -->
        <div>

            <label for="category">
                Category
            </label>

            <select
                id="category"
                name="category"
                required
            >

                <option value="">
                    Select Category
                </option>

                <?php foreach ($categories as $cat): ?>

                    <option
                        value="<?= htmlspecialchars($cat); ?>"
                        <?= ($recipe['category'] === $cat) ? 'selected' : ''; ?>
                    >
                        <?= htmlspecialchars($cat); ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <!-- Description -->
        <div>

            <label for="description">
                Description
            </label>

            <textarea
                id="description"
                name="description"
                placeholder="Brief description..."
            ><?= htmlspecialchars($recipe['description']); ?></textarea>

        </div>


        <!-- Ingredients -->
        <div>

            <label for="ingredients">
                Ingredients
            </label>

            <textarea
                id="ingredients"
                name="ingredients"
                required
            ><?= htmlspecialchars($recipe['ingredients']); ?></textarea>

        </div>


        <!-- Steps -->
        <div>

            <label for="steps">
                Steps
            </label>

            <textarea
                id="steps"
                name="steps"
                required
            ><?= htmlspecialchars($recipe['steps']); ?></textarea>

        </div>


        <!-- Tags -->
        <div>

            <label for="tags">
                Tags (optional)
            </label>

            <input
                type="text"
                id="tags"
                name="tags"
                value="<?= htmlspecialchars($recipe['tags'] ?? ''); ?>"
                placeholder="e.g. spicy, vegan, healthy"
            >

        </div>


        <!-- Image -->
        <div>

            <label for="image">
                Change Recipe Image
            </label>

            <input
                type="file"
                id="image"
                name="image"
                accept=".jpg,.jpeg,.png,.gif,.webp"
            >

            <?php if (!empty($recipe['image_path'])): ?>

                <div class="current-image">

                    <p>
                        Current Recipe Image:
                    </p>

                    <?php
                    /*
                     * Database should contain:
                     *
                     * uploads/image.jpg
                     *
                     * Build the actual browser URL:
                     *
                     * local:
                     * /recipe_meal_planner/uploads/image.jpg
                     *
                     * production:
                     * /uploads/image.jpg
                     */

                    $currentImagePath = $recipe['image_path'];

                    if (preg_match(
                        '/^https?:\/\//i',
                        $currentImagePath
                    )) {

                        $currentImageUrl = $currentImagePath;

                    } else {

                        $currentImageUrl =
                            rtrim(BASE_URL, '/') .
                            '/' .
                            ltrim($currentImagePath, '/');
                    }
                    ?>

                    <img
                        src="<?= htmlspecialchars($currentImageUrl); ?>"
                        alt="Current Recipe Image"
                    >

                </div>

            <?php endif; ?>

        </div>


        <!-- Buttons -->
        <div class="button-container">

            <button
                type="submit"
                class="btn btn-primary"
            >
                💾 Update Recipe
            </button>

            <a
                href="<?= BASE_URL ?>recipes/view_recipe.php?id=<?= $id; ?>"
                class="btn btn-secondary"
            >
                Cancel
            </a>

        </div>

    </form>

</div>


<footer>

    © 2025 RecipeMate.
    All rights reserved.

</footer>

</body>
</html>