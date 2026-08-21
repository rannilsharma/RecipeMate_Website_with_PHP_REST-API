<?php
// ======================
// ENVIRONMENT
// ======================
define('API_ENVIRONMENT', getenv('APP_ENV') ?: 'local');

// ======================
// API BASE URL
// ======================
if (API_ENVIRONMENT === 'local') {
    define('BASE_URL', 'http://192.168.0.250/recipe_meal_planner');
} else {
    define('BASE_URL', getenv('APP_BASE_URL') ?: 'https://recipemate-website-with-php-rest-api.onrender.com');
}