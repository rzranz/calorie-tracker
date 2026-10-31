<?php
/**
 * Onboarding: BMI Calculator & Program Goal Selection
 * Guides the user to measure their body metrics and choose their tailored path.
 */

require_once __DIR__ . '/includes/functions.php';

$user = requireAuth();

$berat = (float)($_POST['berat'] ?? ($user['berat'] > 0 ? $user['berat'] : 70));
$tinggi = (float)($_POST['tinggi'] ?? ($user['tinggi'] > 0 ? $user['tinggi'] : 170));
$usia = (int)($_POST['usia'] ?? ($user['usia'] > 0 ? $user['usia'] : 24));
$gender = trim($_POST['gender'] ?? ($user['gender'] ?: 'Laki-laki'));
$aktivitas = trim($_POST['aktivitas'] ?? ($user['aktivitas'] ?: 'moderate'));

// Initial or submitted calculations
$bmi = calculateBMI($berat, $tinggi);
$cat = getBMICategory($bmi);
$bmr = calculateBMR($berat, $tinggi, $usia, $gender);
$tdee = calculateTDEE($bmr, $aktivitas);

$targetDiet = calculateCalorieTarget($tdee, 'diet');
$targetWorkout = calculateCalorieTarget($tdee, 'workout');
$targetBulking = calculateCalorieTarget($tdee, 'bulking');

// Handle Final Selection and Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_program'])) {
    $selectedProgram = trim($_POST['selected_program'] ?? 'bulking');
    $validPrograms = ['diet', 'workout', 'bulking'];
    if (!in_array($selectedProgram, $validPrograms)) {
        $selectedProgram = 'bulking';
    }

    updateUserProfile(
        (int)$user['id_user'],
        $berat,
        $tinggi,
        $usia,
        $gender,
        $aktivitas,
        $selectedProgram
    );

    // Redirect to tailored dashboard
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalkulator BMI & Pemilihan Program • Let's Move</title>
    <link rel="stylesheet" href="css/modern.css">
