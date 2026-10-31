<?php
/**
 * Exercise & Workout API Endpoint
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/functions.php';

$user = getCurrentUser();
$userId = $user ? (int)$user['id_user'] : 1;

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$input = json_decode(file_get_contents('php://input'), true) ?? [];
if (!empty($input['action'])) {
    $action = $input['action'];
}

$exerciseRates = [
    'Push Up'        => ['type' => 'reps', 'rate' => 0.45, 'unit' => 'reps', 'label' => 'Push Up (0.45 kcal/rep)'],
    'Sit Up'         => ['type' => 'reps', 'rate' => 0.40, 'unit' => 'reps', 'label' => 'Sit Up (0.40 kcal/rep)'],
    'Pull Up'        => ['type' => 'reps', 'rate' => 1.00, 'unit' => 'reps', 'label' => 'Pull Up (1.00 kcal/rep)'],
    'Squat'          => ['type' => 'reps', 'rate' => 0.50, 'unit' => 'reps', 'label' => 'Squat Bodyweight (0.50 kcal/rep)'],
    'Angkat Beban'   => ['type' => 'duration', 'rate' => 6.5, 'unit' => 'minutes', 'label' => 'Angkat Beban / Gym (6.5 kcal/menit)'],
    'Lari (Jogging)' => ['type' => 'duration', 'rate' => 10.0, 'unit' => 'minutes', 'label' => 'Lari / Jogging (10.0 kcal/menit)'],
    'Bersepeda'      => ['type' => 'duration', 'rate' => 8.5, 'unit' => 'minutes', 'label' => 'Bersepeda Sedang (8.5 kcal/menit)'],
    'Renang'         => ['type' => 'duration', 'rate' => 9.0, 'unit' => 'minutes', 'label' => 'Berenang (9.0 kcal/menit)'],
    'Lompat Tali'    => ['type' => 'duration', 'rate' => 11.0, 'unit' => 'minutes', 'label' => 'Lompat Tali / Skipping (11.0 kcal/menit)'],
    'HIIT Cardio'    => ['type' => 'duration', 'rate' => 12.0, 'unit' => 'minutes', 'label' => 'HIIT Workout (12.0 kcal/menit)'],
    'Jalan Kaki'     => ['type' => 'duration', 'rate' => 4.0, 'unit' => 'minutes', 'label' => 'Jalan Santai (4.0 kcal/menit)']
];

try {
    switch ($action) {
        case 'rates':
            echo json_encode([
                'success' => true,
                'rates'   => $exerciseRates
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

            if ($burned <= 0 && isset($exerciseRates[$name])) {
                $meta = $exerciseRates[$name];
                if ($meta['type'] === 'reps' && $reps > 0) {
                    $burned = $reps * $meta['rate'];
                } elseif ($meta['type'] === 'duration' && $duration > 0) {
                    $burned = $duration * $meta['rate'];
                }
            }

            $id = addExerciseLog($name, $duration, $reps, $burned, $date, $userId);
            $summary = getTodayExerciseSummary($date, $userId);

            echo json_encode([
                'success' => true,
                'message' => 'Olahraga berhasil dicatat.',
                'id'      => $id,
                'burned'  => $burned,
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

            $deleted = deleteExerciseLog($id);
            $summary = getTodayExerciseSummary(null, $userId);

            echo json_encode([
                'success' => $deleted,
                'message' => $deleted ? 'Catatan olahraga berhasil dihapus.' : 'Gagal menghapus.',
                'summary' => $summary
            ]);
            break;

        case 'today':
        default:
            $date = trim($_GET['date'] ?? date('Y-m-d'));
            $items = getTodayExerciseLogs($date, $userId);
            $summary = getTodayExerciseSummary($date, $userId);

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
