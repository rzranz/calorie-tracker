<?php
// Fungsi untuk koneksi ke database
function connectDatabase() {
    $servername = "localhost";  // Ganti dengan host jika perlu
    $username = "root";         // Ganti dengan username database Anda
    $password = "";             // Ganti dengan password database Anda
    $dbname = "olahraga";       // Nama database

    // Membuat koneksi
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Cek koneksi
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }

    return $conn;
}

// Fungsi untuk menyimpan data berat, tinggi, bmi, kategori, kalori harian, dan tanggal ke dalam database
function simpanData($berat, $tinggi, $bmi, $usia, $gender, $aktivitas) {
    // Menghubungkan ke database
    $conn = connectDatabase();

    // Menghindari SQL Injection
    $berat = $conn->real_escape_string($berat);
    $tinggi = $conn->real_escape_string($tinggi);
    $bmi = $conn->real_escape_string($bmi);

    // Menentukan kategori berdasarkan BMI
    if ($bmi < 18.5) {
        $kategori = "kurus";
    } elseif ($bmi >= 18.5 && $bmi < 24.9) {
        $kategori = "ideal";
    } elseif ($bmi >= 25 && $bmi < 29.9) {
        $kategori = "gemuk";
    } else {
        $kategori = "Obesitas";
    }

    // Menghitung kalori harian berdasarkan usia, jenis kelamin, berat badan, tinggi badan, dan aktivitas
    if ($gender == "Laki-laki") {
        $bmr = 88.36 + (13.4 * $berat) + (4.8 * $tinggi) - (5.7 * $usia);
    } else {
        $bmr = 447.6 + (9.2 * $berat) + (3.1 * $tinggi) - (4.3 * $usia);
    }

    $faktorAktivitas = [
        "sedentary" => 1.2,
        "light" => 1.375,
        "moderate" => 1.55,
        "active" => 1.725,
        "very_active" => 1.9
    ];

    $kaloriHarian = round($bmr * $faktorAktivitas[$aktivitas]);

    // Pastikan kategori dan kalori harian sudah terisi sebelum digunakan
    echo "<p>Kategori: $kategori</p>"; // Debugging untuk memeriksa kategori
    echo "<p>Kalori Harian: $kaloriHarian</p>"; // Debugging untuk memeriksa kalori harian

    // Query untuk menyimpan data
    $query = "INSERT INTO `beratbadan` (berat, tinggi, bmi, kategori, kalori_harian, tanggal) 
              VALUES ('$berat', '$tinggi', '$bmi', '$kategori', '$kaloriHarian', now())";

    if ($conn->query($query) === TRUE) {
        echo "<p>Data berhasil disimpan ke database!</p>";
    } else {
        echo "Error: " . $query . "<br>" . $conn->error;
    }

    // Menutup koneksi
    $conn->close();
}
?>