</head>
<body style="background: radial-gradient(circle at top, #162032 0%, #0b0f19 100%); min-height: 100vh; padding-bottom: 60px;">

    <!-- Minimal Header -->
    <header class="navbar" style="border-bottom: 1px solid var(--border-subtle); padding: 16px 0;">
        <div class="app-container" style="display: flex; justify-content: space-between; align-items: center;">
            <div class="brand-logo">
                <div class="brand-icon">⚡</div>
                <span>Let's Move</span>
                <span class="brand-badge">Setup Profil</span>
            </div>
            <div style="font-size: 0.88rem; color: var(--text-muted);">
                Login sebagai: <strong style="color: var(--text-main);"><?= htmlspecialchars($user['email']) ?></strong>
                <a href="logout.php" style="color: #fda4af; margin-left: 12px; text-decoration: underline;">Keluar</a>
            </div>
        </div>
    </header>

    <main class="app-container" style="padding-top: 36px;">
        <div style="text-align: center; max-width: 680px; margin: 0 auto 32px;">
            <span class="badge badge-emerald" style="margin-bottom: 10px;">LANGKAH 1 DARI 2</span>
            <h1 style="font-size: 2rem; font-weight: 800; letter-spacing: -0.03em; margin-bottom: 8px;">
                Hitung Kondisi Fisik & Pilih Sasaran Tubuh
            </h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">
                Masukkan data fisik Anda di bawah ini. Kami akan menghitung indeks massa tubuh (BMI) dan kebutuhan kalori harian Anda secara presisi.
            </p>
        </div>

        <!-- Metric Calculation Card -->
        <div class="card" style="max-width: 800px; margin: 0 auto 36px; padding: 30px;">
            <h2 class="card-title" style="margin-bottom: 20px; font-size: 1.25rem;">
                ⚖️ Data Fisik Anda
            </h2>

            <form method="POST" id="metricForm" action="setup.php">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="berat">Berat Badan (kg)</label>
                        <input type="number" step="0.1" name="berat" id="berat" class="form-control" value="<?= $berat ?>" min="1" required onchange="document.getElementById('metricForm').submit()">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="tinggi">Tinggi Badan (cm)</label>
                        <input type="number" step="0.1" name="tinggi" id="tinggi" class="form-control" value="<?= $tinggi ?>" min="1" required onchange="document.getElementById('metricForm').submit()">
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="usia">Usia (Tahun)</label>
                        <input type="number" name="usia" id="usia" class="form-control" value="<?= $usia ?>" min="1" required onchange="document.getElementById('metricForm').submit()">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jenis Kelamin</label>
                        <select name="gender" class="form-control" onchange="document.getElementById('metricForm').submit()">
                            <option value="Laki-laki" <?= ($gender === 'Laki-laki') ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="Perempuan" <?= ($gender === 'Perempuan') ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Tingkat Aktivitas Harian</label>
                    <select name="aktivitas" class="form-control" onchange="document.getElementById('metricForm').submit()">
                        <option value="sedentary" <?= ($aktivitas === 'sedentary') ? 'selected' : '' ?>>Sedentari (Banyak duduk / minim bergerak)</option>
                        <option value="light" <?= ($aktivitas === 'light') ? 'selected' : '' ?>>Ringan (Olahraga santai 1-3 hari/minggu)</option>
                        <option value="moderate" <?= ($aktivitas === 'moderate') ? 'selected' : '' ?>>Sedang (Olahraga teratur 3-5 hari/minggu)</option>
                        <option value="active" <?= ($aktivitas === 'active') ? 'selected' : '' ?>>Aktif (Olahraga intensif 6-7 hari/minggu)</option>
                        <option value="very_active" <?= ($aktivitas === 'very_active') ? 'selected' : '' ?>>Sangat Aktif (Latihan berat & kerja fisik)</option>
                    </select>
                </div>
            </form>

            <!-- Live Calculation Outcome Strip -->
            <div style="margin-top: 24px; padding: 20px; background: var(--bg-surface-elevated); border: 1px solid var(--border-default); border-radius: var(--radius-md);">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 12px;">
                    <div>
                        <span style="font-size: 0.8rem; color: var(--text-dim); text-transform: uppercase;">Skor Indeks Massa Tubuh (BMI)</span>
                        <div style="font-size: 1.85rem; font-weight: 800; color: var(--text-main);">
                            <?= number_format($bmi, 2) ?>
                        </div>
                    </div>
                    <span class="badge <?= $cat['badge'] ?>" style="font-size: 0.9rem; padding: 8px 16px;">
                        <?= htmlspecialchars($cat['title']) ?>
                    </span>
                </div>
                <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.5;">
                    💡 <?= htmlspecialchars($cat['recommendation']) ?>
                </p>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-subtle); text-align: center;">
                    <div>
                        <span style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase;">Basal Metabolic Rate (BMR)</span>
                        <div style="font-size: 1.15rem; font-weight: 700; color: var(--color-cyan);"><?= number_format($bmr) ?> kcal</div>
                        <span style="font-size: 0.72rem; color: var(--text-dim);">Kalori saat tubuh istirahat total</span>
                    </div>
                    <div>
                        <span style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase;">Total Energi Harian (TDEE)</span>
                        <div style="font-size: 1.15rem; font-weight: 700; color: var(--color-amber);"><?= number_format($tdee) ?> kcal</div>
                        <span style="font-size: 0.72rem; color: var(--text-dim);">Kalori pembakaran harian Anda</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section: 3 Guided Program Choices -->
        <div style="max-width: 1040px; margin: 0 auto;">
            <div style="text-align: center; margin-bottom: 24px;">
                <span class="badge badge-blue">LANGKAH 2 DARI 2</span>
                <h2 style="font-size: 1.6rem; font-weight: 800; margin-top: 6px;">
                    Pilih Program Sasaran Anda
                </h2>
                <p style="color: var(--text-muted); font-size: 0.9rem;">
                    Setiap program memiliki dashboard khusus, perhitungan kalori yang disesuaikan, serta saran makanan dan menu latihan spesifik.
                </p>
            </div>

            <!-- 3 Program Cards Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                
                <!-- CARD 1: DIET -->
                <div class="card" style="border-top: 4px solid #06b6d4; display: flex; flex-direction: column; justify-content: space-between; position: relative;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <span style="font-size: 1.8rem;">🥑</span>
                            <span class="badge badge-blue">DEFISIT KALORI</span>
                        </div>
                        <h3 style="font-size: 1.3rem; font-weight: 800; margin-bottom: 8px;">Program Diet</h3>
                        <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 16px; line-height: 1.5;">
                            Fokus membakar lemak berlebih dan menurunkan berat badan dengan defisit kalori sehat tanpa merasa lemas atau kehilangan otot.
                        </p>

                        <div style="background: var(--bg-surface-elevated); padding: 14px; border-radius: var(--radius-md); margin-bottom: 16px;">
                            <div style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase;">Target Kalori Harian</div>
                            <div style="font-size: 1.5rem; font-weight: 800; color: #06b6d4;">
                                <?= number_format($targetDiet) ?> <span style="font-size: 0.85rem; font-weight: 500;">kcal/hari</span>
                            </div>
                            <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">
                                Defisit ~450 kcal di bawah TDEE Anda
                            </div>
                        </div>

                        <ul style="font-size: 0.84rem; color: var(--text-muted); list-style: none; display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px;">
                            <li>✓ Rekomendasi makanan padat nutrisi rendah kalori</li>
                            <li>✓ Panduan kardio (HIIT, lari, skipping)</li>
                            <li>✓ Proteksi massa otot dengan protein optimal</li>
                        </ul>
                    </div>

                    <form method="POST" action="setup.php">
                        <input type="hidden" name="save_program" value="1">
                        <input type="hidden" name="selected_program" value="diet">
                        <input type="hidden" name="berat" value="<?= $berat ?>">
                        <input type="hidden" name="tinggi" value="<?= $tinggi ?>">
                        <input type="hidden" name="usia" value="<?= $usia ?>">
                        <input type="hidden" name="gender" value="<?= $gender ?>">
                        <input type="hidden" name="aktivitas" value="<?= $aktivitas ?>">
                        <button type="submit" class="btn btn-primary btn-block" style="background: #06b6d4; color: #0b0f19;">
                            Pilih Program Diet →
                        </button>
                    </form>
                </div>

                <!-- CARD 2: WORKOUT / FITNESS -->
                <div class="card" style="border-top: 4px solid #10b981; display: flex; flex-direction: column; justify-content: space-between; position: relative;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <span style="font-size: 1.8rem;">🏃</span>
                            <span class="badge badge-emerald">MAINTENANCE & STAMINA</span>
                        </div>
                        <h3 style="font-size: 1.3rem; font-weight: 800; margin-bottom: 8px;">Program Workout</h3>
                        <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 16px; line-height: 1.5;">
                            Fokus menjaga berat badan stabil, meningkatkan stamina fisik, mengencangkan otot, dan kebugaran tubuh secara menyeluruh.
                        </p>

                        <div style="background: var(--bg-surface-elevated); padding: 14px; border-radius: var(--radius-md); margin-bottom: 16px;">
                            <div style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase;">Target Kalori Harian</div>
                            <div style="font-size: 1.5rem; font-weight: 800; color: #10b981;">
                                <?= number_format($targetWorkout) ?> <span style="font-size: 0.85rem; font-weight: 500;">kcal/hari</span>
                            </div>
                            <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">
                                Seimbang sesuai total pembakaran harian
                            </div>
                        </div>

                        <ul style="font-size: 0.84rem; color: var(--text-muted); list-style: none; display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px;">
                            <li>✓ Menu seimbang tinggi mikronutrien & serat</li>
                            <li>✓ Latihan functional fitness, renang, & bersepeda</li>
                            <li>✓ Pelacakan kalori bersih (net calorie balance)</li>
                        </ul>
                    </div>

                    <form method="POST" action="setup.php">
                        <input type="hidden" name="save_program" value="1">
                        <input type="hidden" name="selected_program" value="workout">
                        <input type="hidden" name="berat" value="<?= $berat ?>">
                        <input type="hidden" name="tinggi" value="<?= $tinggi ?>">
                        <input type="hidden" name="usia" value="<?= $usia ?>">
                        <input type="hidden" name="gender" value="<?= $gender ?>">
                        <input type="hidden" name="aktivitas" value="<?= $aktivitas ?>">
                        <button type="submit" class="btn btn-primary btn-block">
                            Pilih Program Workout →
                        </button>
                    </form>
                </div>

                <!-- CARD 3: BULKING -->
                <div class="card" style="border-top: 4px solid #8b5cf6; display: flex; flex-direction: column; justify-content: space-between; position: relative;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <span style="font-size: 1.8rem;">💪</span>
                            <span class="badge" style="background: rgba(139, 92, 246, 0.2); color: #c084fc; border-color: rgba(139, 92, 246, 0.4);">SURPLUS KALORI</span>
                        </div>
                        <h3 style="font-size: 1.3rem; font-weight: 800; margin-bottom: 8px;">Program Bulking</h3>
                        <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 16px; line-height: 1.5;">
                            Fokus menambah massa otot dan kekuatan angkat dengan surplus kalori terukur dan asupan protein tinggi untuk hipertrofi otot.
                        </p>

                        <div style="background: var(--bg-surface-elevated); padding: 14px; border-radius: var(--radius-md); margin-bottom: 16px;">
                            <div style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase;">Target Kalori Harian</div>
                            <div style="font-size: 1.5rem; font-weight: 800; color: #c084fc;">
                                <?= number_format($targetBulking) ?> <span style="font-size: 0.85rem; font-weight: 500;">kcal/hari</span>
                            </div>
                            <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">
                                Surplus ~400 kcal di atas TDEE Anda
                            </div>
                        </div>

                        <ul style="font-size: 0.84rem; color: var(--text-muted); list-style: none; display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px;">
                            <li>✓ Menu tinggi protein & karbohidrat anabolik</li>
                            <li>✓ Panduan latihan beban (Push, Pull, Legs)</li>
                            <li>✓ Target protein tinggi: 1.8 - 2.2g per kg BB</li>
                        </ul>
                    </div>

                    <form method="POST" action="setup.php">
                        <input type="hidden" name="save_program" value="1">
                        <input type="hidden" name="selected_program" value="bulking">
                        <input type="hidden" name="berat" value="<?= $berat ?>">
                        <input type="hidden" name="tinggi" value="<?= $tinggi ?>">
                        <input type="hidden" name="usia" value="<?= $usia ?>">
                        <input type="hidden" name="gender" value="<?= $gender ?>">
                        <input type="hidden" name="aktivitas" value="<?= $aktivitas ?>">
                        <button type="submit" class="btn btn-primary btn-block" style="background: #8b5cf6; color: #ffffff;">
                            Pilih Program Bulking →
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </main>

</body>
</html>
