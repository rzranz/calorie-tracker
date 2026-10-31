<?php
/**
 * Diet & Fat Loss Program Guide
 */

require_once __DIR__ . '/../includes/functions.php';

$user = requireAuth();
$userId = (int)$user['id_user'];

$activeTab = 'diet';
$pageTitle = "Program Diet & Fat Loss • Let's Move";

$weight = (float)($user['berat'] > 0 ? $user['berat'] : 80);
$height = (float)($user['tinggi'] > 0 ? $user['tinggi'] : 175);
$bmi = (float)($user['bmi'] > 0 ? $user['bmi'] : 26.1);
$cat = getBMICategory($bmi);
$targetKalori = (int)($user['target_kalori'] > 0 ? $user['target_kalori'] : 1900);

include __DIR__ . '/../includes/header.php';
?>

<div class="app-container" style="padding-top: 28px; padding-bottom: 50px;">
    <!-- Page Header -->
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 class="page-title">Program Diet & Fat Loss Sehat</h1>
            <p class="page-subtitle">Metode defisit kalori berbasis bukti ilmiah untuk memangkas lemak tubuh tanpa mengorbankan metabolisme.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="makan_hari_ini.php" class="btn btn-primary btn-sm">+ Catat Makanan Diet</a>
            <a href="olahraga.html" class="btn btn-secondary btn-sm">+ Catat Olahraga Kardio</a>
        </div>
    </div>

    <!-- Hero Card with Diet Photo -->
    <div class="card" style="margin-bottom: 30px; overflow: hidden; padding: 0;">
        <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; align-items: center; min-height: 280px;">
            <div style="padding: 32px 36px;">
                <span class="badge badge-blue" style="margin-bottom: 12px;">SUSTAINABLE FAT LOSS PROTOCOL</span>
                <h2 style="font-size: 1.7rem; font-weight: 800; color: var(--text-main); line-height: 1.3; margin-bottom: 12px;">
                    Defisit Kalori Moderat dengan Nutrisi Padat Gizi
                </h2>
                <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 20px;">
                    Diet yang berhasil bukanlah kelaparan ekstrem, melainkan pengaturan defisit energi moderat (300–500 kcal di bawah TDEE) dengan asupan protein tinggi untuk mempertahankan massa otot dan metabolisme tubuh.
                </p>
                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <a href="makan_hari_ini.php" class="btn btn-primary btn-sm">Mulai Catat Nutrisi</a>
                    <a href="../index.php#calculator" class="btn btn-secondary btn-sm">Hitung Ulang TDEE</a>
                </div>
            </div>
            <div style="height: 100%; min-height: 240px; background: url('../img/diet.jpg') center/cover no-repeat; border-left: 1px solid var(--border-subtle);"></div>
        </div>
    </div>

    <!-- 2 Column Layout: Principles & Status -->
    <div class="layout-2col">
        <!-- Core Diet Principles -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">5 Strategi Utama Program Diet</h2>
            </div>
            <ul style="list-style: none; display: flex; flex-direction: column; gap: 16px;">
                <li style="display: flex; gap: 14px; align-items: flex-start;">
                    <div style="background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); width: 32px; height: 32px; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">1</div>
                    <div>
                        <strong style="color: var(--text-main); font-size: 0.95rem;">Defisit Kalori Moderat & Konsisten:</strong>
                        <p style="color: var(--text-muted); font-size: 0.88rem; margin-top: 3px;">
                            Kurangi 400–500 kcal dari TDEE harian. Jangan memangkas kalori di bawah 1,200 kcal karena dapat menurunkan laju metabolisme basal dan memicu kehilangan otot.
                        </p>
                    </div>
                </li>
                <li style="display: flex; gap: 14px; align-items: flex-start;">
                    <div style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); width: 32px; height: 32px; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">2</div>
                    <div>
                        <strong style="color: var(--text-main); font-size: 0.95rem;">Protein Tinggi Pelindung Otot:</strong>
                        <p style="color: var(--text-muted); font-size: 0.88rem; margin-top: 3px;">
                            Konsumsi 1.6–2.0 gram protein per kg berat badan. Protein memiliki efek termis makanan (TEF) tertinggi dan memberikan rasa kenyang lebih lama.
                        </p>
                    </div>
                </li>
                <li style="display: flex; gap: 14px; align-items: flex-start;">
                    <div style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); width: 32px; height: 32px; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">3</div>
                    <div>
                        <strong style="color: var(--text-main); font-size: 0.95rem;">Tingkatkan Serat & Sayuran Hijau:</strong>
                        <p style="color: var(--text-muted); font-size: 0.88rem; margin-top: 3px;">
                            Penuhi piring makan dengan sayuran rendah kalori bervolume tinggi (brokoli, bayam, selada, mentimun) untuk mengontrol rasa lapar dan menstabilkan gula darah.
                        </p>
                    </div>
                </li>
                <li style="display: flex; gap: 14px; align-items: flex-start;">
                    <div style="background: rgba(139, 92, 246, 0.15); color: #8b5cf6; border: 1px solid rgba(139, 92, 246, 0.3); width: 32px; height: 32px; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">4</div>
                    <div>
                        <strong style="color: var(--text-main); font-size: 0.95rem;">Kombinasi Latihan Beban + Kardio (HIIT/LISS):</strong>
                        <p style="color: var(--text-muted); font-size: 0.88rem; margin-top: 3px;">
                            Tetap lakukan latihan beban agar tubuh mempertahankan jaringan otot, lalu tambahkan jalan cepat 8.000–10.000 langkah atau lari santai untuk meningkatkan pembakaran kalori.
                        </p>
                    </div>
                </li>
                <li style="display: flex; gap: 14px; align-items: flex-start;">
                    <div style="background: rgba(6, 182, 212, 0.15); color: #06b6d4; border: 1px solid rgba(6, 182, 212, 0.3); width: 32px; height: 32px; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">5</div>
                    <div>
                        <strong style="color: var(--text-main); font-size: 0.95rem;">Hidrasi Cukup (2.5 - 3 Liter Air Putih):</strong>
                        <p style="color: var(--text-muted); font-size: 0.88rem; margin-top: 3px;">
                            Rasa haus seringkali disalahartikan tubuh sebagai sinyal lapar. Minum segelas air 15 menit sebelum makan dapat membantu mengendalikan porsi makan.
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
                    <h2 class="card-title">Status Fisik Terkini</h2>
                    <span class="badge <?= $cat['badge'] ?>"><?= htmlspecialchars($cat['title']) ?></span>
                </div>

                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 18px; text-align: center;">
                    <div style="background: var(--bg-surface-elevated); padding: 14px; border-radius: var(--radius-md);">
                        <span style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase;">Berat Saat Ini</span>
                        <div style="font-size: 1.4rem; font-weight: 800; color: var(--text-main); font-family: var(--font-mono);"><?= number_format($weight, 1) ?> kg</div>
                    </div>
                    <div style="background: var(--bg-surface-elevated); padding: 14px; border-radius: var(--radius-md);">
                        <span style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase;">Skor BMI</span>
                        <div style="font-size: 1.4rem; font-weight: 800; color: var(--color-cyan); font-family: var(--font-mono);"><?= number_format($bmi, 2) ?></div>
                    </div>
                    <div style="background: var(--bg-surface-elevated); padding: 14px; border-radius: var(--radius-md);">
                        <span style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase;">Target Kalori</span>
                        <div style="font-size: 1.4rem; font-weight: 800; color: var(--color-primary); font-family: var(--font-mono);"><?= number_format($targetKalori) ?> kcal</div>
                    </div>
                </div>

                <div style="background: var(--bg-surface); padding: 14px 18px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); font-size: 0.88rem; color: var(--text-muted);">
                    Target penurunan berat badan yang aman dan berkelanjutan adalah <strong>0.5 - 1.0 kg per minggu</strong>.
                </div>
            </div>

            <!-- Example Diet Meal Plan -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Rekomendasi Menu Diet Sehari-hari</h2>
                    <span class="badge badge-blue">Low Calorie Dense</span>
                </div>
                <div style="display: flex; flex-direction: column; gap: 12px; font-size: 0.88rem;">
                    <div style="padding-bottom: 10px; border-bottom: 1px solid var(--border-subtle);">
                        <strong style="color: var(--text-main);">Pagi (Sarapan Rendah Kalori):</strong>
                        <p style="color: var(--text-muted); margin-top: 2px;">2 butir telur rebus (1 utuh, 1 putih) + 1 buah apel potong + secangkir teh hijau tanpa gula.</p>
                    </div>
                    <div style="padding-bottom: 10px; border-bottom: 1px solid var(--border-subtle);">
                        <strong style="color: var(--text-main);">Siang (Makan Utama):</strong>
                        <p style="color: var(--text-muted); margin-top: 2px;">Nasi merah/putih 100g + Dada ayam panggang 130g + Semangkuk sayur brokoli dan wortel rebus.</p>
                    </div>
                    <div style="padding-bottom: 10px; border-bottom: 1px solid var(--border-subtle);">
                        <strong style="color: var(--text-main);">Sore (Camilan Sehat):</strong>
                        <p style="color: var(--text-muted); margin-top: 2px;">Yogurt plain rendah lemak 100g atau segenggam kecil kacang almond panggang.</p>
                    </div>
                    <div>
                        <strong style="color: var(--text-main);">Malam (Makan Malam Ringan):</strong>
                        <p style="color: var(--text-muted); margin-top: 2px;">Ikan gurame/nila bakar tanpa santan 120g + Tahu rebus + Salad selada dan tomat segar dengan dressing lemon.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
