<?php

session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../dbConnection.php';


// =====================================================
// CHECK IF USER IS LOGGED IN
// =====================================================

if (!isset($_SESSION['user_id'])) {

    header(
        'Location: ' .
        BASE_URL .
        'index.php'
    );

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

$stmtAdmin->bind_param(
    "i",
    $userId
);

$stmtAdmin->execute();

$resultAdmin = $stmtAdmin->get_result();

$userAdmin = $resultAdmin->fetch_assoc();

$stmtAdmin->close();


if (
    !$userAdmin ||
    (int) $userAdmin['is_admin'] !== 1
) {

    echo "Access denied. Admins only.";
    exit();
}


// =====================================================
// INITIALIZE FILTERS
// =====================================================

$startDate = $_GET['start_date'] ?? '';
$endDate   = $_GET['end_date'] ?? '';
$postedBy  = $_GET['posted_by'] ?? '';


// =====================================================
// BASE QUERY
// =====================================================

$query = "
    SELECT
        r.id,
        r.title,
        r.category,
        r.created_at,
        r.image_path,
        u.userName,

        (
            SELECT COUNT(*)
            FROM recipe_likes rl
            WHERE rl.recipe_id = r.id
        ) AS total_likes

    FROM recipes r

    JOIN users u
        ON r.user_id = u.user_id

    WHERE 1
";


// =====================================================
// APPLY FILTERS
// =====================================================

$params = [];
$types = '';


// Start date
if (!empty($startDate)) {

    $query .= "
        AND r.created_at >= ?
    ";

    $params[] = $startDate . " 00:00:00";

    $types .= 's';
}


// End date
if (!empty($endDate)) {

    $query .= "
        AND r.created_at <= ?
    ";

    $params[] = $endDate . " 23:59:59";

    $types .= 's';
}


// Posted by
if (!empty($postedBy)) {

    $query .= "
        AND u.userName LIKE ?
    ";

    $params[] = "%" . $postedBy . "%";

    $types .= 's';
}


// =====================================================
// ORDER RESULTS
// =====================================================

$query .= "
    ORDER BY r.created_at DESC
";


// =====================================================
// PREPARE QUERY
// =====================================================

$stmt = $conn->prepare($query);

if (!$stmt) {
    die("Database query failed.");
}


// Bind parameters if required
if (!empty($params)) {

    $stmt->bind_param(
        $types,
        ...$params
    );
}


// Execute
$stmt->execute();

$result = $stmt->get_result();

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
    Manage Recipes | Admin | RecipeMate
</title>


<style>

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
   PAGE HEADER
===================================================== */

.page-header {

    text-align: center;

    margin:
        60px 20px 40px;
}


.page-header h1 {

    font-size: 2.5rem;

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


.page-header p {

    font-size: 1.2rem;

    color: #d0f0ff;
}


/* =====================================================
   FILTERS
===================================================== */

.filter-form {

    display: flex;

    flex-wrap: wrap;

    justify-content: center;

    align-items: flex-end;

    gap: 20px;

    margin-bottom: 30px;
}


.filter-form div {

    display: flex;

    flex-direction: column;

    gap: 5px;
}


.filter-form label {

    font-size: 0.9rem;

    color: #e0f7ff;
}


.filter-form input[type="text"],
.filter-form input[type="date"] {

    box-sizing: border-box;

    height: 36px;

    padding:
        0 12px;

    border-radius: 6px;

    border: none;

    font-size: 0.95rem;

    outline: none;
}


.filter-form input[type="text"] {

    width: 180px;
}


.filter-form input[type="date"] {

    width: 180px;
}


.filter-form button {

    box-sizing: border-box;

    height: 36px;

    padding:
        0 20px;

    border-radius: 6px;

    border: none;

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
   RECIPE CONTAINER
===================================================== */

.recipes-container {

    display: flex;

    flex-wrap: wrap;

    justify-content: center;

    gap: 30px;

    padding:
        20px 60px;
}


/* =====================================================
   RECIPE CARD
===================================================== */

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

    width: 300px;

    text-align: center;

    padding: 15px;

    color: #fff;

    box-shadow:
        0 8px 20px
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
        translateY(-8px)
        scale(1.03);

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

    border-radius: 15px;

    margin-bottom: 10px;

    display: block;
}


/* =====================================================
   RECIPE TEXT
===================================================== */

.recipe-card h2 {

    font-size: 1.4rem;

    margin:
        5px 0 8px;

    color: #b3ecff;
}


.recipe-card p {

    font-size: 0.95rem;

    margin-bottom: 10px;

    color: #e0f7ff;
}


/* =====================================================
   BUTTON
===================================================== */

.btn-primary {

    padding:
        8px 20px;

    background-color: white;

    color: #0d47a1;

    border-radius: 25px;

    text-decoration: none;

    font-weight: 600;

    transition:
        all 0.3s ease;

    display: inline-block;

    margin: 5px;
}


.btn-primary:hover {

    background-color: #00bcd4;

    color: white;

    transform:
        translateY(-3px);
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

            <a
                href="<?= BASE_URL ?>admin/dashboard.php"
            >
                Dashboard
            </a>

        </li>


        <li>

            <a
                href="<?= BASE_URL ?>admin/manage_users.php"
            >
                Manage Users
            </a>

        </li>


        <li>

            <a
                href="<?= BASE_URL ?>admin/manage_recipes.php"
                class="active"
            >
                Manage Recipes
            </a>

        </li>


        <li>

            <a
                href="<?= BASE_URL ?>admin/moderate_content.php"
            >
                Moderate Content
            </a>

        </li>


        <li>

            <a
                href="<?= BASE_URL ?>admin/profile.php"
            >
                Profile
            </a>

        </li>


        <li>

            <a
                href="<?= BASE_URL ?>logout.php"
            >
                Logout
            </a>

        </li>

    </ul>

</nav>


<!-- =====================================================
     PAGE HEADER
===================================================== -->

<section class="page-header">

    <h1>
        Manage Recipes
    </h1>


    <p>
        Review and manage recipes submitted by users.
    </p>

</section>


<!-- =====================================================
     FILTERS
===================================================== -->

<form
    class="filter-form"
    method="GET"
>

    <div>

        <label for="start_date">
            Start Date:
        </label>

        <input
            type="date"
            id="start_date"
            name="start_date"
            value="<?= htmlspecialchars($startDate); ?>"
        >

    </div>


    <div>

        <label for="end_date">
            End Date:
        </label>

        <input
            type="date"
            id="end_date"
            name="end_date"
            value="<?= htmlspecialchars($endDate); ?>"
        >

    </div>


    <div>

        <label for="posted_by">
            Posted By:
        </label>

        <input
            type="text"
            id="posted_by"
            name="posted_by"
            value="<?= htmlspecialchars($postedBy); ?>"
            placeholder="User Name"
        >

    </div>


    <button type="submit">
        Filter
    </button>

</form>


<!-- =====================================================
     RECIPE CARDS
===================================================== -->

<section class="recipes-container">

<?php if ($result->num_rows > 0): ?>


    <?php while ($row = $result->fetch_assoc()): ?>


        <?php

        /*
         * =================================================
         * BUILD RECIPE IMAGE URL
         * =================================================
         *
         * The database may contain:
         *
         * uploads/image.jpg
         *
         * /uploads/image.jpg
         *
         * /recipe_meal_planner/uploads/image.jpg
         *
         * http://localhost/recipe_meal_planner/uploads/image.jpg
         *
         * https://example.com/uploads/image.jpg
         *
         * This section converts all of them into a
         * browser-compatible URL.
         */


        $storedImagePath = trim(
            $row['image_path'] ?? ''
        );


        $imageUrl = '';


        if (!empty($storedImagePath)) {


            // -------------------------------------------------
            // Full HTTP / HTTPS URL
            // -------------------------------------------------

            if (
                preg_match(
                    '#^https?://#i',
                    $storedImagePath
                )
            ) {

                $imageUrl = $storedImagePath;

            } else {


                // -------------------------------------------------
                // Remove leading slash
                // -------------------------------------------------

                $normalizedImagePath =
                    ltrim(
                        $storedImagePath,
                        '/'
                    );


                // -------------------------------------------------
                // Get BASE_URL path
                // -------------------------------------------------

                $baseUrlPath = parse_url(
                    BASE_URL,
                    PHP_URL_PATH
                );


                if ($baseUrlPath === null) {
                    $baseUrlPath = '';
                }


                $baseUrlPath = trim(
                    $baseUrlPath,
                    '/'
                );


                // -------------------------------------------------
                // Remove BASE_URL if it is already stored
                // -------------------------------------------------

                if (
                    !empty($baseUrlPath) &&
                    strpos(
                        $normalizedImagePath,
                        $baseUrlPath . '/'
                    ) === 0
                ) {

                    $normalizedImagePath =
                        substr(
                            $normalizedImagePath,
                            strlen($baseUrlPath) + 1
                        );
                }


                // -------------------------------------------------
                // Build final browser URL
                // -------------------------------------------------

                $imageUrl =
                    BASE_URL .
                    $normalizedImagePath;
            }

        } else {

            // -------------------------------------------------
            // Default image
            // -------------------------------------------------

            $imageUrl =
                BASE_URL .
                'assets/images/default_recipe.jpg';
        }

        ?>


        <div class="recipe-card">


            <!-- =================================================
                 RECIPE IMAGE
            ================================================== -->

            <img
                src="<?= htmlspecialchars($imageUrl); ?>"
                alt="<?= htmlspecialchars($row['title']); ?>"
                onerror="this.onerror=null; this.src='<?= htmlspecialchars(BASE_URL . 'assets/images/default_recipe.jpg'); ?>';"
            >


            <!-- =================================================
                 RECIPE TITLE
            ================================================== -->

            <h2>

                <?= htmlspecialchars(
                    $row['title']
                ); ?>

            </h2>


            <!-- =================================================
                 CATEGORY
            ================================================== -->

            <p>

                Category:

                <?= htmlspecialchars(
                    $row['category']
                ); ?>

            </p>


            <!-- =================================================
                 POSTED BY
            ================================================== -->

            <p>

                Posted by:

                <?= htmlspecialchars(
                    $row['userName']
                ); ?>

            </p>


            <!-- =================================================
                 LIKES
            ================================================== -->

            <p>

                Likes:

                <?= (int) $row['total_likes']; ?>

            </p>


            <!-- =================================================
                 CREATED DATE
            ================================================== -->

            <p>

                Created:

                <?= htmlspecialchars(
                    date(
                        "d M Y",
                        strtotime(
                            $row['created_at']
                        )
                    )
                ); ?>

            </p>


            <!-- =================================================
                 DELETE BUTTON
            ================================================== -->

            <a
                href="<?= BASE_URL ?>admin/delete_recipe.php?id=<?= (int) $row['id']; ?>"
                class="btn-primary"
                onclick="
                    return confirm(
                        'Are you sure you want to delete this recipe?'
                    );
                "
            >
                Delete
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
// CLOSE STATEMENT
// =====================================================

$stmt->close();

?>