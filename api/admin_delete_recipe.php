<?php

header('Content-Type: application/json');

require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/api_config.php';


// =====================================================
// GET AUTHENTICATED USER
// =====================================================

$userId = getAuthenticatedUserId();

if (!$userId) {

    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);

    exit;
}


// =====================================================
// VERIFY ADMIN ACCESS
// =====================================================

$stmt = $conn->prepare("
    SELECT is_admin
    FROM users
    WHERE user_id = ?
");

if (!$stmt) {

    echo json_encode([
        "success" => false,
        "message" => "Database error"
    ]);

    exit;
}

$stmt->bind_param("i", $userId);
$stmt->execute();

$adminCheck = $stmt->get_result()->fetch_assoc();

$stmt->close();


if (!$adminCheck || (int)$adminCheck['is_admin'] !== 1) {

    echo json_encode([
        "success" => false,
        "message" => "Admin access required"
    ]);

    exit;
}


// =====================================================
// GET RECIPE ID
// =====================================================

$recipeId = intval($_POST['recipe_id'] ?? 0);

if ($recipeId <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid recipe ID"
    ]);

    exit;
}


// =====================================================
// GET RECIPE IMAGE PATH
// BEFORE DELETING THE RECIPE
// =====================================================

$stmt = $conn->prepare("
    SELECT image_path
    FROM recipes
    WHERE id = ?
");

if (!$stmt) {

    echo json_encode([
        "success" => false,
        "message" => "Database error"
    ]);

    exit;
}

$stmt->bind_param("i", $recipeId);
$stmt->execute();

$result = $stmt->get_result();

$recipe = $result->fetch_assoc();

$stmt->close();


// =====================================================
// CHECK IF RECIPE EXISTS
// =====================================================

if (!$recipe) {

    echo json_encode([
        "success" => false,
        "message" => "Recipe not found"
    ]);

    exit;
}


$imagePath = $recipe['image_path'] ?? '';


// =====================================================
// DELETE RELATED LIKES
// =====================================================

$stmtLikes = $conn->prepare("
    DELETE FROM recipe_likes
    WHERE recipe_id = ?
");

if ($stmtLikes) {

    $stmtLikes->bind_param("i", $recipeId);
    $stmtLikes->execute();
    $stmtLikes->close();
}


// =====================================================
// DELETE RELATED REVIEWS
// =====================================================

$stmtReviews = $conn->prepare("
    DELETE FROM reviews
    WHERE recipe_id = ?
");

if ($stmtReviews) {

    $stmtReviews->bind_param("i", $recipeId);
    $stmtReviews->execute();
    $stmtReviews->close();
}


// =====================================================
// DELETE RECIPE IMAGE
// =====================================================

if (!empty($imagePath)) {

    /*
     * The database may contain:
     *
     * 1. uploads/example.jpg
     *
     * 2. /uploads/example.jpg
     *
     * 3. /recipe_meal_planner/uploads/example.jpg
     *
     * 4. http://192.168.0.250/recipe_meal_planner/uploads/example.jpg
     *
     * 5. https://yourdomain.com/uploads/example.jpg
     *
     * We convert all of these into the actual
     * filesystem path before deleting.
     */


    // =================================================
    // GET URL PATH ONLY
    // =================================================

    $imageUrlPath = parse_url(
        trim($imagePath),
        PHP_URL_PATH
    );


    if ($imageUrlPath !== false && $imageUrlPath !== null) {


        // =============================================
        // GET BASE URL PATH
        // =============================================

        $baseUrlPath = parse_url(
            BASE_URL,
            PHP_URL_PATH
        );


        if ($baseUrlPath === false || $baseUrlPath === null) {
            $baseUrlPath = '';
        }


        /*
         * Example:
         *
         * BASE_URL:
         * http://192.168.0.250/recipe_meal_planner
         *
         * BASE URL PATH:
         * /recipe_meal_planner
         */


        $baseUrlPath = '/' . trim(
            $baseUrlPath,
            '/'
        );


        // =============================================
        // NORMALIZE IMAGE URL PATH
        // =============================================

        $imageUrlPath = '/' . ltrim(
            $imageUrlPath,
            '/'
        );


        // =============================================
        // REMOVE BASE URL FROM IMAGE PATH
        // =============================================

        if (
            $baseUrlPath !== '/' &&
            strpos(
                $imageUrlPath,
                $baseUrlPath . '/'
            ) === 0
        ) {

            $relativeImagePath = substr(
                $imageUrlPath,
                strlen($baseUrlPath) + 1
            );

        } else {

            $relativeImagePath = ltrim(
                $imageUrlPath,
                '/'
            );
        }


        /*
         * If the database contains:
         *
         * /recipe_meal_planner/uploads/example.jpg
         *
         * it becomes:
         *
         * uploads/example.jpg
         *
         *
         * If it already contains:
         *
         * uploads/example.jpg
         *
         * it stays:
         *
         * uploads/example.jpg
         */


        // =============================================
        // NORMALIZE DIRECTORY SEPARATORS
        // =============================================

        $relativeImagePath = str_replace(
            ['/', '\\'],
            DIRECTORY_SEPARATOR,
            $relativeImagePath
        );


        // =============================================
        // PROJECT DIRECTORY
        // =============================================

        /*
         * __DIR__:
         *
         * recipe_meal_planner/api
         *
         *
         * dirname(__DIR__):
         *
         * recipe_meal_planner
         */

        $projectDirectory = dirname(__DIR__);


        // =============================================
        // FULL IMAGE FILESYSTEM PATH
        // =============================================

        $fullImagePath =
            $projectDirectory .
            DIRECTORY_SEPARATOR .
            $relativeImagePath;


        // =============================================
        // UPLOADS DIRECTORY
        // =============================================

        $uploadsDirectory = realpath(
            $projectDirectory .
            DIRECTORY_SEPARATOR .
            'uploads'
        );


        // =============================================
        // REAL IMAGE PATH
        // =============================================

        $realImagePath = realpath(
            $fullImagePath
        );


        // =============================================
        // SAFETY CHECK
        // =============================================

        /*
         * IMPORTANT:
         *
         * Only delete files that are actually inside:
         *
         * recipe_meal_planner/uploads/
         *
         * This prevents an incorrect image_path from
         * accidentally deleting another file.
         */

        if (
            $uploadsDirectory !== false &&
            $realImagePath !== false &&
            is_file($realImagePath)
        ) {

            $uploadsPrefix =
                $uploadsDirectory .
                DIRECTORY_SEPARATOR;


            if (
                strpos(
                    $realImagePath,
                    $uploadsPrefix
                ) === 0
            ) {

                @unlink($realImagePath);
            }
        }
    }
}


// =====================================================
// DELETE RECIPE
// =====================================================

$stmt = $conn->prepare("
    DELETE FROM recipes
    WHERE id = ?
");

if (!$stmt) {

    echo json_encode([
        "success" => false,
        "message" => "Failed to prepare recipe deletion"
    ]);

    $conn->close();

    exit;
}


$stmt->bind_param(
    "i",
    $recipeId
);


// =====================================================
// EXECUTE RECIPE DELETE
// =====================================================

if (!$stmt->execute()) {

    $stmt->close();
    $conn->close();

    echo json_encode([
        "success" => false,
        "message" => "Failed to delete recipe"
    ]);

    exit;
}


$stmt->close();


// =====================================================
// CLOSE DATABASE
// =====================================================

$conn->close();


// =====================================================
// SUCCESS RESPONSE
// =====================================================

echo json_encode([
    "success" => true,
    "message" => "Recipe deleted successfully"
]);

?>