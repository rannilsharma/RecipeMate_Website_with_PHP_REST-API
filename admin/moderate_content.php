<?php

session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../dbConnection.php';


// =====================================================
// CHECK LOGIN
// =====================================================

if (!isset($_SESSION['user_id'])) {

    header('Location: ' . BASE_URL . 'index.php');
    exit();
}


// =====================================================
// VERIFY ADMIN ACCESS
// =====================================================

$userId = (int) $_SESSION['user_id'];

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
// HANDLE COMMENT REMOVAL
// =====================================================

if (isset($_POST['remove_comment_id'])) {

    $commentId = (int) $_POST['remove_comment_id'];

    $msg = "Content removed by admin due to violation";

    $stmt = $conn->prepare("
        UPDATE reviews
        SET comment = ?
        WHERE review_id = ?
    ");

    if ($stmt) {

        $stmt->bind_param("si", $msg, $commentId);
        $stmt->execute();
        $stmt->close();
    }
}


// =====================================================
// FILTER HANDLING
// =====================================================

$startDate = $_GET['start_date'] ?? '';
$endDate   = $_GET['end_date'] ?? '';
$postedBy  = $_GET['posted_by'] ?? '';


// =====================================================
// GET RECIPES
// =====================================================

$query = "
    SELECT
        r.id,
        r.title,
        r.image_path,
        u.userName,
        r.created_at
    FROM recipes r
    JOIN users u
        ON r.user_id = u.user_id
    WHERE 1
";

$params = [];
$types = '';


// Start date
if (!empty($startDate)) {

    $query .= " AND r.created_at >= ?";

    $params[] = $startDate . " 00:00:00";
    $types .= "s";
}


// End date
if (!empty($endDate)) {

    $query .= " AND r.created_at <= ?";

    $params[] = $endDate . " 23:59:59";
    $types .= "s";
}


// Posted by
if (!empty($postedBy)) {

    $query .= " AND u.userName LIKE ?";

    $params[] = "%" . $postedBy . "%";
    $types .= "s";
}


$query .= " ORDER BY r.created_at DESC";


$stmtRecipes = $conn->prepare($query);

if (!$stmtRecipes) {
    die("Database query failed.");
}


if (!empty($params)) {

    $stmtRecipes->bind_param(
        $types,
        ...$params
    );
}


$stmtRecipes->execute();

$recipes = $stmtRecipes->get_result();


// =====================================================
// SELECTED RECIPE COMMENTS
// =====================================================

$selectedRecipeId = isset($_GET['view_comments'])
    ? (int) $_GET['view_comments']
    : 0;

$comments = null;


if ($selectedRecipeId > 0) {

    $stmtComments = $conn->prepare("
        SELECT
            rv.review_id,
            rv.comment,
            rv.rating,
            u.userName,
            rv.created_at
        FROM reviews rv
        JOIN users u
            ON rv.user_id = u.user_id
        WHERE rv.recipe_id = ?
        AND rv.comment != 'Content removed by admin due to violation'
        ORDER BY rv.created_at DESC
    ");

    if ($stmtComments) {

        $stmtComments->bind_param(
            "i",
            $selectedRecipeId
        );

        $stmtComments->execute();

        $comments = $stmtComments->get_result();

        $stmtComments->close();
    }
}


// =====================================================
// IMAGE URL HELPER
// =====================================================
//
// image_path should normally contain:
//
// LOCAL:
// /recipe_meal_planner/uploads/example.jpg
//
// PRODUCTION:
// /uploads/example.jpg
//
// This function makes sure the browser receives a
// correct URL regardless of whether the database stores
// a relative path or a path beginning with BASE_URL.
// =====================================================

function getRecipeImageUrl($imagePath)
{
    global $baseUrl;

    if (empty($imagePath)) {

        return BASE_URL . 'assets/images/placeholder.jpg';
    }


    $imagePath = trim($imagePath);


    // -------------------------------------------------
    // Absolute URL
    // -------------------------------------------------

    if (
        strpos($imagePath, 'http://') === 0 ||
        strpos($imagePath, 'https://') === 0
    ) {

        return $imagePath;
    }


    // -------------------------------------------------
    // Normalize slashes
    // -------------------------------------------------

    $imagePath = str_replace('\\', '/', $imagePath);


    // -------------------------------------------------
    // If already starts with BASE_URL, use it
    // -------------------------------------------------

    $basePath = parse_url(
        BASE_URL,
        PHP_URL_PATH
    );

    if ($basePath === null) {
        $basePath = '';
    }

    $basePath = '/' . trim(
        $basePath,
        '/'
    ) . '/';


    if (
        $basePath !== '/' &&
        strpos($imagePath, $basePath) === 0
    ) {

        return $imagePath;
    }


    // -------------------------------------------------
    // If BASE_URL is root
    // -------------------------------------------------

    if ($basePath === '/') {

        return '/' . ltrim(
            $imagePath,
            '/'
        );
    }


    // -------------------------------------------------
    // Remove leading slash
    // -------------------------------------------------

    $imagePath = ltrim(
        $imagePath,
        '/'
    );


    // -------------------------------------------------
    // If image_path already contains the project
    // folder, don't add BASE_URL twice.
    //
    // Example:
    // recipe_meal_planner/uploads/test.jpg
    // -------------------------------------------------

    $baseWithoutSlashes = trim(
        $basePath,
        '/'
    );


    if (
        !empty($baseWithoutSlashes) &&
        strpos(
            $imagePath,
            $baseWithoutSlashes . '/'
        ) === 0
    ) {

        return '/' . $imagePath;
    }


    // -------------------------------------------------
    // Normal relative path
    // -------------------------------------------------

    return BASE_URL . $imagePath;
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
    Moderate Content | RecipeMate
</title>


<style>

/* =====================================================
   BODY
===================================================== */

body {

    margin: 0;

    font-family:
        'Poppins',
        sans-serif;

    color: #fff;

    background:
        linear-gradient(
            120deg,
            #0d47a1,
            #00bcd4
        );

    background-attachment: fixed;
}


/* =====================================================
   NAVBAR
===================================================== */

.navbar {

    display: flex;

    justify-content: space-between;

    align-items: center;

    padding: 20px 60px;

    background:
        rgba(
            255,
            255,
            255,
            0.2
        );

    backdrop-filter:
        blur(15px);

    border-radius:
        0 0 20px 20px;

    box-shadow:
        0 4px 20px
        rgba(
            0,
            0,
            0,
            0.2
        );

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

    transition:
        all 0.3s ease;
}


.nav-links a:hover,
.nav-links a.active {

    color: #80deea;

    transform:
        scale(1.1);
}


/* =====================================================
   HEADER
===================================================== */

.dashboard-header {

    text-align: center;

    margin:
        60px
        20px
        40px;
}


.dashboard-header h1 {

    font-size: 2.6rem;

    margin-bottom: 10px;

    text-shadow:
        0 3px 8px
        rgba(
            0,
            0,
            0,
            0.3
        );
}


.dashboard-header p {

    font-size: 1.1rem;

    color: #d0f0ff;
}


/* =====================================================
   FILTER SECTION
===================================================== */

.filter-section {

    text-align: center;

    margin-bottom: 30px;
}


.filter-form {

    display: flex;

    justify-content: center;

    align-items: flex-end;

    flex-wrap: wrap;

    gap: 15px;
}


.filter-form label {

    display: block;

    font-size: 0.9rem;

    margin-bottom: 5px;

    color: #e0f7ff;
}


.filter-form input[type="date"],
.filter-form input[type="text"],
.filter-form button {

    box-sizing: border-box;

    height: 36px;

    border: none;

    border-radius: 6px;

    font-size: 0.95rem;
}


.filter-form input[type="date"],
.filter-form input[type="text"] {

    padding:
        0 12px;

    width: 180px;
}


.filter-form button {

    padding:
        0 20px;

    background: #fff;

    color: #0d47a1;

    font-weight: 600;

    cursor: pointer;

    transition:
        all 0.3s ease;
}


.filter-form button:hover {

    background: #00bcd4;

    color: white;

    transform:
        translateY(-2px);
}


/* =====================================================
   RECIPES
===================================================== */

.recipes-container {

    display: flex;

    flex-wrap: wrap;

    justify-content: center;

    gap: 25px;

    padding:
        20px 60px;
}


.recipe-card {

    background:
        rgba(
            255,
            255,
            255,
            0.2
        );

    backdrop-filter:
        blur(10px);

    border-radius: 20px;

    width: 280px;

    text-align: center;

    padding: 20px;

    color: #fff;

    box-shadow:
        0 6px 20px
        rgba(
            0,
            0,
            0,
            0.2
        );

    transition:
        all 0.3s ease;
}


.recipe-card:hover {

    transform:
        translateY(-8px);

    box-shadow:
        0 12px 30px
        rgba(
            0,
            0,
            0,
            0.4
        );
}


/* =====================================================
   RECIPE IMAGE
===================================================== */

.recipe-card img {

    width: 100%;

    height: 180px;

    object-fit: cover;

    border-radius: 12px;

    margin-bottom: 15px;

    display: block;
}


.recipe-card h3 {

    margin-bottom: 10px;

    color: #b3ecff;
}


.recipe-card p {

    font-size: 0.9rem;

    color: #d0f0ff;

    margin-bottom: 10px;
}


/* =====================================================
   VIEW COMMENTS BUTTON
===================================================== */

.btn-view-comments {

    display: inline-block;

    padding:
        8px 18px;

    background: white;

    color: #0d47a1;

    border-radius: 25px;

    text-decoration: none;

    font-weight: 600;

    transition:
        all 0.3s ease;
}


.btn-view-comments:hover {

    background: #00bcd4;

    color: white;

    transform:
        translateY(-2px);
}


/* =====================================================
   COMMENTS
===================================================== */

.comments-section {

    background:
        rgba(
            255,
            255,
            255,
            0.1
        );

    backdrop-filter:
        blur(10px);

    border-radius: 15px;

    margin:
        40px auto;

    padding: 30px;

    max-width: 900px;

    box-shadow:
        0 8px 20px
        rgba(
            0,
            0,
            0,
            0.2
        );
}


.comment-card {

    background:
        rgba(
            255,
            255,
            255,
            0.2
        );

    border-radius: 12px;

    padding: 15px;

    margin-bottom: 15px;

    box-shadow:
        0 4px 10px
        rgba(
            0,
            0,
            0,
            0.2
        );
}


.comment-card strong {

    color: #b3ecff;
}


.comment-card small {

    color: #d0f0ff;

    font-size: 0.8rem;
}


.comment-card p {

    margin: 8px 0;

    color: #fff;
}


.comment-card form {

    text-align: right;
}


.comment-card button {

    background: #ff5252;

    border: none;

    color: white;

    padding:
        6px 14px;

    border-radius: 6px;

    cursor: pointer;

    font-weight: 600;

    transition:
        background 0.3s;
}


.comment-card button:hover {

    background: #e53935;
}


/* =====================================================
   FOOTER
===================================================== */

footer {

    text-align: center;

    padding: 30px;

    color: white;

    font-size: 0.9rem;

    background:
        rgba(
            255,
            255,
            255,
            0.15
        );

    border-top-left-radius: 20px;

    border-top-right-radius: 20px;

    margin-top: 100px;
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 900px) {

    .navbar {

        flex-direction: column;

        gap: 15px;

        padding:
            18px 25px;
    }


    .nav-links {

        flex-wrap: wrap;

        justify-content: center;

        gap: 15px;
    }


    .recipes-container {

        padding:
            20px;
    }


    .recipe-card {

        width: min(
            280px,
            90%
        );
    }


    .comments-section {

        margin:
            30px 20px;

        padding: 20px;
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
     HEADER
===================================================== -->

<section class="dashboard-header">

    <h1>
        Moderate Content
    </h1>

    <p>
        Review and manage user comments on recipes.
    </p>

</section>


<!-- =====================================================
     FILTERS
===================================================== -->

<section class="filter-section">

    <form
        class="filter-form"
        method="GET"
    >

        <div>

            <label for="start_date">
                Start Date
            </label>

            <input
                type="date"
                name="start_date"
                id="start_date"
                value="<?= htmlspecialchars($startDate) ?>"
            >

        </div>


        <div>

            <label for="end_date">
                End Date
            </label>

            <input
                type="date"
                name="end_date"
                id="end_date"
                value="<?= htmlspecialchars($endDate) ?>"
            >

        </div>


        <div>

            <label for="posted_by">
                Posted By
            </label>

            <input
                type="text"
                name="posted_by"
                id="posted_by"
                placeholder="Username"
                value="<?= htmlspecialchars($postedBy) ?>"
            >

        </div>


        <button type="submit">
            Filter
        </button>

    </form>

</section>


<!-- =====================================================
     RECIPE CARDS
===================================================== -->

<section class="recipes-container">

<?php if ($recipes && $recipes->num_rows > 0): ?>

    <?php while ($recipe = $recipes->fetch_assoc()): ?>

        <?php

        /*
         * Build a browser-accessible image URL.
         */
        $recipeImageUrl = getRecipeImageUrl(
            $recipe['image_path']
        );

        ?>

        <div class="recipe-card">


            <!-- RECIPE IMAGE -->

            <img
                src="<?= htmlspecialchars($recipeImageUrl) ?>"
                alt="<?= htmlspecialchars($recipe['title']) ?>"
                onerror="this.onerror=null; this.src='<?= htmlspecialchars(BASE_URL . 'assets/images/placeholder.jpg') ?>';"
            >


            <!-- TITLE -->

            <h3>
                <?= htmlspecialchars($recipe['title']) ?>
            </h3>


            <!-- USER -->

            <p>
                By:
                <?= htmlspecialchars($recipe['userName']) ?>
            </p>


            <!-- DATE -->

            <p>

                <small>
                    <?= htmlspecialchars($recipe['created_at']) ?>
                </small>

            </p>


            <!-- COMMENTS -->

            <a
                href="?view_comments=<?= (int) $recipe['id'] ?>"
                class="btn-view-comments"
            >
                View Comments
            </a>

        </div>

    <?php endwhile; ?>

<?php else: ?>

    <p
        style="
            text-align:center;
            width:100%;
        "
    >
        No recipes found.
    </p>

<?php endif; ?>

</section>


<!-- =====================================================
     COMMENTS SECTION
===================================================== -->

<?php if ($selectedRecipeId > 0): ?>

<section class="comments-section">


    <h2 style="text-align:center;">

        Comments for Recipe
        #<?= htmlspecialchars($selectedRecipeId) ?>

    </h2>


    <?php if ($comments && $comments->num_rows > 0): ?>


        <?php while ($comment = $comments->fetch_assoc()): ?>


            <div class="comment-card">


                <strong>

                    <?= htmlspecialchars(
                        $comment['userName']
                    ) ?>

                </strong>


                <small>

                    rated
                    <?= intval($comment['rating']) ?>/5

                    on

                    <?= htmlspecialchars(
                        $comment['created_at']
                    ) ?>

                </small>


                <p>

                    <?= nl2br(
                        htmlspecialchars(
                            $comment['comment']
                        )
                    ) ?>

                </p>


                <form
                    method="POST"
                    onsubmit="
                        return confirm(
                            'Are you sure you want to remove this comment?'
                        );
                    "
                >

                    <input
                        type="hidden"
                        name="remove_comment_id"
                        value="<?= (int) $comment['review_id'] ?>"
                    >


                    <button type="submit">
                        Remove Comment
                    </button>

                </form>


            </div>


        <?php endwhile; ?>


    <?php else: ?>


        <p style="text-align:center;">

            No comments found for this recipe.

        </p>


    <?php endif; ?>


</section>

<?php endif; ?>


<!-- =====================================================
     FOOTER
===================================================== -->

<footer>

    © 2025 RecipeMate Admin Panel.
    All rights reserved.

</footer>


</body>

</html>

<?php

// =====================================================
// CLOSE DATABASE CONNECTION
// =====================================================

$stmtRecipes->close();

$conn->close();

?>