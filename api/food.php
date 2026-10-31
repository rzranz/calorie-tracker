<?php
/**
 * Food Nutrition API Endpoint
 * Handles search, add, delete, and summary requests.
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/functions.php';

$user = getCurrentUser();
$userId = $user ? (int)$user['id_user'] : 1;

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!is_array($input)) {
    $input = [];
}

if (!empty($input['action'])) {
    $action = $input['action'];
}

try {
    switch ($action) {
        case 'search':
            $query = trim($_GET['q'] ?? ($input['q'] ?? ($_POST['q'] ?? '')));
            $items = NutritionService::searchNutrition($query);
            echo json_encode([
                'success' => true,
                'query'   => $query,
                'count'   => count($items),
                'items'   => $items
            ]);
            break;

        case 'add':
            $name = trim($_POST['name'] ?? ($input['name'] ?? ''));
            $servingSize = (float)($_POST['serving_size'] ?? ($input['serving_size'] ?? 100));
            $calories = (float)($_POST['calories'] ?? ($input['calories'] ?? 0));
            $protein = (float)($_POST['protein'] ?? ($input['protein'] ?? 0));
            $carbs = (float)($_POST['carbohydrates'] ?? ($input['carbohydrates'] ?? ($_POST['carbs'] ?? ($input['carbs'] ?? 0))));
            $fat = (float)($_POST['fat'] ?? ($input['fat'] ?? 0));
            $date = trim($_POST['date'] ?? ($input['date'] ?? date('Y-m-d')));

            if (empty($name)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Nama makanan tidak boleh kosong.']);
                exit;
            }

            $id = NutritionService::addFoodLog($name, $servingSize, $calories, $protein, $carbs, $fat, $date, $userId);
            $summary = NutritionService::getTodayFoodSummary($date, $userId);

            echo json_encode([
                'success' => true,
                'message' => 'Makanan berhasil dicatat ke jurnal harian.',
                'id'      => $id,
                'item'    => [
                    'id_hitung'    => $id,
                    'makanan'      => $name,
                    'berat'        => $servingSize,
                    'kalori'       => $calories,
                    'protein'      => $protein,
                    'karbohidrat'  => $carbs,
                    'lemak'        => $fat,
                    'tanggal'      => $date
                ],
                'summary' => $summary
            ]);
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? ($_GET['id'] ?? ($input['id'] ?? 0)));
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID makanan tidak valid.']);
                exit;
            }

            $deleted = NutritionService::deleteFoodLog($id, $userId);
            $summary = NutritionService::getTodayFoodSummary(null, $userId);

            echo json_encode([
                'success' => $deleted,
                'message' => $deleted ? 'Makanan berhasil dihapus.' : 'Gagal menghapus makanan.',
                'summary' => $summary
            ]);
            break;

        case 'reset':
            $date = trim($_POST['date'] ?? ($input['date'] ?? date('Y-m-d')));
            $reset = NutritionService::resetTodayFoodLogs($date, $userId);
            $summary = NutritionService::getTodayFoodSummary($date, $userId);

            echo json_encode([
                'success' => $reset,
                'message' => 'Catatan makanan hari ini telah di-reset.',
                'summary' => $summary
            ]);
            break;

        case 'today':
        default:
            $date = trim($_GET['date'] ?? date('Y-m-d'));
            $items = NutritionService::getTodayFoodLogs($date, $userId);
            $summary = NutritionService::getTodayFoodSummary($date, $userId);

            echo json_encode([
                'success' => true,
                'date'    => $date,
                'summary' => $summary,
                'items'   => $items
            ]);
            break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}
