/**
 * Food Nutrition Tracker Client Service (Clean Architecture)
 * Handles food searching, logging, and real-time state synchronization.
 */

const CalorieTrackerApp = (function() {
    // Dynamically resolve API root whether caller is in / or /tracker/
    const isSubdir = window.location.pathname.includes('/tracker/');
    const apiBase = isSubdir ? '../api/' : 'api/';

    async function search(query) {
        if (!query || !query.trim()) {
            showToast('Ketik nama makanan untuk mencari.', 'error');
            return [];
        }

        const res = await fetch(`${apiBase}food.php?action=search&q=${encodeURIComponent(query.trim())}`);
        if (!res.ok) {
            throw new Error('Gagal memuat data nutrisi.');
        }
        const data = await res.json();
        return data.items || [];
    }

    async function addFood(payload) {
        const res = await fetch(`${apiBase}food.php?action=add`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            throw new Error(err.error || 'Gagal menambahkan makanan.');
        }

        return await res.json();
    }

    async function deleteFood(id) {
        const res = await fetch(`${apiBase}food.php?action=delete&id=${id}`, {
            method: 'POST'
        });
        return await res.json();
    }

    async function addWorkout(payload) {
        const res = await fetch(`${apiBase}exercise.php?action=add`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            throw new Error(err.error || 'Gagal mencatat latihan.');
        }

        return await res.json();
    }

    async function deleteWorkout(id) {
        const res = await fetch(`${apiBase}exercise.php?action=delete&id=${id}`, {
            method: 'POST'
        });
        return await res.json();
    }

    return {
        search,
        addFood,
        deleteFood,
        addWorkout,
        deleteWorkout
    };
})();

// Global Search Food Trigger
async function searchFood() {
    const searchInput = document.getElementById('food-search');
    const resultsDiv = document.getElementById('search-results');
    const query = searchInput ? searchInput.value.trim() : '';

    if (!query) {
        showToast('Masukkan kata kunci makanan (misal: telur, dada ayam, nasi).', 'error');
        return;
    }

    if (resultsDiv) {
        resultsDiv.innerHTML = '<div style="padding: 16px; text-align: center; color: var(--text-dim); font-size: 0.88rem;">Mencari data nutrisi...</div>';
    }

    try {
        const items = await CalorieTrackerApp.search(query);
        displaySearchResults(items);
    } catch (e) {
        if (resultsDiv) {
            resultsDiv.innerHTML = `<div style="padding: 12px; color: var(--color-rose); font-size: 0.85rem;">${escapeHtml(e.message)}</div>`;
        }
    }
}

// Display clean, high-end search result cards (Zero childish emoji stickers)
function displaySearchResults(items) {
    const resultsDiv = document.getElementById('search-results');
    if (!resultsDiv) return;
    resultsDiv.innerHTML = '';

    if (!items || items.length === 0) {
        resultsDiv.innerHTML = '<div style="padding: 16px; text-align: center; color: var(--text-dim); font-size: 0.88rem;">Makanan tidak ditemukan. Coba gunakan istilah umum atau menu masakan lokal.</div>';
        return;
    }

    items.forEach(item => {
        const card = document.createElement('div');
        card.className = 'food-result-card';

        const cal = parseFloat(item.calories) || 0;
        const prot = parseFloat(item.protein_g) || 0;
        const carbs = parseFloat(item.carbohydrates_total_g) || 0;
        const fat = parseFloat(item.fat_total_g) || 0;
        const serving = parseFloat(item.serving_size_g) || 100;

        card.innerHTML = `
            <div style="flex: 1;">
                <div style="font-weight: 600; font-size: 0.95rem; color: var(--text-main); margin-bottom: 3px;">
                    ${escapeHtml(item.name)}
                </div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap; font-size: 0.78rem; color: var(--text-muted);">
                    <span class="pill-metric">${cal.toFixed(1)} kcal</span>
                    <span class="pill-metric">Protein: ${prot.toFixed(1)}g</span>
                    <span class="pill-metric">Porsi: ${serving}g</span>
                </div>
            </div>
            <button type="button" class="btn btn-primary btn-sm" onclick="handleAddFoodClick('${escapeJs(item.name)}', ${serving}, ${cal}, ${prot}, ${carbs}, ${fat})">
                + Tambah
            </button>
        `;

        resultsDiv.appendChild(card);
    });
}

// Handle Add Food Click
async function handleAddFoodClick(name, servingSize, calories, protein, carbs = 0, fat = 0) {
    try {
        const res = await CalorieTrackerApp.addFood({
            name: name,
            serving_size: servingSize,
            calories: calories,
            protein: protein,
            carbohydrates: carbs,
            fat: fat
        });

        if (res.success) {
            showToast(`"${name}" berhasil ditambahkan ke catatan hari ini.`, 'success');
            
            // Clean up search
            const searchInput = document.getElementById('food-search');
            const resultsDiv = document.getElementById('search-results');
            if (searchInput) searchInput.value = '';
            if (resultsDiv) resultsDiv.innerHTML = '';

            // Reload to synchronize all dashboard KPIs and tables smoothly
            setTimeout(() => {
                window.location.reload();
            }, 450);
        }
    } catch (err) {
        showToast(err.message || 'Gagal menyimpan makanan.', 'error');
    }
}

// Compatibility wrapper for legacy addFood call
function addFood(name, servingSize, calories, protein, carbs = 0, fat = 0) {
    return handleAddFoodClick(name, servingSize, calories, protein, carbs, fat);
}

// Escape helpers
function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function escapeJs(str) {
    if (!str) return '';
    return str.replace(/['"\\]/g, '\\$&');
}

// Global Toast Notification Helper
function showToast(message, type = 'success') {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `<span>${type === 'success' ? '✓' : '!'}</span> <span>${escapeHtml(message)}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        toast.style.transition = 'all 0.25s ease';
        setTimeout(() => toast.remove(), 260);
    }, 3200);
}

// Support Enter key in food search
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('food-search');
    if (input) {
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchFood();
            }
        });
    }
});
