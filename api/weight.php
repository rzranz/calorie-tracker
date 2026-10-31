<?php
/**
 * Weight & Progress History API Endpoint
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/functions.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$input = json_decode(file_get_contents('php://input'), true) ?? [];
if (!empty($input['action'])) {
    $action = $input['action'];
}

try {
    switch ($action) {
        case 'add':
            $weight = (float)($_POST['berat'] ?? ($input['berat'] ?? 0));
            $height = (float)($_POST['tinggi'] ?? ($input['tinggi'] ?? 0));
            $age = (int)($_POST['usia'] ?? ($input['usia'] ?? 25));
            $gender = trim($_POST['gender'] ?? ($input['gender'] ?? 'Laki-laki'));
            $activity = trim($_POST['aktivitas'] ?? ($input['aktivitas'] ?? 'moderate'));
            $goal = trim($_POST['goal'] ?? ($input['goal'] ?? 'bulking'));
            $date = trim($_POST['tanggal'] ?? ($input['tanggal'] ?? date('Y-m-d')));

            if ($weight <= 0 || $height <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Berat dan tinggi harus bernilai lebih dari 0.']);
                exit;
            }

            $id = saveWeightLog($weight, $height, $age, $gender, $activity, $goal, $date);
            $bmi = calculateBMI($weight, $height);
            $cat = getBMICategory($bmi);
            $bmr = calculateBMR($weight, $height, $age, $gender);
            $tdee = calculateTDEE($bmr, $activity);
            $target = calculateCalorieTarget($tdee, $goal);

            echo json_encode([
                'success'        => true,
                'message'        => 'Data berat badan dan kalori berhasil disimpan.',
                'id'             => $id,
                'bmi'            => $bmi,
                'kategori'       => $cat['title'],
                'kalori_harian'  => $target,
                'tdee'           => $tdee,
                'bmr'            => $bmr,
                'recommendation' => $cat['recommendation']
            ]);
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? ($_GET['id'] ?? ($input['id'] ?? 0)));
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID data tidak valid.']);
                exit;
            }

            $deleted = deleteWeightLog($id);
            echo json_encode([
                'success' => $deleted,
                'message' => $deleted ? 'Data berhasil dihapus.' : 'Gagal menghapus data.'
            ]);
            break;

        case 'history':
        default:
            $history = getWeightHistory(30);
            $latest = getLatestWeightLog();

            // Prepare timeline for Chart.js (chronological order)
            $chartData = array_reverse($history);
            $labels = [];
            $weights = [];
            $bmis = [];

            foreach ($chartData as $entry) {
                $labels[] = date('d M', strtotime($entry['tanggal']));
                $weights[] = (float)$entry['berat'];
                $bmis[] = (float)$entry['bmi'];
            }

            echo json_encode([
                'success'   => true,
                'latest'    => $latest,
                'history'   => $history,
                'chart'     => [
                    'labels'  => $labels,
                    'weights' => $weights,
                    'bmis'    => $bmis
                ]
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
