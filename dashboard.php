<?php
require_once __DIR__ . '/config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>RecipeMate | Home</title>
  <style>
    /* Global Styles */
    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #071524, #0b2238);
      color: #fff;
      overflow-x: hidden;
    }

    /* Navbar */
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
      -webkit-backdrop-filter: blur(12px) saturate(150%);
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

    /* Hero Section */
    .hero {
      text-align: center;
      padding: 120px 20px 100px;
      background: radial-gradient(circle at top, rgba(80, 150, 255, 0.15), transparent);
    }

    .hero-text h1 {
      font-size: 3.2rem;
      font-weight: 700;
      margin-bottom: 15px;
      text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    .hero-text span {
      color: #4fc3f7;
    }

    .hero-text p {
      font-size: 1.2rem;
      color: #d0eaff;
      margin-bottom: 35px;
    }

    .btn-primary {
      padding: 14px 35px;
      background: linear-gradient(90deg, #007bff, #00bcd4);
      color: #fff;
      border: none;
      border-radius: 40px;
      text-decoration: none;
      font-weight: 600;
      box-shadow: 0 8px 25px rgba(0, 188, 212, 0.4);
      transition: all 0.3s;
    }

    .btn-primary:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 30px rgba(0, 188, 212, 0.6);
    }

    /* Discover Section */
    .discover-section {
      text-align: center;
      padding: 80px 20px;
      background: rgba(255, 255, 255, 0.03);
      border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .discover-section h2 {
      font-size: 2.2rem;
      margin-bottom: 50px;
      color: #a7d8ff;
      text-shadow: 0 0 10px rgba(167, 216, 255, 0.4);
    }

    .discover-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 30px;
      justify-items: center;
      padding: 0 40px;
    }

    .discover-card {
      background: rgba(255, 255, 255, 0.07);
      border: 1px solid rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(12px);
      border-radius: 20px;
      padding: 25px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
      transition: all 0.4s ease;
      color: #eaf6ff;
    }

    .discover-card:hover {
      transform: translateY(-8px);
      background: rgba(0, 120, 255, 0.15);
    }

    .discover-card h3 {
      color: #4fc3f7;
      margin-bottom: 10px;
    }

    .discover-card p {
      font-size: 0.95rem;
      color: #cfe9ff;
    }

    /* Footer */
    footer {
      text-align: center;
      padding: 15px;
      color: rgba(255, 255, 255, 0.7);
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      background: rgba(0, 0, 30, 0.2);
    }

    /* Page Load Animation */
    body {
      opacity: 0;
      transform: scale(0.98);
      transition: opacity 0.8s ease, transform 0.8s ease;
    }

    body.page-loaded {
      opacity: 1;
      transform: scale(1);
    }
  </style>
</head>
<body>
  <!-- HEADER -->
  <header>
    <nav class="navbar">
      <div class="logo">🍽️ RecipeMate</div>
      <ul class="nav-links">
        <li><a href="<?= BASE_URL ?>dashboard.php" class="active">Home</a></li>
        <li><a href="<?= BASE_URL ?>recipes/search_recipe.php">Search Recipes</a></li>
        <li><a href="<?= BASE_URL ?>recipes/recipe_index.php">Recipes</a></li>
        <li><a href="<?= BASE_URL ?>planner/meal_planner.php">Meal Planner</a></li>
        <li><a href="<?= BASE_URL ?>favorites/favorites.php">Favorites</a></li>
        <li><a href="<?= BASE_URL ?>profile/profile.php">Profile</a></li>
        <li><a href="<?= BASE_URL ?>logout.php">Logout</a></li>
      </ul>
    </nav>
  </header>

  <!-- HERO -->
  <section class="hero">
    <div class="hero-text">
      <h1>Welcome to <span>RecipeMate</span></h1>
      <p>Plan, share, and discover delicious recipes for every meal.</p>
      <a href="<?= BASE_URL ?>recipes/recipe_index.php" class="btn-primary">Start Cooking</a>
    </div>
  </section>

  <!-- Discover Section -->
  <section class="discover-section">
    <h2>✨ Discover More with RecipeMate</h2>
    <div class="discover-container">

      <div class="discover-card">
        <h3>🍽️ Recipe Management</h3>
        <p>Browse, organize, and explore thousands of recipes tailored to your taste — complete with ingredient lists and cooking steps.</p>
      </div>

      <div class="discover-card">
        <h3>🥗 Smart Meal Planner</h3>
        <p>Generate a personalized daily meal plan with complete recipes, ingredients, and cooking steps!</p>
      </div>

      <div class="discover-card">
        <h3>❤️ Favorites Collection</h3>
        <p>Save your favorite recipes in one place and revisit them anytime for quick access and inspiration.</p>
      </div>

      <div class="discover-card">
        <h3>👤 Personalized Profile</h3>
        <p>Customize your RecipeMate experience with your own profile — track preferences, and saved meal plans all in one dashboard.</p>
      </div>

    </div>
  </section>

  <!-- FOOTER -->
  <footer>
    <p>© 2025 RecipeMate. All rights reserved.</p>
  </footer>

  <script>
    window.addEventListener("load", () => {
      document.body.classList.add("page-loaded");
    });
  </script>
</body>
</html>