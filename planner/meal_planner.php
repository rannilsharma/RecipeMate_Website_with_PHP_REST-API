<?php

require_once __DIR__ . '/../config/config.php';   // Load BASE_URL
require_once __DIR__ . '/../dbConnection.php';

session_start();

// ---------------------------------------------------------
// Check if user is logged in
// ---------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ---------------------------------------------------------
// Get Spoonacular API key from environment variable
// ---------------------------------------------------------
// On Render, configure:
// SPOONACULAR_API_KEY = your_actual_key
$apiKey = getenv('SPOONACULAR_API_KEY');

if (!$apiKey) {
    die('Error: Spoonacular API key is not configured on the server.');
}

$mealPlan = null;
$error = '';

// ---------------------------------------------------------
// Helper function to fetch API with cURL
// ---------------------------------------------------------
function fetchApi($url)
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json'
        ]
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);

        return [
            'error' => $error
        ];
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    $decoded = json_decode($response, true);

    // Handle invalid JSON response
    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
        return [
            'error' => 'Invalid response from external API',
            'http_code' => $httpCode
        ];
    }

    return $decoded;
}

// ---------------------------------------------------------
// Generate Meal Plan
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_plan'])) {

    $calories = $_POST['calories'] ?? '';
    $diet = $_POST['diet'] ?? '';

    // Validate calories
    if (!is_numeric($calories) || (int)$calories <= 0) {
        $error = "Please enter a valid calorie amount.";
    }

    // Validate diet
    if ($error === '' && $diet === '') {
        $error = "Please select a diet type.";
    }

    if ($error === '') {

        $calories = (int)$calories;

        // -------------------------------------------------
        // Generate meal plan using Spoonacular
        // -------------------------------------------------
        $url = "https://api.spoonacular.com/mealplanner/generate"
             . "?apiKey=" . urlencode($apiKey)
             . "&timeFrame=day"
             . "&targetCalories=" . urlencode($calories)
             . "&diet=" . urlencode($diet);

        $result = fetchApi($url);

        if (isset($result['error'])) {

            $error = "Unable to connect to the meal planning service: "
                   . htmlspecialchars($result['error']);

        } elseif (!isset($result['meals']) || !is_array($result['meals'])) {

            $error = "No meals found for your criteria. Please try adjusting your inputs.";

        } else {

            $mealPlan = $result;

            // -------------------------------------------------
            // Fetch detailed recipe information for each meal
            // -------------------------------------------------
            foreach ($mealPlan['meals'] as &$meal) {

                if (!isset($meal['id'])) {
                    $meal['details'] = null;
                    continue;
                }

                $mealId = (int)$meal['id'];

                $infoUrl =
                    "https://api.spoonacular.com/recipes/"
                    . $mealId
                    . "/information"
                    . "?includeNutrition=true"
                    . "&apiKey=" . urlencode($apiKey);

                $details = fetchApi($infoUrl);

                // If the detail request fails, don't break
                // the entire meal plan.
                if (
                    !is_array($details) ||
                    isset($details['error'])
                ) {
                    $meal['details'] = null;
                } else {
                    $meal['details'] = $details;
                }
            }

            unset($meal);
        }
    }
}

