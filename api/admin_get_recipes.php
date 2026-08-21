<?php

header('Content-Type: application/json');

require_once __DIR__ . '/api_config.php';
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';


// =====================================================
// AUTHENTICATION
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
// ADMIN CHECK
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
// FILTERS
// =====================================================

$startDate = $_GET['start_date'] ?? '';
$endDate   = $_GET['end_date'] ?? '';
$postedBy  = $_GET['posted_by'] ?? '';


// =====================================================
// BASE QUERY
// =====================================================

$query = "
    SELECT
        r.id,
        r.title,
        r.category,
        r.created_at,
        r.image_path,
        u.userName,

        (
            SELECT COUNT(*)
            FROM recipe_likes rl
            WHERE rl.recipe_id = r.id
        ) AS total_likes

    FROM recipes r

    JOIN users u
        ON r.user_id = u.user_id

    WHERE 1
";


// =====================================================
// PARAMETERS
// =====================================================

$params = [];
$types  = '';


// =====================================================
// START DATE
// =====================================================

if (!empty($startDate)) {

    $query .= "
        AND r.created_at >= ?
    ";

    $params[] = $startDate . " 00:00:00";
    $types .= 's';
}


// =====================================================
// END DATE
// =====================================================

if (!empty($endDate)) {

    $query .= "
        AND r.created_at <= ?
    ";

    $params[] = $endDate . " 23:59:59";
    $types .= 's';
}


// =====================================================
// POSTED BY
// =====================================================

if (!empty($postedBy)) {

    $query .= "
        AND u.userName LIKE ?
    ";

    $params[] = "%" . $postedBy . "%";
    $types .= 's';
}


// =====================================================
// ORDER
// =====================================================

$query .= "
    ORDER BY r.created_at DESC
";


// =====================================================
// PREPARE
// =====================================================

$stmt = $conn->prepare($query);

if (!$stmt) {

    echo json_encode([
        "success" => false,
        "message" => "Database query preparation failed"
    ]);

    exit;
}


// =====================================================
// BIND PARAMETERS
// =====================================================

if (!empty($params)) {

    $stmt->bind_param(
        $types,
        ...$params
    );
}


// =====================================================
// EXECUTE
// =====================================================

if (!$stmt->execute()) {

    $stmt->close();

    echo json_encode([
        "success" => false,
        "message" => "Database query execution failed"
    ]);

    exit;
}


$result = $stmt->get_result();


// =====================================================
// BUILD RESPONSE
// =====================================================

$recipes = [];


// =====================================================
// BASE URL
// =====================================================

$baseUrl = rtrim(BASE_URL, '/');


// =====================================================
// PROCESS RECIPES
// =====================================================

while ($row = $result->fetch_assoc()) {

    // =================================================
    // ORIGINAL DATABASE IMAGE PATH
    // =================================================

    $imagePath = trim(
        $row['image_path'] ?? ''
    );


    // =================================================
    // IMAGE URL
    // =================================================

    if ($imagePath === '') {

        /*
         * No image stored.
         */

        $row['image_url'] = null;

    }

    elseif (
        strpos($imagePath, 'http://') === 0 ||
        strpos($imagePath, 'https://') === 0
    ) {

        /*
         * Complete URL already stored.
         *
         * Example:
         *
         * https://example.com/uploads/image.jpg
         */

        $row['image_url'] = $imagePath;

    }

    else {

        /*
         * Normalize Windows slashes.
         */

        $imagePath = str_replace(
            '\\',
            '/',
            $imagePath
        );


        /*
         * Remove leading slash.
         */

        $imagePath = ltrim(
            $imagePath,
            '/'
        );


        /*
         * Find "uploads/" anywhere in the path.
         *
         * This handles:
         *
         * image.jpg
         *
         * uploads/image.jpg
         *
         * recipe_meal_planner/uploads/image.jpg
         *
         * /recipe_meal_planner/uploads/image.jpg
         */

        $uploadsPosition = strpos(
            $imagePath,
            'uploads/'
        );


        if ($uploadsPosition !== false) {

            /*
             * Keep everything after uploads/
             *
             * Example:
             *
             * recipe_meal_planner/uploads/chicken.jpg
             *
             * becomes:
             *
             * chicken.jpg
             */

            $imageFile = substr(
                $imagePath,
                $uploadsPosition + strlen('uploads/')
            );

        } else {

            /*
             * If the database only contains:
             *
             * chicken.jpg
             *
             * use it directly.
             */

            $imageFile = $imagePath;
        }


        /*
         * Remove any accidental leading slash.
         */

        $imageFile = ltrim(
            $imageFile,
            '/'
        );


        /*
         * Build final browser URL.
         *
         * LOCAL:
         *
         * http://192.168.0.250/
         * recipe_meal_planner/
         * uploads/chicken.jpg
         *
         * PRODUCTION:
         *
         * https://yourdomain.com/
         * uploads/chicken.jpg
         */

        $row['image_url'] =
            $baseUrl .
            '/uploads/' .
            $imageFile;
    }


    // =================================================
    // ADD RECIPE
    // =================================================

    $recipes[] = $row;
}


// =====================================================
// CLOSE
// =====================================================

$stmt->close();

$conn->close();


// =====================================================
// RESPONSE
// =====================================================

echo json_encode([
    "success" => true,
    "recipes" => $recipes
]);

?>