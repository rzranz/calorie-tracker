<?php
// Termasuk file fungsi dan CSS
include('tracker/function.php');
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Let's Move</title>

    <!-- Memanggil CSS -->
    <link rel="stylesheet" type="text/css" href="css/kalkulator.css">
</head>

<body>

    <div class="container">
        <h1>Kalkulator BMI</h1>

        <form method="post">
            <label for="berat">Berat (kg):</label>
            <input type="number" name="berat" id="berat" required min="1"><br>

            <label for="tinggi">Tinggi (cm):</label>
            <input type="number" name="tinggi" id="tinggi" required min="1"><br>

            <label for="usia">Usia (tahun):</label>
            <input type="number" name="usia" id="usia" required min="1"><br>

            <label>Jenis Kelamin:</label><br>
            <input type="radio" name="gender" value="Laki-laki" required> Laki-laki
            <input type="radio" name="gender" value="Perempuan" required> Perempuan<br>

            <label for="aktivitas">Tingkat Aktivitas:</label>
            <select name="aktivitas" id="aktivitas" required>
                <option value="sedentary">Sedentari (tidak aktif)</option>
                <option value="light">Ringan (olahraga ringan)</option>
                <option value="moderate">Sedang (olahraga sedang)</option>
                <option value="active">Aktif (olahraga berat)</option>
                <option value="very_active">Sangat Aktif</option>
            </select><br>

            <input type="submit" value="Hitung BMI">
        </form>

        <?php
        // Cek jika form disubmit
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Mendapatkan input berat, tinggi, usia, gender, dan aktivitas dari form
            $berat = $_POST['berat'];
            $tinggi = $_POST['tinggi'];
            $usia = $_POST['usia'];
            $gender = $_POST['gender'];
            $aktivitas = $_POST['aktivitas'];

            // Validasi input agar tidak bernilai 0 atau negatif
            if ($berat <= 0 || $tinggi <= 0 || $usia <= 0) {
                echo "<p style='color: red;'>Berat, tinggi, dan usia harus lebih besar dari 0!</p>";
            } else {
                // Mengubah tinggi dari cm ke meter
                $tinggi_meter = $tinggi / 100;

                // Menghitung BMI
                $bmi = $berat / ($tinggi_meter * $tinggi_meter);

                // Menentukan kategori BMI
                if ($bmi < 18.5) {
                    $kategori = "kurus";
                } elseif ($bmi >= 18.5 && $bmi < 24.9) {
                    $kategori = "ideal";
                } elseif ($bmi >= 25 && $bmi < 29.9) {
                    $kategori = "gemuk";
                } else {
                    $kategori = "Obesitas";
                }

                // Panggil fungsi untuk menyimpan data ke database
                simpanData($berat, $tinggi, $bmi, $usia, $gender, $aktivitas);

                // Menampilkan hasil BMI, kategori, kalori harian
                echo "<div class='result'>Hasil BMI: " . number_format($bmi, 2) . "</div>";
                echo "<div class='category'>Kategori: $kategori</div>";
                // Menampilkan tombol berdasarkan kategori
                echo "<div class='buttons'>
                                <a href='tracker/diet.php'><button>Diet</button></a>
                                <a href='tracker/bulking.php'><button>Bulking</button></a>
                              </div>";
            }
        }
        ?>

    </div>

</body>

</html>