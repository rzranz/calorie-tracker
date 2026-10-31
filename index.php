<?php
/**
 * Tailored Personalized Dashboard
 * Dynamically customizes layout, recommendations, and goals based on user program (Diet, Workout, or Bulking).
 */

require_once __DIR__ . '/includes/functions.php';

$user = requireAuth();

// If user hasn't set up BMI/metrics yet, redirect to onboarding setup
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
        $bmr = calculateBMR((float)$user['berat'], (float)$user['tinggi'], (int)$user['usia'], $user['gender']);
        $tdee = calculateTDEE($bmr, $user['aktivitas']);
        $newTarget = calculateCalorieTarget($tdee, $newProgram);

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
$cat = getBMICategory($bmi);
$macros = calculateMacros($weight, $targetKalori, $program);

// Today's summary data
$todayFood = getTodayFoodSummary(null, $userId);
$todayExercise = getTodayExerciseSummary(null, $userId);
$todayFoodItems = getTodayFoodLogs(null, $userId);
$todayExerciseItems = getTodayExerciseLogs(null, $userId);

$kaloriMasuk = $todayFood['total_calories'];
$kaloriTerbakar = $todayExercise['total_burned'];
$kaloriBersih = max(0, $kaloriMasuk - $kaloriTerbakar);
$persenKalori = ($targetKalori > 0) ? min(100, round(($kaloriBersih / $targetKalori) * 100)) : 0;
$isOverflow = ($kaloriBersih > $targetKalori);

// Curated Recommendations for active program
$mealRecommendations = getMealRecommendations($program);
$workoutRecommendations = getWorkoutRecommendations($program);

// Program Theme Customization
$programMeta = [
    'diet' => [
        'title'        => 'Program Diet & Fat Loss',
        'icon'         => '🥑',
        'badge'        => 'badge-blue',
        'color'        => '#06b6d4',
        'goal_desc'    => 'Defisit Kalori Terencana (-450 kcal)',
        'subtitle'     => 'Fokus defisit kalori moderat untuk membakar simpanan lemak tubuh tanpa mengorbankan metabolisme & massa otot.',
        'target_badge' => 'DEFISIT KALORI'
    ],
    'workout' => [
        'title'        => 'Program Workout & Kebugaran',
        'icon'         => '🏃',
        'badge'        => 'badge-emerald',
        'color'        => '#10b981',
        'goal_desc'    => 'Keseimbangan Energi Harian (Maintenance)',
        'subtitle'     => 'Fokus menjaga berat badan stabil, memperkuat stamina kardiovaskular, dan mengencangkan otot fungsional.',
        'target_badge' => 'MAINTENANCE'
    ],
    'bulking' => [
        'title'        => 'Program Bulking & Hipertrofi',
        'icon'         => '💪',
        'badge'        => 'badge-purple',
        'color'        => '#8b5cf6',
        'goal_desc'    => 'Surplus Kalori Terkontrol (+400 kcal)',
        'subtitle'     => 'Fokus lingkungan anabolik dengan surplus kalori bersih dan asupan protein tinggi untuk menambah massa otot baru.',
        'target_badge' => 'SURPLUS ANABOLIK'
    ]
];

$activeMeta = $programMeta[$program] ?? $programMeta['bulking'];