// ---------------------------------------------------------
// Save Meal Plan
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_plan'])) {

    $planData = $_POST['plan_data'] ?? '';

    if ($planData) {

        $stmt = $conn->prepare("
            INSERT INTO meal_plans (user_id, plan_name, plan_date, meals)
            VALUES (?, 'My Meal Plan', CURDATE(), ?)
        ");

        if (!$stmt) {
            echo "<script>
                    alert('❌ Failed to prepare meal plan.');
                    window.location.href='" . BASE_URL . "planner/meal_planner.php';
                  </script>";
            exit();
        }

        $stmt->bind_param("is", $user_id, $planData);

        if ($stmt->execute()) {

            echo "<script>
                    alert('✅ Meal Plan saved successfully!');
                    window.location.href='" . BASE_URL . "planner/meal_planner.php';
                  </script>";

            $stmt->close();
            exit();

        } else {

            echo "<script>
                    alert('❌ Failed to save meal plan.');
                    window.location.href='" . BASE_URL . "planner/meal_planner.php';
                  </script>";

            $stmt->close();
            exit();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>RecipeMate | Meal Planner</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap"
      rel="stylesheet">

<style>

body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #071524, #0b2238);
    color: #fff;
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
    border-bottom: 1px solid rgba(255,255,255,0.1);
    border-radius: 0 0 20px 20px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.35);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.logo {
    font-size: 1.8rem;
    font-weight: 700;
    color: #a7d8ff;
    text-shadow: 0 0 8px rgba(167,216,255,0.5);
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
    position: relative;
    transition: 0.3s;
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

.container {
    max-width: 950px;
    margin: 60px auto;
    padding: 30px 40px;
    background: rgba(255,255,255,0.08);
    border-radius: 25px;
    backdrop-filter: blur(20px);
}

h1 {
    text-align: center;
    color: #a7d8ff;
    margin-bottom: 10px;
}

p {
    text-align: center;
    color: #bcdcff;
    margin-bottom: 25px;
}

form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

input,
select {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 12px;
    background: rgba(255,255,255,0.1);
    color: #fff;
    font-size: 1rem;
    box-sizing: border-box;
}

select option {
    color: #000;
    background: #fff;
}

.form-buttons {
    display: flex;
    justify-content: space-between;
}

.btn {
    padding: 12px 28px;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s;
}

.btn-primary {
    background: linear-gradient(90deg,#007bff,#00bcd4);
    color: #fff;
}

.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 0 15px rgba(0,188,212,0.6);
}

.meals-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit,minmax(280px,1fr));
    gap: 25px;
    margin-top: 30px;
}

.meal-card {
    background: rgba(255,255,255,0.1);
    border-radius: 15px;
    padding: 20px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    box-shadow: 0 5px 20px rgba(0,0,0,0.3);
}

.meal-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 8px 25px rgba(0,200,255,0.3);
}

.meal-card img {
    width: 100%;
    border-radius: 12px;
    margin-bottom: 15px;
}

.meal-card h3 {
    color: #a7d8ff;
    margin-bottom: 8px;
}

.meal-card h4 {
    color: #7fc8ff;
    margin-top: 12px;
}

.meal-card ul {
    padding-left: 20px;
    color: #d5eaff;
}

details.steps-box {
    margin-top: 15px;
    background: rgba(255,255,255,0.08);
    border-radius: 12px;
    padding: 10px 15px;
}

details.steps-box[open] {
    background: rgba(255,255,255,0.12);
}

details.steps-box summary {
    cursor: pointer;
    font-weight: 600;
    color: #a7d8ff;
    padding: 8px 0;
    font-size: 1rem;
    list-style: none;
}

.nutrition-card {
    margin-top: 20px;
    padding: 15px;
    background: rgba(0,0,0,0.3);
    border-radius: 12px;
    color: #d5eaff;
}

.nutrition-card p {
    margin: 4px 0;
}

