<?php
/**
 * Modern Food Tracking & Nutrition Logger
 * Unified food consumption management with instant search & manual entry.
 * Clean Architecture & Linear/Vercel Minimalist Aesthetic.
 */

require_once __DIR__ . '/../includes/functions.php';

$user = requireAuth();
$userId = (int)$user['id_user'];

$activeTab = 'food';
$pageTitle = "Catatan Makan • Let's Move";

$targetKalori = (int)($user['target_kalori'] > 0 ? $user['target_kalori'] : 2400);
$targetProgram = strtolower($user['program'] ?: 'bulking');

$todaySummary = NutritionService::getTodayFoodSummary(null, $userId);
$todayItems = NutritionService::getTodayFoodLogs(null, $userId);

$totalCalories = $todaySummary['total_calories'];
$totalProtein = $todaySummary['total_protein'];
$totalCarbs = $todaySummary['total_carbs'];
$totalFat = $todaySummary['total_fat'];

$isOverflow = ($totalCalories > $targetKalori);
$persenKalori = ($targetKalori > 0) ? min(100, round(($totalCalories / $targetKalori) * 100)) : 0;

include __DIR__ . '/../includes/header.php';
?>

<div class="app-container" style="padding-top: 28px; padding-bottom: 50px;">
    <!-- Page Header -->
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 class="page-title">Catatan Makanan & Asupan Nutrisi</h1>
            <p class="page-subtitle">Pantau asupan kalori dan makronutrisi harian Anda secara akurat untuk mendukung sasaran program.</p>
        </div>
        <div>
            <?php if (!empty($todayItems)): ?>
                <form action="hapussemuamakanan.php" method="POST" onsubmit="return confirm('Reset semua data makanan yang telah dicatat hari ini?');">
                    <button type="submit" class="btn btn-secondary btn-sm">
                        Reset Jurnal Hari Ini
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Daily Calorie Progress Meter -->
    <div class="card" style="margin-bottom: 24px; padding: 22px 26px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <span class="stat-label">STATUS KALORI HARIAN</span>
                <div style="font-size: 1.8rem; font-weight: 800; color: var(--text-main); font-family: var(--font-mono);">
                    <?= number_format($totalCalories, 1) ?> <span style="font-size: 1rem; color: var(--text-muted); font-weight: 500; font-family: var(--font-sans);">/ <?= number_format($targetKalori) ?> kcal</span>
                </div>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <span class="badge badge-neutral" style="text-transform: uppercase;">PROGRAM <?= htmlspecialchars($targetProgram) ?></span>
                <span class="badge <?= $isOverflow ? 'badge-rose' : 'badge-emerald' ?>" style="font-size: 0.82rem; padding: 6px 12px;">
                    <?= $isOverflow ? 'Melebihi Target' : 'Dalam Batas Target' ?>
                </span>
            </div>
        </div>

        <div class="progress-bar-container" style="height: 10px; margin-top: 14px;">
            <div class="progress-bar-fill <?= $isOverflow ? 'overflow' : '' ?>" style="width: <?= min(100, $persenKalori) ?>%;"></div>
        </div>

        <!-- Macro Stats Strip -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--border-subtle);">
            <div>
                <span style="font-size: 0.72rem; color: var(--text-dim); text-transform: uppercase; font-family: var(--font-mono);">Protein</span>
                <div style="font-size: 1.15rem; font-weight: 700; color: var(--color-cyan); font-family: var(--font-mono);"><?= number_format($totalProtein, 1) ?> g</div>
            </div>
            <div>
                <span style="font-size: 0.72rem; color: var(--text-dim); text-transform: uppercase; font-family: var(--font-mono);">Karbohidrat</span>
                <div style="font-size: 1.15rem; font-weight: 700; color: var(--color-amber); font-family: var(--font-mono);"><?= number_format($totalCarbs, 1) ?> g</div>
            </div>
            <div>
                <span style="font-size: 0.72rem; color: var(--text-dim); text-transform: uppercase; font-family: var(--font-mono);">Lemak</span>
                <div style="font-size: 1.15rem; font-weight: 700; color: #fb7185; font-family: var(--font-mono);"><?= number_format($totalFat, 1) ?> g</div>
            </div>
            <div>
                <span style="font-size: 0.72rem; color: var(--text-dim); text-transform: uppercase; font-family: var(--font-mono);">Total Entri</span>
                <div style="font-size: 1.15rem; font-weight: 700; color: var(--text-main); font-family: var(--font-mono);"><?= count($todayItems) ?> porsi</div>
            </div>
        </div>
    </div>

    <!-- 2 Column Layout: Search Food & Consumed Items -->
    <div class="layout-2col">
        <!-- Search & Add Food Section -->
        <div>
            <div class="card" style="margin-bottom: 24px;">
                <div class="card-header">
                    <h2 class="card-title">Cari Database Makanan</h2>
                    <span class="badge badge-blue">API Nutrisi</span>
                </div>

                <div class="form-group">
                    <label for="food-search" class="form-label">Nama Bahan / Makanan</label>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" id="food-search" class="form-control" placeholder="Contoh: telur rebus, banana, dada ayam..." autocomplete="off">
                        <button type="button" class="btn btn-primary" onclick="searchFood()">Cari</button>
                    </div>
                    <span style="font-size: 0.78rem; color: var(--text-dim); margin-top: 6px; display: block;">
                        Mendukung pencarian CalorieNinjas dan katalog kuliner lokal.
                    </span>
                </div>

                <!-- Instant Search Results -->
                <div id="search-results" style="margin-top: 14px;"></div>
            </div>

            <!-- Manual Custom Entry Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title" style="font-size: 1.05rem;">Tambah Makanan Manual</h3>
                    <span class="badge badge-neutral">Custom</span>
                </div>
                <form id="customFoodForm" onsubmit="handleManualAddFood(event)">
                    <div class="form-group">
                        <label class="form-label" for="manualName">Nama Makanan</label>
                        <input type="text" id="manualName" class="form-control" placeholder="Contoh: Nasi Goreng Spesial" required>
                    </div>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label" for="manualServing">Porsi (gram)</label>
                            <input type="number" id="manualServing" class="form-control" placeholder="100" value="100" min="1" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="manualCalories">Kalori (kcal)</label>
                            <input type="number" step="0.1" id="manualCalories" class="form-control" placeholder="250" required>
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label" for="manualProtein">Protein (g)</label>
                            <input type="number" step="0.1" id="manualProtein" class="form-control" placeholder="12" value="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="manualCarbs">Karbohidrat (g)</label>
                            <input type="number" step="0.1" id="manualCarbs" class="form-control" placeholder="30" value="0">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-secondary btn-block" style="margin-top: 6px;">
                        + Simpan Makanan Custom
                    </button>
                </form>
            </div>
        </div>

        <!-- Consumed Food List Table -->
        <div>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Jurnal Makanan Hari Ini</h2>
                    <span class="badge badge-blue"><?= count($todayItems) ?> Item</span>
                </div>

                <?php if (!empty($todayItems)): ?>
                    <div class="table-responsive">
                        <table class="data-table" id="foodTable">
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
                                <?php foreach ($todayItems as $item): ?>
                                    <tr id="food-row-<?= $item['id_hitung'] ?>">
                                        <td><strong><?= htmlspecialchars($item['makanan']) ?></strong></td>
                                        <td><span class="tabular-nums"><?= number_format((float)$item['berat'], 0) ?></span> g</td>
                                        <td><span style="font-weight: 700; color: var(--color-primary); font-family: var(--font-mono);"><?= number_format((float)$item['kalori'], 1) ?></span> kcal</td>
                                        <td><span style="font-family: var(--font-mono);"><?= number_format((float)$item['protein'], 1) ?></span> g</td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm" onclick="deleteFoodItem(<?= $item['id_hitung'] ?>, this)">
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
                        <p style="font-weight: 600; color: var(--text-main); margin-bottom: 6px;">Belum ada makanan yang dicatat hari ini</p>
                        <p style="font-size: 0.88rem; color: var(--text-dim);">Gunakan kolom pencarian di sebelah kiri untuk menambahkan menu makanan Anda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="../js/scriptt.js"></script>
