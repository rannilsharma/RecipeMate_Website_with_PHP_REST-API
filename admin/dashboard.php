<?php
require_once __DIR__ . '/../config/config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | RecipeMate</title>

  <style>
    /* === REUSE YOUR EXISTING DESIGN === */
    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      color: #fff;
      background: linear-gradient(120deg, #0d47a1, #00bcd4);
      background-attachment: fixed;
    }

    /* NAVBAR */
    .navbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px 60px;
      background: rgba(255, 255, 255, 0.2);
      backdrop-filter: blur(15px);
      border-radius: 0 0 20px 20px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
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
    }

    .nav-links a {
      color: white;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s ease;
    }

    .nav-links a:hover,
    .nav-links a.active {
      color: #80deea;
      transform: scale(1.1);
    }

    /* DASHBOARD HEADER */
    .dashboard-header {
      text-align: center;
      margin: 60px 20px 40px;
    }

    .dashboard-header h1 {
      font-size: 2.8rem;
      margin-bottom: 10px;
      text-shadow: 0 3px 8px rgba(0, 0, 0, 0.3);
    }

    .dashboard-header p {
      font-size: 1.2rem;
      color: #d0f0ff;
    }

    /* DASHBOARD CARDS */
    .dashboard-container {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 30px;
      padding: 20px 60px;
    }

    .dashboard-card {
      background: rgba(255, 255, 255, 0.2);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      width: 300px;
      text-align: center;
      padding: 30px;
      color: #fff;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
      transition: all 0.3s ease;
    }

    .dashboard-card:hover {
      transform: translateY(-8px) scale(1.03);
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.4);
    }

    .dashboard-card h2 {
      font-size: 1.5rem;
      margin-bottom: 15px;
      color: #b3ecff;
    }

    .dashboard-card p {
      font-size: 1rem;
      margin-bottom: 25px;
      color: #e0f7ff;
    }

    .btn-primary {
      padding: 10px 22px;
      background-color: white;
      color: #0d47a1;
      border-radius: 25px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .btn-primary:hover {
      background-color: #00bcd4;
      color: white;
      transform: translateY(-3px);
    }

    /* FOOTER */
    footer {
      text-align: center;
      padding: 30px;
      color: white;
      font-size: 0.9rem;
      background: rgba(255, 255, 255, 0.15);
      border-top-left-radius: 20px;
      border-top-right-radius: 20px;
      margin-top: 100px;
    }
  </style>
</head>

<body>

  <!-- NAVBAR -->
  <nav class="navbar">

    <div class="logo">RecipeMate Admin</div>

    <ul class="nav-links">
      <li>
        <a href="<?= BASE_URL ?>admin/dashboard.php" class="active">
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
        <a href="<?= BASE_URL ?>admin/moderate_content.php">
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

  <!-- HEADER -->
  <section class="dashboard-header">
    <h1>Admin Dashboard</h1>
    <p>Manage users, recipes, and moderate content efficiently.</p>
  </section>

  <!-- DASHBOARD CARDS -->
  <section class="dashboard-container">

    <div class="dashboard-card">
      <h2>👥 Manage Users</h2>

      <p>
        View, edit, or remove user accounts and handle access roles.
      </p>

      <a
        href="<?= BASE_URL ?>admin/manage_users.php"
        class="btn-primary"
      >
        Go to Users
      </a>
    </div>


    <div class="dashboard-card">
      <h2>🍽 Manage Recipes</h2>

      <p>
        Review, edit, or delete submitted recipes from the platform.
      </p>

      <a
        href="<?= BASE_URL ?>admin/manage_recipes.php"
        class="btn-primary"
      >
        Go to Recipes
      </a>
    </div>


    <div class="dashboard-card">
      <h2>🛡 Moderate Content</h2>

      <p>
        Approve or remove inappropriate or flagged posts and comments.
      </p>

      <a
        href="<?= BASE_URL ?>admin/moderate_content.php"
        class="btn-primary"
      >
        Go to Moderation
      </a>
    </div>

  </section>

  <!-- FOOTER -->
  <footer>
    © 2025 RecipeMate Admin Panel. All rights reserved.
  </footer>

</body>
</html>