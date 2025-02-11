// Simulasi data kalori per menit untuk beberapa jenis olahraga
const calorieBurnRate = {
  "Push Up": 0.29, // kalori per repetisi
  "Sit Up": 0.39,  // kalori per repetisi
  "Back Up": 0.19, // kalori per repetisi
  "Lari": 10,      // kalori per menit
  "Renang": 8,     // kalori per menit
  "Angkat Beban": 5 // kalori per repetisi
};

// Fungsi untuk menambahkan olahraga baru
function addExercise() {
  console.log("Tombol ditekan!"); // Log untuk memastikan fungsi ini dipanggil

  // Mengambil nilai input
  const exercise = document.getElementById('exercise').value;
  const duration = parseFloat(document.getElementById('duration').value);
  const repetitions = parseInt(document.getElementById('repetitions').value);

  // Validasi input
  if (!exercise || (isNaN(duration) && isNaN(repetitions))) {
    alert("Harap masukkan data olahraga dengan benar.");
    return;
  }

  let caloriesBurned = 0;

  // Menghitung kalori terbakar berdasarkan input
  if (calorieBurnRate[exercise]) {
    if (!isNaN(duration) && duration > 0) {
      // Kalori terbakar per menit (durasi)
      caloriesBurned = calorieBurnRate[exercise] * duration;
    } else if (!isNaN(repetitions) && repetitions > 0) {
      // Kalori terbakar per repetisi
      caloriesBurned = calorieBurnRate[exercise] * repetitions;
    }
  }

  // Menambahkan ke daftar olahraga
  const exerciseList = document.getElementById('olahraga-cards');
  const newCard = document.createElement('div');
  newCard.classList.add('card');
  newCard.innerHTML = `
    <h3>${exercise}</h3>
    <p>Durasi: ${duration} menit</p>
    <p>Repetisi: ${repetitions}</p>
    <p>Kalori Terbakar: ${caloriesBurned.toFixed(2)} kalori</p>
  `;

  exerciseList.appendChild(newCard);

  // Update total kalori terbakar
  updateTotalCalories(caloriesBurned);
}

// Fungsi untuk menghitung total kalori
let totalCalories = 0;
function updateTotalCalories(newCalories) {
  totalCalories += newCalories;
  document.getElementById('totalKalori').textContent = `${totalCalories.toFixed(2)} kalori`;
}

// Event Listener untuk tombol "Tambah Olahraga"
document.addEventListener('DOMContentLoaded', function () {
  const addExerciseBtn = document.getElementById('addExerciseBtn');
  console.log("Mengecek tombol");  // Log jika tombol benar-benar ditemukan
  if (addExerciseBtn) {
    addExerciseBtn.addEventListener('click', addExercise); // Menghubungkan tombol dengan fungsi addExercise
  } else {
    console.log("Tombol tidak ditemukan!"); // Jika tombol tidak ditemukan
  }
});
