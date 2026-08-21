<?php

session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../dbConnection.php';


// =====================================================
// REDIRECT IF NOT LOGGED IN
// =====================================================

if (!isset($_SESSION['user_id'])) {

    header("Location: " . BASE_URL . "index.php");
    exit();

}

$user_id = $_SESSION['user_id'];


// =====================================================
// FETCH FAVORITE RECIPES
// =====================================================

$stmt = $conn->prepare("
    SELECT 
        r.id AS recipe_id,
        r.title,
        r.category,
        r.description,
        r.image_path,
        f.added_at

    FROM favorite_recipes f

    JOIN recipes r
        ON f.recipe_id = r.id

    WHERE f.user_id = ?

    ORDER BY f.added_at DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();


// =====================================================
// NORMALIZE BASE URL
// =====================================================

$baseUrl = rtrim(BASE_URL, '/');

?>

<!DOCTYPE html>
<html lang="en">

<head>

  <meta charset="UTF-8">

  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Favorites | RecipeMate</title>

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


    /* === NAVBAR === */

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


    /* ===== CONTAINER ===== */

    .container {
      max-width: 1200px;
      margin: 80px auto;
      padding: 30px 40px;
      background: rgba(255, 255, 255, 0.08);
      border-radius: 25px;
      backdrop-filter: blur(10px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    }

    h2 {
      text-align: center;
      font-size: 2rem;
      color: #a7d8ff;
      margin-bottom: 40px;
    }


    /* ===== GRID ===== */

    .recipe-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 25px;
    }

    .recipe-card {
      background: rgba(255, 255, 255, 0.08);
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .recipe-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
    }

    .recipe-image img {
      width: 100%;
      height: 200px;
      object-fit: cover;
      display: block;
    }

    .recipe-content {
      padding: 20px;
    }

    .recipe-content h3 {
      margin: 0;
      color: #ffffff;
      font-size: 1.3rem;
    }

    .recipe-content p {
      color: #d6e6f5;
      font-size: 0.95rem;
      margin: 12px 0 20px 0;
      height: 50px;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .recipe-actions {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .btn {
      padding: 10px 18px;
      background: linear-gradient(90deg, #007bff, #00bcd4);
      border: none;
      color: #fff;
      border-radius: 10px;
      cursor: pointer;
      font-weight: 600;
      transition: 0.3s;
    }

    .btn-view {
      color: #fff;
      text-decoration: none;
    }

    .btn-view:hover {
      transform: translateY(-3px);
      box-shadow: 0 0 15px rgba(0, 188, 212, 0.6);
    }

    .btn-remove {
      background: #ff4d4d;
      color: #fff;
    }

    .btn-remove:hover {
      background: #d93636;
    }


    /* ===== FOOTER ===== */

    footer {
      text-align: center;
      padding: 15px;
      color: rgba(255, 255, 255, 0.7);
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      background: rgba(0, 0, 30, 0.2);
      margin-top: 220px;
    }

  </style>

</head>


<body>


  <!-- =====================================================
       HEADER
  ====================================================== -->

  <header>

    <nav class="navbar">

      <div class="logo">
        🍽️ RecipeMate
      </div>

      <ul class="nav-links">

        <li>
          <a href="<?= htmlspecialchars(BASE_URL) ?>dashboard.php">
            Home
          </a>
        </li>

        <li>
          <a href="<?= htmlspecialchars(BASE_URL) ?>recipes/search_recipe.php">
            Search Recipes
          </a>
        </li>

        <li>
          <a href="<?= htmlspecialchars(BASE_URL) ?>recipes/recipe_index.php">
            Recipes
          </a>
        </li>

        <li>
          <a href="<?= htmlspecialchars(BASE_URL) ?>planner/meal_planner.php">
            Meal Planner
          </a>
        </li>

        <li>
          <a
            href="<?= htmlspecialchars(BASE_URL) ?>favorites/favorites.php"
            class="active"
          >
            Favorites
          </a>
        </li>

        <li>
          <a href="<?= htmlspecialchars(BASE_URL) ?>profile/profile.php">
            Profile
          </a>
        </li>

        <li>
          <a href="<?= htmlspecialchars(BASE_URL) ?>index.php">
            Logout
          </a>
        </li>

      </ul>

    </nav>

  </header>


  <!-- =====================================================
       MAIN CONTENT
  ====================================================== -->

  <div class="container">

    <h2>
      ❤️ My Favorite Recipes
    </h2>


    <div class="recipe-grid">


      <?php if ($result->num_rows > 0): ?>


        <?php while ($recipe = $result->fetch_assoc()): ?>


          <?php

          // =================================================
          // BUILD RECIPE IMAGE URL
          // =================================================

          $imagePath = trim(
              $recipe['image_path'] ?? ''
          );


          /*
           * If there is no image stored in the database,
           * use a simple fallback.
           *
           * Since you said there is NO assets folder,
           * we do not reference assets/images here.
           *
           * Instead, use a known image from uploads only
           * if you have one available.
           */

          if ($imagePath === '') {

              $imageUrl = '';

          }

          /*
           * Database already contains a complete URL.
           */

          elseif (
              strpos($imagePath, 'http://') === 0 ||
              strpos($imagePath, 'https://') === 0
          ) {

              $imageUrl = $imagePath;

          }

          else {

              /*
               * Remove leading slash.
               *
               * Examples:
               *
               * /uploads/chicken.jpg
               * uploads/chicken.jpg
               * /recipe_meal_planner/uploads/chicken.jpg
               * recipe_meal_planner/uploads/chicken.jpg
               */

              $imagePath = ltrim(
                  $imagePath,
                  '/'
              );


              /*
               * Remove the project folder if it is
               * already included in the database value.
               *
               * Local BASE_URL:
               *
               * /recipe_meal_planner/
               *
               * We therefore remove:
               *
               * recipe_meal_planner/
               */

              $basePath = parse_url(
                  BASE_URL,
                  PHP_URL_PATH
              );


              if (!empty($basePath)) {

                  $basePath = trim(
                      $basePath,
                      '/'
                  );


                  if (
                      strpos(
                          $imagePath,
                          $basePath . '/'
                      ) === 0
                  ) {

                      $imagePath = substr(
                          $imagePath,
                          strlen($basePath) + 1
                      );

                  }

              }


              /*
               * Remove uploads/ because we add it
               * ourselves below.
               */

              if (
                  strpos(
                      $imagePath,
                      'uploads/'
                  ) === 0
              ) {

                  $imagePath = substr(
                      $imagePath,
                      strlen('uploads/')
                  );

              }


              /*
               * Final URL.
               *
               * LOCAL:
               *
               * /recipe_meal_planner/uploads/image.jpg
               *
               * PRODUCTION:
               *
               * /uploads/image.jpg
               */

              $imageUrl =
                  $baseUrl .
                  '/uploads/' .
                  ltrim(
                      $imagePath,
                      '/'
                  );

          }

          ?>


          <div class="recipe-card">


            <!-- =================================================
                 RECIPE IMAGE
            ================================================== -->

            <div class="recipe-image">

              <?php if (!empty($imageUrl)): ?>

                <img
                  src="<?= htmlspecialchars($imageUrl) ?>"
                  alt="<?= htmlspecialchars($recipe['title']) ?>"
                >

              <?php else: ?>

                <div
                  style="
                    height:200px;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    background:rgba(255,255,255,0.05);
                    color:#a7d8ff;
                  "
                >
                  No Image Available
                </div>

              <?php endif; ?>

            </div>


            <!-- =================================================
                 RECIPE CONTENT
            ================================================== -->

            <div class="recipe-content">

              <h3>
                <?= htmlspecialchars($recipe['title']) ?>
              </h3>


              <p>
                <?= htmlspecialchars($recipe['description']) ?>
              </p>


              <div class="recipe-actions">


                <a
                  href="<?= htmlspecialchars(BASE_URL) ?>recipes/view_recipe.php?id=<?= (int)$recipe['recipe_id'] ?>"
                  class="btn btn-view"
                >
                  👀 View
                </a>


                <form
                  action="remove_favorites.php"
                  method="POST"
                  style="display:inline;"
                >

                  <input
                    type="hidden"
                    name="recipe_id"
                    value="<?= (int)$recipe['recipe_id'] ?>"
                  >


                  <button
                    type="submit"
                    class="btn btn-remove"
                    onclick="return confirm('Remove from favorites?')"
                  >
                    🗑 Remove
                  </button>

                </form>


              </div>

            </div>


          </div>


        <?php endwhile; ?>


      <?php else: ?>


        <p
          style="
            text-align:center;
            color:#d6e6f5;
            grid-column:1/-1;
          "
        >
          You haven’t added any favorites yet.
        </p>


      <?php endif; ?>


    </div>

  </div>


  <!-- =====================================================
       FOOTER
  ====================================================== -->

  <footer>

    <p>
      © 2025 RecipeMate. All rights reserved.
    </p>

  </footer>


</body>

</html>