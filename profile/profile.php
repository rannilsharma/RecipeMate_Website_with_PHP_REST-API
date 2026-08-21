<?php
session_start();

require_once __DIR__ . '/../dbConnection.php';
require_once __DIR__ . '/../config/config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- Fetch basic user info ---
$query = "SELECT userName, email, created_at FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "User not found.";
    exit();
}

$user = $result->fetch_assoc();

// --- COUNT favorite recipes ---
$favCountQuery = "SELECT COUNT(*) AS favCount FROM favorite_recipes WHERE user_id = ?";
$stmtFav = $conn->prepare($favCountQuery);
$stmtFav->bind_param("i", $user_id);
$stmtFav->execute();
$favResult = $stmtFav->get_result();
$favRow = $favResult->fetch_assoc();
$favCount = $favRow ? $favRow['favCount'] : 0;

// --- COUNT recipe likes ---
$likeQuery = "
    SELECT COUNT(rl.like_id) AS likesCount
    FROM recipe_likes rl
    JOIN recipes r ON rl.recipe_id = r.id
    WHERE r.user_id = ?
";

$stmtLike = $conn->prepare($likeQuery);
$stmtLike->bind_param("i", $user_id);
$stmtLike->execute();
$likeResult = $stmtLike->get_result();
$likeRow = $likeResult->fetch_assoc();
$likesCount = $likeRow ? $likeRow['likesCount'] : 0;

