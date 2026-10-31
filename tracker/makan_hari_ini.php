<?php
/**
 * Modern Food Tracking & Nutrition Logger
 * Unified food consumption management with instant search & manual entry.
 */

require_once __DIR__ . '/../includes/functions.php';

$activeTab = 'food';
$pageTitle = "Catatan Makan Hari Ini • Let's Move";

$latestLog = getLatestWeightLog();
$targetKalori = $latestLog ? (int)$latestLog['kalori_harian'] : 2500;
$targetProgram = $latestLog['target_program'] ?? 'bulking';

$todaySummary = getTodayFoodSummary();
$todayItems = getTodayFoodLogs();

$totalCalories = $todaySummary['total_calories'];
$totalProtein = $todaySummary['total_protein'];
$totalCarbs = $todaySummary['total_carbs'];
$totalFat = $todaySummary['total_fat'];

$isOverflow = ($totalCalories > $targetKalori);
$persenKalori = ($targetKalori > 0) ? min(100, round(($totalCalories / $targetKalori) * 100)) : 0;

include __DIR__ . '/../includes/header.php';
?>

<div class="app-container" style="padding-top: 28px; padding-bottom: 50px;">
    <!-- Header -->
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 class="page-title">🥗 Catatan Makanan & Nutrisi Hari Ini</h1>
            <p class="page-subtitle">Pantau asupan kalori dan makronutrisi harian Anda secara akurat untuk mendukung target tubuh.</p>
        </div>
        <div>
            <form action="hapussemuamakanan.php" method="POST" onsubmit="return confirm('Reset semua data makanan yang telah dicatat hari ini?');">
                <button type="submit" class="btn btn-secondary btn-sm">
                    🔄 Saya Belum Makan Hari Ini (Reset)
                </button>
            </form>
        </div>
    </div>

    <!-- Daily Calorie Progress Meter -->
    <div class="card" style="margin-bottom: 24px; padding: 22px 26px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <span class="stat-label">STATUS KALORI HARIAN</span>
                <div style="font-size: 1.8rem; font-weight: 800; color: var(--text-main);">
                    <?= number_format($totalCalories, 1) ?> <span style="font-size: 1rem; color: var(--text-muted); font-weight: 500;">/ <?= number_format($targetKalori) ?> kcal</span>
                </div>
            </div>
            <div style="display: flex; gap: 12px; align-items: center;">
                <span class="badge badge-blue" style="text-transform: uppercase;">PROGRAM <?= htmlspecialchars($targetProgram) ?></span>
                <span class="badge <?= $isOverflow ? 'badge-rose' : 'badge-emerald' ?>" style="font-size: 0.82rem; padding: 6px 12px;">
                    <?= $isOverflow ? '⚠️ Melebihi Batas Kalori' : '✓ Dalam Batas Aman' ?>
                </span>
            </div>
        </div>

        <div class="progress-bar-container" style="height: 12px; margin-top: 14px;">
            <div class="progress-bar-fill <?= $isOverflow ? 'overflow' : '' ?>" style="width: <?= min(100, $persenKalori) ?>%;"></div>
        </div>

        <!-- Macro Stats Strip -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--border-subtle);">
            <div>
                <span style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase;">Protein</span>
                <div style="font-size: 1.15rem; font-weight: 700; color: var(--color-cyan);"><?= number_format($totalProtein, 1) ?> g</div>
            </div>
            <div>
                <span style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase;">Karbohidrat</span>
                <div style="font-size: 1.15rem; font-weight: 700; color: var(--color-amber);"><?= number_format($totalCarbs, 1) ?> g</div>
            </div>
            <div>
                <span style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase;">Lemak</span>
                <div style="font-size: 1.15rem; font-weight: 700; color: #fb7185;"><?= number_format($totalFat, 1) ?> g</div>
            </div>
            <div>
                <span style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase;">Total Item</span>
                <div style="font-size: 1.15rem; font-weight: 700; color: var(--text-main);"><?= count($todayItems) ?> porsi</div>
            </div>
        </div>
    </div>

    <!-- 2 Column Layout: Search Food & Consumed Items -->
    <div class="layout-2col">
        <!-- Search & Add Food Section -->
        <div>
            <div class="card" style="margin-bottom: 24px;">
                <div class="card-header">
                    <h2 class="card-title">🔍 Cari & Tambah Makanan</h2>
                    <span class="badge badge-emerald">Live Nutrition</span>
                </div>

                <div class="form-group">
                    <label for="food-search" class="form-label">Nama Makanan / Minuman</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="food-search" class="form-control" placeholder="Contoh: banana, chicken breast, nasi putih, telur..." autocomplete="off">
                        <button type="button" class="btn btn-primary" onclick="searchFood()">Cari</button>
                    </div>
                    <span style="font-size: 0.78rem; color: var(--text-dim); margin-top: 6px; display: block;">
                        💡 Mendukung pencarian CalorieNinjas dan katalog kuliner Indonesia otomatis.
                    </span>
                </div>

                <!-- Instant Search Results -->
                <div id="search-results" style="margin-top: 14px;"></div>
            </div>

            <!-- Manual Custom Entry Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title" style="font-size: 1.05rem;">📝 Tambah Manual (Custom Meal)</h3>
                </div>
                <form id="customFoodForm" onsubmit="handleManualAddFood(event)">
                    <div class="form-group">
                        <label class="form-label">Nama Makanan</label>
                        <input type="text" id="manualName" class="form-control" placeholder="Contoh: Nasi Goreng Spesial" required>
                    </div>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Porsi (gram)</label>
                            <input type="number" id="manualServing" class="form-control" placeholder="100" value="100" min="1" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kalori (kcal)</label>
                            <input type="number" step="0.1" id="manualCalories" class="form-control" placeholder="250" required>
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Protein (g)</label>
                            <input type="number" step="0.1" id="manualProtein" class="form-control" placeholder="12" value="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Karbohidrat (g)</label>
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
                    <h2 class="card-title">🍽️ Makanan Yang Dikonsumsi Hari Ini</h2>
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
                                        <td><?= number_format((float)$item['berat'], 0) ?> g</td>
                                        <td><span style="font-weight: 700; color: var(--color-primary);"><?= number_format((float)$item['kalori'], 1) ?></span> kcal</td>
                                        <td><?= number_format((float)$item['protein'], 1) ?> g</td>
                                        <td>
                                            <button class="btn btn-danger btn-sm" onclick="deleteFoodItem(<?= $item['id_hitung'] ?>, this)">
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 36px 20px; color: var(--text-muted);">
                        <div style="font-size: 2.2rem; margin-bottom: 10px;">🍽️</div>
                        <p style="font-weight: 600; color: var(--text-main); margin-bottom: 6px;">Belum ada makanan yang dicatat hari ini</p>
                        <p style="font-size: 0.88rem;">Gunakan kolom pencarian di sebelah kiri untuk menambahkan menu makanan Anda.</p>
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
        const res = await fetch('../api/food.php?action=delete&id=' + id, { method: 'POST' });
        const data = await res.json();
        if (data.success) {
            btn.closest('tr').remove();
            showToast('Makanan berhasil dihapus.', 'success');
            setTimeout(() => window.location.reload(), 600);
        } else {
            showToast(data.error || 'Gagal menghapus.', 'error');
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
        alert('Harap isi nama dan kalori makanan.');
        return;
    }

    try {
        const res = await fetch('../api/food.php?action=add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                name: name,
                serving_size: serving,
                calories: calories,
                protein: protein,
                carbs: carbs
            })
        });
        const data = await res.json();
        if (data.success) {
            showToast('Makanan custom berhasil ditambahkan!', 'success');
            setTimeout(() => window.location.reload(), 600);
        } else {
            showToast(data.error || 'Gagal menambahkan makanan.', 'error');
        }
    } catch (err) {
        showToast('Gagal terhubung ke server.', 'error');
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
