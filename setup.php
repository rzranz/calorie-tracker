<?php
/**
 * Onboarding: Physical Metrics & Target Selection
 * Clean Architecture & Anti-Slop Interface.
 */

require_once __DIR__ . '/includes/functions.php';

$user = requireAuth();

$berat = (float)($_POST['berat'] ?? ($user['berat'] > 0 ? $user['berat'] : 70));
$tinggi = (float)($_POST['tinggi'] ?? ($user['tinggi'] > 0 ? $user['tinggi'] : 170));
$usia = (int)($_POST['usia'] ?? ($user['usia'] > 0 ? $user['usia'] : 24));
$gender = trim($_POST['gender'] ?? ($user['gender'] ?: 'Laki-laki'));
$aktivitas = trim($_POST['aktivitas'] ?? ($user['aktivitas'] ?: 'moderate'));

// Calculations
$bmi = FitnessService::calculateBMI($berat, $tinggi);
$cat = FitnessService::getBMICategory($bmi);
$bmr = FitnessService::calculateBMR($berat, $tinggi, $usia, $gender);
$tdee = FitnessService::calculateTDEE($bmr, $aktivitas);

$targetDiet = FitnessService::calculateCalorieTarget($tdee, 'diet');
$targetWorkout = FitnessService::calculateCalorieTarget($tdee, 'workout');
$targetBulking = FitnessService::calculateCalorieTarget($tdee, 'bulking');

// Handle Final Selection and Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_program'])) {
    $selectedProgram = trim($_POST['selected_program'] ?? 'bulking');
    $validPrograms = ['diet', 'workout', 'bulking'];
    if (!in_array($selectedProgram, $validPrograms)) {
        $selectedProgram = 'bulking';
    }

    AuthService::updateProfile(
        (int)$user['id_user'],
        $berat,
        $tinggi,
        $usia,
        $gender,
        $aktivitas,
        $selectedProgram
    );

    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalkulator BMI & Sasaran Program — Let's Move</title>
    <link rel="stylesheet" href="css/modern.css">
