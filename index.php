<?php
/**
 * Main Application Dashboard & BMI/TDEE Calculator
 */

require_once __DIR__ . '/includes/functions.php';

$activeTab = 'dashboard';
$pageTitle = "Dashboard & Kalkulator BMI • Let's Move";

$latestLog = getLatestWeightLog();
$todayFood = getTodayFoodSummary();
$todayExercise = getTodayExerciseSummary();

// Handle Form Submission
$calculationResult = null;
$formError = null;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['calculate_bmi'])) {
    $berat = (float)($_POST['berat'] ?? 0);
    $tinggi = (float)($_POST['tinggi'] ?? 0);
    $usia = (int)($_POST['usia'] ?? 0);
    $gender = trim($_POST['gender'] ?? 'Laki-laki');
    $aktivitas = trim($_POST['aktivitas'] ?? 'moderate');
    $goal = trim($_POST['goal'] ?? 'bulking');

    if ($berat <= 0 || $tinggi <= 0 || $usia <= 0) {
        $formError = "Berat, tinggi, dan usia harus berupa angka lebih besar dari 0!";
    } else {
        $bmi = calculateBMI($berat, $tinggi);
        $cat = getBMICategory($bmi);
        $bmr = calculateBMR($berat, $tinggi, $usia, $gender);
        $tdee = calculateTDEE($bmr, $aktivitas);
        $targetKalori = calculateCalorieTarget($tdee, $goal);
        $macros = calculateMacros($berat, $targetKalori, $goal);

        // Save to database
        $savedId = saveWeightLog($berat, $tinggi, $usia, $gender, $aktivitas, $goal);

        $calculationResult = [
            'bmi'           => $bmi,
            'kategori'      => $cat,
            'bmr'           => $bmr,
            'tdee'          => $tdee,
            'target_kalori' => $targetKalori,
            'macros'        => $macros,
            'goal'          => $goal,
            'berat'         => $berat,
            'tinggi'        => $tinggi
        ];

        // Refresh latest log
        $latestLog = getLatestWeightLog();
    }
}

// Calorie targets for KPI metrics
$targetKalori = $latestLog ? (int)$latestLog['kalori_harian'] : 2500;
$kaloriMasuk = $todayFood['total_calories'];
$kaloriTerbakar = $todayExercise['total_burned'];
$kaloriBersih = max(0, $kaloriMasuk - $kaloriTerbakar);
$persenKalori = ($targetKalori > 0) ? min(100, round(($kaloriBersih / $targetKalori) * 100)) : 0;
$isOverflow = ($kaloriBersih > $targetKalori);

// Weight history for chart & table
$weightHistory = getWeightHistory(15);
$chartHistory = array_reverse($weightHistory);
$chartLabels = [];
$chartWeights = [];
$chartBmis = [];

foreach ($chartHistory as $row) {
    $chartLabels[] = date('d M', strtotime($row['tanggal']));
    $chartWeights[] = (float)$row['berat'];
    $chartBmis[] = (float)$row['bmi'];
}

include __DIR__ . '/includes/header.php';
?>

