<?php
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../dbConnection.php';


// ============================================================
// GET RECIPE ID
// ============================================================

$recipeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($recipeId <= 0) {
    die("Recipe not found.");
}


// ============================================================
// FETCH RECIPE DETAILS
// ============================================================

$stmt = $conn->prepare("
    SELECT *
    FROM recipes
    WHERE id = ?
");

$stmt->bind_param("i", $recipeId);
$stmt->execute();

$recipe = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$recipe) {
    die("Recipe not found.");
}


// ============================================================
// ADD TO FAVORITES
// ============================================================

if (isset($_POST['add_fav']) && isset($_SESSION['user_id'])) {

    $uid = (int)$_SESSION['user_id'];

    // Check if already favorited
    $stmtFavCheck = $conn->prepare("
        SELECT 1
        FROM favorite_recipes
        WHERE user_id = ?
        AND recipe_id = ?
    ");

    $stmtFavCheck->bind_param(
        "ii",
        $uid,
        $recipeId
    );

    $stmtFavCheck->execute();
    $stmtFavCheck->store_result();

    if ($stmtFavCheck->num_rows === 0) {

        $stmtFav = $conn->prepare("
            INSERT INTO favorite_recipes
            (user_id, recipe_id)
            VALUES (?, ?)
        ");

        $stmtFav->bind_param(
            "ii",
            $uid,
            $recipeId
        );

        $stmtFav->execute();

        $stmtFav->close();
    }

    $stmtFavCheck->close();

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}


// ============================================================
// HANDLE LIKE BUTTON
// ============================================================

if (isset($_POST['like_recipe']) && isset($_SESSION['user_id'])) {

    $uid = (int)$_SESSION['user_id'];

    // Check if user already liked
    $stmtCheckLike = $conn->prepare("
        SELECT 1
        FROM recipe_likes
        WHERE recipe_id = ?
        AND user_id = ?
    ");

    $stmtCheckLike->bind_param(
        "ii",
        $recipeId,
        $uid
    );

    $stmtCheckLike->execute();
    $stmtCheckLike->store_result();

    if ($stmtCheckLike->num_rows === 0) {

        // Add like
        $stmtLike = $conn->prepare("
            INSERT INTO recipe_likes
            (recipe_id, user_id)
            VALUES (?, ?)
        ");

        $stmtLike->bind_param(
            "ii",
            $recipeId,
            $uid
        );

        if ($stmtLike->execute()) {

            // Increment total likes
            $stmtUpdateLikes = $conn->prepare("
                UPDATE recipes
                SET likes_count = likes_count + 1
                WHERE id = ?
            ");

            $stmtUpdateLikes->bind_param(
                "i",
                $recipeId
            );

            $stmtUpdateLikes->execute();
            $stmtUpdateLikes->close();
        }

        $stmtLike->close();

    } else {

        echo "
        <script>
            alert('You have already liked this recipe.');
        </script>
        ";
    }

    $stmtCheckLike->close();

    echo "
    <script>
        window.location.href='" . htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES) . "';
    </script>
    ";

    exit();
}


// ============================================================
// CHECK IF USER ALREADY LIKED
// ============================================================

$hasLiked = false;

if (isset($_SESSION['user_id'])) {

    $uid = (int)$_SESSION['user_id'];

    $stmtLikeCheck = $conn->prepare("
        SELECT 1
        FROM recipe_likes
        WHERE recipe_id = ?
        AND user_id = ?
    ");

    $stmtLikeCheck->bind_param(
        "ii",
        $recipeId,
        $uid
    );

    $stmtLikeCheck->execute();
    $stmtLikeCheck->store_result();

    $hasLiked = $stmtLikeCheck->num_rows > 0;

    $stmtLikeCheck->close();
}


// ============================================================
// CHECK IF ALREADY FAVORITED
// ============================================================

$isFav = false;

if (isset($_SESSION['user_id'])) {

    $uid = (int)$_SESSION['user_id'];

    $stmtFavCheck = $conn->prepare("
        SELECT 1
        FROM favorite_recipes
        WHERE user_id = ?
        AND recipe_id = ?
    ");

    $stmtFavCheck->bind_param(
        "ii",
        $uid,
        $recipeId
    );

    $stmtFavCheck->execute();
    $stmtFavCheck->store_result();

    $isFav = $stmtFavCheck->num_rows > 0;

    $stmtFavCheck->close();
}


// ============================================================
// HANDLE REVIEW SUBMISSION
// ============================================================

if (
    isset($_POST['submit_review']) &&
    isset($_SESSION['user_id'])
) {

    $uid = (int)$_SESSION['user_id'];

    $rating = (int)($_POST['rating'] ?? 0);

    $comment = trim($_POST['comment'] ?? '');


    // Check if user already reviewed this recipe
    $stmtCheck = $conn->prepare("
        SELECT 1
        FROM reviews
        WHERE recipe_id = ?
        AND user_id = ?
    ");

    $stmtCheck->bind_param(
        "ii",
        $recipeId,
        $uid
    );

    $stmtCheck->execute();
    $stmtCheck->store_result();


    if ($stmtCheck->num_rows > 0) {

        echo "
        <script>
            alert('You have already submitted a review for this recipe.');
        </script>
        ";

    } else {

        if (
            $rating >= 1 &&
            $rating <= 5 &&
            !empty($comment)
        ) {

            $stmtReview = $conn->prepare("
                INSERT INTO reviews
                (recipe_id, user_id, rating, comment)
                VALUES (?, ?, ?, ?)
            ");

            $stmtReview->bind_param(
                "iiis",
                $recipeId,
                $uid,
                $rating,
                $comment
            );

            if ($stmtReview->execute()) {

                echo "
                <script>
                    alert('Thank you! Your review has been submitted.');
                </script>
                ";
            }

            $stmtReview->close();

        } else {

            echo "
            <script>
                alert('Please select a rating and enter a comment.');
            </script>
            ";
        }
    }

    $stmtCheck->close();


    echo "
    <script>
        window.location.href='" . htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES) . "';
    </script>
    ";

    exit();
}


// ============================================================
// FETCH REVIEWS
// ============================================================

$stmtReviews = $conn->prepare("
    SELECT
        r.rating,
        r.comment,
        u.userName,
        r.created_at
    FROM reviews r
    JOIN users u
        ON r.user_id = u.user_id
    WHERE r.recipe_id = ?
    ORDER BY r.created_at DESC
");

$stmtReviews->bind_param(
    "i",
    $recipeId
);

$stmtReviews->execute();

$reviews = $stmtReviews
    ->get_result()
    ->fetch_all(MYSQLI_ASSOC);

$stmtReviews->close();


// ============================================================
// FETCH AVERAGE RATING
// ============================================================

$stmtAvg = $conn->prepare("
    SELECT
        COALESCE(AVG(rating), 0) AS avg_rating,
        COUNT(*) AS total_reviews
    FROM reviews
    WHERE recipe_id = ?
");

$stmtAvg->bind_param(
    "i",
    $recipeId
);

$stmtAvg->execute();

$resAvg = $stmtAvg
    ->get_result()
    ->fetch_assoc();

$stmtAvg->close();

$avg = round(
    (float)$resAvg['avg_rating'],
    1
);

$total = (int)$resAvg['total_reviews'];


// ============================================================
// BUILD RECIPE IMAGE URL
// ============================================================

$imageUrl = null;

if (!empty($recipe['image_path'])) {

    /*
     * Database should contain:
     *
     * uploads/imagefile.jpg
     *
     * Local:
     * /recipe_meal_planner/uploads/imagefile.jpg
     *
     * Production:
     * /uploads/imagefile.jpg
     */

    if (preg_match('/^https?:\/\//i', $recipe['image_path'])) {

        // Already a complete URL
        $imageUrl = $recipe['image_path'];

    } else {

        // Build URL using BASE_URL
        $imageUrl =
            rtrim(BASE_URL, '/') .
            '/' .
            ltrim($recipe['image_path'], '/');
    }
}

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
    View Recipe | RecipeMate
</title>

<link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap"
    rel="stylesheet"
>

<style>

body {
    margin: 0;

    font-family: 'Poppins', sans-serif;

    background:
        linear-gradient(
            135deg,
            #071524,
            #0b2238
        );

    color: #fff;

    overflow-x: hidden;
}


/* ============================================================
   NAVBAR
============================================================ */

header {
    background: transparent;
}

.navbar {

    display: flex;

    justify-content: space-between;

    align-items: center;

    padding: 18px 60px;

    background:
        rgba(0, 40, 80, 0.4);

    backdrop-filter:
        blur(12px)
        saturate(150%);

    border-bottom:
        1px solid
        rgba(255,255,255,0.1);

    border-radius:
        0 0 20px 20px;

    box-shadow:
        0 6px 20px
        rgba(0,0,0,0.35);

    position: sticky;

    top: 0;

    z-index: 1000;

    transition: all 0.4s ease;
}


.logo {

    font-size: 1.8rem;

    font-weight: 700;

    color: #a7d8ff;

    text-shadow:
        0 0 8px
        rgba(167,216,255,0.5);

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

    transition:
        width 0.3s ease;
}


.nav-links a:hover::after,
.nav-links a.active::after {

    width: 100%;
}


/* ============================================================
   MAIN CONTAINER
============================================================ */

.container {

    max-width: 800px;

    margin: 60px auto;

    padding: 30px 40px;

    background:
        rgba(255,255,255,0.1);

    border-radius: 20px;

    backdrop-filter:
        blur(15px);

    box-shadow:
        0 8px 30px
        rgba(0,0,0,0.3);
}


/* ============================================================
   RECIPE HEADER
============================================================ */

.recipe-header {

    text-align: center;

    margin-bottom: 30px;
}


.recipe-header h1 {

    font-size: 2rem;

    margin-bottom: 10px;

    color: #fff;
}


.recipe-header p {

    color: #c9d6f0;
}


/* ============================================================
   RECIPE IMAGE
============================================================ */

.recipe-image {

    width: 100%;

    max-height: 400px;

    border-radius: 20px;

    object-fit: cover;

    margin-bottom: 25px;

    box-shadow:
        0 5px 15px
        rgba(0,0,0,0.4);
}


/* ============================================================
   RECIPE SECTIONS
============================================================ */

.recipe-section {

    margin-bottom: 25px;
}


.recipe-section h2 {

    font-size: 1.3rem;

    color: #a7d8ff;

    border-bottom:
        2px solid
        rgba(255,255,255,0.2);

    padding-bottom: 5px;

    margin-bottom: 15px;
}


.recipe-section ul {

    list-style-type: disc;

    padding-left: 25px;

    line-height: 1.7;

    color: #f0f5ff;
}


.recipe-section p {

    line-height: 1.5;

    color: #f0f5ff;

    white-space: pre-line;
}


/* ============================================================
   TAGS
============================================================ */

.tags {

    display: flex;

    flex-wrap: wrap;

    gap: 10px;

    margin-top: 10px;
}


.tag {

    background:
        rgba(255,255,255,0.2);

    border-radius: 20px;

    padding: 5px 12px;

    font-size: 0.9rem;

    color: #cdeaff;

    backdrop-filter:
        blur(8px);
}


/* ============================================================
   ACTION BUTTONS
============================================================ */

.actions {

    text-align: center;

    margin-top: 30px;

    display: flex;

    justify-content: center;

    flex-wrap: wrap;

    gap: 10px;
}


.btn {

    padding: 12px 28px;

    background:
        linear-gradient(
            90deg,
            #007bff,
            #00bcd4
        );

    border: none;

    color: #fff;

    border-radius: 10px;

    cursor: pointer;

    font-weight: 600;

    transition: 0.3s;

    font-family: 'Poppins', sans-serif;
}


.btn-primary {

    color: white;
}


.btn-primary:hover {

    transform:
        translateY(-3px);

    box-shadow:
        0 0 15px
        rgba(0,188,212,0.6);
}


.btn-secondary {

    background:
        rgba(255,255,255,0.2);

    color: white;
}


.btn-secondary:hover {

    background:
        rgba(255,255,255,0.35);
}


/* ============================================================
   COLLAPSIBLE RECIPE DETAILS
============================================================ */

.recipe-details {

    margin-top: 15px;

    background:
        rgba(255,255,255,0.1);

    border-radius: 10px;

    padding: 12px;
}


.recipe-details summary {

    font-weight: 600;

    color: #00bcd4;

    cursor: pointer;

    list-style: none;

    outline: none;
}


.recipe-details summary:hover {

    color: #4fc3f7;
}


.recipe-details p {

    margin-top: 10px;

    line-height: 1.6;

    color: #f0f5ff;

    white-space: pre-line;
}


/* ============================================================
   REVIEW FORM
============================================================ */

.review-form {

    margin-bottom: 50px;
}


.review-form select,
.review-form textarea {

    width: 100%;

    padding: 10px;

    border-radius: 8px;

    border: none;

    margin-top: 5px;

    margin-bottom: 10px;

    box-sizing: border-box;

    font-family: 'Poppins', sans-serif;
}


.review-form select {

    background: #fff;

    color: #111;

    cursor: pointer;
}


.review-form textarea {

    resize: vertical;

    min-height: 80px;
}


/* ============================================================
   REVIEW ITEMS
============================================================ */

.review-item {

    background:
        rgba(255,255,255,0.1);

    padding: 15px;

    margin-bottom: 20px;

    border-radius: 10px;
}


.review-item em {

    color: #ffb74d;

    font-style: italic;
}


/* ============================================================
   BACK LINK
============================================================ */

.back-link {

    text-align: center;

    margin-top: 25px;
}


.back-link a {

    color: #9cd2ff;

    text-decoration: none;

    font-weight: 500;
}


.back-link a:hover {

    color: white;
}


/* ============================================================
   FOOTER
============================================================ */

footer {

    text-align: center;

    padding: 30px;

    color:
        rgba(255,255,255,0.7);

    border-top:
        1px solid
        rgba(255,255,255,0.1);

    backdrop-filter:
        blur(10px);

    background:
        rgba(0,0,30,0.2);

    margin-top: 80px;
}


/* ============================================================
   RESPONSIVE
============================================================ */

@media (max-width: 900px) {

    .navbar {

        padding:
            18px 30px;
    }

    .nav-links {

        gap: 15px;
    }

}


@media (max-width: 700px) {

    .navbar {

        flex-direction: column;

        gap: 15px;
    }

    .nav-links {

        flex-wrap: wrap;

        justify-content: center;
    }

    .container {

        margin:
            30px 15px;

        padding:
            25px 20px;
    }

    .actions {

        flex-direction: column;

        align-items: stretch;
    }

    .actions form,
    .actions button,
    .actions a {

        width: 100%;

        box-sizing: border-box;
    }

}

</style>

</head>

<body>


<!-- ============================================================
     HEADER
============================================================ -->

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


<!-- ============================================================
     MAIN CONTENT
============================================================ -->

<div class="container">


    <!-- RECIPE HEADER -->

    <div class="recipe-header">

        <h1>
            <?= htmlspecialchars($recipe['title']) ?>
        </h1>


        <div
            style="
                margin-top:6px;
                color:#cdeaff;
            "
        >

            👍
            <?= (int)$recipe['likes_count'] ?>
            likes

            <br>


            <?php if ($total > 0): ?>

                <span
                    style="
                        color:#ffd166;
                    "
                >
                    <?= str_repeat(
                        "★",
                        (int)floor($avg)
                    ) ?>

                    <?= str_repeat(
                        "☆",
                        5 - (int)floor($avg)
                    ) ?>
                </span>

                <span
                    style="
                        margin-left:8px;
                    "
                >
                    <?= $avg ?>
                    / 5 •

                    <?= $total ?>

                    review<?= $total === 1 ? '' : 's' ?>
                </span>

            <?php else: ?>

                <span>
                    No reviews yet
                </span>

            <?php endif; ?>

        </div>


        <p>
            Category:
            <?= htmlspecialchars($recipe['category']) ?>
        </p>

    </div>


    <!-- ========================================================
         RECIPE IMAGE
    ========================================================= -->

    <?php if (!empty($imageUrl)): ?>

        <img
            src="<?= htmlspecialchars($imageUrl) ?>"
            alt="Recipe Image"
            class="recipe-image"
        >

    <?php endif; ?>


    <!-- ========================================================
         LIKE / FAVORITE / EDIT / DELETE
    ========================================================= -->

    <div class="actions">


        <?php if (!isset($_SESSION['user_id'])): ?>

            <a
                class="btn btn-primary"
                href="<?= BASE_URL ?>index.php"
                style="text-decoration:none;"
            >
                Login to like
            </a>


        <?php else: ?>


            <!-- LIKE -->

            <form
                method="post"
                style="display:inline;"
            >

                <button
                    type="submit"
                    class="btn btn-primary"
                    name="like_recipe"

                    <?php if ($hasLiked): ?>

                        disabled

                        style="
                            opacity:0.6;
                            cursor:not-allowed;
                        "

                    <?php endif; ?>
                >

                    👍

                    <?= $hasLiked
                        ? "Liked"
                        : "Like Recipe";
                    ?>

                </button>

            </form>


            <!-- FAVORITE -->

            <form
                method="post"
                style="display:inline;"
            >

                <button
                    type="submit"
                    class="btn btn-primary"
                    name="add_fav"

                    <?php if ($isFav): ?>

                        disabled

                        style="
                            opacity:0.6;
                            cursor:not-allowed;
                        "

                    <?php endif; ?>
                >

                    ❤️

                    <?= $isFav
                        ? "Added to Favorites"
                        : "Add to Favorites";
                    ?>

                </button>

            </form>


            <!-- EDIT / DELETE -->

            <?php if (
                (int)$_SESSION['user_id']
                ===
                (int)$recipe['user_id']
            ): ?>

                <button
                    class="btn btn-secondary"
                    onclick="
                        window.location.href='edit_recipe.php?id=<?= $recipeId ?>'
                    "
                >
                    📝 Edit Recipe
                </button>


                <button
                    class="btn btn-secondary"
                    onclick="
                        window.location.href='delete_recipe.php?id=<?= $recipeId ?>'
                    "
                >
                    🗑️ Delete Recipe
                </button>

            <?php endif; ?>


        <?php endif; ?>

    </div>


    <!-- ========================================================
         INGREDIENTS
    ========================================================= -->

    <details class="recipe-details">

        <summary>
            🍽️ Ingredients
        </summary>

        <p>
            <?= nl2br(
                htmlspecialchars(
                    $recipe['ingredients']
                )
            ) ?>
        </p>

    </details>


    <!-- ========================================================
         STEPS
    ========================================================= -->

    <details class="recipe-details">

        <summary>
            👨‍🍳 Steps
        </summary>

        <p>
            <?= nl2br(
                htmlspecialchars(
                    $recipe['steps']
                )
            ) ?>
        </p>

    </details>


    <!-- ========================================================
         REVIEWS
    ========================================================= -->

    <div class="recipe-section">

        <h2>
            Reviews
        </h2>


        <?php if (isset($_SESSION['user_id'])): ?>

            <form
                method="post"
                class="review-form"
            >

                <label for="rating">
                    Rating:
                </label>

                <select
                    name="rating"
                    id="rating"
                    required
                >

                    <option value="">
                        Select
                    </option>

                    <?php for (
                        $i = 1;
                        $i <= 5;
                        $i++
                    ): ?>

                        <option value="<?= $i ?>">
                            <?= $i ?> ★
                        </option>

                    <?php endfor; ?>

                </select>


                <label for="comment">
                    Comment:
                </label>

                <textarea
                    name="comment"
                    id="comment"
                    rows="3"
                    required
                ></textarea>


                <button
                    type="submit"
                    class="btn btn-primary"
                    name="submit_review"
                >
                    Submit Review
                </button>

            </form>


        <?php else: ?>

            <p>

                <a
                    href="<?= BASE_URL ?>index.php"
                    style="color:#9cd2ff;"
                >
                    Login
                </a>

                to write a review.

            </p>

        <?php endif; ?>


        <!-- EXISTING REVIEWS -->

        <?php if (empty($reviews)): ?>

            <p>
                No reviews yet.
                Be the first to review this recipe!
            </p>


        <?php else: ?>


            <?php foreach ($reviews as $rev): ?>

                <div class="review-item">

                    <strong>
                        <?= htmlspecialchars(
                            $rev['userName']
                        ) ?>
                    </strong>


                    <span
                        style="
                            color:#ffd166;
                        "
                    >
                        <?= str_repeat(
                            "★",
                            (int)$rev['rating']
                        ) ?>
                    </span>


                    <p>

                        <?php

                        if (
                            $rev['comment']
                            ===
                            "Content removed by admin due to violation"
                        ) {

                            echo "<em>"
                                . htmlspecialchars(
                                    $rev['comment']
                                )
                                . "</em>";

                        } else {

                            echo nl2br(
                                htmlspecialchars(
                                    $rev['comment']
                                )
                            );
                        }

                        ?>

                    </p>


                    <small
                        style="
                            color:#c9d6f0;
                        "
                    >

                        Posted on

                        <?= date(
                            "M d, Y",
                            strtotime(
                                $rev['created_at']
                            )
                        ) ?>

                    </small>

                </div>

            <?php endforeach; ?>


        <?php endif; ?>


        <!-- BACK TO RECIPES -->

        <div class="back-link">

            <a
                href="<?= BASE_URL ?>recipes/recipe_index.php"
            >
                ← Back to All Recipes
            </a>

        </div>

    </div>

</div>


<!-- ============================================================
     FOOTER
============================================================ -->

<footer>

    © 2025 RecipeMate.
    All rights reserved.

</footer>


</body>
</html>