<?php
/**
 * Workout & Exercise API Endpoint
 * Handles logging, calculation, and deletion of workouts.
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
        case 'rates':
            echo json_encode([
                'success' => true,
                'rates'   => WorkoutService::getRates()
            ]);
            break;

        case 'add':
            $name = trim($_POST['name'] ?? ($input['name'] ?? ''));
            $duration = (float)($_POST['duration'] ?? ($input['duration'] ?? 0));
            $reps = (int)($_POST['repetitions'] ?? ($input['repetitions'] ?? ($input['reps'] ?? 0)));
            $burned = (float)($_POST['calories_burned'] ?? ($input['calories_burned'] ?? ($input['burned'] ?? 0)));
            $date = trim($_POST['date'] ?? ($input['date'] ?? date('Y-m-d')));

            if (empty($name)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Nama olahraga harus diisi.']);
                exit;
            }

            $id = WorkoutService::addExerciseLog($name, $duration, $reps, $burned, $date, $userId);
            $summary = WorkoutService::getTodayExerciseSummary($date, $userId);

            echo json_encode([
                'success' => true,
                'message' => 'Latihan berhasil dicatat.',
                'id'      => $id,
                'item'    => [
                    'id_olahraga'     => $id,
                    'nama_olahraga'   => $name,
                    'durasi_menit'    => $duration,
                    'repetisi'        => $reps,
                    'kalori_terbakar' => $burned,
                    'tanggal'         => $date
                ],
                'summary' => $summary
            ]);
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? ($_GET['id'] ?? ($input['id'] ?? 0)));
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID olahraga tidak valid.']);
                exit;
            }

            $deleted = WorkoutService::deleteExerciseLog($id, $userId);
            $summary = WorkoutService::getTodayExerciseSummary(null, $userId);

            echo json_encode([
                'success' => $deleted,
                'message' => $deleted ? 'Catatan olahraga berhasil dihapus.' : 'Gagal menghapus.',
                'summary' => $summary
            ]);
            break;

        case 'today':
        default:
            $date = trim($_GET['date'] ?? date('Y-m-d'));
            $items = WorkoutService::getTodayExerciseLogs($date, $userId);
            $summary = WorkoutService::getTodayExerciseSummary($date, $userId);

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