</head>
<body style="background: radial-gradient(circle at top, #131d31 0%, #090d16 100%); min-height: 100vh; padding-bottom: 60px;">

    <!-- Minimal Header -->
    <header class="navbar" style="border-bottom: 1px solid var(--border-subtle); padding: 14px 0;">
        <div class="app-container" style="display: flex; justify-content: space-between; align-items: center;">
            <div class="brand-logo">
                <span class="brand-mark">LM</span>
                <span>Let's Move</span>
                <span class="badge badge-neutral">Onboarding</span>
            </div>
            <div style="font-size: 0.85rem; color: var(--text-muted);">
                Pengguna: <strong style="color: var(--text-main);"><?= htmlspecialchars($user['email']) ?></strong>
                <a href="logout.php" style="color: #fda4af; margin-left: 14px; text-decoration: underline;">Keluar</a>
            </div>
        </div>
    </header>

    <main class="app-container" style="padding-top: 32px;">
        <div style="text-align: center; max-width: 640px; margin: 0 auto 28px;">
            <span class="badge badge-neutral" style="margin-bottom: 8px;">TAHAP 1 DARI 2</span>
            <h1 style="font-size: 1.85rem; font-weight: 800; letter-spacing: -0.03em; margin-bottom: 6px;">
                Pengukuran Parameter Fisik
            </h1>
            <p style="color: var(--text-muted); font-size: 0.9rem;">
                Sistem menghitung indeks massa tubuh (BMI) dan total pembakaran energi harian (TDEE) secara otomatis.
            </p>
        </div>

        <!-- Metric Calculation Card -->
        <div class="card" style="max-width: 760px; margin: 0 auto 32px; padding: 26px;">
            <div class="card-header">
                <h2 class="card-title">Parameter Tubuh Anda</h2>
                <span class="badge badge-neutral">Mifflin-St Jeor</span>
            </div>

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
                        <option value="sedentary" <?= ($aktivitas === 'sedentary') ? 'selected' : '' ?>>Sedentari (Banyak duduk / minim gerak fisik)</option>
                        <option value="light" <?= ($aktivitas === 'light') ? 'selected' : '' ?>>Ringan (Olahraga ringan 1-3 hari/minggu)</option>
                        <option value="moderate" <?= ($aktivitas === 'moderate') ? 'selected' : '' ?>>Sedang (Olahraga teratur 3-5 hari/minggu)</option>
                        <option value="active" <?= ($aktivitas === 'active') ? 'selected' : '' ?>>Aktif (Olahraga intensif 6-7 hari/minggu)</option>
                        <option value="very_active" <?= ($aktivitas === 'very_active') ? 'selected' : '' ?>>Sangat Aktif (Latihan fisik keras & pekerjaan lapangan)</option>
                    </select>
                </div>
            </form>

            <!-- Result Strip -->
            <div style="margin-top: 20px; padding: 18px; background: var(--bg-surface-elevated); border: 1px solid var(--border-default); border-radius: var(--radius-sm);">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 10px;">
                    <div>
                        <span style="font-size: 0.74rem; color: var(--text-dim); text-transform: uppercase; font-family: var(--font-mono);">SKOR INDEKS MASSA TUBUH (BMI)</span>
                        <div style="font-size: 1.7rem; font-weight: 800; color: var(--text-main); font-family: var(--font-mono);">
                            <?= number_format($bmi, 2) ?>
                        </div>
                    </div>
                    <span class="badge <?= $cat['badge'] ?>" style="font-size: 0.82rem; padding: 6px 12px;">
                        <?= htmlspecialchars($cat['title']) ?>
                    </span>
                </div>
                <p style="font-size: 0.84rem; color: var(--text-muted); line-height: 1.5;">
                    <?= htmlspecialchars($cat['recommendation']) ?>
                </p>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 12px; padding-top: 10px; border-top: 1px solid var(--border-subtle); text-align: center;">
                    <div>
                        <span style="font-size: 0.72rem; color: var(--text-dim); text-transform: uppercase; font-family: var(--font-mono);">BASAL METABOLIC RATE (BMR)</span>
                        <div style="font-size: 1.1rem; font-weight: 700; color: var(--color-cyan); font-family: var(--font-mono);"><?= number_format($bmr) ?> kcal</div>
                        <span style="font-size: 0.72rem; color: var(--text-dim);">Pembakaran saat istirahat</span>
                    </div>
                    <div>
                        <span style="font-size: 0.72rem; color: var(--text-dim); text-transform: uppercase; font-family: var(--font-mono);">TOTAL HARIAN (TDEE)</span>
                        <div style="font-size: 1.1rem; font-weight: 700; color: var(--color-amber); font-family: var(--font-mono);"><?= number_format($tdee) ?> kcal</div>
                        <span style="font-size: 0.72rem; color: var(--text-dim);">Total energi dibakar per hari</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section: 3 Programs Selection -->
        <div style="max-width: 980px; margin: 0 auto;">
            <div style="text-align: center; margin-bottom: 20px;">
                <span class="badge badge-neutral">TAHAP 2 DARI 2</span>
                <h2 style="font-size: 1.5rem; font-weight: 800; margin-top: 4px; letter-spacing: -0.02em;">
                    Pilih Sasaran Program Anda
                </h2>
                <p style="color: var(--text-muted); font-size: 0.88rem;">
                    Setiap program mengkalkulasi anggaran kalori dan menyediakan saran menu serta latihan yang berbeda.
                </p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                
                <!-- CARD 1: DIET -->
                <div class="card" style="border-top: 3px solid #06b6d4; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <span class="badge badge-blue">DEFISIT KALORI</span>
                            <span style="font-family: var(--font-mono); font-size: 0.74rem; color: var(--text-dim);">PROGRAM 01</span>
                        </div>
                        <h3 style="font-size: 1.2rem; font-weight: 800; margin-bottom: 6px;">Diet & Fat Loss</h3>
                        <p style="font-size: 0.84rem; color: var(--text-muted); margin-bottom: 14px; line-height: 1.5;">
                            Fokus pembakaran simpanan lemak tubuh dengan defisit energi moderat tanpa mengorbankan massa otot.
                        </p>

                        <div style="background: var(--bg-surface-elevated); padding: 12px; border-radius: var(--radius-sm); margin-bottom: 14px;">
                            <div style="font-size: 0.72rem; color: var(--text-dim); text-transform: uppercase; font-family: var(--font-mono);">TARGET ANGGARAN HARIAN</div>
                            <div style="font-size: 1.4rem; font-weight: 800; color: #06b6d4; font-family: var(--font-mono);">
                                <?= number_format($targetDiet) ?> <span style="font-size: 0.82rem; font-weight: 500; font-family: var(--font-sans);">kcal/hari</span>
                            </div>
                            <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 2px;">
                                Defisit ~450 kcal di bawah TDEE
                            </div>
                        </div>

                        <ul style="font-size: 0.8rem; color: var(--text-muted); list-style: none; display: flex; flex-direction: column; gap: 6px; margin-bottom: 18px;">
                            <li>• Saran menu rendah kalori tinggi protein</li>
                            <li>• Jadwal kardio interval dan jalan santai</li>
                            <li>• Pelacakan defisit kalori harian</li>
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
                        <button type="submit" class="btn btn-primary btn-block" style="background: #06b6d4; border-color: #06b6d4; color: #080c14;">
                            Pilih Program Diet
                        </button>
                    </form>
                </div>

                <!-- CARD 2: WORKOUT -->
                <div class="card" style="border-top: 3px solid #10b981; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <span class="badge badge-emerald">MAINTENANCE</span>
                            <span style="font-family: var(--font-mono); font-size: 0.74rem; color: var(--text-dim);">PROGRAM 02</span>
                        </div>
                        <h3 style="font-size: 1.2rem; font-weight: 800; margin-bottom: 6px;">Workout & Maintenance</h3>
                        <p style="font-size: 0.84rem; color: var(--text-muted); margin-bottom: 14px; line-height: 1.5;">
                            Fokus pemeliharaan berat badan stabil, peningkatan stamina, dan pengencangan otot fungsional.
                        </p>

                        <div style="background: var(--bg-surface-elevated); padding: 12px; border-radius: var(--radius-sm); margin-bottom: 14px;">
                            <div style="font-size: 0.72rem; color: var(--text-dim); text-transform: uppercase; font-family: var(--font-mono);">TARGET ANGGARAN HARIAN</div>
                            <div style="font-size: 1.4rem; font-weight: 800; color: #10b981; font-family: var(--font-mono);">
                                <?= number_format($targetWorkout) ?> <span style="font-size: 0.82rem; font-weight: 500; font-family: var(--font-sans);">kcal/hari</span>
                            </div>
                            <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 2px;">
                                Keseimbangan energi sesuai TDEE
                            </div>
                        </div>

                        <ul style="font-size: 0.8rem; color: var(--text-muted); list-style: none; display: flex; flex-direction: column; gap: 6px; margin-bottom: 18px;">
                            <li>• Saran menu seimbang makronutrien</li>
                            <li>• Latihan functional fitness dan renang</li>
                            <li>• Pemeliharaan komposisi tubuh</li>
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
                            Pilih Program Workout
                        </button>
                    </form>
                </div>

                <!-- CARD 3: BULKING -->
                <div class="card" style="border-top: 3px solid #8b5cf6; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <span class="badge badge-purple">SURPLUS ANABOLIK</span>
                            <span style="font-family: var(--font-mono); font-size: 0.74rem; color: var(--text-dim);">PROGRAM 03</span>
                        </div>
                        <h3 style="font-size: 1.2rem; font-weight: 800; margin-bottom: 6px;">Hypertrophy & Bulking</h3>
                        <p style="font-size: 0.84rem; color: var(--text-muted); margin-bottom: 14px; line-height: 1.5;">
                            Fokus peningkatan massa otot dan kekuatan angkat dengan surplus kalori terukur dan protein tinggi.
                        </p>

                        <div style="background: var(--bg-surface-elevated); padding: 12px; border-radius: var(--radius-sm); margin-bottom: 14px;">
                            <div style="font-size: 0.72rem; color: var(--text-dim); text-transform: uppercase; font-family: var(--font-mono);">TARGET ANGGARAN HARIAN</div>
                            <div style="font-size: 1.4rem; font-weight: 800; color: #c084fc; font-family: var(--font-mono);">
                                <?= number_format($targetBulking) ?> <span style="font-size: 0.82rem; font-weight: 500; font-family: var(--font-sans);">kcal/hari</span>
                            </div>
                            <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 2px;">
                                Surplus ~400 kcal di atas TDEE
                            </div>
                        </div>

                        <ul style="font-size: 0.8rem; color: var(--text-muted); list-style: none; display: flex; flex-direction: column; gap: 6px; margin-bottom: 18px;">
                            <li>• Menu tinggi protein & karbohidrat</li>
                            <li>• Latihan beban compound & progressive overload</li>
                            <li>• Target protein 1.8 - 2.2g per kg BB</li>
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
                        <button type="submit" class="btn btn-primary btn-block" style="background: #8b5cf6; border-color: #8b5cf6; color: #ffffff;">
                            Pilih Program Bulking
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </main>

</body>
</html>