<script>
async function deleteFoodItem(id, btn) {
    if (!confirm('Hapus makanan ini dari daftar hari ini?')) return;
    try {
        const res = await CalorieTrackerApp.deleteFood(id);
        if (res.success) {
            btn.closest('tr').remove();
            showToast('Makanan berhasil dihapus.', 'success');
            setTimeout(() => window.location.reload(), 450);
        } else {
            showToast(res.error || 'Gagal menghapus.', 'error');
        }
    } catch (e) {
        showToast('Koneksi terganggu.', 'error');
    }
}

async function handleManualAddFood(e) {
    e.preventDefault();
    const name = document.getElementById('manualName').value.trim();
    const serving = parseFloat(document.getElementById('manualServing').value) || 100;
    const calories = parseFloat(document.getElementById('manualCalories').value) || 0;
    const protein = parseFloat(document.getElementById('manualProtein').value) || 0;
    const carbs = parseFloat(document.getElementById('manualCarbs').value) || 0;

    if (!name || calories <= 0) {
        showToast('Harap isi nama dan kalori makanan.', 'error');
        return;
    }

    try {
        const res = await CalorieTrackerApp.addFood({
            name: name,
            serving_size: serving,
            calories: calories,
            protein: protein,
            carbohydrates: carbs,
            fat: 0
        });
        if (res.success) {
            showToast('Makanan berhasil dicatat.', 'success');
            setTimeout(() => window.location.reload(), 450);
        } else {
            showToast(res.error || 'Gagal menambahkan makanan.', 'error');
        }
    } catch (err) {
        showToast(err.message || 'Gagal terhubung ke server.', 'error');
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
