/**
 * Exercise Calculator & Activity Logger
 * Calculates calorie burn and persists logs to the backend.
 */

const calorieBurnRate = {
  "Push Up": 0.45,       // kalori per repetisi
  "Sit Up": 0.40,        // kalori per repetisi
  "Back Up": 0.35,       // kalori per repetisi
  "Squat": 0.50,         // kalori per repetisi
  "Pull Up": 1.00,       // kalori per repetisi
  "Lari": 10.0,          // kalori per menit
  "Renang": 9.0,         // kalori per menit
  "Barble": 6.5,         // kalori per menit (angkat beban)
  "Angkat Beban": 6.5,   // kalori per menit
  "Bersepeda": 8.5,      // kalori per menit
  "Lompat Tali": 11.0,   // kalori per menit
  "Jalan Kaki": 4.0      // kalori per menit
};

let totalCalories = 0;

// Update total display
function updateTotalCalories(newCalories) {
  totalCalories += newCalories;
  const totalEl = document.getElementById('totalKalori');
  if (totalEl) {
    totalEl.textContent = `${totalCalories.toFixed(1)} kalori`;
  }
}

// Render an exercise card in DOM
function renderExerciseCard(exercise, duration, repetitions, caloriesBurned, id = null) {
  const exerciseList = document.getElementById('olahraga-cards');
  if (!exerciseList) return;

  const newCard = document.createElement('div');
  newCard.classList.add('card', 'exercise-card-item');
  if (id) newCard.dataset.id = id;

  let detailsHtml = '';
  if (duration > 0) detailsHtml += `<p>Durasi: <strong>${duration}</strong> menit</p>`;
  if (repetitions > 0) detailsHtml += `<p>Repetisi: <strong>${repetitions}</strong>x</p>`;

  newCard.innerHTML = `
    <div class="exercise-info">
      <h3>${exercise}</h3>
      ${detailsHtml}
      <p class="burned-badge">Terbakar: <strong>${caloriesBurned.toFixed(1)}</strong> kcal</p>
    </div>
    ${id ? `<button class="btn-delete-card" onclick="deleteExercise(${id}, this, ${caloriesBurned})" title="Hapus">×</button>` : ''}
  `;

  exerciseList.appendChild(newCard);
}

// Add new exercise
async function addExercise() {
  const exerciseSelect = document.getElementById('exercise');
  const durationInput = document.getElementById('duration');
  const repetitionsInput = document.getElementById('repetitions');

  const exercise = exerciseSelect ? exerciseSelect.value : '';
  const duration = durationInput && durationInput.value ? parseFloat(durationInput.value) : 0;
  const repetitions = repetitionsInput && repetitionsInput.value ? parseInt(repetitionsInput.value) : 0;

  if (!exercise || (duration <= 0 && repetitions <= 0)) {
    alert("Harap masukkan durasi (menit) atau jumlah repetisi.");
    return;
  }

  let caloriesBurned = 0;
  const rate = calorieBurnRate[exercise] || 5.0;

  if (duration > 0) {
    caloriesBurned = rate * duration;
  } else if (repetitions > 0) {
    caloriesBurned = rate * repetitions;
  }

  // Persist to backend API
  try {
    const response = await fetch('../api/exercise.php?action=add', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        name: exercise,
        duration: duration,
        repetitions: repetitions,
        calories_burned: caloriesBurned
      })
    });
    const result = await response.json();
    const newId = result.id || Date.now();

    renderExerciseCard(exercise, duration, repetitions, caloriesBurned, newId);
    updateTotalCalories(caloriesBurned);

    // Reset input fields
    if (durationInput) durationInput.value = '';
    if (repetitionsInput) repetitionsInput.value = '';

  } catch (err) {
    console.warn("Could not save to API, saving locally:", err);
    renderExerciseCard(exercise, duration, repetitions, caloriesBurned);
    updateTotalCalories(caloriesBurned);
  }
}

// Delete exercise entry
async function deleteExercise(id, btnElement, caloriesBurned) {
  if (!confirm("Hapus catatan olahraga ini?")) return;
  try {
    await fetch('../api/exercise.php?action=delete&id=' + id, { method: 'POST' });
  } catch (e) {
    console.error(e);
  }
  const card = btnElement.closest('.card');
  if (card) card.remove();
  updateTotalCalories(-caloriesBurned);
}

// Load existing exercises from server on page load
async function loadTodayExercises() {
  try {
    const res = await fetch('../api/exercise.php?action=today');
    const data = await res.json();
    if (data.success && Array.isArray(data.items)) {
      totalCalories = 0;
      const exerciseList = document.getElementById('olahraga-cards');
      
      // Preserve the input card, clear any previous exercise result cards
      const inputCard = exerciseList ? exerciseList.querySelector('.card:first-child') : null;
      if (exerciseList) {
        exerciseList.innerHTML = '';
        if (inputCard) exerciseList.appendChild(inputCard);
      }

      data.items.forEach(item => {
        renderExerciseCard(
          item.nama_olahraga,
          parseFloat(item.durasi_menit) || 0,
          parseInt(item.repetisi) || 0,
          parseFloat(item.kalori_terbakar) || 0,
          item.id_olahraga
        );
        totalCalories += parseFloat(item.kalori_terbakar) || 0;
      });

      const totalEl = document.getElementById('totalKalori');
      if (totalEl) totalEl.textContent = `${totalCalories.toFixed(1)} kalori`;
    }
  } catch (e) {
    console.log("Offline mode or no API connection for exercise history.");
  }
}

// Event Listener
document.addEventListener('DOMContentLoaded', function () {
  const addExerciseBtn = document.getElementById('addExerciseBtn');
  if (addExerciseBtn) {
    addExerciseBtn.addEventListener('click', addExercise);
  }
  loadTodayExercises();
});
