<?php
// Termasuk fungsi untuk menghubungkan ke database
include('function.php');

// Ambil id_hitung yang akan dihapus
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Menghapus data berdasarkan id_hitung
    $conn = connectDatabase();
    $query = "DELETE FROM hitung_makanan WHERE id_hitung = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);  // 'i' menunjukkan bahwa parameter yang diterima adalah integer
    $stmt->execute();

    // Setelah data dihapus, redirect ke halaman sebelumnya
    header("Location: makandiet.php");
    exit();
} else {
    echo "ID tidak ditemukan!";
}
?>
