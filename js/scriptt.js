/**
 * Food Calorie Tracker Client Script
 * Handles real-time search, nutrition calculations, and food additions.
 */

const API_KEY = 'pCjiAjuWkNTVfyqKocoaTQ==Muah9DT1TKQXZchH';
let totalCalories = 0;
let totalProteins = 0;

// Search for food nutrition
async function searchFood() {
    const searchInput = document.getElementById('food-search');
    const query = searchInput ? searchInput.value.trim() : '';
    const resultsDiv = document.getElementById('search-results');

    if (!query) {
        alert('Masukkan nama makanan (misal: "banana", "chicken breast", "nasi", "telur").');
        return;
    }

    if (resultsDiv) {
        resultsDiv.innerHTML = '<div class="loading-spinner">Mencari data nutrisi...</div>';
    }

    let items = [];

    // Attempt backend proxy first (supports both CalorieNinjas + local database)
    try {
        const response = await fetch(`../api/food.php?action=search&q=${encodeURIComponent(query)}`);
        if (response.ok) {
            const data = await response.json();
            if (data.success && Array.isArray(data.items)) {
                items = data.items;
            }
        }
    } catch (e) {
        console.warn('Backend proxy fetch failed, attempting direct API:', e);
    }

    // Direct fallback to CalorieNinjas if backend returned empty
    if (items.length === 0) {
        try {
            const directRes = await fetch(`https://api.calorieninjas.com/v1/nutrition?query=${encodeURIComponent(query)}`, {
                headers: { 'X-Api-Key': API_KEY }
            });
            if (directRes.ok) {
                const directData = await directRes.json();
                items = (directData.items || []).map(i => ({
                    name: i.name,
                    serving_size_g: i.serving_size_g,
                    calories: i.calories,
                    protein_g: i.protein_g,
                    carbohydrates_total_g: i.carbohydrates_total_g,
                    fat_total_g: i.fat_total_g
                }));
            }
        } catch (err) {
            console.error('Direct API failed too:', err);
        }
    }

    displaySearchResults(items);
}

// Display results in container
function displaySearchResults(items) {
    const resultsDiv = document.getElementById('search-results');
    if (!resultsDiv) return;
    resultsDiv.innerHTML = '';

    if (!items || items.length === 0) {
        resultsDiv.innerHTML = '<p class="text-muted">Makanan tidak ditemukan. Coba gunakan kata kunci bahasa Inggris atau pilih menu umum.</p>';
        return;
    }

    items.forEach(item => {
        const foodDiv = document.createElement('div');
        foodDiv.className = 'food-item';
        
        const cal = parseFloat(item.calories) || 0;
        const prot = parseFloat(item.protein_g) || 0;
        const serving = parseFloat(item.serving_size_g) || 100;
        const carbs = parseFloat(item.carbohydrates_total_g) || 0;
        const fat = parseFloat(item.fat_total_g) || 0;

        foodDiv.innerHTML = `
            <div class="food-meta">
                <p class="food-name"><strong>${escapeHtml(item.name)}</strong></p>
                <p class="food-nutrients">
                    <span class="badge badge-cal">${cal.toFixed(1)} kcal</span>
                    <span class="badge badge-prot">Prot: ${prot.toFixed(1)}g</span>
                    <span class="badge badge-serving">Porsi: ${serving}g</span>
                </p>
            </div>
            <button class="btn btn-sm btn-primary" onclick="addFood('${escapeJsString(item.name)}', ${serving}, ${cal}, ${prot}, ${carbs}, ${fat})">
                + Tambah
            </button>
        `;
        resultsDiv.appendChild(foodDiv);
    });
}

// Add food to consumption table and persist to database
async function addFood(name, servingSize, calories, protein, carbs = 0, fat = 0) {
    const tableBody = document.querySelector('#foodTable tbody');
    if (tableBody) {
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td><strong>${escapeHtml(name)}</strong></td>
            <td>${servingSize} g</td>
            <td>${calories.toFixed(1)} kcal</td>
            <td>${protein.toFixed(1)} g</td>
        `;
        tableBody.appendChild(newRow);
    }

    totalCalories += calories;
    totalProteins += protein;

    const calEl = document.getElementById('totalCalories');
    const protEl = document.getElementById('totalProteins');
    if (calEl) calEl.textContent = totalCalories.toFixed(1);
    if (protEl) protEl.textContent = totalProteins.toFixed(1);

    // Persist via modern API or addfood.php
    try {
        await fetch('addfood.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                'name': name,
                'serving_size': servingSize,
                'calories': calories,
                'protein': protein,
                'carbs': carbs,
                'fat': fat
            })
        });
    } catch (error) {
        console.error('Error saving food record:', error);
    }

    // Clear search results and input
    const resDiv = document.getElementById('search-results');
    const searchInp = document.getElementById('food-search');
    if (resDiv) resDiv.innerHTML = '<div class="alert alert-success">Makanan berhasil ditambahkan ke daftar harian!</div>';
    setTimeout(() => { if (resDiv) resDiv.innerHTML = ''; }, 2500);
    if (searchInp) searchInp.value = '';
}

// Helper utilities
function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function escapeJsString(str) {
    return str.replace(/['"\\]/g, '\\$&');
}

// Support Enter key on food search input
document.addEventListener('DOMContentLoaded', () => {
    const foodSearchInput = document.getElementById('food-search');
    if (foodSearchInput) {
        foodSearchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchFood();
            }
        });
    }
});
