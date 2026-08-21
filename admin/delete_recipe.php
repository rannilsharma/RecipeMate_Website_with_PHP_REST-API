<?php

session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../dbConnection.php';


// =====================================================
// CHECK IF USER IS LOGGED IN
// =====================================================

if (!isset($_SESSION['user_id'])) {

    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

$userId = (int) $_SESSION['user_id'];


// =====================================================
// VERIFY ADMIN ACCESS
// =====================================================

$isAdminQuery = "
    SELECT is_admin
    FROM users
    WHERE user_id = ?
";

$stmtAdmin = $conn->prepare($isAdminQuery);

if (!$stmtAdmin) {
    die("Database error.");
}

$stmtAdmin->bind_param("i", $userId);
$stmtAdmin->execute();

$resultAdmin = $stmtAdmin->get_result();
$userAdmin = $resultAdmin->fetch_assoc();

$stmtAdmin->close();

if (!$userAdmin || (int) $userAdmin['is_admin'] !== 1) {

    echo "Access denied. Admins only.";
    exit();
}


// =====================================================
// CHECK FOR RECIPE ID
// =====================================================

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {

    header(
        'Location: ' .
        BASE_URL .
        'admin/moderate_content.php?error=Missing+recipe+ID'
    );

    exit();
}

$recipeId = (int) $_GET['id'];


// =====================================================
// GET RECIPE IMAGE PATH BEFORE DELETING
// =====================================================

$imageQuery = "
    SELECT image_path
    FROM recipes
    WHERE id = ?
";

$stmtImage = $conn->prepare($imageQuery);

if (!$stmtImage) {
    die("Database error.");
}

$stmtImage->bind_param("i", $recipeId);
$stmtImage->execute();

$resultImage = $stmtImage->get_result();

if ($resultImage->num_rows === 0) {

    $stmtImage->close();

    header(
        'Location: ' .
        BASE_URL .
        'admin/moderate_content.php?error=Recipe+not+found'
    );

    exit();
}

$row = $resultImage->fetch_assoc();

$imagePath = trim($row['image_path'] ?? '');

$stmtImage->close();


// =====================================================
// DELETE RELATED RECIPE LIKES
// =====================================================

$stmtLikes = $conn->prepare("
    DELETE FROM recipe_likes
    WHERE recipe_id = ?
");

if ($stmtLikes) {

    $stmtLikes->bind_param("i", $recipeId);
    $stmtLikes->execute();
    $stmtLikes->close();
}


// =====================================================
// DELETE RELATED REVIEWS
// =====================================================

$stmtReviews = $conn->prepare("
    DELETE FROM reviews
    WHERE recipe_id = ?
");

if ($stmtReviews) {

    $stmtReviews->bind_param("i", $recipeId);
    $stmtReviews->execute();
    $stmtReviews->close();
}


// =====================================================
// DELETE RECIPE
// =====================================================

$deleteQuery = "
    DELETE FROM recipes
    WHERE id = ?
";

$stmtDelete = $conn->prepare($deleteQuery);

if (!$stmtDelete) {

    $message = "Failed to prepare recipe deletion.";
    $status = "error";

} else {

    $stmtDelete->bind_param("i", $recipeId);

    if ($stmtDelete->execute()) {

        // =================================================
        // DELETE RECIPE IMAGE FROM SERVER
        // =================================================

        if (!empty($imagePath)) {

            /*
             * The database stores the browser URL.
             *
             * LOCAL:
             * /recipe_meal_planner/uploads/example.jpg
             *
             * PRODUCTION:
             * /uploads/example.jpg
             *
             * We need to convert this URL into the
             * corresponding physical filesystem path.
             */

            $imageUrlPath = parse_url(
                $imagePath,
                PHP_URL_PATH
            );


            if ($imageUrlPath !== null && $imageUrlPath !== '') {

                // Normalize URL slashes
                $imageUrlPath = '/' . ltrim(
                    $imageUrlPath,
                    '/'
                );


                // Get BASE_URL path
                $baseUrlPath = parse_url(
                    BASE_URL,
                    PHP_URL_PATH
                );


                if ($baseUrlPath === null) {
                    $baseUrlPath = '';
                }


                // Normalize BASE_URL
                $baseUrlPath = '/' . trim(
                    $baseUrlPath,
                    '/'
                );


                /*
                 * Remove BASE_URL from the image URL.
                 *
                 * Example:
                 *
                 * /recipe_meal_planner/uploads/test.jpg
                 *
                 * becomes:
                 *
                 * uploads/test.jpg
                 */

                if (
                    $baseUrlPath !== '/' &&
                    $baseUrlPath !== '' &&
                    strpos(
                        $imageUrlPath,
                        $baseUrlPath . '/'
                    ) === 0
                ) {

                    $relativeImagePath = substr(
                        $imageUrlPath,
                        strlen($baseUrlPath) + 1
                    );

                } else {

                    $relativeImagePath = ltrim(
                        $imageUrlPath,
                        '/'
                    );
                }


                /*
                 * Remove any accidental ../ segments.
                 * This prevents the image path from escaping
                 * the website directory.
                 */

                $relativeImagePath = str_replace(
                    ['../', '..\\'],
                    '',
                    $relativeImagePath
                );


                /*
                 * Build the physical filesystem path.
                 *
                 * This file is:
                 *
                 * /recipe_meal_planner/admin/delete_recipe.php
                 *
                 * Therefore:
                 *
                 * __DIR__ . '/../'
                 *
                 * points to:
                 *
                 * /recipe_meal_planner/
                 */

                $fullImagePath = __DIR__ .
                    '/../' .
                    $relativeImagePath;


                /*
                 * Get the real uploads directory.
                 */

                $uploadsDirectory = realpath(
                    __DIR__ . '/../uploads'
                );


                /*
                 * Resolve the actual image path.
                 */

                $realImagePath = realpath(
                    $fullImagePath
                );


                /*
                 * Only delete the file if:
                 *
                 * 1. uploads directory exists
                 * 2. image file exists
                 * 3. image is actually inside uploads
                 * 4. image is a regular file
                 */

                if (
                    $uploadsDirectory !== false &&
                    $realImagePath !== false &&
                    is_file($realImagePath)
                ) {

                    $uploadsDirectoryWithSeparator =
                        rtrim(
                            $uploadsDirectory,
                            DIRECTORY_SEPARATOR
                        ) .
                        DIRECTORY_SEPARATOR;


                    if (
                        strpos(
                            $realImagePath,
                            $uploadsDirectoryWithSeparator
                        ) === 0
                    ) {

                        unlink($realImagePath);
                    }
                }
            }
        }


        // =================================================
        // SUCCESS
        // =================================================

        $message = "Recipe successfully deleted.";
        $status = "success";

    } else {

        // =================================================
        // DELETE FAILED
        // =================================================

        $message = "Failed to delete recipe. Please try again.";
        $status = "error";
    }

    $stmtDelete->close();
}


// =====================================================
// CLOSE DATABASE CONNECTION
// =====================================================

$conn->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Delete Recipe | Admin | RecipeMate
</title>

<style>

body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    color: #fff;
    background: linear-gradient(120deg, #0d47a1, #00bcd4);
    background-attachment: fixed;
    text-align: center;
}

.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 60px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(15px);
    border-radius: 0 0 20px 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.logo {
    font-size: 1.8em;
    font-weight: 700;
    color: #fff;
}

.nav-links {
    list-style: none;
    display: flex;
    gap: 25px;
    margin: 0;
    padding: 0;
}

.nav-links a {
    color: white;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
}

.nav-links a:hover,
.nav-links a.active {
    color: #80deea;
    transform: scale(1.1);
}

.container {
    max-width: 600px;
    margin: 100px auto;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(15px);
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
}

h1 {
    font-size: 2rem;
    margin-bottom: 15px;
    text-shadow: 0 3px 8px rgba(0,0,0,0.3);
}

p {
    font-size: 1rem;
    margin-bottom: 30px;
    color: #e0f7ff;
}

.message-success {
    background-color: rgba(0, 255, 127, 0.2);
    border: 2px solid #00e676;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.message-error {
    background-color: rgba(255, 0, 0, 0.2);
    border: 2px solid #ff5252;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.btn {
    display: inline-block;
    padding: 10px 25px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary {
    background-color: #fff;
    color: #0d47a1;
}

.btn-primary:hover {
    background-color: #00bcd4;
    color: #fff;
    transform: translateY(-3px);
}

footer {
    text-align: center;
    padding: 30px;
    color: white;
    font-size: 0.9rem;
    background: rgba(255, 255, 255, 0.15);
    border-top-left-radius: 20px;
    border-top-right-radius: 20px;
    margin-top: 100px;
}

@media (max-width: 900px) {

    .navbar {
        flex-direction: column;
        gap: 15px;
        padding: 18px 25px;
    }

    .nav-links {
        flex-wrap: wrap;
        justify-content: center;
        gap: 15px;
    }

    .container {
        margin: 50px 20px;
    }

}

</style>

</head>

<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="navbar">

    <div class="logo">
        RecipeMate Admin
    </div>

    <ul class="nav-links">

        <li>
            <a href="<?= BASE_URL ?>admin/dashboard.php">
                Dashboard
            </a>
        </li>

        <li>
            <a href="<?= BASE_URL ?>admin/manage_users.php">
                Manage Users
            </a>
        </li>

        <li>
            <a href="<?= BASE_URL ?>admin/manage_recipes.php">
                Manage Recipes
            </a>
        </li>

        <li>
            <a
                href="<?= BASE_URL ?>admin/moderate_content.php"
                class="active"
            >
                Moderate Content
            </a>
        </li>

        <li>
            <a href="<?= BASE_URL ?>admin/profile.php">
                Profile
            </a>
        </li>

        <li>
            <a href="<?= BASE_URL ?>logout.php">
                Logout
            </a>
        </li>

    </ul>

</nav>


<!-- =====================================================
     CONTENT
===================================================== -->

<div class="container">

    <h1>

        <?php

        echo ($status === "success")
            ? "Recipe Deleted"
            : "Deletion Failed";

        ?>

    </h1>


    <div class="message-<?= htmlspecialchars($status); ?>">

        <?= htmlspecialchars($message); ?>

    </div>


    <p>

        <a
            href="<?= BASE_URL ?>admin/moderate_content.php"
            class="btn btn-primary"
        >
            Back to Moderate Content
        </a>

    </p>

</div>


<!-- =====================================================
     FOOTER
===================================================== -->

<footer>

    © 2025 RecipeMate Admin Panel. All rights reserved.

</footer>


</body>
</html>