<?php
/**
 * Tailored Personalized Dashboard
 * Clean Architecture & Anti-Slop Interface (Linear / Vercel Aesthetic).
 */

require_once __DIR__ . '/includes/functions.php';

$user = requireAuth();

// If user hasn't completed onboarding/metrics yet, redirect
if (empty($user['berat']) || empty($user['tinggi'])) {
    header("Location: setup.php");
    exit();
}

$userId = (int)$user['id_user'];

// Handle quick program switcher if requested via query parameter
if (isset($_GET['switch'])) {
    $valid = ['diet', 'workout', 'bulking'];
    $newProgram = strtolower(trim($_GET['switch']));
    if (in_array($newProgram, $valid)) {
        $bmr = FitnessService::calculateBMR((float)$user['berat'], (float)$user['tinggi'], (int)$user['usia'], $user['gender']);
        $tdee = FitnessService::calculateTDEE($bmr, $user['aktivitas']);
        $newTarget = FitnessService::calculateCalorieTarget($tdee, $newProgram);

        $db = getDb();
        $db->prepare("UPDATE users SET program = :p, target_kalori = :t WHERE id_user = :uid")
           ->execute([':p' => $newProgram, ':t' => $newTarget, ':uid' => $userId]);

        header("Location: index.php");
        exit();
    }
}

$program = strtolower($user['program'] ?? 'bulking');
$targetKalori = (int)$user['target_kalori'];
$weight = (float)$user['berat'];
$height = (float)$user['tinggi'];
$bmi = (float)$user['bmi'];
$cat = FitnessService::getBMICategory($bmi);
$macros = FitnessService::calculateMacros($weight, $targetKalori, $program);

// Today's summary data
$todayFood = NutritionService::getTodayFoodSummary(null, $userId);
$todayExercise = WorkoutService::getTodayExerciseSummary(null, $userId);
$todayFoodItems = NutritionService::getTodayFoodLogs(null, $userId);
$todayExerciseItems = WorkoutService::getTodayExerciseLogs(null, $userId);

$kaloriMasuk = $todayFood['total_calories'];
$kaloriTerbakar = $todayExercise['total_burned'];
$kaloriBersih = max(0, $kaloriMasuk - $kaloriTerbakar);
$persenKalori = ($targetKalori > 0) ? min(100, round(($kaloriBersih / $targetKalori) * 100)) : 0;
$isOverflow = ($kaloriBersih > $targetKalori);

// Curated Recommendations for active program
$mealRecommendations = FitnessService::getMealRecommendations($program);
$workoutRecommendations = FitnessService::getWorkoutRecommendations($program);

// Program Metadata (Clean typography & colors)
$programMeta = [
    'diet' => [
        'title'        => 'Diet & Fat Loss',
        'badge'        => 'badge-blue',
        'color'        => '#06b6d4',
        'goal_desc'    => 'Defisit Terencana (-450 kcal)',
        'subtitle'     => 'Fokus defisit kalori moderat untuk mobilisasi lemak tubuh secara konsisten tanpa mengorbankan massa otot.',
        'target_badge' => 'Defisit Kalori'
    ],
    'workout' => [
        'title'        => 'Workout & Maintenance',
        'badge'        => 'badge-emerald',
        'color'        => '#10b981',
        'goal_desc'    => 'Keseimbangan Energi (TDEE)',
        'subtitle'     => 'Fokus stabilitas berat badan, peningkatan kapasitas aerobik, kelenturan, dan kebugaran fungsional.',
        'target_badge' => 'Pemeliharaan'
    ],
    'bulking' => [
        'title'        => 'Hypertrophy & Bulking',
        'badge'        => 'badge-purple',
        'color'        => '#8b5cf6',
        'goal_desc'    => 'Surplus Terkendali (+400 kcal)',
        'subtitle'     => 'Fokus sintesis protein maksimal dengan surplus energi bersih dan latihan beban terstruktur.',
        'target_badge' => 'Surplus Anabolik'
    ]
];