<div class="app-container" style="padding-top: 28px; padding-bottom: 50px;">
    <!-- Page Header -->
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 class="page-title">Fitness & Calorie Dashboard</h1>
            <p class="page-subtitle">Pantau asupan kalori harian, defisit/surplus energi, dan target komposisi tubuh secara terukur.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="tracker/makan_hari_ini.php" class="btn btn-primary btn-sm">+ Catat Makanan</a>
            <a href="tracker/olahraga.html" class="btn btn-secondary btn-sm">+ Catat Olahraga</a>
        </div>
    </div>

    <!-- KPI Stats Cards -->
    <div class="stats-grid">
        <!-- Target Kalori -->
        <div class="stat-card card-blue">
            <span class="stat-label">
                <span>Target Harian</span>
                <span class="badge badge-blue"><?= strtoupper($latestLog['target_program'] ?? 'BULKING') ?></span>
            </span>
            <div class="stat-value">
                <?= number_format($targetKalori) ?><span class="stat-unit">kcal</span>
            </div>
            <div class="stat-meta">
                Berdasarkan kalkulasi TDEE terbaru
            </div>
        </div>

        <!-- Kalori Masuk (Makanan) -->
        <div class="stat-card card-amber">
            <span class="stat-label">Kalori Masuk (Makanan)</span>
            <div class="stat-value">
                <?= number_format($kaloriMasuk, 1) ?><span class="stat-unit">kcal</span>
            </div>
            <div class="stat-meta">
                <?= $todayFood['item_count'] ?> item makanan hari ini
            </div>
        </div>

        <!-- Kalori Terbakar (Olahraga) -->
        <div class="stat-card card-rose">
            <span class="stat-label">Kalori Terbakar</span>
            <div class="stat-value">
                <?= number_format($kaloriTerbakar, 1) ?><span class="stat-unit">kcal</span>
            </div>
            <div class="stat-meta">
                <?= $todayExercise['workout_count'] ?> sesi latihan dicatat
            </div>
        </div>

        <!-- Kalori Bersih (Net) -->
        <div class="stat-card <?= $isOverflow ? 'card-rose' : 'card-purple' ?>">
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
                <?= ($targetKalori - $kaloriBersih >= 0) ? (number_format($targetKalori - $kaloriBersih, 1) . ' kcal tersisa') : (number_format($kaloriBersih - $targetKalori, 1) . ' kcal melebihi target') ?>
            </div>
        </div>

        <!-- Protein Terkumpul -->
        <div class="stat-card">
            <span class="stat-label">Protein Hari Ini</span>
            <div class="stat-value">
                <?= number_format($todayFood['total_protein'], 1) ?><span class="stat-unit">g</span>
            </div>
            <div class="stat-meta">
                Sintesis & pemulihan serat otot
            </div>
        </div>
    </div>

    <!-- Progress Meter -->
    <div class="card" style="margin-bottom: 30px; padding: 20px 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <strong style="font-size: 0.95rem;">Budget Kalori Harian</strong>
                <span style="color: var(--text-muted); font-size: 0.85rem; margin-left: 8px;">
                    <?= number_format($kaloriBersih, 1) ?> dari <?= number_format($targetKalori) ?> kcal (<?= $persenKalori ?>%)
                </span>
            </div>
            <span class="badge <?= $isOverflow ? 'badge-rose' : 'badge-emerald' ?>">
                <?= $isOverflow ? 'Melebihi Batas' : 'Dalam Batas Aman' ?>
            </span>
        </div>
        <div class="progress-bar-container">
            <div class="progress-bar-fill <?= $isOverflow ? 'overflow' : '' ?>" style="width: <?= min(100, $persenKalori) ?>%;"></div>
        </div>
    </div>

    <!-- Main Content 2-Column Grid -->
    <div class="layout-2col">
        <!-- Left Column: BMI & TDEE Calculator -->
        <div class="card" id="calculator">
            <div class="card-header">
                <h2 class="card-title">⚖️ Kalkulator BMI & Kebutuhan Kalori</h2>
                <span class="badge badge-blue">Mifflin-St Jeor</span>
            </div>

            <?php if (!empty($formError)): ?>
                <div style="background: rgba(244, 63, 94, 0.15); border: 1px solid rgba(244, 63, 94, 0.4); color: #fda4af; padding: 12px 16px; border-radius: var(--radius-md); margin-bottom: 18px; font-size: 0.9rem;">
                    <?= htmlspecialchars($formError) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php#calculator">
                <input type="hidden" name="calculate_bmi" value="1">

                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="berat" class="form-label">Berat Badan (kg)</label>
                        <input type="number" step="0.1" name="berat" id="berat" class="form-control" placeholder="Contoh: 70" required min="1" value="<?= htmlspecialchars($_POST['berat'] ?? ($latestLog['berat'] ?? '70')) ?>">
                    </div>

                    <div class="form-group">
                        <label for="tinggi" class="form-label">Tinggi Badan (cm)</label>
                        <input type="number" step="0.1" name="tinggi" id="tinggi" class="form-control" placeholder="Contoh: 175" required min="1" value="<?= htmlspecialchars($_POST['tinggi'] ?? ($latestLog['tinggi'] ?? '175')) ?>">
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="usia" class="form-label">Usia (Tahun)</label>
                        <input type="number" name="usia" id="usia" class="form-control" placeholder="Contoh: 24" required min="1" value="<?= htmlspecialchars($_POST['usia'] ?? ($latestLog['usia'] ?? '24')) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Jenis Kelamin</label>
                        <select name="gender" class="form-control">
                            <option value="Laki-laki" <?= (($_POST['gender'] ?? ($latestLog['gender'] ?? '')) === 'Laki-laki') ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="Perempuan" <?= (($_POST['gender'] ?? ($latestLog['gender'] ?? '')) === 'Perempuan') ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="aktivitas" class="form-label">Tingkat Aktivitas Harian</label>
                    <select name="aktivitas" id="aktivitas" class="form-control">
                        <option value="sedentary" <?= (($_POST['aktivitas'] ?? ($latestLog['aktivitas'] ?? '')) === 'sedentary') ? 'selected' : '' ?>>Sedentari (Banyak duduk / tidak rutin olahraga)</option>
                        <option value="light" <?= (($_POST['aktivitas'] ?? ($latestLog['aktivitas'] ?? '')) === 'light') ? 'selected' : '' ?>>Ringan (Olahraga ringan 1-3 hari/minggu)</option>
                        <option value="moderate" <?= (($_POST['aktivitas'] ?? ($latestLog['aktivitas'] ?? 'moderate')) === 'moderate') ? 'selected' : '' ?>>Sedang (Olahraga teratur 3-5 hari/minggu)</option>
                        <option value="active" <?= (($_POST['aktivitas'] ?? ($latestLog['aktivitas'] ?? '')) === 'active') ? 'selected' : '' ?>>Aktif (Olahraga berat 6-7 hari/minggu)</option>
                        <option value="very_active" <?= (($_POST['aktivitas'] ?? ($latestLog['aktivitas'] ?? '')) === 'very_active') ? 'selected' : '' ?>>Sangat Aktif (Latihan fisik intens & pekerjaan fisik)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="goal" class="form-label">Target Program Fitness</label>
                    <select name="goal" id="goal" class="form-control">
                        <option value="bulking" <?= (($_POST['goal'] ?? ($latestLog['target_program'] ?? 'bulking')) === 'bulking') ? 'selected' : '' ?>>💪 Bulking (Surplus Kalori +400 kcal untuk menaikkan massa otot)</option>
                        <option value="diet" <?= (($_POST['goal'] ?? ($latestLog['target_program'] ?? '')) === 'diet') ? 'selected' : '' ?>>🥑 Diet / Cutting (Defisit Kalori -450 kcal untuk memotong lemak)</option>
                        <option value="maintenance" <?= (($_POST['goal'] ?? ($latestLog['target_program'] ?? '')) === 'maintenance') ? 'selected' : '' ?>>⚖️ Maintenance (Menjaga berat badan stabil)</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="margin-top: 10px;">
                    Hitung & Simpan ke Profil
                </button>
            </form>

            <?php if ($calculationResult): ?>
                <!-- Calculation Result Card -->
                <div style="margin-top: 24px; padding: 20px; background: var(--bg-surface-elevated); border: 1px solid var(--border-default); border-radius: var(--radius-md);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                        <div>
                            <span style="font-size: 0.85rem; color: var(--text-muted);">Indeks Massa Tubuh (BMI)</span>
                            <div style="font-size: 2rem; font-weight: 800; color: var(--text-main);">
                                <?= number_format($calculationResult['bmi'], 2) ?>
                            </div>
                        </div>
                        <span class="badge <?= $calculationResult['kategori']['badge'] ?>" style="font-size: 0.85rem; padding: 6px 14px;">
                            <?= htmlspecialchars($calculationResult['kategori']['title']) ?>
                        </span>
                    </div>

                    <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 16px;">
                        <?= htmlspecialchars($calculationResult['kategori']['recommendation']) ?>
                    </p>

                    <!-- Nutritional Breakdown -->
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 16px; text-align: center;">
                        <div style="background: var(--bg-card); padding: 10px; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
                            <div style="font-size: 0.72rem; color: var(--text-dim); text-transform: uppercase;">Target Kalori</div>
                            <div style="font-weight: 700; font-size: 1.1rem; color: var(--color-primary);"><?= $calculationResult['target_kalori'] ?> kcal</div>
                        </div>
                        <div style="background: var(--bg-card); padding: 10px; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
                            <div style="font-size: 0.72rem; color: var(--text-dim); text-transform: uppercase;">Target Protein</div>
                            <div style="font-weight: 700; font-size: 1.1rem; color: var(--color-cyan);"><?= $calculationResult['macros']['protein_g'] ?>g</div>
                        </div>
                        <div style="background: var(--bg-card); padding: 10px; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
                            <div style="font-size: 0.72rem; color: var(--text-dim); text-transform: uppercase;">TDEE Harian</div>
                            <div style="font-weight: 700; font-size: 1.1rem; color: var(--color-amber);"><?= $calculationResult['tdee'] ?> kcal</div>
                        </div>
                    </div>

                    <!-- Direct Action Buttons -->
                    <div style="display: flex; gap: 10px; margin-top: 14px;">
                        <a href="tracker/makan_hari_ini.php" class="btn btn-primary btn-sm" style="flex: 1;">
                            Mulai Catat Makanan
                        </a>
                        <?php if ($calculationResult['goal'] === 'diet'): ?>
                            <a href="tracker/diet.php" class="btn btn-secondary btn-sm" style="flex: 1;">
                                Buka Panduan Diet
                            </a>
                        <?php else: ?>
                            <a href="tracker/bulking.php" class="btn btn-secondary btn-sm" style="flex: 1;">
                                Buka Panduan Bulking
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Column: Visual Progress Analytics & Fast Actions -->
        <div>
            <!-- Visual Chart Card -->
            <div class="card" style="margin-bottom: 24px;">
                <div class="card-header">
                    <h2 class="card-title">📈 Tren Berat Badan & BMI</h2>
                    <span class="badge badge-emerald">Analytics</span>
                </div>
                <div style="position: relative; height: 260px; width: 100%;">
                    <canvas id="weightChart"></canvas>
                </div>
            </div>

            <!-- Program Selector Cards -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="card" style="padding: 20px; border-left: 4px solid var(--color-primary);">
                    <h3 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 6px;">💪 Program Bulking</h3>
                    <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 14px;">
                        Tingkatkan massa otot dengan surplus kalori terkontrol, 1.8-2.2g protein/kg, dan progressive overload.
                    </p>
                    <a href="tracker/bulking.php" class="btn btn-secondary btn-sm btn-block">Lihat Program →</a>
                </div>

                <div class="card" style="padding: 20px; border-left: 4px solid var(--color-cyan);">
                    <h3 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 6px;">🥑 Program Diet</h3>
                    <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 14px;">
                        Pangkas lemak tubuh dengan defisit kalori moderat tanpa kehilangan massa otot berharga.
                    </p>
                    <a href="tracker/diet.php" class="btn btn-secondary btn-sm btn-block">Lihat Program →</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Weight Log History Table -->
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h2 class="card-title">📋 Riwayat Pengukuran Berat Badan</h2>
            <span class="stat-meta">15 data terkini</span>
        </div>

        <?php if (!empty($weightHistory)): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Berat (kg)</th>
                            <th>Tinggi (cm)</th>
                            <th>BMI</th>
                            <th>Kategori</th>
                            <th>Target Kalori</th>
                            <th>Program</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($weightHistory as $log): 
                            $cat = getBMICategory((float)$log['bmi']);
                        ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($log['tanggal']) ?></strong></td>
                                <td><?= number_format((float)$log['berat'], 1) ?> kg</td>
                                <td><?= (int)$log['tinggi'] ?> cm</td>
                                <td><span style="font-weight: 700;"><?= number_format((float)$log['bmi'], 2) ?></span></td>
                                <td>
                                    <span class="badge <?= $cat['badge'] ?>">
                                        <?= htmlspecialchars($cat['title']) ?>
                                    </span>
                                </td>
                                <td><?= number_format((float)$log['kalori_harian']) ?> kcal</td>
                                <td>
                                    <span class="badge badge-secondary" style="text-transform: capitalize;">
                                        <?= htmlspecialchars($log['target_program'] ?? 'bulking') ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-danger btn-sm" onclick="deleteWeight(<?= $log['id_beratbadan'] ?>, this)">
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="color: var(--text-muted); text-align: center; padding: 20px;">
                Belum ada data riwayat berat badan. Gunakan form kalkulator di atas untuk menyimpan pengukuran pertama Anda!
            </p>
        <?php endif; ?>
    </div>