// --- Fetch meal plans ---
$stmtMeal = $conn->prepare("
    SELECT *
    FROM meal_plans
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$stmtMeal->bind_param("i", $user_id);
$stmtMeal->execute();
$mealPlans = $stmtMeal->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">

<title>Profile | RecipeMate</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

<style>

body {
    margin:0;
    font-family:'Poppins',sans-serif;
    background:linear-gradient(135deg,#071524,#0b2238);
    color:#fff;
    overflow-x:hidden;
}

header {
    background:transparent;
}

.navbar {
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:18px 60px;
    background:rgba(0,40,80,0.4);
    backdrop-filter:blur(12px) saturate(150%);
    border-bottom:1px solid rgba(255,255,255,0.1);
    border-radius:0 0 20px 20px;
    box-shadow:0 6px 20px rgba(0,0,0,0.35);
    position:sticky;
    top:0;
    z-index:1000;
    transition:all 0.4s ease;
}

.logo {
    font-size:1.8rem;
    font-weight:700;
    color:#a7d8ff;
    text-shadow:0 0 8px rgba(167,216,255,0.5);
    display:flex;
    align-items:center;
    gap:8px;
}

.nav-links {
    list-style:none;
    display:flex;
    gap:28px;
    margin:0;
    padding:0;
}

.nav-links a {
    color:#e3f2fd;
    text-decoration:none;
    font-weight:500;
    transition:all 0.3s ease;
    position:relative;
}

.nav-links a:hover,
.nav-links a.active {
    color:#4fc3f7;
}

.nav-links a::after {
    content:'';
    position:absolute;
    bottom:-5px;
    left:0;
    width:0;
    height:2px;
    background:#4fc3f7;
    transition:width 0.3s ease;
}

.nav-links a:hover::after,
.nav-links a.active::after {
    width:100%;
}


/* ==============================
   PROFILE
============================== */

.profile-container {
    max-width:1000px;
    margin:60px auto;
    padding:40px;
    background:rgba(255,255,255,0.08);
    border-radius:20px;
    backdrop-filter:blur(20px);
    box-shadow:0 8px 25px rgba(0,0,0,0.4);
}

.profile-header {
    display:flex;
    align-items:center;
    gap:30px;
    margin-bottom:40px;
    flex-wrap:wrap;
}

.user-icon {
    font-size:3.0rem;
    vertical-align:middle;
    margin-right:8px;
}

.user-info h2 {
    font-size:1.8rem;
    color:#ffffff;
    margin-bottom:6px;
}

.user-info p {
    color:#a9c7e8;
    font-size:1rem;
}

.profile-actions {
    text-align:right;
    flex:1;
    min-width:200px;
}

.btn {
    padding:10px 24px;
    background:linear-gradient(90deg,#007bff,#00bcd4);
    border:none;
    color:#fff;
    border-radius:10px;
    cursor:pointer;
    font-weight:600;
    transition:0.3s;
}

.btn-primary {
    color:white;
}

.btn-primary:hover {
    transform:translateY(-3px);
    box-shadow:0 0 15px rgba(0,188,212,0.6);
}

.profile-details {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
    gap:20px;
    margin-bottom:30px;
}

.detail-card {
    background:rgba(255,255,255,0.12);
    border-radius:15px;
    padding:20px;
    backdrop-filter:blur(10px);
    box-shadow:0 6px 20px rgba(0,0,0,0.3);
    transition:transform 0.3s;
}

.detail-card:hover {
    transform:translateY(-5px);
}

.detail-card h3 {
    color:#a7d8ff;
    margin-bottom:10px;
    font-size:1.1rem;
}

.detail-card p {
    color:#e8f1ff;
    font-size:0.95rem;
}


/* ==============================
   MEAL PLANS
============================== */

.meal-plan-card {
    background:rgba(255,255,255,0.1);
    padding:20px;
    border-radius:15px;
    margin-bottom:25px;
    box-shadow:0 5px 20px rgba(0,0,0,0.3);
}

.meal-plan-card h3 {
    margin-bottom:10px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.view-btn {
    background:rgba(0,188,212,0.2);
    color:#fff;
    border:none;
    border-radius:8px;
    padding:6px 12px;
    cursor:pointer;
    font-weight:500;
    transition:0.3s;
}

.view-btn:hover {
    background:rgba(0,188,212,0.4);
}

.meal-plan-content {
    max-height:0;
    overflow:hidden;
    transition:max-height 0.5s ease, padding 0.5s ease;
    padding-top:0;
    padding-bottom:0;
}

.meal-plan-content.show {
    max-height:2000px;
    padding-top:15px;
    padding-bottom:15px;
}

.meal-card {
    margin-top:15px;
    padding:15px;
    background:rgba(255,255,255,0.05);
    border-radius:12px;
    transition:0.3s;
    box-shadow:0 3px 10px rgba(0,0,0,0.2);
}

.meal-card:hover {
    box-shadow:0 6px 15px rgba(0,0,0,0.3);
}

.meal-card img {
    width:100%;
    max-width:300px;
    border-radius:10px;
    margin-top:8px;
}


/* ==============================
   TOGGLE BUTTONS
============================== */

.toggle-btn {
    background:rgba(0,188,212,0.2);
    color:#fff;
    border:none;
    border-radius:8px;
    padding:6px 12px;
    margin-top:8px;
    cursor:pointer;
    font-weight:500;
    transition:background 0.3s;
}

.toggle-btn:hover {
    background:rgba(0,188,212,0.4);
}

.toggle-content {
    max-height:0;
    overflow:hidden;
    transition:max-height 0.4s ease, padding 0.4s ease;
    padding-left:20px;
    margin:0;
}

.toggle-content.show {
    max-height:1000px;
    padding-top:8px;
    padding-bottom:8px;
}


/* ==============================
   FOOTER
============================== */

footer {
    text-align:center;
    padding:15px;
    color:rgba(255,255,255,0.7);
    border-top:1px solid rgba(255,255,255,0.1);
    backdrop-filter:blur(10px);
    background:rgba(0,0,30,0.2);
    margin-top:80px;
}

</style>
</head>

<body>

<header>

<nav class="navbar">

    <div class="logo">🍽️ RecipeMate</div>

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
            <a href="<?= BASE_URL ?>recipes/recipe_index.php">
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
            <a href="<?= BASE_URL ?>profile/profile.php" class="active">
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


<div class="profile-container">

    <div class="profile-header">

        <div class="user-info">

            <h2 id="userName">
                <span class="user-icon">👤</span>
                <?= htmlspecialchars($user['userName']); ?>
            </h2>

            <p id="userEmail">
                <?= htmlspecialchars($user['email']); ?>
                |
                Member since
                <?= date("Y", strtotime($user['created_at'])); ?>
            </p>

        </div>

    </div>


    <div class="profile-details">

        <div class="detail-card">

            <h3>📧 Email</h3>

            <p id="emailField">
                <?= htmlspecialchars($user['email']); ?>
            </p>

        </div>


        <div class="detail-card">

            <h3>🍳 Favorite Recipes</h3>

            <p>
                <?= $favCount ?: '0'; ?> Saved Recipes
            </p>

        </div>


        <div class="detail-card">

            <h3>👍 Total Recipe Likes</h3>

            <p>
                <?= $likesCount ?: '0'; ?> Likes
            </p>

        </div>

    </div>


    <div class="profile-section" style="margin-top:40px;">

        <h2 style="color:#a7d8ff;margin-bottom:20px;">
            📅 My Meal Plans
        </h2>


        <?php if (!empty($mealPlans)): ?>

            <?php foreach ($mealPlans as $planIndex => $plan): ?>

                <?php

                $decodedPlan = json_decode($plan['meals'], true);

                $meals = $decodedPlan['meals'] ?? [];

                $mealNames = [
                    "Breakfast",
                    "Lunch",
                    "Dinner"
                ];

                ?>

                <div class="meal-plan-card">

                    <h3>

                        <?= htmlspecialchars($plan['plan_name']); ?>

                        -
                        
                        <?= date(
                            "M d, Y",
                            strtotime($plan['created_at'])
                        ); ?>

                        <button
                            class="view-btn"
                            data-target="plan-<?= $planIndex ?>"
                        >
                            View
                        </button>

                    </h3>


                    <div
                        id="plan-<?= $planIndex ?>"
                        class="meal-plan-content"
                    >

                        <?php foreach ($meals as $index => $meal): ?>

                            <?php
                            $details = $meal['details'] ?? null;
                            ?>

                            <div class="meal-card">

                                <strong>

                                    <?= $mealNames[$index] ?? "Meal" ?>:

                                    <?= htmlspecialchars(
                                        $meal['title'] ?? 'N/A'
                                    ); ?>

                                </strong>


                                <?php if (!empty($details['image'])): ?>

                                    <img
                                        src="<?= htmlspecialchars($details['image']); ?>"
                                        alt="<?= htmlspecialchars(
                                            $meal['title'] ?? 'Meal'
                                        ); ?>"
                                    />

                                <?php endif; ?>


                                <p>
                                    🕒 Ready in
                                    <?= htmlspecialchars(
                                        $meal['readyInMinutes'] ?? 'N/A'
                                    ); ?>
                                    min

                                    |

                                    🍽️ Servings:
                                    <?= htmlspecialchars(
                                        $meal['servings'] ?? 'N/A'
                                    ); ?>
                                </p>


                                <?php if (!empty($details['extendedIngredients'])): ?>

                                    <button class="toggle-btn">
                                        🧂 Show Ingredients
                                    </button>

                                    <ul class="toggle-content">

                                        <?php foreach (
                                            $details['extendedIngredients']
                                            as $ing
                                        ): ?>

                                            <li>
                                                <?= htmlspecialchars(
                                                    $ing['original']
                                                ); ?>
                                            </li>

                                        <?php endforeach; ?>

                                    </ul>

                                <?php endif; ?>


                                <?php if (
                                    !empty(
                                        $details['analyzedInstructions'][0]['steps']
                                    )
                                ): ?>

                                    <button class="toggle-btn">
                                        👨‍🍳 Show Steps
                                    </button>

                                    <ol class="toggle-content">

                                        <?php foreach (
                                            $details['analyzedInstructions'][0]['steps']
                                            as $step
                                        ): ?>

                                            <li>
                                                <?= htmlspecialchars(
                                                    $step['step']
                                                ); ?>
                                            </li>

                                        <?php endforeach; ?>

                                    </ol>

                                <?php endif; ?>


                                <?php if (
                                    !empty(
                                        $details['nutrition']['nutrients']
                                    )
                                ): ?>

                                    <div class="toggle-content show">

                                        <h4>
                                            🌿 Nutrition (per serving)
                                        </h4>

                                        <?php

                                        $nutrients = [];

                                        foreach (
                                            $details['nutrition']['nutrients']
                                            as $n
                                        ) {

                                            $nutrients[$n['name']] =
                                                $n['amount'] . ' ' . $n['unit'];

                                        }

                                        ?>

                                        <p>
                                            Calories:
                                            <?= $nutrients['Calories'] ?? 'N/A'; ?>
                                        </p>

                                        <p>
                                            Protein:
                                            <?= $nutrients['Protein'] ?? 'N/A'; ?>
                                        </p>

                                        <p>
                                            Fat:
                                            <?= $nutrients['Fat'] ?? 'N/A'; ?>
                                        </p>

                                        <p>
                                            Carbohydrates:
                                            <?= $nutrients['Carbohydrates'] ?? 'N/A'; ?>
                                        </p>

                                    </div>

                                <?php endif; ?>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <p style="color:#cdeaff;margin-top:20px;">
                You have no saved meal plans yet.
            </p>

        <?php endif; ?>

    </div>

</div>


<footer>

    <p>
        © 2025 RecipeMate. All rights reserved.
    </p>

</footer>


<script>

// Toggle individual meal plan content

document.querySelectorAll('.view-btn').forEach(btn => {

    btn.addEventListener('click', () => {

        const targetId = btn.dataset.target;

        const content = document.getElementById(targetId);

        content.classList.toggle('show');

        btn.textContent =
            content.classList.contains('show')
                ? btn.textContent.replace('View', 'Hide')
                : btn.textContent.replace('Hide', 'View');

    });

});


// Toggle ingredients and steps

document.querySelectorAll('.toggle-btn').forEach(btn => {

    btn.addEventListener('click', () => {

        const content = btn.nextElementSibling;

        content.classList.toggle('show');

        btn.textContent =
            content.classList.contains('show')
                ? btn.textContent.replace('Show', 'Hide')
                : btn.textContent.replace('Hide', 'Show');

    });

});

</script>

</body>
</html>