// Weight History for Chart
$history = getWeightHistory(15, $userId);
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
    <title><?= htmlspecialchars($activeMeta['title']) ?> • Let's Move</title>
    <link rel="stylesheet" href="css/modern.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>

    <!-- Clean Topbar Navigation -->
    <header class="navbar">
        <div class="app-container navbar-inner">
            <div style="display: flex; align-items: center; gap: 14px;">
                <a href="index.php" class="brand-logo">
                    <div class="brand-icon">⚡</div>
                    <span>Let's Move</span>
                </a>
                <!-- Active Program Badge -->
                <span class="badge" style="background: rgba(255, 255, 255, 0.08); border-color: <?= $activeMeta['color'] ?>; color: var(--text-main); font-size: 0.8rem; padding: 5px 12px;">
                    <?= $activeMeta['icon'] ?> <?= htmlspecialchars($activeMeta['title']) ?>
                </span>
            </div>

            <!-- Program Selector & Profile Actions -->
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <!-- Quick Program Dropdown / Switcher -->
                <div style="display: flex; gap: 6px; background: var(--bg-surface-elevated); padding: 3px; border-radius: var(--radius-md);">
                    <a href="index.php?switch=diet" style="padding: 5px 10px; border-radius: var(--radius-sm); font-size: 0.8rem; text-decoration: none; font-weight: 600; <?= ($program === 'diet') ? 'background: #06b6d4; color: #0b0f19;' : 'color: var(--text-muted);' ?>">
                        🥑 Diet
                    </a>
                    <a href="index.php?switch=workout" style="padding: 5px 10px; border-radius: var(--radius-sm); font-size: 0.8rem; text-decoration: none; font-weight: 600; <?= ($program === 'workout') ? 'background: #10b981; color: #0b0f19;' : 'color: var(--text-muted);' ?>">
                        🏃 Workout
                    </a>
                    <a href="index.php?switch=bulking" style="padding: 5px 10px; border-radius: var(--radius-sm); font-size: 0.8rem; text-decoration: none; font-weight: 600; <?= ($program === 'bulking') ? 'background: #8b5cf6; color: #ffffff;' : 'color: var(--text-muted);' ?>">
                        💪 Bulking
                    </a>
                </div>

                <a href="setup.php" class="btn btn-secondary btn-sm" title="Ubah Berat/Tinggi Badan">
                    ⚖️ Hitung Ulang BMI
                </a>

                <a href="logout.php" class="btn btn-secondary btn-sm" style="color: #fda4af;" title="Keluar dari akun">
                    🚪 Keluar
                </a>
            </div>
        </div>
    </header>

    <!-- Toast Notification Container -->
    <div class="toast-container" id="toastContainer"></div>

    <main class="app-container" style="padding-top: 28px; padding-bottom: 60px;">
        
        <!-- Welcome & Program Hero Banner -->
        <div class="card" style="margin-bottom: 24px; border-left: 5px solid <?= $activeMeta['color'] ?>; padding: 24px 28px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                <div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                        <span style="font-size: 0.82rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 0.05em;">DASHBOARD PRIBADI</span>
                        <span class="badge" style="background: rgba(255,255,255,0.06); color: <?= $activeMeta['color'] ?>; font-weight: 700;">
                            <?= $activeMeta['target_badge'] ?>
                        </span>
                    </div>
                    <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--text-main); margin-bottom: 6px;">
                        Halo, <?= htmlspecialchars($user['nama'] ?: explode('@', $user['email'])[0]) ?>! <?= $activeMeta['icon'] ?>
                    </h1>
                    <p style="color: var(--text-muted); font-size: 0.92rem; max-width: 720px; line-height: 1.5;">
                        <?= htmlspecialchars($activeMeta['subtitle']) ?>
                    </p>
                </div>

                <div style="text-align: right; background: var(--bg-surface-elevated); padding: 14px 20px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
                    <span style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase;">Kondisi Fisik Saat Ini</span>
                    <div style="font-size: 1.15rem; font-weight: 700; color: var(--text-main); margin-top: 2px;">
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
            <!-- Target Kalori -->
            <div class="stat-card" style="--accent-gradient: linear-gradient(90deg, <?= $activeMeta['color'] ?>, #06b6d4);">
                <span class="stat-label">
                    <span>Target Harian</span>
                    <span class="badge" style="color: <?= $activeMeta['color'] ?>;"><?= strtoupper($program) ?></span>
                </span>
                <div class="stat-value">
                    <?= number_format($targetKalori) ?><span class="stat-unit">kcal</span>
                </div>
                <div class="stat-meta"><?= htmlspecialchars($activeMeta['goal_desc']) ?></div>
            </div>

            <!-- Kalori Masuk (Makanan) -->
            <div class="stat-card card-amber">
                <span class="stat-label">Kalori Masuk (Makanan)</span>
                <div class="stat-value">
                    <?= number_format($kaloriMasuk, 1) ?><span class="stat-unit">kcal</span>
                </div>
                <div class="stat-meta"><?= $todayFood['item_count'] ?> porsi tercatat hari ini</div>
            </div>

            <!-- Kalori Terbakar (Olahraga) -->
            <div class="stat-card card-rose">
                <span class="stat-label">Kalori Terbakar (Olahraga)</span>
                <div class="stat-value">
                    <?= number_format($kaloriTerbakar, 1) ?><span class="stat-unit">kcal</span>
                </div>
                <div class="stat-meta"><?= $todayExercise['workout_count'] ?> sesi latihan dicatat</div>
            </div>

            <!-- Kalori Bersih (Net) -->
            <div class="stat-card <?= $isOverflow ? 'card-rose' : 'card-blue' ?>">
                <span class="stat-label">
                    <span>Kalori Bersih (Net)</span>
                    <?php if ($isOverflow): ?>
                        <span class="badge badge-rose">OVERFLOW</span>
                    <?php else: ?>
                        <span class="badge badge-emerald">ON TRACK</span>
                    <?php endif; ?>
                </span>
                <div class="stat-value">
                    <?= number_format($kaloriBersih, 1) ?><span class="stat-unit">kcal</span>
                </div>
                <div class="stat-meta">
                    <?= ($targetKalori - $kaloriBersih >= 0) ? (number_format($targetKalori - $kaloriBersih, 1) . ' kcal tersisa') : (number_format($kaloriBersih - $targetKalori, 1) . ' kcal melebihi batas') ?>
                </div>
            </div>

            <!-- Protein Hari Ini -->
            <div class="stat-card">
                <span class="stat-label">Protein Hari Ini</span>
                <div class="stat-value">
                    <?= number_format($todayFood['total_protein'], 1) ?><span class="stat-unit">/ <?= $macros['protein_g'] ?>g</span>
                </div>
                <div class="stat-meta">Target: <?= $macros['protein_g'] ?>g untuk berat <?= $weight ?>kg</div>
            </div>
        </div>

        <!-- Visual Progress Bar -->
        <div class="card" style="margin-bottom: 28px; padding: 20px 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong style="font-size: 0.95rem;">Budget Kalori Harian</strong>
                    <span style="color: var(--text-muted); font-size: 0.85rem; margin-left: 8px;">
                        <?= number_format($kaloriBersih, 1) ?> dari <?= number_format($targetKalori) ?> kcal (<?= $persenKalori ?>%)
                    </span>
                </div>
                <span class="badge <?= $isOverflow ? 'badge-rose' : 'badge-emerald' ?>">
                    <?= $isOverflow ? '⚠️ Melebihi Target' : '✓ Aman Terkendali' ?>
                </span>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar-fill <?= $isOverflow ? 'overflow' : '' ?>" style="width: <?= min(100, $persenKalori) ?>%;"></div>
            </div>
        </div>

        <!-- SECTION 1: SARAN MAKANAN & SARAN LATIHAN SPESIFIK -->
        <div style="margin-bottom: 32px;">
            <div style="margin-bottom: 16px;">
                <span class="badge" style="background: rgba(255,255,255,0.06); color: <?= $activeMeta['color'] ?>;">REKOMENDASI AKURAT KHUSUS <?= strtoupper($program) ?></span>
                <h2 style="font-size: 1.45rem; font-weight: 800; margin-top: 4px;">Saran Menu Makanan & Latihan untuk Anda</h2>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Klik tombol <strong>+ Catat Langsung</strong> untuk memasukkan rekomendasi ke catatan harian Anda tanpa perlu mengetik ulang.</p>
            </div>

            <div class="layout-2col">
                <!-- Saran Makanan -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title" style="font-size: 1.12rem;">
                            🥗 Rekomendasi Menu <?= htmlspecialchars($activeMeta['title']) ?>
                        </h3>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php foreach ($mealRecommendations as $meal): ?>
                            <div style="background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 14px 16px; display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                                <div>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <strong style="font-size: 0.95rem; color: var(--text-main);"><?= htmlspecialchars($meal['name']) ?></strong>
                                        <span class="badge badge-secondary" style="font-size: 0.7rem;"><?= htmlspecialchars($meal['tag']) ?></span>
                                    </div>
                                    <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 3px;">
                                        Porsi: <?= htmlspecialchars($meal['portion']) ?> • <span style="color: var(--color-primary); font-weight: 700;"><?= $meal['calories'] ?> kcal</span> • <span style="color: var(--color-cyan); font-weight: 600;">Prot: <?= $meal['protein'] ?>g</span>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm" onclick="quickAddMeal('<?= addslashes($meal['name']) ?>', 100, <?= $meal['calories'] ?>, <?= $meal['protein'] ?>, <?= $meal['carbs'] ?>, <?= $meal['fat'] ?>)">
                                    + Catat
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Saran Latihan Olahraga -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title" style="font-size: 1.12rem;">
                            🏋️ Rekomendasi Sesi Olahraga
                        </h3>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php foreach ($workoutRecommendations as $work): ?>
                            <div style="background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 14px 16px; display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                                <div>
                                    <strong style="font-size: 0.95rem; color: var(--text-main);"><?= htmlspecialchars($work['name']) ?></strong>
                                    <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 3px;">
                                        Target: <strong><?= htmlspecialchars($work['target']) ?></strong> • Intensitas: <?= htmlspecialchars($work['intensity']) ?>
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--color-rose); font-weight: 600; margin-top: 2px;">
                                        🔥 Estimasi Terbakar: ~<?= $work['burn'] ?> kcal
                                    </div>
                                </div>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="quickAddWorkout('<?= addslashes($work['name']) ?>', 30, 0, <?= $work['burn'] ?>)">
                                    + Catat Latihan
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 2: PENCATAT MAKANAN & OLAHRAGA MANDIRI -->
        <div style="margin-bottom: 32px;">
            <div style="margin-bottom: 16px;">
                <span class="badge badge-emerald">INPUT FLEKSIBEL</span>
                <h2 style="font-size: 1.45rem; font-weight: 800; margin-top: 4px;">Pencatat Mandiri (Makanan & Olahraga)</h2>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Cari makanan bebas atau catat sesi olahraga yang Anda lakukan secara mandiri.</p>
            </div>

            <div class="layout-2col">
                <!-- Food Search & Custom Food -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title" style="font-size: 1.1rem;">🔍 Cari Nutrisi Makanan Bebas</h3>
                        <span class="badge badge-blue">CalorieNinjas API</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="food-search">Ketik Nama Makanan / Minuman</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="text" id="food-search" class="form-control" placeholder="Contoh: telur rebus, banana, ayam panggang, nasi..." autocomplete="off">
                            <button type="button" class="btn btn-primary" onclick="searchFood()">Cari</button>
                        </div>
                    </div>

                    <!-- Search Results -->
                    <div id="search-results" style="margin-top: 14px;"></div>

                    <!-- Custom Food Collapse / Accordion -->
                    <details style="margin-top: 18px; border-top: 1px solid var(--border-subtle); padding-top: 14px;">
                        <summary style="font-size: 0.88rem; font-weight: 600; color: var(--color-primary); cursor: pointer;">
                            + Atau Tambah Makanan Manual (Porsi Custom)
                        </summary>
                        <form id="manualFoodForm" onsubmit="handleManualFoodSubmit(event)" style="margin-top: 14px;">
                            <div class="form-group">
                                <label class="form-label">Nama Makanan</label>
                                <input type="text" id="manualName" class="form-control" placeholder="Contoh: Soto Ayam Tanpa Santan" required>
                            </div>
                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label class="form-label">Porsi (gram)</label>
                                    <input type="number" id="manualServing" class="form-control" value="100" min="1" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Kalori (kcal)</label>
                                    <input type="number" step="0.1" id="manualCalories" class="form-control" placeholder="Contoh: 180" required>
                                </div>
                            </div>
                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label class="form-label">Protein (g)</label>
                                    <input type="number" step="0.1" id="manualProtein" class="form-control" placeholder="Contoh: 15" value="0">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Karbohidrat (g)</label>
                                    <input type="number" step="0.1" id="manualCarbs" class="form-control" placeholder="Contoh: 20" value="0">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-secondary btn-block">Simpan Makanan Manual</button>
                        </form>
                    </details>
                </div>

                <!-- Custom Workout Logger -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title" style="font-size: 1.1rem;">🏋️ Catat Olahraga yang Baru Selesai</h3>
                    </div>

                    <form id="workoutLogForm" onsubmit="handleWorkoutSubmit(event)">
                        <div class="form-group">
                            <label class="form-label" for="exName">Jenis Olahraga</label>
                            <select id="exName" class="form-control">
                                <optgroup label="Latihan Kardio (Berdasarkan Menit)">
                                    <option value="Lari (Jogging)">Lari / Jogging (10.0 kcal/menit)</option>
                                    <option value="Bersepeda">Bersepeda (8.5 kcal/menit)</option>
                                    <option value="Renang">Renang (9.0 kcal/menit)</option>
                                    <option value="Lompat Tali">Lompat Tali / Skipping (11.0 kcal/menit)</option>
                                    <option value="HIIT Cardio">HIIT Workout (12.0 kcal/menit)</option>
                                    <option value="Jalan Kaki">Jalan Santai (4.0 kcal/menit)</option>
                                </optgroup>
                                <optgroup label="Latihan Repetisi & Beban">
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

                        <button type="submit" class="btn btn-primary btn-block" style="margin-top: 10px;">
                            + Simpan Catatan Olahraga
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- SECTION 3: TABEL DATA MAKANAN & OLAHRAGA HARI INI -->
        <div class="layout-2col" style="margin-bottom: 32px;">
            <!-- Tabel Makanan Hari Ini -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title" style="font-size: 1.15rem;">
                        🍽️ Konsumsi Makanan Hari Ini
                    </h3>
                    <?php if (!empty($todayFoodItems)): ?>
                        <form action="tracker/hapussemuamakanan.php" method="POST" onsubmit="return confirm('Reset semua catatan makanan hari ini?');">
                            <button type="submit" class="btn btn-secondary btn-sm" style="font-size: 0.78rem;">
                                🔄 Reset Hari Ini
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
                                        <td><span style="color: var(--color-primary); font-weight: 700;"><?= number_format((float)$f['kalori'], 1) ?></span> kcal</td>
                                        <td><?= number_format((float)$f['protein'], 1) ?> g</td>
                                        <td>
                                            <button class="btn btn-danger btn-sm" onclick="deleteFood(<?= $f['id_hitung'] ?>, this)">
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 28px 16px; color: var(--text-muted);">
                        <p>Belum ada makanan yang dicatat hari ini.</p>
                        <p style="font-size: 0.82rem; margin-top: 4px;">Pilih salah satu saran menu di atas atau gunakan kolom pencarian.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tabel Olahraga Hari Ini -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title" style="font-size: 1.15rem;">
                        🔥 Latihan & Pembakaran Hari Ini
                    </h3>
                    <span class="badge badge-rose"><?= number_format($kaloriTerbakar, 1) ?> kcal terbakar</span>
                </div>

                <?php if (!empty($todayExerciseItems)): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Olahraga</th>
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
                                        <td><span style="color: var(--color-rose); font-weight: 700;">-<?= number_format((float)$e['kalori_terbakar'], 1) ?></span> kcal</td>
                                        <td>
                                            <button class="btn btn-danger btn-sm" onclick="deleteExercise(<?= $e['id_olahraga'] ?>, this)">
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 28px 16px; color: var(--text-muted);">
                        <p>Belum ada latihan yang dicatat hari ini.</p>
                        <p style="font-size: 0.82rem; margin-top: 4px;">Pilih salah satu rekomendasi latihan atau catat latihan mandiri di atas.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- SECTION 4: GRAFIK ANALITIK RIWAYAT BERAT BADAN -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📈 Tren Progres Berat Badan & BMI Anda</h3>
                <span class="badge badge-emerald">Analytics</span>
            </div>
            <div style="position: relative; height: 260px; width: 100%;">
                <canvas id="weightChart"></canvas>
            </div>
        </div>

    </main>

    <footer class="footer">
        <div class="app-container">
            <p><strong>Let's Move</strong> — Calorie Tracker & Fitness Management System.</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="js/scriptt.js"></script>
    <script>
    // 1-Click Quick Add Meal from Recommendation
    async function quickAddMeal(name, serving, cal, prot, carbs, fat) {
        try {
            const res = await fetch('api/food.php?action=add', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    name: name,
                    serving_size: serving,
                    calories: cal,
                    protein: prot,
                    carbohydrates: carbs,
                    fat: fat
                })
            });
            const data = await res.json();
            if (data.success) {
                showToast(`"${name}" berhasil ditambahkan ke catatan hari ini!`, 'success');
                setTimeout(() => window.location.reload(), 600);
            } else {
                showToast(data.error || 'Gagal menambahkan makanan.', 'error');
            }
        } catch (e) {
            showToast('Koneksi terganggu.', 'error');
        }
    }

    // 1-Click Quick Add Workout from Recommendation
    async function quickAddWorkout(name, duration, reps, burned) {
        try {
            const res = await fetch('api/exercise.php?action=add', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    name: name,
                    duration: duration,
                    repetitions: reps,
                    calories_burned: burned
                })
            });
            const data = await res.json();
            if (data.success) {
                showToast(`Latihan "${name}" berhasil dicatat!`, 'success');
                setTimeout(() => window.location.reload(), 600);
            } else {
                showToast(data.error || 'Gagal mencatat latihan.', 'error');
            }
        } catch (e) {
            showToast('Koneksi terganggu.', 'error');
        }
    }

    // Manual Food Form
    async function handleManualFoodSubmit(e) {
        e.preventDefault();
        const name = document.getElementById('manualName').value.trim();
        const serving = parseFloat(document.getElementById('manualServing').value) || 100;
        const cal = parseFloat(document.getElementById('manualCalories').value) || 0;
        const prot = parseFloat(document.getElementById('manualProtein').value) || 0;
        const carbs = parseFloat(document.getElementById('manualCarbs').value) || 0;

        await quickAddMeal(name, serving, cal, prot, carbs, 0);
    }

    // Manual Workout Form
    async function handleWorkoutSubmit(e) {
        e.preventDefault();
        const name = document.getElementById('exName').value;
        const duration = parseFloat(document.getElementById('exDuration').value) || 0;
        const reps = parseInt(document.getElementById('exReps').value) || 0;

        if (duration <= 0 && reps <= 0) {
            alert('Harap masukkan durasi (menit) atau jumlah repetisi latihan.');
            return;
        }

        await quickAddWorkout(name, duration, reps, 0);
    }

    // Delete Food Row
    async function deleteFood(id, btn) {
        if (!confirm('Hapus item makanan ini?')) return;
        try {
            const res = await fetch('api/food.php?action=delete&id=' + id, { method: 'POST' });
            const data = await res.json();
            if (data.success) {
                showToast('Makanan dihapus.', 'success');
                setTimeout(() => window.location.reload(), 500);
            }
        } catch (e) {
            showToast('Gagal menghapus.', 'error');
        }
    }

    // Delete Workout Row
    async function deleteExercise(id, btn) {
        if (!confirm('Hapus catatan latihan ini?')) return;
        try {
            const res = await fetch('api/exercise.php?action=delete&id=' + id, { method: 'POST' });
            const data = await res.json();
            if (data.success) {
                showToast('Catatan latihan dihapus.', 'success');
                setTimeout(() => window.location.reload(), 500);
            }
        } catch (e) {
            showToast('Gagal menghapus.', 'error');
        }
    }

    // Render Progress Chart
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
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2.5,
                        pointBackgroundColor: '<?= $activeMeta['color'] ?>',
                        pointRadius: 4,
                        yAxisID: 'y'
                    },
                    {
                        label: 'BMI',
                        data: bmis.length ? bmis : [<?= $bmi ?>],
                        borderColor: '#06b6d4',
                        borderDash: [4, 4],
                        borderWidth: 2,
                        tension: 0.35,
                        pointRadius: 3,
                        pointBackgroundColor: '#06b6d4',
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: '#94a3b8', font: { family: 'Plus Jakarta Sans', size: 12 } } }
                },
                scales: {
                    x: { grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: '#64748b' } },
                    y: { type: 'linear', display: true, position: 'left', grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: '#10b981' } },
                    y1: { type: 'linear', display: true, position: 'right', grid: { drawOnChartArea: false }, ticks: { color: '#06b6d4' } }
                }
            }
        });
    });

    // Global Toast Function
    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container) return;
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `<span>${type === 'success' ? '✓' : '⚠️'}</span> <span>${message}</span>`;
        container.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    </script>

</body>
</html>