</div>

<!-- Chart Script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('weightChart');
    if (!ctx) return;

    const labels = <?= json_encode($chartLabels) ?>;
    const weights = <?= json_encode($chartWeights) ?>;
    const bmis = <?= json_encode($chartBmis) ?>;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Berat Badan (kg)',
                    data: weights,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.12)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2.5,
                    pointBackgroundColor: '#10b981',
                    pointRadius: 4,
                    yAxisID: 'y'
                },
                {
                    label: 'BMI',
                    data: bmis,
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
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    labels: { color: '#94a3b8', font: { family: 'Plus Jakarta Sans', size: 12 } }
                },
                tooltip: {
                    backgroundColor: '#1f2937',
                    titleColor: '#f8fafc',
                    bodyColor: '#cbd5e1',
                    borderColor: '#374151',
                    borderWidth: 1
                }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#64748b', font: { family: 'Plus Jakarta Sans' } }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#10b981', font: { family: 'JetBrains Mono' } },
                    title: { display: true, text: 'Berat (kg)', color: '#10b981' }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    ticks: { color: '#06b6d4', font: { family: 'JetBrains Mono' } },
                    title: { display: true, text: 'BMI', color: '#06b6d4' }
                }
            }
        }
    });
});

async function deleteWeight(id, btnElement) {
    if (!confirm('Hapus riwayat data pengukuran berat badan ini?')) return;
    try {
        const response = await fetch('api/weight.php?action=delete&id=' + id, { method: 'POST' });
        const res = await response.json();
        if (res.success) {
            btnElement.closest('tr').remove();
            showToast('Data berhasil dihapus.', 'success');
        } else {
            showToast(res.error || 'Gagal menghapus data.', 'error');
        }
    } catch (e) {
        showToast('Terjadi kesalahan koneksi.', 'error');
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
