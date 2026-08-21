<?php
// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(403);
    exit('Direct access not allowed');
}

// ======================
// ENVIRONMENT SETTING
// ======================
// 'local'     = your computer
// 'production' = Render
define('ENVIRONMENT', getenv('APP_ENV') ?: 'local');

// ======================
// BASE URL
// ======================
if (ENVIRONMENT === 'local') {
    define('BASE_URL', '/recipe_meal_planner/');
} else {
    define('BASE_URL', '/');   // on Render the files are at the root
}

// ======================
// DATABASE CREDENTIALS
// ======================
if (ENVIRONMENT === 'local') {
    // Local settings (XAMPP / Laragon)
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'recipe_portal');
    define('DB_PORT', 3309);
} else {
    // Production (Render + Aiven)
    define('DB_HOST', getenv('DB_HOST'));
    define('DB_USER', getenv('DB_USER'));
    define('DB_PASS', getenv('DB_PASS'));
    define('DB_NAME', getenv('DB_NAME'));
    define('DB_PORT', getenv('DB_PORT') ?: 3306);
}

// ======================
// SPOONACULAR API KEY
// ======================
define('API_KEY', getenv('SPOONACULAR_API_KEY')); //set api key as environment variable