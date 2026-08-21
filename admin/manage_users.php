<?php
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../dbConnection.php';

// Only allow admins
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

// Fetch all users
$query = "SELECT user_id, userName, email, status, created_at FROM users ORDER BY user_id ASC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Users | RecipeMate Admin</title>
  <style>
    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      color: #fff;
      background: linear-gradient(120deg, #0d47a1, #00bcd4);
      background-attachment: fixed;
    }

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

    .container {
      max-width: 1100px;
      margin: 60px auto;
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(15px);
      border-radius: 20px;
      padding: 40px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
    }

    h1 {
      text-align: center;
      margin-bottom: 30px;
      color: #e0f7ff;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      color: #fff;
    }

    th, td {
      padding: 14px;
      text-align: center;
      border-bottom: 1px solid rgba(255,255,255,0.2);
    }

    th {
      background: rgba(255,255,255,0.2);
      color: #b3ecff;
      font-size: 1.1rem;
    }

    tr:hover {
      background: rgba(255,255,255,0.1);
    }

    .btn {
      padding: 8px 16px;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s ease;
      color: white;
    }

    .btn-enable {
      background: #4CAF50;
    }

    .btn-disable {
      background: #f39c12;
    }

    .btn-delete {
      background: #e74c3c;
    }

    .btn:hover {
      opacity: 0.85;
      transform: translateY(-2px);
    }

    footer {
      text-align: center;
      padding: 25px;
      color: #cceaff;
      background: rgba(255, 255, 255, 0.1);
      border-top-left-radius: 20px;
      border-top-right-radius: 20px;
      margin-top: 80px;
    }
  </style>
</head>
<body>

  <!-- NAVBAR -->
  <nav class="navbar">
    <div class="logo">RecipeMate Admin</div>

    <ul class="nav-links">
      <li>
        <a href="<?php echo BASE_URL; ?>admin/dashboard.php">
          Dashboard
        </a>
      </li>

      <li>
        <a href="<?php echo BASE_URL; ?>admin/manage_users.php" class="active">
          Manage Users
        </a>
      </li>

      <li>
        <a href="<?php echo BASE_URL; ?>admin/manage_recipes.php">
          Manage Recipes
        </a>
      </li>

      <li>
        <a href="<?php echo BASE_URL; ?>admin/moderate_content.php">
          Moderate Content
        </a>
      </li>

      <li>
        <a href="<?php echo BASE_URL; ?>admin/profile.php">
          Profile
        </a>
      </li>

      <li>
        <a href="<?php echo BASE_URL; ?>index.php">
          Logout
        </a>
      </li>
    </ul>
  </nav>

  <div class="container">
    <h1>Manage Users</h1>

    <table>
      <tr>
        <th>ID</th>
        <th>Username</th>
        <th>Email</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>

      <?php if ($result->num_rows > 0): ?>

        <?php while ($row = $result->fetch_assoc()): ?>

          <tr id="user-<?php echo $row['user_id']; ?>">

            <td>
              <?php echo $row['user_id']; ?>
            </td>

            <td>
              <?php echo htmlspecialchars($row['userName']); ?>
            </td>

            <td>
              <?php echo htmlspecialchars($row['email']); ?>
            </td>

            <td>
              <?php echo ucfirst($row['status']); ?>
            </td>

            <td>

              <?php if ($row['status'] === 'active'): ?>

                <button
                  class="btn btn-disable"
                  onclick="updateStatus(<?php echo $row['user_id']; ?>, 'disabled')">
                  Disable
                </button>

              <?php else: ?>

                <button
                  class="btn btn-enable"
                  onclick="updateStatus(<?php echo $row['user_id']; ?>, 'active')">
                  Enable
                </button>

              <?php endif; ?>

              <button
                class="btn btn-delete"
                onclick="deleteUser(<?php echo $row['user_id']; ?>)">
                Delete
              </button>

            </td>

          </tr>

        <?php endwhile; ?>

      <?php else: ?>

        <tr>
          <td colspan="5">
            No users found.
          </td>
        </tr>

      <?php endif; ?>

    </table>
  </div>

  <footer>
    © 2025 RecipeMate Admin Panel. All rights reserved.
  </footer>

  <script>
    async function updateStatus(userId, newStatus) {
      if (!confirm(`Are you sure you want to ${newStatus} this user?`)) {
        return;
      }

      const response = await fetch('user_actions.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `action=update&user_id=${userId}&status=${newStatus}`
      });

      const result = await response.text();

      alert(result);

      location.reload();
    }

    async function deleteUser(userId) {
      if (!confirm("Are you sure you want to delete this user?")) {
        return;
      }

      const response = await fetch('user_actions.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `action=delete&user_id=${userId}`
      });

      const result = await response.text();

      alert(result);

      document.getElementById(`user-${userId}`).remove();
    }
  </script>

</body>
</html>