$activeMeta = $programMeta[$program] ?? $programMeta['bulking'];

// Weight History for Chart
$history = FitnessService::getWeightHistory(15, $userId);
$chartHistory = array_reverse($history);
$chartLabels = [];
$chartWeights = [];
$chartBmis = [];

foreach ($chartHistory as $row) {
    $chartLabels[] = date('d M', strtotime($row['tanggal']));
    $chartWeights[] = (float)$row['berat'];
    $chartBmis[] = (float)$row['bmi'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($activeMeta['title']) ?> — Let's Move</title>
    <link rel="stylesheet" href="css/modern.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>

    <!-- Minimalist Top Navigation -->
    <header class="navbar">
        <div class="app-container navbar-inner">
            <div style="display: flex; align-items: center; gap: 14px;">
                <a href="index.php" class="brand-logo">
                    <span class="brand-mark">LM</span>
                    <span>Let's Move</span>
                </a>
                <span class="badge" style="background: rgba(255, 255, 255, 0.05); border-color: <?= $activeMeta['color'] ?>; color: var(--text-main);">
                    <?= htmlspecialchars($activeMeta['title']) ?>
                </span>
            </div>

            <!-- Program Selector & Navigation -->
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <div style="display: flex; gap: 4px; background: var(--bg-surface-elevated); padding: 3px; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
                    <a href="index.php?switch=diet" style="padding: 4px 10px; border-radius: var(--radius-sm); font-size: 0.78rem; font-weight: 600; text-decoration: none; <?= ($program === 'diet') ? 'background: #06b6d4; color: #080c14;' : 'color: var(--text-muted);' ?>">
                        Diet
                    </a>
                    <a href="index.php?switch=workout" style="padding: 4px 10px; border-radius: var(--radius-sm); font-size: 0.78rem; font-weight: 600; text-decoration: none; <?= ($program === 'workout') ? 'background: #10b981; color: #080c14;' : 'color: var(--text-muted);' ?>">
                        Workout
                    </a>
                    <a href="index.php?switch=bulking" style="padding: 4px 10px; border-radius: var(--radius-sm); font-size: 0.78rem; font-weight: 600; text-decoration: none; <?= ($program === 'bulking') ? 'background: #8b5cf6; color: #ffffff;' : 'color: var(--text-muted);' ?>">
                        Bulking
                    </a>
                </div>

                <a href="setup.php" class="btn btn-secondary btn-sm" title="Ubah parameter fisik">
                    Kalkulator BMI
                </a>

                <a href="logout.php" class="btn btn-secondary btn-sm" style="color: #fda4af;" title="Keluar dari akun">
                    Keluar
                </a>
            </div>
        </div>
    </header>

    <!-- Global Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <main class="app-container" style="padding-top: 24px; padding-bottom: 50px;">
        
        <!-- Header Info Surface -->
        <div class="card" style="margin-bottom: 20px; border-left: 3px solid <?= $activeMeta['color'] ?>; padding: 20px 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
                <div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 3px;">
                        <span style="font-size: 0.74rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 0.06em; font-family: var(--font-mono);">DASHBOARD AKTIF</span>
                        <span class="badge" style="color: <?= $activeMeta['color'] ?>; border-color: rgba(255,255,255,0.08);">
                            <?= $activeMeta['target_badge'] ?>
                        </span>
                    </div>
                    <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--text-main); margin-bottom: 4px; letter-spacing: -0.02em;">
                        <?= htmlspecialchars($user['nama'] ?: explode('@', $user['email'])[0]) ?>
                    </h1>
                    <p style="color: var(--text-muted); font-size: 0.88rem; max-width: 680px; line-height: 1.5;">
                        <?= htmlspecialchars($activeMeta['subtitle']) ?>
                    </p>
                </div>

                <div style="text-align: right; background: var(--bg-surface-elevated); padding: 12px 18px; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
                    <span style="font-size: 0.72rem; color: var(--text-dim); text-transform: uppercase; font-family: var(--font-mono);">METRIK FISIK</span>
                    <div style="font-size: 1.05rem; font-weight: 700; color: var(--text-main); margin-top: 1px; font-family: var(--font-mono);">
                        <?= $weight ?> kg • <?= $height ?> cm
                    </div>
                    <div style="margin-top: 4px;">
                        <span class="badge <?= $cat['badge'] ?>"><?= htmlspecialchars($cat['title']) ?> (BMI <?= number_format($bmi, 1) ?>)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI Metrics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-label">Target Harian</span>
                <div class="stat-value">
                    <?= number_format($targetKalori) ?><span class="stat-unit">kcal</span>
                </div>
                <div class="stat-meta"><?= htmlspecialchars($activeMeta['goal_desc']) ?></div>
            </div>

            <div class="stat-card">
                <span class="stat-label">Kalori Masuk</span>
                <div class="stat-value" style="color: #fbbf24;">
                    <?= number_format($kaloriMasuk, 1) ?><span class="stat-unit">kcal</span>
                </div>
                <div class="stat-meta"><?= $todayFood['item_count'] ?> porsi tercatat hari ini</div>
            </div>

            <div class="stat-card">
                <span class="stat-label">Kalori Terbakar</span>
                <div class="stat-value" style="color: #fb7185;">
                    <?= number_format($kaloriTerbakar, 1) ?><span class="stat-unit">kcal</span>
                </div>
                <div class="stat-meta"><?= $todayExercise['workout_count'] ?> sesi latihan dicatat</div>
            </div>

            <div class="stat-card">
                <span class="stat-label">
                    <span>Kalori Bersih (Net)</span>
                    <?php if ($isOverflow): ?>
                        <span class="badge badge-rose">OVERFLOW</span>
                    <?php else: ?>
                        <span class="badge badge-emerald">ON TRACK</span>
                    <?php endif; ?>
                </span>
                <div class="stat-value" style="color: <?= $isOverflow ? '#fb7185' : 'var(--text-main)' ?>;">
                    <?= number_format($kaloriBersih, 1) ?><span class="stat-unit">kcal</span>
                </div>
                <div class="stat-meta">
                    <?= ($targetKalori - $kaloriBersih >= 0) ? (number_format($targetKalori - $kaloriBersih, 1) . ' kcal tersisa') : (number_format($kaloriBersih - $targetKalori, 1) . ' kcal melebihi target') ?>
                </div>
            </div>

            <div class="stat-card">
                <span class="stat-label">Protein Hari Ini</span>
                <div class="stat-value" style="color: #60a5fa;">
                    <?= number_format($todayFood['total_protein'], 1) ?><span class="stat-unit">/ <?= $macros['protein_g'] ?>g</span>
                </div>
                <div class="stat-meta">Target minimum <?= $macros['protein_g'] ?>g/hari</div>
            </div>
        </div>

        <!-- Budget Progress Bar -->
        <div class="card" style="margin-bottom: 24px; padding: 18px 22px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <span style="font-size: 0.88rem; font-weight: 600; color: var(--text-main);">Anggaran Kalori Bersih</span>
                    <span style="color: var(--text-muted); font-size: 0.82rem; margin-left: 8px;">
                        <?= number_format($kaloriBersih, 1) ?> dari <?= number_format($targetKalori) ?> kcal (<?= $persenKalori ?>%)
                    </span>
                </div>
                <span class="badge <?= $isOverflow ? 'badge-rose' : 'badge-emerald' ?>">
                    <?= $isOverflow ? 'Melebihi Target' : 'Dalam Batas Aman' ?>
                </span>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar-fill <?= $isOverflow ? 'overflow' : '' ?>" style="width: <?= min(100, $persenKalori) ?>%;"></div>
            </div>
        </div>

        <!-- SECTION 1: SARAN MAKANAN & LATIHAN SPESIFIK -->
        <div style="margin-bottom: 28px;">
            <div style="margin-bottom: 14px;">
                <h2 style="font-size: 1.3rem; font-weight: 700; letter-spacing: -0.02em;">Rekomendasi Menu & Latihan</h2>
                <p style="color: var(--text-muted); font-size: 0.86rem;">Pilih rekomendasi yang disesuaikan dengan sasaran Anda untuk dicatat secara langsung.</p>
            </div>

            <div class="layout-2col">
                <!-- Saran Makanan -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Menu Nutrisi Pilihan</h3>
                        <span class="badge badge-neutral"><?= strtoupper($program) ?></span>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <?php foreach ($mealRecommendations as $meal): ?>
                            <div style="background: var(--bg-card-inner); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm); padding: 12px 14px; display: flex; justify-content: space-between; align-items: center; gap: 10px;">
                                <div>
                                    <div style="font-weight: 600; font-size: 0.9rem; color: var(--text-main);">
                                        <?= htmlspecialchars($meal['name']) ?>
                                    </div>
                                    <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 3px;">
                                        <span class="pill-metric"><?= htmlspecialchars($meal['portion']) ?></span>
                                        <span class="pill-metric" style="color: var(--color-primary); font-weight: 600;"><?= $meal['calories'] ?> kcal</span>
                                        <span class="pill-metric" style="color: #60a5fa;">Prot: <?= $meal['protein'] ?>g</span>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm" onclick="handleQuickAddMeal('<?= addslashes($meal['name']) ?>', 100, <?= $meal['calories'] ?>, <?= $meal['protein'] ?>, <?= $meal['carbs'] ?>, <?= $meal['fat'] ?>)">
                                    + Catat
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Saran Latihan Olahraga -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Sesi Latihan Terarah</h3>
                        <span class="badge badge-neutral"><?= strtoupper($program) ?></span>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <?php foreach ($workoutRecommendations as $work): ?>
                            <div style="background: var(--bg-card-inner); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm); padding: 12px 14px; display: flex; justify-content: space-between; align-items: center; gap: 10px;">
                                <div>
                                    <div style="font-weight: 600; font-size: 0.9rem; color: var(--text-main);">
                                        <?= htmlspecialchars($work['name']) ?>
                                    </div>
                                    <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 3px;">
                                        <span class="pill-metric"><?= htmlspecialchars($work['target']) ?></span>
                                        <span class="pill-metric" style="color: #fb7185; font-weight: 600;">-<?= $work['burn'] ?> kcal</span>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="handleQuickAddWorkout('<?= addslashes($work['name']) ?>', 30, 0, <?= $work['burn'] ?>)">
                                    + Catat
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 2: PENCATAT MAKANAN & OLAHRAGA MANDIRI -->
        <div style="margin-bottom: 28px;">
            <div style="margin-bottom: 14px;">
                <h2 style="font-size: 1.3rem; font-weight: 700; letter-spacing: -0.02em;">Pencatatan Mandiri</h2>
                <p style="color: var(--text-muted); font-size: 0.86rem;">Pencarian nutrisi langsung melalui CalorieNinjas API atau input resep custom.</p>
            </div>

            <div class="layout-2col">
                <!-- Food Search -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Cari Database Makanan</h3>
                        <span class="badge badge-blue">API Nutrisi</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="food-search">Nama Bahan / Makanan</label>
                        <div style="display: flex; gap: 8px;">
                            <input type="text" id="food-search" class="form-control" placeholder="Ketik misal: telur rebus, banana, dada ayam..." autocomplete="off">
                            <button type="button" class="btn btn-primary" onclick="searchFood()">Cari</button>
                        </div>
                    </div>

                    <div id="search-results" style="margin-top: 10px;"></div>

                    <details style="margin-top: 14px; border-top: 1px solid var(--border-subtle); padding-top: 12px;">
                        <summary style="font-size: 0.84rem; font-weight: 600; color: var(--color-primary); cursor: pointer;">
                            + Tambah Makanan Manual (Porsi Custom)
                        </summary>
                        <form id="manualFoodForm" onsubmit="handleManualFoodForm(event)" style="margin-top: 12px;">
                            <div class="form-group">
                                <label class="form-label">Nama Makanan</label>
                                <input type="text" id="manualName" class="form-control" placeholder="Contoh: Nasi Liwet Gurih" required>
                            </div>
                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label class="form-label">Porsi (gram)</label>
                                    <input type="number" id="manualServing" class="form-control" value="100" min="1" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Kalori (kcal)</label>
                                    <input type="number" step="0.1" id="manualCalories" class="form-control" placeholder="180" required>
                                </div>
                            </div>
                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label class="form-label">Protein (g)</label>
                                    <input type="number" step="0.1" id="manualProtein" class="form-control" value="0">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Karbohidrat (g)</label>
                                    <input type="number" step="0.1" id="manualCarbs" class="form-control" value="0">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-secondary btn-block">Simpan Makanan</button>
                        </form>
                    </details>
                </div>

                <!-- Custom Workout Logger -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Catat Aktivitas Latihan</h3>
                    </div>

                    <form id="workoutLogForm" onsubmit="handleWorkoutForm(event)">
                        <div class="form-group">
                            <label class="form-label" for="exName">Jenis Olahraga</label>
                            <select id="exName" class="form-control">
                                <optgroup label="Kardio & Durasi">
                                    <option value="Lari (Jogging)">Lari / Jogging (10.0 kcal/menit)</option>
                                    <option value="Bersepeda">Bersepeda (8.5 kcal/menit)</option>
                                    <option value="Renang">Renang (9.0 kcal/menit)</option>
                                    <option value="Lompat Tali">Lompat Tali / Skipping (11.0 kcal/menit)</option>
                                    <option value="HIIT Cardio">HIIT Cardio (12.0 kcal/menit)</option>
                                    <option value="Jalan Kaki">Jalan Kaki (4.0 kcal/menit)</option>
                                </optgroup>
                                <optgroup label="Beban & Repetisi">
                                    <option value="Angkat Beban">Angkat Beban / Gym (6.5 kcal/menit)</option>
                                    <option value="Push Up">Push Up (0.45 kcal/rep)</option>
                                    <option value="Sit Up">Sit Up (0.40 kcal/rep)</option>
                                    <option value="Pull Up">Pull Up (1.00 kcal/rep)</option>
                                    <option value="Squat">Squat Bodyweight (0.50 kcal/rep)</option>
                                </optgroup>
                            </select>
                        </div>

                        <div class="form-grid-2">
                            <div class="form-group">
                                <label class="form-label" for="exDuration">Durasi (Menit)</label>
                                <input type="number" id="exDuration" class="form-control" placeholder="Contoh: 30" min="0">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="exReps">Repetisi (Hitungan)</label>
                                <input type="number" id="exReps" class="form-control" placeholder="Contoh: 50" min="0">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block" style="margin-top: 8px;">
                            + Simpan Catatan Latihan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- SECTION 3: TABEL DATA MAKANAN & OLAHRAGA HARI INI -->
        <div class="layout-2col" style="margin-bottom: 28px;">
            <!-- Tabel Makanan Hari Ini -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Jurnal Makanan Hari Ini</h3>
                    <?php if (!empty($todayFoodItems)): ?>
                        <form action="tracker/hapussemuamakanan.php" method="POST" onsubmit="return confirm('Reset semua catatan makanan hari ini?');">
                            <button type="submit" class="btn btn-secondary btn-sm" style="font-size: 0.74rem;">
                                Reset Hari Ini
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <?php if (!empty($todayFoodItems)): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Makanan</th>
                                    <th>Porsi</th>
                                    <th>Kalori</th>
                                    <th>Protein</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($todayFoodItems as $f): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($f['makanan']) ?></strong></td>
                                        <td><?= number_format((float)$f['berat'], 0) ?> g</td>
                                        <td><span style="color: var(--color-primary); font-weight: 600; font-family: var(--font-mono);"><?= number_format((float)$f['kalori'], 1) ?></span> kcal</td>
                                        <td><span style="font-family: var(--font-mono);"><?= number_format((float)$f['protein'], 1) ?></span> g</td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm" onclick="handleDeleteFood(<?= $f['id_hitung'] ?>)">
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 24px 16px; color: var(--text-dim); font-size: 0.88rem;">
                        Belum ada makanan yang dicatat hari ini. Gunakan kolom pencarian atau klik salah satu rekomendasi di atas.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tabel Olahraga Hari Ini -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Jurnal Latihan Hari Ini</h3>
                    <span class="badge badge-rose"><?= number_format($kaloriTerbakar, 1) ?> kcal</span>
                </div>

                <?php if (!empty($todayExerciseItems)): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Aktivitas</th>
                                    <th>Durasi / Reps</th>
                                    <th>Terbakar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($todayExerciseItems as $e): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($e['nama_olahraga']) ?></strong></td>
                                        <td>
                                            <?php if ((float)$e['durasi_menit'] > 0): ?>
                                                <?= number_format((float)$e['durasi_menit'], 0) ?> menit
                                            <?php elseif ((int)$e['repetisi'] > 0): ?>
                                                <?= (int)$e['repetisi'] ?>x reps
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td><span style="color: #fb7185; font-weight: 600; font-family: var(--font-mono);">-<?= number_format((float)$e['kalori_terbakar'], 1) ?></span> kcal</td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm" onclick="handleDeleteWorkout(<?= $e['id_olahraga'] ?>)">
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 24px 16px; color: var(--text-dim); font-size: 0.88rem;">
                        Belum ada aktivitas fisik yang dicatat hari ini.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- SECTION 4: GRAFIK ANALITIK RIWAYAT BERAT BADAN -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Tren Berat Badan & BMI</h3>
                <span class="badge badge-neutral">Riwayat Pengukuran</span>
            </div>
            <div style="position: relative; height: 240px; width: 100%;">
                <canvas id="weightChart"></canvas>
            </div>
        </div>

    </main>

    <footer class="footer">
        <div class="app-container">
            <p>Let's Move — Clean Architecture Fitness & Calorie Management System</p>
        </div>
    </footer>

    <!-- Client Script -->
    <script src="js/scriptt.js"></script>
    <script>
    // Quick Add Meal from Curated Recommendations
    async function handleQuickAddMeal(name, serving, cal, prot, carbs, fat) {
        try {
            const res = await CalorieTrackerApp.addFood({
                name: name,
                serving_size: serving,
                calories: cal,
                protein: prot,
                carbohydrates: carbs,
                fat: fat
            });
            if (res.success) {
                showToast(`"${name}" berhasil ditambahkan.`, 'success');
                setTimeout(() => window.location.reload(), 450);
            }
        } catch (e) {
            showToast(e.message || 'Gagal menambahkan makanan.', 'error');
        }
    }

    // Quick Add Workout from Curated Recommendations
    async function handleQuickAddWorkout(name, duration, reps, burned) {
        try {
            const res = await CalorieTrackerApp.addWorkout({
                name: name,
                duration: duration,
                repetitions: reps,
                calories_burned: burned
            });
            if (res.success) {
                showToast(`Latihan "${name}" berhasil dicatat.`, 'success');
                setTimeout(() => window.location.reload(), 450);
            }
        } catch (e) {
            showToast(e.message || 'Gagal mencatat latihan.', 'error');
        }
    }

    // Manual Food Form Handler
    async function handleManualFoodForm(e) {
        e.preventDefault();
        const name = document.getElementById('manualName').value.trim();
        const serving = parseFloat(document.getElementById('manualServing').value) || 100;
        const cal = parseFloat(document.getElementById('manualCalories').value) || 0;
        const prot = parseFloat(document.getElementById('manualProtein').value) || 0;
        const carbs = parseFloat(document.getElementById('manualCarbs').value) || 0;

        await handleQuickAddMeal(name, serving, cal, prot, carbs, 0);
    }

    // Workout Form Handler
    async function handleWorkoutForm(e) {
        e.preventDefault();
        const name = document.getElementById('exName').value;
        const duration = parseFloat(document.getElementById('exDuration').value) || 0;
        const reps = parseInt(document.getElementById('exReps').value) || 0;

        if (duration <= 0 && reps <= 0) {
            showToast('Masukkan durasi (menit) atau jumlah repetisi latihan.', 'error');
            return;
        }

        await handleQuickAddWorkout(name, duration, reps, 0);
    }

    // Delete Handlers
    async function handleDeleteFood(id) {
        if (!confirm('Hapus item makanan ini?')) return;
        try {
            const res = await CalorieTrackerApp.deleteFood(id);
            if (res.success) {
                showToast('Item berhasil dihapus.', 'success');
                setTimeout(() => window.location.reload(), 400);
            }
        } catch (e) {
            showToast('Gagal menghapus item.', 'error');
        }
    }

    async function handleDeleteWorkout(id) {
        if (!confirm('Hapus catatan latihan ini?')) return;
        try {
            const res = await CalorieTrackerApp.deleteWorkout(id);
            if (res.success) {
                showToast('Catatan berhasil dihapus.', 'success');
                setTimeout(() => window.location.reload(), 400);
            }
        } catch (e) {
            showToast('Gagal menghapus catatan.', 'error');
        }
    }

    // Render Progress Analytics Chart
    document.addEventListener('DOMContentLoaded', () => {
        const ctx = document.getElementById('weightChart');
        if (!ctx) return;

        const labels = <?= json_encode($chartLabels) ?>;
        const weights = <?= json_encode($chartWeights) ?>;
        const bmis = <?= json_encode($chartBmis) ?>;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels.length ? labels : ['Hari Ini'],
                datasets: [
                    {
                        label: 'Berat Badan (kg)',
                        data: weights.length ? weights : [<?= $weight ?>],
                        borderColor: '<?= $activeMeta['color'] ?>',
                        backgroundColor: 'rgba(16, 185, 129, 0.08)',
                        fill: true,
                        tension: 0.3,
                        borderWidth: 2,
                        pointBackgroundColor: '<?= $activeMeta['color'] ?>',
                        pointRadius: 3,
                        yAxisID: 'y'
                    },
                    {
                        label: 'BMI',
                        data: bmis.length ? bmis : [<?= $bmi ?>],
                        borderColor: '#64748b',
                        borderDash: [3, 3],
                        borderWidth: 1.5,
                        tension: 0.3,
                        pointRadius: 2,
                        pointBackgroundColor: '#64748b',
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: '#94a3b8', font: { family: 'Plus Jakarta Sans', size: 11 } } }
                },
                scales: {
                    x: { grid: { color: 'rgba(255, 255, 255, 0.04)' }, ticks: { color: '#64748b', font: { size: 11 } } },
                    y: { type: 'linear', display: true, position: 'left', grid: { color: 'rgba(255, 255, 255, 0.04)' }, ticks: { color: '#94a3b8', font: { family: 'JetBrains Mono', size: 11 } } },
                    y1: { type: 'linear', display: true, position: 'right', grid: { drawOnChartArea: false }, ticks: { color: '#64748b', font: { family: 'JetBrains Mono', size: 11 } } }
                }
            }
        });
    });
    </script>

</body>
</html>