footer {
    text-align: center;
    padding: 30px;
    color: rgba(255,255,255,0.7);
    border-top: 1px solid rgba(255,255,255,0.1);
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
            <a href="<?= BASE_URL ?>planner/meal_planner.php"
               class="active">
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

    <h1>🥗 Smart Meal Planner</h1>

    <p>
        Generate a personalized daily meal plan with complete recipes,
        ingredients, cooking steps, and nutrition info!
    </p>

    <form method="POST">

        <label for="calories">
            Daily Calorie Goal
        </label>

        <input
            type="text"
            id="calories"
            name="calories"
            placeholder="e.g. 2000"
            required
        >

        <label for="diet">
            Diet Type
        </label>

        <select
            id="diet"
            name="diet"
            required
        >

            <option value="">
                Select Diet
            </option>

            <option value="balanced">
                Balanced
            </option>

            <option value="low-carb">
                Low Carb
            </option>

            <option value="high-protein">
                High Protein
            </option>

            <option value="vegetarian">
                Vegetarian
            </option>

            <option value="vegan">
                Vegan
            </option>

        </select>

        <div class="form-buttons">

            <button
                type="submit"
                name="generate_plan"
                class="btn btn-primary">
                Generate Plan
            </button>

            <button
                type="reset"
                class="btn">
                Clear
            </button>

        </div>

    </form>

    <?php if ($error): ?>

        <p style="text-align:center;color:#ff7f7f;">
            <?= htmlspecialchars($error) ?>
        </p>

    <?php elseif ($mealPlan): ?>

        <h2 style="text-align:center;margin-top:40px;">
            🍽️ Your Personalized Meal Plan
        </h2>

        <div class="meals-grid">

            <?php

            $mealNames = [
                "Breakfast",
                "Lunch",
                "Dinner"
            ];

            $index = 0;

            foreach ($mealPlan['meals'] as $meal):

                $details = $meal['details'] ?? null;

            ?>

            <div class="meal-card">

                <h3>
                    <?= $mealNames[$index] ?? "Meal" ?>
                    —
                    <?= htmlspecialchars($meal['title'] ?? "No Title") ?>
                </h3>

                <?php if ($details && isset($details['image'])): ?>

                    <img
                        src="<?= htmlspecialchars($details['image']); ?>"
                        alt="<?= htmlspecialchars($meal['title'] ?? "Meal") ?>"
                    >

                <?php endif; ?>

                <p>
                    🕒 Ready in
                    <?= htmlspecialchars($meal['readyInMinutes'] ?? "N/A") ?>
                    minutes

                    |

                    🍽️ Servings:
                    <?= htmlspecialchars($meal['servings'] ?? "N/A") ?>
                </p>

                <?php if ($details && isset($details['extendedIngredients'])): ?>

                    <details class="steps-box">

                        <summary>
                            🧂 Show Ingredients
                        </summary>

                        <ul>

                            <?php foreach ($details['extendedIngredients'] as $ingredient): ?>

                                <li>
                                    <?= htmlspecialchars($ingredient['original']) ?>
                                </li>

                            <?php endforeach; ?>

                        </ul>

                    </details>

                <?php endif; ?>

                <?php if ($details && isset($details['analyzedInstructions'][0]['steps'])): ?>

                    <details class="steps-box">

                        <summary>
                            👨‍🍳 Show Cooking Steps
                        </summary>

                        <ol>

                            <?php foreach ($details['analyzedInstructions'][0]['steps'] as $step): ?>

                                <li>
                                    <?= htmlspecialchars($step['step']) ?>
                                </li>

                            <?php endforeach; ?>

                        </ol>

                    </details>

                <?php elseif ($details && isset($details['instructions'])): ?>

                    <details class="steps-box">

                        <summary>
                            👨‍🍳 Show Cooking Instructions
                        </summary>

                        <p>
                            <?= $details['instructions']
                                ? strip_tags($details['instructions'])
                                : "No instructions provided."
                            ?>
                        </p>

                    </details>

                <?php endif; ?>

                <?php

                // Nutrition display

                if (
                    $details &&
                    isset($details['nutrition']['nutrients'])
                ):

                    $nutrients = [];

                    foreach ($details['nutrition']['nutrients'] as $n) {

                        $nutrients[$n['name']] =
                            $n['amount'] . ' ' . $n['unit'];
                    }

                ?>

                <div class="nutrition-card">

                    <h4>
                        🌿 Nutrition (per serving)
                    </h4>

                    <p>
                        Calories:
                        <?= $nutrients['Calories'] ?? 'N/A' ?>
                    </p>

                    <p>
                        Protein:
                        <?= $nutrients['Protein'] ?? 'N/A' ?>
                    </p>

                    <p>
                        Fat:
                        <?= $nutrients['Fat'] ?? 'N/A' ?>
                    </p>

                    <p>
                        Carbohydrates:
                        <?= $nutrients['Carbohydrates'] ?? 'N/A' ?>
                    </p>

                </div>

                <?php endif; ?>

            </div>

            <?php

            $index++;

            endforeach;

            ?>

        </div>

        <form method="POST">

            <input
                type="hidden"
                name="plan_data"
                value='<?= htmlspecialchars(
                    json_encode($mealPlan),
                    ENT_QUOTES
                ) ?>'
            >

            <button
                type="submit"
                name="save_plan"
                class="btn btn-primary"
                style="margin-top:20px;width:100%;">
                💾 Save Meal Plan
            </button>

        </form>

    <?php endif; ?>

</div>

<footer>

    © 2025 RecipeMate. All rights reserved.

</footer>

</body>

</html>