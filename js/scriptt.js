const API_KEY = 'pCjiAjuWkNTVfyqKocoaTQ==Muah9DT1TKQXZchH';
let totalCalories = 0;
let totalProteins = 0;

async function searchFood() {
    const query = document.getElementById('food-search').value.trim();
    if (!query) {
        alert('Please enter a food name to search.');
        return;
    }

    const response = await fetch(`https://api.calorieninjas.com/v1/nutrition?query=${query}`, {
        headers: {
            'X-Api-Key': API_KEY
        }
    });

    if (!response.ok) {
        alert('Failed to fetch food data. Please try again later.');
        return;
    }

    const data = await response.json();
    displaySearchResults(data.items);
}

function displaySearchResults(items) {
    const resultsDiv = document.getElementById('search-results');
    resultsDiv.innerHTML = ''; // Clear previous results

    if (items.length === 0) {
        resultsDiv.textContent = 'No food items found.';
        return;
    }

    items.forEach(item => {
        const foodDiv = document.createElement('div');
        foodDiv.className = 'food-item';
        foodDiv.innerHTML = `
            <p><strong>${item.name}</strong></p>
            <p>Calories: ${item.calories} kcal, Proteins: ${item.protein_g} g</p>
            <button onclick="addFood('${item.name}', ${item.serving_size_g}, ${item.calories}, ${item.protein_g})">Add</button>
        `;
        resultsDiv.appendChild(foodDiv);
    });
}

function addFood(name, servingSize, calories, protein) {
    const tableBody = document.querySelector('#foodTable tbody');
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td>${name}</td>
        <td>${servingSize} g</td>
        <td>${calories.toFixed(2)} kcal</td>
        <td>${protein.toFixed(2)} g</td>
    `;
    tableBody.appendChild(newRow);

    totalCalories += calories;
    totalProteins += protein;

    document.getElementById('totalCalories').textContent = totalCalories.toFixed(2);
    document.getElementById('totalProteins').textContent = totalProteins.toFixed(2);

    // Debugging: Cek data yang dikirim ke backend
    console.log({
        'name': name,
        'serving_size': servingSize,
        'calories': calories,
        'protein': protein
    });

    // Kirim data ke backend
    fetch('addfood.php', {
        method: 'POST',
        body: new URLSearchParams({
            'name': name,
            'serving_size': servingSize,
            'calories': calories,
            'protein': protein
        })
    })
        .then(response => response.text())
        .then(data => {
            console.log(data); // Cek respons dari server
        })
        .catch(error => {
            console.error('Error:', error);
        });

    // Clear search results
    document.getElementById('search-results').innerHTML = '';
    document.getElementById('food-search').value = '';
}


