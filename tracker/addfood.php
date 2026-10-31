<?php
/**
 * Legacy Endpoint: addfood.php
 * Handles POST requests to add food items to the database.
 */

require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $servingSize = (float)($_POST['serving_size'] ?? 100);
    $calories = (float)($_POST['calories'] ?? 0);
    $protein = (float)($_POST['protein'] ?? 0);
    $carbs = (float)($_POST['carbs'] ?? ($_POST['carbohydrates'] ?? 0));
    $fat = (float)($_POST['fat'] ?? 0);

    if (!empty($name)) {
        $id = addFoodLog($name, $servingSize, $calories, $protein, $carbs, $fat);

        // Check if request expects JSON
        if (
            (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
            (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        ) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'id' => $id, 'message' => 'New record created successfully']);
        } else {
            echo "New record created successfully. ID: " . $id;
        }
    } else {
        http_response_code(400);
        echo "Error: Food name is required.";
    }
} else {
    echo "No data received.";
}
