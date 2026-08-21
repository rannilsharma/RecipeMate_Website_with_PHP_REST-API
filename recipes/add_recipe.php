<?php
session_start();

require_once __DIR__ . '/../config/config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Recipe | RecipeMate</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

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

    /* === CONTAINER === */
    .container {
      max-width: 900px;
      margin: 80px auto;
      padding: 40px;
      background: rgba(255, 255, 255, 0.05);
      border-radius: 20px;
      backdrop-filter: blur(12px);
      box-shadow: 0 6px 30px rgba(0, 0, 0, 0.4);
    }

    h1 {
      text-align: center;
      font-size: 2.2rem;
      color: #a7d8ff;
      margin-bottom: 30px;
    }

    form {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    label {
      font-weight: 500;
      color: #d6e9ff;
      margin-bottom: 5px;
    }

    input[type="text"],
    textarea,
    select,
    input[type="file"] {
      width: 100%;
      padding: 12px 15px;
      border: none;
      border-radius: 10px;
      background: rgba(255,255,255,0.1);
      color: white;
      font-size: 1rem;
      outline: none;
      transition: all 0.3s ease;
      box-sizing: border-box;
    }

    input[type="text"]:focus,
    textarea:focus,
    select:focus {
      background: rgba(255,255,255,0.2);
      box-shadow: 0 0 10px rgba(108,184,255,0.6);
    }

    textarea {
      resize: none;
      height: 100px;
    }

    select {
      appearance: none;
      cursor: pointer;
    }

    option {
      background-color: #0b2238;
      color: #fff;
    }

    .form-buttons {
      display: flex;
      justify-content: space-between;
      margin-top: 20px;
    }

    .btn {
      padding: 12px 28px;
      border: none;
      border-radius: 12px;
      font-weight: 600;
      cursor: pointer;
      transition: 0.3s;
      font-size: 1rem;
    }

    .btn-primary {
      background: linear-gradient(90deg, #007bff, #00bcd4);
      color: white;
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 0 12px rgba(0,188,212,0.6);
    }

    .btn-secondary {
      background: rgba(255,255,255,0.2);
      color: white;
    }

    .btn-secondary:hover {
      background: rgba(255,255,255,0.35);
    }

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
      color: #fff;
    }

    footer {
      text-align: center;
      padding: 15px;
      color: rgba(255, 255, 255, 0.7);
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      background: rgba(0, 0, 30, 0.2);
      margin-top: 80px;
    }

    @media (max-width: 900px) {
      .navbar {
        padding: 18px 25px;
        flex-direction: column;
        gap: 15px;
      }

      .nav-links {
        flex-wrap: wrap;
        justify-content: center;
        gap: 15px;
      }

      .container {
        margin: 40px 20px;
        padding: 25px;
      }
    }
  </style>
</head>

<body>

<header>
  <nav class="navbar">

    <div class="logo">🍽️ RecipeMate</div>

    <ul class="nav-links">
      <li>
        <a href="<?= BASE_URL ?>dashboard.php">Home</a>
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

<div class="container">

  <h1>🍳 Add a New Recipe</h1>

  <form
    action="<?= BASE_URL ?>recipes/save_recipe.php"
    method="POST"
    enctype="multipart/form-data"
  >

    <div>
      <label for="title">Recipe Title</label>

      <input
        type="text"
        id="title"
        name="title"
        placeholder="Enter recipe name"
        required
      >
    </div>

    <div>
      <label for="category">Category</label>

      <select id="category" name="category" required>

        <option value="">Select Category</option>

        <option value="Breakfast">Breakfast</option>
        <option value="Lunch">Lunch</option>
        <option value="Dinner">Dinner</option>
        <option value="Dessert">Dessert</option>
        <option value="Appetizer">Appetizer</option>
        <option value="Snack">Snack</option>
        <option value="Beverage">Beverage</option>
        <option value="Soup">Soup</option>
        <option value="Salad">Salad</option>
        <option value="Main Course">Main Course</option>
        <option value="Side Dish">Side Dish</option>
        <option value="Seafood">Seafood</option>
        <option value="Pasta">Pasta</option>
        <option value="Pizza">Pizza</option>

      </select>
    </div>

    <div>
      <label for="description">Description</label>

      <textarea
        id="description"
        name="description"
        placeholder="Write a short description about this recipe..."
        required
      ></textarea>
    </div>

    <div>
      <label for="ingredients">Ingredients</label>

      <textarea
        id="ingredients"
        name="ingredients"
        placeholder="List ingredients separated by commas..."
        required
      ></textarea>
    </div>

    <div>
      <label for="steps">Steps</label>

      <textarea
        id="steps"
        name="steps"
        placeholder="Write each step clearly..."
        required
      ></textarea>
    </div>

    <div>
      <label for="tags">Tags (optional)</label>

      <input
        type="text"
        id="tags"
        name="tags"
        placeholder="e.g. spicy, vegan, healthy"
      >
    </div>

    <div>
      <label for="image">Recipe Image</label>

      <input
        type="file"
        id="image"
        name="image"
        accept="image/*"
      >
    </div>

    <div class="form-buttons">

      <button
        type="submit"
        class="btn btn-primary"
      >
        Save Recipe
      </button>

      <button
        type="reset"
        class="btn btn-secondary"
      >
        Clear
      </button>

    </div>

  </form>

  <div class="back-link">
    <a href="<?= BASE_URL ?>recipes/recipe_index.php">
      ← Back to Recipes
    </a>
  </div>

</div>

<footer>
  <p>© 2025 RecipeMate. All rights reserved.</p>
</footer>

</body>
</html>