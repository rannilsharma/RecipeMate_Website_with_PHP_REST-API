<?php
session_start();

require_once __DIR__ . '/../dbConnection.php';
require_once __DIR__ . '/../config/config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$currentUser = $_SESSION['user_id'];

// Fetch recipes created only by current logged-in user
$sql = "SELECT id, title, description, image_path FROM recipes WHERE user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $currentUser);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>RecipeMate | Recipes</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />

  <style>
    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #071524, #0b2238);
      color: #fff;
      overflow-x: hidden;
    }

    /* =========================
       NAVBAR
    ========================= */

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

    /* =========================
       CONTAINER
    ========================= */

    .container {
      max-width: 1200px;
      margin: 80px auto;
      padding: 0 40px;
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 40px;
    }

    .header h1 {
      font-size: 2.2rem;
      color: #a7d8ff;
      text-shadow: 0 0 10px rgba(167, 216, 255, 0.4);
    }

    /* =========================
       ADD RECIPE BUTTON
    ========================= */

    .add-btn {
      background: linear-gradient(90deg, #007bff, #00bcd4);
      color: #fff;
      border: none;
      padding: 10px 25px;
      border-radius: 40px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s;
      box-shadow: 0 4px 15px rgba(0, 188, 212, 0.4);
    }

    .add-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(0, 188, 212, 0.6);
    }

    /* =========================
       RECIPE GRID
    ========================= */

    .recipe-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 30px;
    }

    .recipe-card {
      background: rgba(255, 255, 255, 0.07);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 20px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
      overflow: hidden;
      backdrop-filter: blur(10px);
      transition: transform 0.3s ease, background 0.3s ease;
    }

    .recipe-card:hover {
      transform: translateY(-8px);
      background: rgba(0, 120, 255, 0.15);
    }

    .recipe-card img {
      width: 100%;
      height: 200px;
      object-fit: cover;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .recipe-content {
      padding: 20px;
    }

    .recipe-content h3 {
      margin: 0 0 10px;
      font-size: 1.3rem;
      color: #4fc3f7;
    }

    .recipe-content p {
      color: #d0eaff;
      font-size: 0.95rem;
    }

    /* =========================
       VIEW BUTTON
    ========================= */

    .view-btn {
      display: inline-block;
      margin-top: 15px;
      background: linear-gradient(90deg, #007bff, #00bcd4);
      color: #fff;
      text-decoration: none;
      padding: 8px 18px;
      border-radius: 30px;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 0 12px rgba(0, 188, 212, 0.4);
    }

    .view-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 0 18px rgba(0, 188, 212, 0.7);
    }

    /* =========================
       FOOTER
    ========================= */

    footer {
      text-align: center;
      padding: 20px;
      color: rgba(255, 255, 255, 0.7);
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      background: rgba(0, 0, 30, 0.2);
      margin-top: 250px;
    }
  </style>
</head>

<body>

  <!-- =========================
       HEADER
  ========================= -->

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
          <a href="<?= BASE_URL ?>recipes/recipe_index.php" class="active">
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


  <!-- =========================
       MAIN CONTENT
  ========================= -->

  <div class="container">

    <div class="header">

      <h1>
        🍲 My Recipes
      </h1>

      <a href="<?= BASE_URL ?>recipes/add_recipe.php" class="add-btn">
        + Add Recipe
      </a>

    </div>


    <div class="recipe-grid">

      <?php if ($result->num_rows > 0): ?>

        <?php while ($row = $result->fetch_assoc()): ?>

          <div class="recipe-card">

            <?php
            /*
             * image_path in the database is now:
             *
             * uploads/imagefile.jpg
             *
             * Build the complete browser URL using BASE_URL.
             *
             * Local:
             * /recipe_meal_planner/uploads/imagefile.jpg
             *
             * Production:
             * /uploads/imagefile.jpg
             */

            if (!empty($row['image_path'])) {

                // Already a complete URL
                if (preg_match('/^https?:\/\//i', $row['image_path'])) {

                    $recipeImage = $row['image_path'];

                } else {

                    $recipeImage =
                        rtrim(BASE_URL, '/') .
                        '/' .
                        ltrim($row['image_path'], '/');
                }

            } else {

                $recipeImage =
                    rtrim(BASE_URL, '/') .
                    '/assets/images/default_recipe.jpg';
            }
            ?>

            <img
              src="<?= htmlspecialchars($recipeImage); ?>"
              alt="<?= htmlspecialchars($row['title']); ?>"
            >

            <div class="recipe-content">

              <h3>
                <?= htmlspecialchars($row['title']); ?>
              </h3>

              <p>
                <?= htmlspecialchars($row['description']); ?>
              </p>

              <a
                href="<?= BASE_URL ?>recipes/view_recipe.php?id=<?= (int)$row['id']; ?>"
                class="view-btn"
              >
                View Recipe
              </a>

            </div>

          </div>

        <?php endwhile; ?>

      <?php else: ?>

        <p>
          No recipes found.
          <a
            href="<?= BASE_URL ?>recipes/add_recipe.php"
            style="color:#4fc3f7;"
          >
            Add one now!
          </a>
        </p>

      <?php endif; ?>

    </div>

  </div>


  <!-- =========================
       FOOTER
  ========================= -->

  <footer>
    <p>© 2025 RecipeMate. All rights reserved.</p>
  </footer>

</body>
</html>