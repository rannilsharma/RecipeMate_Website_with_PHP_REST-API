<?php
require_once __DIR__ . '/config/config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register | RecipeMate</title>

  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #0a2540, #163d69);
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      color: #fff;
    }

    .register-container {
      background: rgba(255, 255, 255, 0.1);
      border-radius: 20px;
      padding: 50px 40px;
      width: 420px;
      backdrop-filter: blur(15px);
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
      text-align: center;
    }

    .register-container h1 {
      margin-bottom: 25px;
      font-size: 1.8rem;
    }

    .register-container p {
      color: #c9d6f0;
      font-size: 0.95rem;
      margin-bottom: 30px;
    }

    input[type="text"],
    input[type="email"],
    input[type="password"] {
      width: 100%;
      padding: 12px;
      margin-bottom: 18px;
      border: none;
      border-radius: 10px;
      background: rgba(255,255,255,0.2);
      color: #fff;
      font-size: 1rem;
      outline: none;
      backdrop-filter: blur(8px);
    }

    input::placeholder {
      color: #d0d0d0;
    }

    .btn-register {
      width: 100%;
      padding: 12px;
      border: none;
      border-radius: 12px;
      background: #0077ff;
      color: white;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: 0.3s;
    }

    .btn-register:hover {
      background: #005fcc;
    }

    .extra-links {
      margin-top: 20px;
      font-size: 0.9rem;
    }

    .extra-links a {
      color: #9cd2ff;
      text-decoration: none;
    }

    .extra-links a:hover {
      color: #fff;
    }

    .brand {
      font-size: 1.5rem;
      font-weight: 600;
      margin-bottom: 15px;
      color: #ffffff;
    }

    .brand span {
      color: #4db2ff;
    }

    /* === Admin Option === */
    .admin-option {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin-bottom: 20px;
      font-size: 0.9rem;
      color: #cfe0ff;
    }

    .admin-option input[type="checkbox"] {
      accent-color: #0077ff;
      width: 16px;
      height: 16px;
      cursor: pointer;
    }
  </style>
</head>

<body>

  <div class="register-container">
    <div class="brand">🍽️ <span>RecipeMate</span></div>

    <h1>Create Account</h1>

    <p>Join RecipeMate and start planning your meals!</p>

    <form action="<?= BASE_URL ?>register_process.php" method="POST">

      <input
        type="text"
        name="name"
        placeholder="Username"
        required
      >

      <input
        type="email"
        name="email"
        placeholder="Email Address"
        required
      >

      <input
        type="password"
        name="password"
        placeholder="Password"
        required
      >

      <input
        type="password"
        name="confirm_password"
        placeholder="Confirm Password"
        required
      >

      <!-- Admin Registration Option -->
      <div class="admin-option">
        <input
          type="checkbox"
          id="is_admin"
          name="is_admin"
          value="1"
        >

        <label for="is_admin">Register as Admin</label>
      </div>

      <button type="submit" class="btn-register">
        Register
      </button>

    </form>

    <div class="extra-links">
      <p>
        Already have an account?
        <a href="<?= BASE_URL ?>index.php">Login</a>
      </p>
    </div>

  </div>

</body>
</html>