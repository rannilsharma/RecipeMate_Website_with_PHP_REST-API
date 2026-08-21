<?php
session_start();

require_once __DIR__ . '/../dbConnection.php';
require_once __DIR__ . '/../config/config.php';


// ============================================================
// CHECK LOGIN
// ============================================================

if (!isset($_SESSION['user_id'])) {
    die("❌ You must be logged in to delete a recipe.");
}

$userId = (int) $_SESSION['user_id'];


// ============================================================
// GET RECIPE ID
// ============================================================

if (!isset($_GET['id'])) {
    die("❌ No recipe ID specified.");
}

$recipeId = intval($_GET['id']);


// ============================================================
// CHECK IF RECIPE BELONGS TO LOGGED-IN USER
// ============================================================

$stmt = $conn->prepare(
    "SELECT title, user_id FROM recipes WHERE id = ?"
);

$stmt->bind_param(
    "i",
    $recipeId
);

$stmt->execute();

$result = $stmt->get_result();

$recipe = $result->fetch_assoc();

$stmt->close();


if (!$recipe) {
    die("❌ Recipe not found.");
}


// ============================================================
// MAKE SURE USER OWNS RECIPE
// ============================================================

if ((int)$recipe['user_id'] !== $userId) {
    die("❌ You cannot delete a recipe that is not yours.");
}


// ============================================================
// HANDLE DELETION AFTER CONFIRMATION
// ============================================================

if (isset($_POST['confirm'])) {


    // --------------------------------------------------------
    // DELETE RECIPE FROM DATABASE
    // --------------------------------------------------------

    $stmt = $conn->prepare(
        "DELETE FROM recipes WHERE id = ?"
    );

    $stmt->bind_param(
        "i",
        $recipeId
    );


    if ($stmt->execute()) {

        $stmt->close();

        $conn->close();


        // ----------------------------------------------------
        // SUCCESS
        // ----------------------------------------------------

        echo "
        <script>

            alert('🗑️ Recipe deleted successfully!');

            window.location.href = '" .
            BASE_URL .
            "recipes/recipe_index.php';

        </script>
        ";

        exit();


    } else {

        $stmt->close();

        $conn->close();

        die("❌ Failed to delete recipe.");
    }
}


$conn->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Delete Recipe | RecipeMate
    </title>


    <style>

        body {

            background: #081a30;

            font-family:
                'Poppins',
                sans-serif;

            color: white;

            text-align: center;

            padding-top: 100px;

        }


        .box {

            background:
                rgba(255,255,255,0.1);

            padding: 40px;

            border-radius: 15px;

            display: inline-block;

            box-shadow:
                0 8px 25px
                rgba(0,0,0,0.3);

        }


        button {

            padding: 10px 25px;

            border: none;

            border-radius: 10px;

            margin: 10px;

            cursor: pointer;

            font-weight: 600;

            font-size: 0.95rem;

        }


        .yes {

            background: #ff4d4d;

            color: white;

        }


        .no {

            background: #555;

            color: white;

        }


        button:hover {

            opacity: 0.85;

        }

    </style>

</head>


<body>


    <div class="box">


        <h2>
            ⚠️ Confirm Deletion
        </h2>


        <p>

            Are you sure you want to delete

            "<b>
                <?= htmlspecialchars($recipe['title']); ?>
            </b>"?

        </p>


        <form method="POST">


            <button
                type="submit"
                name="confirm"
                class="yes"
            >

                Yes, Delete

            </button>


            <button
                type="button"
                class="no"
                onclick="
                    window.location.href='<?= BASE_URL ?>recipes/view_recipe.php?id=<?= $recipeId; ?>'
                "
            >

                Cancel

            </button>


        </form>


    </div>


</body>

</html>