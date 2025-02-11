<?php
include('function.php'); // Termasuk fungsi koneksi database

// Menghubungkan ke database
$conn = connectDatabase();

// Query untuk menghapus semua data di tabel hitung_makanan
$query = "DELETE FROM hitung_makanan";
$stmt = $conn->prepare($query);

// Menjalankan query
if ($stmt->execute()) {
    echo "Semua data makanan telah dihapus.";
    header("Location: makan_hari_ini.php"); // Redirect kembali ke halaman "Makan Hari Ini"
} else {
    echo "Terjadi kesalahan saat menghapus data.";
}

// Menutup koneksi
$stmt->close();
$conn->close();
?>
