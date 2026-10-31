<?php
/**
 * Bulking Program & Hypertrophy Guide
 */

require_once __DIR__ . '/../includes/functions.php';

$activeTab = 'bulking';
$pageTitle = "Program Bulking • Let's Move";

$latestLog = getLatestWeightLog();
$weight = $latestLog ? (float)$latestLog['berat'] : 70;
$height = $latestLog ? (float)$latestLog['tinggi'] : 175;
$bmi = $latestLog ? (float)$latestLog['bmi'] : 22.8;
$cat = getBMICategory($bmi);
$targetKalori = $latestLog ? (int)$latestLog['kalori_harian'] : 2700;

include __DIR__ . '/../includes/header.php';
?>

<div class="app-container" style="padding-top: 28px; padding-bottom: 50px;">
    <!-- Page Header -->
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 class="page-title">💪 Program Bulking & Hipertrofi Otot</h1>
            <p class="page-subtitle">Panduan lengkap meningkatkan massa otot secara sehat (lean bulking) tanpa penumpukan lemak berlebih.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="makan_hari_ini.php" class="btn btn-primary btn-sm">+ Catat Makanan Bulking</a>
            <a href="olahraga.html" class="btn btn-secondary btn-sm">+ Catat Latihan Beban</a>
        </div>
    </div>

    <!-- Hero Card with Gym Photo -->
    <div class="card" style="margin-bottom: 30px; overflow: hidden; padding: 0;">
        <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; align-items: center; min-height: 280px;">
            <div style="padding: 32px 36px;">
                <span class="badge badge-emerald" style="margin-bottom: 12px;">LEAN BULKING PROTOCOL</span>
                <h2 style="font-size: 1.7rem; font-weight: 800; color: var(--text-main); line-height: 1.3; margin-bottom: 12px;">
                    Membangun Otot Berkualitas dengan Surplus Kalori Terkontrol
                </h2>
                <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 20px;">
                    Program bulking bertujuan menciptakan lingkungan anabolik di dalam tubuh dengan surplus energi sekitar 300–500 kcal di atas TDEE harian, disertai asupan protein optimal dan latihan beban progresif.
                </p>
                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <a href="makan_hari_ini.php" class="btn btn-primary btn-sm">Mulai Catat Nutrisi</a>
                    <a href="../index.php#calculator" class="btn btn-secondary btn-sm">Hitung Ulang TDEE</a>
                </div>
            </div>
            <div style="height: 100%; min-height: 240px; background: url('../img/gym.jpg') center/cover no-repeat; border-left: 1px solid var(--border-subtle);"></div>
        </div>
    </div>

    <!-- 2 Column Layout: Principles & Status -->
    <div class="layout-2col">
        <!-- Core Bulking Principles -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">📌 5 Pilar Sukses Program Bulking</h2>
            </div>
            <ul style="list-style: none; display: flex; flex-direction: column; gap: 16px;">
                <li style="display: flex; gap: 14px; align-items: flex-start;">
                    <div style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); width: 32px; height: 32px; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">1</div>
                    <div>
                        <strong style="color: var(--text-main); font-size: 0.95rem;">Surplus Kalori Terencana (Lean Bulk):</strong>
                        <p style="color: var(--text-muted); font-size: 0.88rem; margin-top: 3px;">
                            Hindari "dirty bulking". Cukup tambahkan 300–500 kcal dari kebutuhan kalori harian (TDEE). Hal ini memaksimalkan pertumbuhan otot sambil meminimalisir lemak baru.
                        </p>
                    </div>
                </li>
                <li style="display: flex; gap: 14px; align-items: flex-start;">
                    <div style="background: rgba(6, 182, 212, 0.15); color: #06b6d4; border: 1px solid rgba(6, 182, 212, 0.3); width: 32px; height: 32px; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">2</div>
                    <div>
                        <strong style="color: var(--text-main); font-size: 0.95rem;">Protein Tinggi (1.8 - 2.2g per kg BB):</strong>
                        <p style="color: var(--text-muted); font-size: 0.88rem; margin-top: 3px;">
                            Untuk berat <?= $weight ?> kg, target protein ideal Anda adalah <strong><?= round($weight * 2.0) ?> gram/hari</strong>. Sumber utama: dada ayam, telur, tempe, tahu, ikan salmon/tuna, susu whey.
                        </p>
                    </div>
                </li>
                <li style="display: flex; gap: 14px; align-items: flex-start;">
                    <div style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); width: 32px; height: 32px; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">3</div>
                    <div>
                        <strong style="color: var(--text-main); font-size: 0.95rem;">Karbohidrat Kompleks untuk Bahan Bakar:</strong>
                        <p style="color: var(--text-muted); font-size: 0.88rem; margin-top: 3px;">
                            Karbohidrat mengisi glikogen otot untuk sesi angkat beban berat. Pilihlah nasi merah, nasi putih terukur, oatmeal, kentang, dan ubi.
                        </p>
                    </div>
                </li>
                <li style="display: flex; gap: 14px; align-items: flex-start;">
                    <div style="background: rgba(139, 92, 246, 0.15); color: #8b5cf6; border: 1px solid rgba(139, 92, 246, 0.3); width: 32px; height: 32px; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">4</div>
                    <div>
                        <strong style="color: var(--text-main); font-size: 0.95rem;">Latihan Beban Progresif (Progressive Overload):</strong>
                        <p style="color: var(--text-muted); font-size: 0.88rem; margin-top: 3px;">
                            Fokus pada latihan gabungan (compound lifts) seperti Bench Press, Squat, Deadlift, dan Barbell Row. Naikkan beban atau repetisi secara bertahap setiap minggu.
                        </p>
                    </div>
                </li>
                <li style="display: flex; gap: 14px; align-items: flex-start;">
                    <div style="background: rgba(244, 63, 94, 0.15); color: #f43f5e; border: 1px solid rgba(244, 63, 94, 0.3); width: 32px; height: 32px; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">5</div>
                    <div>
                        <strong style="color: var(--text-main); font-size: 0.95rem;">Tidur Cukup (7-9 Jam Setiap Malam):</strong>
                        <p style="color: var(--text-muted); font-size: 0.88rem; margin-top: 3px;">
                            Otot tidak bertumbuh saat di gym, melainkan saat Anda beristirahat dan tidur nyenyak melalui pelepasan hormon pertumbuhan (HGH).
                        </p>
                    </div>
                </li>
            </ul>
        </div>

        <!-- Current User Profile & Recommended Routine -->
        <div>
            <!-- Latest Weight Record -->
            <div class="card" style="margin-bottom: 24px;">
                <div class="card-header">
                    <h2 class="card-title">📊 Status Fisik Terkini Anda</h2>
                    <span class="badge <?= $cat['badge'] ?>"><?= htmlspecialchars($cat['title']) ?></span>
                </div>

                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 18px; text-align: center;">
                    <div style="background: var(--bg-surface-elevated); padding: 14px; border-radius: var(--radius-md);">
                        <span style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase;">Berat Saat Ini</span>
                        <div style="font-size: 1.4rem; font-weight: 800; color: var(--text-main);"><?= number_format($weight, 1) ?> kg</div>
                    </div>
                    <div style="background: var(--bg-surface-elevated); padding: 14px; border-radius: var(--radius-md);">
                        <span style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase;">Skor BMI</span>
                        <div style="font-size: 1.4rem; font-weight: 800; color: var(--color-cyan);"><?= number_format($bmi, 2) ?></div>
                    </div>
                    <div style="background: var(--bg-surface-elevated); padding: 14px; border-radius: var(--radius-md);">
                        <span style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase;">Target Kalori</span>
                        <div style="font-size: 1.4rem; font-weight: 800; color: var(--color-primary);"><?= number_format($targetKalori) ?> kcal</div>
                    </div>
                </div>

                <div style="background: var(--bg-surface); padding: 14px 18px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); font-size: 0.88rem; color: var(--text-muted);">
                    💡 Target kenaikan berat badan ideal untuk bulking adalah <strong>0.25 - 0.5 kg per minggu</strong> agar sebagian besar kenaikan berupa jaringan otot, bukan lemak.
                </div>
            </div>

            <!-- Example Bulking Meal Plan -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">🍽️ Rekomendasi Menu Bulking Harian</h2>
                    <span class="badge badge-emerald">Sample Plan</span>
                </div>
                <div style="display: flex; flex-direction: column; gap: 12px; font-size: 0.88rem;">
                    <div style="padding-bottom: 10px; border-bottom: 1px solid var(--border-subtle);">
                        <strong style="color: var(--text-main);">Pagi (Sarapan):</strong>
                        <p style="color: var(--text-muted); margin-top: 2px;">Oatmeal 80g + 1 pisang + 3 butir telur rebus (2 putih, 1 utuh) + 1 sdm selai kacang.</p>
                    </div>
                    <div style="padding-bottom: 10px; border-bottom: 1px solid var(--border-subtle);">
                        <strong style="color: var(--text-main);">Siang (Makan Utama):</strong>
                        <p style="color: var(--text-muted); margin-top: 2px;">Nasi putih 200g + Dada ayam panggang 150g + Tempe goreng 2 potong + Sayur bayam/brokoli.</p>
                    </div>
                    <div style="padding-bottom: 10px; border-bottom: 1px solid var(--border-subtle);">
                        <strong style="color: var(--text-main);">Pre/Post Workout:</strong>
                        <p style="color: var(--text-muted); margin-top: 2px;">1 scoop Whey Protein shake + 1 buah pisang atau roti gandum isi telur.</p>
                    </div>
                    <div>
                        <strong style="color: var(--text-main);">Malam (Makan Malam):</strong>
                        <p style="color: var(--text-muted); margin-top: 2px;">Ubi rebus/kentang 200g + Ikan bakar/daging sapi 120g + Tahu putih + Sayur lalapan.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
