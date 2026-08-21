<?php
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../dbConnection.php';

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Profile | RecipeMate Admin</title>

  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap"
    rel="stylesheet"
  >

  <style>
    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(120deg, #0d47a1, #00bcd4);
      background-attachment: fixed;
      color: #fff;
      overflow-x: hidden;
    }

    /* === NAVBAR === */
    .navbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px 60px;
      background: rgba(255, 255, 255, 0.2);
      backdrop-filter: blur(15px);
      border-radius: 0 0 20px 20px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    .logo {
      font-size: 1.8rem;
      font-weight: 700;
      color: #fff;
    }

    .nav-links {
      list-style: none;
      display: flex;
      gap: 25px;
      margin: 0;
      padding: 0;
    }

    .nav-links a {
      color: #fff;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s ease;
    }

    .nav-links a:hover,
    .nav-links a.active {
      color: #80deea;
      transform: scale(1.1);
    }

    /* === PROFILE CONTAINER === */
    .profile-container {
      max-width: 900px;
      margin: 60px auto;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 20px;
      padding: 40px;
      backdrop-filter: blur(15px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
    }

    .profile-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      margin-bottom: 30px;
    }

    .user-info h2 {
      font-size: 1.8rem;
      margin-bottom: 5px;
      color: #b3ecff;
    }

    .user-info p {
      font-size: 1rem;
      color: #e0f7ff;
    }

    .btn {
      padding: 10px 22px;
      background: #fff;
      color: #0d47a1;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .btn:hover {
      background: #00bcd4;
      color: white;
      transform: translateY(-2px);
    }

    .profile-details {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
    }

    .detail-card {
      background: rgba(255, 255, 255, 0.2);
      border-radius: 15px;
      padding: 20px;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
      transition: transform 0.3s ease;
    }

    .detail-card:hover {
      transform: translateY(-5px);
    }

    .detail-card h3 {
      color: #b3ecff;
      margin-bottom: 10px;
      font-size: 1.1rem;
    }

    .detail-card p {
      color: #fff;
      font-size: 0.95rem;
    }

    footer {
      text-align: center;
      padding: 25px;
      color: #fff;
      background: rgba(255, 255, 255, 0.15);
      border-top-left-radius: 20px;
      border-top-right-radius: 20px;
      margin-top: 100px;
      font-size: 0.9rem;
    }
  </style>
</head>

<body>

  <!-- NAVBAR -->
  <nav class="navbar">

    <div class="logo">
      RecipeMate Admin
    </div>

    <ul class="nav-links">

      <li>
        <a href="<?php echo BASE_URL; ?>admin/dashboard.php">
          Dashboard
        </a>
      </li>

      <li>
        <a href="<?php echo BASE_URL; ?>admin/manage_users.php">
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
        <a href="<?php echo BASE_URL; ?>admin/profile.php" class="active">
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

  <!-- PROFILE CONTAINER -->
  <div class="profile-container">

    <div class="profile-header">

      <div class="user-info">

        <h2>
          👤 <?= htmlspecialchars($user['userName']) ?>
        </h2>

        <p id="headerEmail">
          <?= htmlspecialchars($user['email']) ?>
          |
          Member since <?= date("Y", strtotime($user['created_at'])) ?>
        </p>

      </div>

      <div class="profile-actions">

        <button id="editBtn" class="btn">
          ✏️ Edit Profile
        </button>

      </div>

    </div>

    <div class="profile-details">

      <div class="detail-card">

        <h3>
          📧 Email
        </h3>

        <p id="emailField">
          <?= htmlspecialchars($user['email']) ?>
        </p>

      </div>

      <div class="detail-card">

        <h3>
          📅 Joined
        </h3>

        <p>
          <?= date("F j, Y", strtotime($user['created_at'])) ?>
        </p>

      </div>

    </div>

  </div>

  <footer>
    © 2025 RecipeMate Admin Panel. All rights reserved.
  </footer>

  <script>
    const editBtn = document.getElementById("editBtn");

    let isEditing = false;
    let emailInput = null;

    editBtn.addEventListener("click", async () => {

      /* =========================
         EDIT MODE
         ========================= */
      if (!isEditing) {

        const emailField = document.getElementById("emailField");

        if (!emailField) {
          return;
        }

        const emailText = emailField.textContent.trim();

        emailInput = document.createElement("input");

        emailInput.type = "email";
        emailInput.value = emailText;

        emailInput.style.width = "100%";
        emailInput.style.padding = "8px";
        emailInput.style.borderRadius = "6px";
        emailInput.style.border = "none";
        emailInput.style.fontSize = "1rem";
        emailInput.style.boxSizing = "border-box";

        emailField.replaceWith(emailInput);

        editBtn.textContent = "💾 Save Changes";

        isEditing = true;

        emailInput.focus();

        return;
      }


      /* =========================
         SAVE MODE
         ========================= */

      if (!emailInput) {
        return;
      }

      const newEmail = emailInput.value.trim();

      if (newEmail === "") {
        alert("Email cannot be empty.");
        emailInput.focus();
        return;
      }

      // Basic email validation
      const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      if (!emailPattern.test(newEmail)) {
        alert("Please enter a valid email address.");
        emailInput.focus();
        return;
      }

      // Prevent multiple clicks while saving
      editBtn.disabled = true;
      editBtn.textContent = "Saving...";

      try {

        const response = await fetch("update_profile.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded"
          },
          body: `email=${encodeURIComponent(newEmail)}`
        });

        const result = await response.text();

        if (result.trim() === "success") {

          /* =========================
             UPDATE EMAIL FIELD
             ========================= */

          const newP = document.createElement("p");

          newP.id = "emailField";
          newP.textContent = newEmail;

          emailInput.replaceWith(newP);


          /* =========================
             UPDATE HEADER EMAIL
             ========================= */

          const headerEmail = document.getElementById("headerEmail");

          if (headerEmail) {
            headerEmail.textContent =
              newEmail +
              " | Member since " +
              "<?= date("Y", strtotime($user['created_at'])) ?>";
          }


          /* =========================
             RESET EDIT STATE
             ========================= */

          emailInput = null;
          isEditing = false;

          editBtn.disabled = false;
          editBtn.textContent = "✏️ Edit Profile";

          alert("✅ Profile updated successfully!");

        } else {

          editBtn.disabled = false;
          editBtn.textContent = "💾 Save Changes";

          alert("❌ Update failed. Please try again.");
        }

      } catch (error) {

        console.error("Profile update error:", error);

        editBtn.disabled = false;
        editBtn.textContent = "💾 Save Changes";

        alert("❌ Unable to update profile. Please try again.");

      }

    });
  </script>

</body>
</html>