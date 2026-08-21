<?php
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../dbConnection.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    exit('DB connection ($conn) not initialized.');
}

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$q = trim($_GET['q'] ?? '');
$recipes = [];

if ($q !== '') {

    $stmt = $conn->prepare("
        SELECT r.*, u.userName
        FROM recipes r
        JOIN users u ON r.user_id = u.user_id
        WHERE r.title LIKE ?
           OR r.description LIKE ?
           OR r.ingredients LIKE ?
           OR r.tags LIKE ?
        ORDER BY r.created_at DESC
    ");

    if ($stmt) {
        $like = "%{$q}%";

        $stmt->bind_param(
            'ssss',
            $like,
            $like,
            $like,
            $like
        );

        $stmt->execute();

        $recipes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Search Recipes | RecipeMate</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

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

    /* =========================
       NAVBAR
    ========================= */

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

    /* =========================
       MAIN CONTAINER
    ========================= */

    .container {
      max-width: 1000px;

      margin: 80px auto;

      padding: 40px;

      background: rgba(255,255,255,0.05);

      border-radius: 20px;

      backdrop-filter: blur(12px);

      box-shadow: 0 6px 30px rgba(0,0,0,0.4);
    }

    .search-header {
      text-align: center;

      margin-bottom: 30px;
    }

    .search-header h1 {
      font-size: 2.2rem;

      color: #a7d8ff;
    }

    /* =========================
       SEARCH BOX
    ========================= */

    .search-box {
      display: flex;

      justify-content: center;

      gap: 10px;

      margin-bottom: 30px;
    }

    .search-box input[type="text"] {
      width: 70%;

      padding: 12px 15px;

      border-radius: 10px;

      border: none;

      outline: none;

      background: rgba(255,255,255,0.1);

      color: #fff;

      font-size: 1rem;
    }

    .search-box input[type="text"]::placeholder {
      color: rgba(255,255,255,0.65);
    }

    .search-box button {
      background: linear-gradient(90deg,#007bff,#00bcd4);

      border: none;

      color: #fff;

      padding: 12px 25px;

      border-radius: 10px;

      cursor: pointer;

      font-weight: 600;

      transition: 0.3s;
    }

    .search-box button:hover {
      transform: translateY(-3px);

      box-shadow: 0 0 15px rgba(0,188,212,0.6);
    }

    /* =========================
       RECIPE CARD
    ========================= */

    .recipe-card {
      display: flex;

      gap: 20px;

      background: rgba(255,255,255,0.08);

      border-radius: 15px;

      padding: 20px;

      margin-bottom: 25px;

      transition: all 0.3s ease;
    }

    .recipe-card:hover {
      background: rgba(255,255,255,0.15);

      transform: translateY(-3px);
    }

    .recipe-card img {
      width: 200px;

      height: 130px;

      border-radius: 12px;

      object-fit: cover;

      flex-shrink: 0;
    }

    .recipe-info {
      flex: 1;
    }

    .recipe-info h3 {
      color: #4fc3f7;

      margin: 0 0 8px;
    }

    .recipe-info p {
      color: #d0eaff;

      font-size: 0.95rem;

      margin-bottom: 5px;
    }

    .recipe-info .username {
      font-size: 0.85rem;

      color: #a7d8ff;

      margin-top: 5px;
    }

    /* =========================
       RECIPE ACTIONS
    ========================= */

    .recipe-actions {
      display: flex;

      justify-content: flex-end;

      gap: 10px;

      margin-top: 12px;
    }

    .recipe-actions a {
      padding: 10px 18px;

      background: linear-gradient(90deg,#007bff,#00bcd4);

      border: none;

      color: #fff;

      border-radius: 10px;

      cursor: pointer;

      font-weight: 600;

      transition: 0.3s;

      text-decoration: none;
    }

    .recipe-actions a:hover {
      transform: translateY(-3px);

      box-shadow: 0 0 15px rgba(0,188,212,0.6);
    }

    /* =========================
       NO RESULTS
    ========================= */

    .no-results {
      text-align: center;

      color: #cfe9ff;

      font-size: 1.1rem;

      margin-top: 20px;
    }

    /* =========================
       FOOTER
    ========================= */

    footer {
      text-align: center;

      padding: 15px;

      color: rgba(255,255,255,0.7);

      border-top: 1px solid rgba(255,255,255,0.1);

      backdrop-filter: blur(10px);

      background: rgba(0,0,30,0.2);

      margin-top: 200px;
    }

    /* =========================
       RESPONSIVE
    ========================= */

    @media (max-width: 900px) {

      .navbar {
        padding: 18px 30px;
      }

      .nav-links {
        gap: 15px;
      }

      .container {
        margin: 50px 20px;
        padding: 25px;
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

      .search-box {
        flex-direction: column;
      }

      .search-box input[type="text"] {
        width: auto;
      }

      .recipe-card {
        flex-direction: column;
      }

      .recipe-card img {
        width: 100%;
        height: 200px;
      }

      .recipe-actions {
        justify-content: flex-start;
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
        <a href="<?= BASE_URL ?>recipes/search_recipe.php" class="active">
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

  <div class="search-header">

    <h1>
      Search Recipes
    </h1>

  </div>


  <form method="get" class="search-box">

    <input
      type="text"
      name="q"
      placeholder="Search by title, ingredient, or tag..."
      value="<?= h($q) ?>"
      required
    >

    <button type="submit">
      Search
    </button>

  </form>


  <?php if ($q === ''): ?>

    <div class="no-results">
      🔍 Start by typing a keyword to find recipes.
    </div>


  <?php elseif (empty($recipes)): ?>

    <div class="no-results">
      No recipes found for
      "<b><?= h($q) ?></b>".
    </div>


  <?php else: ?>

    <?php foreach ($recipes as $r): ?>

      <div class="recipe-card">

        <?php
        /*
         * image_path is stored in the database as:
         *
         * uploads/imagefile.jpg
         *
         * BASE_URL is then added here so the website works
         * in both local and production environments.
         *
         * Existing full URLs are also supported.
         */

        if (!empty($r['image_path'])) {

            if (preg_match('/^https?:\/\//i', $r['image_path'])) {
                $imageUrl = $r['image_path'];
            } else {
                $imageUrl = rtrim(BASE_URL, '/') . '/' . ltrim($r['image_path'], '/');
            }

        } else {

            $imageUrl = rtrim(BASE_URL, '/') . '/assets/images/default_recipe.jpg';

        }
        ?>

        <img
          src="<?= h($imageUrl) ?>"
          alt="<?= h($r['title']) ?>"
        >


        <div class="recipe-info">

          <h3>
            <?= h($r['title']) ?>
          </h3>

          <p>
            <?= h(
              mb_strimwidth(
                $r['description'],
                0,
                150,
                '...'
              )
            ) ?>
          </p>

          <p class="username">
            Posted by:
            <?= h($r['userName']) ?>
          </p>


          <div class="recipe-actions">

            <a href="<?= BASE_URL ?>recipes/view_recipe.php?id=<?= (int)$r['id'] ?>">
              View Recipe
            </a>

          </div>

        </div>

      </div>

    <?php endforeach; ?>

  <?php endif; ?>

</div>


<footer>

  <p>
    © 2025 RecipeMate. All rights reserved.
  </p>

</footer>

</body>
</html>