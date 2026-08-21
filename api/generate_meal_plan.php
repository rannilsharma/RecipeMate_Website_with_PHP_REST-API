<?php

require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

// 🔐 Token-based authentication
$userId = getAuthenticatedUserId();

if (!$userId) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit();
}

// 🔑 Get Spoonacular API key from environment variable
// On Render, this will be configured as:
// SPOONACULAR_API_KEY = your_actual_key
$apiKey = getenv('SPOONACULAR_API_KEY');

if (!$apiKey) {
    echo json_encode([
        'success' => false,
        'message' => 'Server configuration error: Spoonacular API key missing'
    ]);
    exit();
}

// ---------------------------------------------------------
// Helper: Fetch data from an external API using cURL
// ---------------------------------------------------------
function fetchApi($url)
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json'
        ]
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);

        return [
            'error' => $error
        ];
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    $decoded = json_decode($response, true);

    // Handle invalid JSON response
    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
        return [
            'error' => 'Invalid response from external API',
            'http_code' => $httpCode,
            'raw_response' => $response
        ];
    }

    // Attach HTTP status code for easier error handling
    if (is_array($decoded)) {
        $decoded['_http_code'] = $httpCode;
    }

    return $decoded;
}

// ---------------------------------------------------------
// Only accept POST requests
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Use POST.'
    ]);
    exit();
}

// ---------------------------------------------------------
// Read request body
// Supports both JSON and form-data/x-www-form-urlencoded
// ---------------------------------------------------------
$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    $input = [];
}

$calories = $input['calories'] ?? $_POST['calories'] ?? '';
$diet     = $input['diet'] ?? $_POST['diet'] ?? '';

// ---------------------------------------------------------
// Validate parameters
// ---------------------------------------------------------
if ($calories === '' || $diet === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Missing parameters'
    ]);
    exit();
}

// Validate calories
if (!is_numeric($calories) || (int)$calories <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Calories must be a valid positive number'
    ]);
    exit();
}

$calories = (int)$calories;

// ---------------------------------------------------------
// Generate meal plan using Spoonacular
// ---------------------------------------------------------
$url = 'https://api.spoonacular.com/mealplanner/generate'
     . '?apiKey=' . urlencode($apiKey)
     . '&timeFrame=day'
     . '&targetCalories=' . urlencode($calories)
     . '&diet=' . urlencode($diet);

$result = fetchApi($url);

// Check for API errors
if (
    !is_array($result) ||
    isset($result['error']) ||
    !isset($result['meals'])
) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to generate meal plan',
        'error' => $result['error'] ?? 'No meals returned from Spoonacular'
    ]);
    exit();
}

// ---------------------------------------------------------
// Fetch detailed information for each meal
// ---------------------------------------------------------
foreach ($result['meals'] as &$meal) {

    if (!isset($meal['id'])) {
        $meal['details'] = null;
        continue;
    }

    $mealId = (int)$meal['id'];

    $infoUrl = 'https://api.spoonacular.com/recipes/'
             . $mealId
             . '/information'
             . '?includeNutrition=true'
             . '&apiKey=' . urlencode($apiKey);

    $details = fetchApi($infoUrl);

    // If details request fails, don't fail the entire meal plan
    if (
        !is_array($details) ||
        isset($details['error'])
    ) {
        $meal['details'] = null;
    } else {
        $meal['details'] = $details;
    }
}

unset($meal);

// ---------------------------------------------------------
// Return meal plan
// ---------------------------------------------------------
echo json_encode([
    'success' => true,
    'data' => $result
]);

?>