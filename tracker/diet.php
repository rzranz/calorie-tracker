<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diet - Program Menurunkan Berat Badan</title>

    <!-- Memanggil CSS -->
    <link rel="stylesheet" href="../css/diet.css"> <!-- CSS utama -->
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Menu Diet</h2>
        <a href="diet.php">Beranda</a>
        <a href="diet.html">Program Diet</a>
        <a href="makandiet.php">Makan Hari Ini</a>
    </div>

    <!-- Konten Utama -->
    <div class="main-content">
        <section class="program-intro">
            <div class="card">
                <h1>Program Diet</h1>
                <p>Program Diet bertujuan untuk menurunkan berat badan dengan cara yang sehat melalui pengaturan pola makan dan aktivitas fisik yang tepat. Berikut adalah beberapa tips dan rekomendasi untuk memulai program diet Anda:</p>
            </div>
            <div class="image">
                <img src="../img/diet.jpg" alt="Program Diet" class="diet-img">
            </div>
        </section>

        <section class="program-steps">
            <div class="card">
                <h2>Langkah-langkah Program Diet</h2>
                <ul>
                    <li><strong>Konsumsi Kalori Lebih Sedikit:</strong> Untuk menurunkan berat badan, Anda perlu mengkonsumsi kalori lebih sedikit daripada yang Anda bakar.</li>
                    <li><strong>Protein untuk Menjaga Massa Otot:</strong> Protein penting untuk mempertahankan massa otot selama proses penurunan berat badan. Konsumsilah sekitar 1.2-1.6 gram protein per kilogram berat badan per hari.</li>
                    <li><strong>Karbohidrat dengan Indeks Glikemik Rendah:</strong> Pilih karbohidrat dengan indeks glikemik rendah untuk menjaga kadar gula darah stabil.</li>
                    <li><strong>Lemak Sehat:</strong> Lemak sehat penting untuk mendukung metabolisme dan keseimbangan hormon. Pilihlah lemak tak jenuh dari sumber seperti alpukat, kacang-kacangan, dan minyak zaitun.</li>
                    <li><strong>Latihan Kardio:</strong> Lakukan latihan kardio seperti berlari, bersepeda, atau berenang untuk membantu membakar kalori lebih banyak.</li>
                </ul>
                <p>Dengan mengikuti program diet yang tepat dan konsisten, Anda akan melihat penurunan berat badan secara bertahap dengan tetap menjaga kesehatan tubuh.</p>
            </div>
        </section>

        <section class="data-berat">
            <h2>Data Berat Badan Hari Ini</h2>

            <?php
            // Termasuk fungsi untuk mengambil data beratbadan
            include('function.php');

            // Ambil data beratbadan terbaru
            $conn = connectDatabase();
            $query = "SELECT id_beratbadan, berat, tinggi, bmi, kategori, tanggal FROM beratbadan ORDER BY tanggal DESC LIMIT 1";
            $result = $conn->query($query);

            if ($result->num_rows > 0) {
                // Tampilkan data dalam tabel
                echo "<table>
            <tr>
                <th>ID</th>
                <th>Berat (kg)</th>
                <th>Tinggi (cm)</th>
                <th>BMI</th>
                <th>Kategori</th>
                <th>Tanggal</th>
            </tr>";

                // Loop untuk menampilkan baris data terbaru
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                <td>" . $row['id_beratbadan'] . "</td>
                <td>" . $row['berat'] . "</td>
                <td>" . $row['tinggi'] . "</td>
                <td>" . number_format($row['bmi'], 2) . "</td>
                <td>" . $row['kategori'] . "</td>
                <td>" . $row['tanggal'] . "</td>
            </tr>";
                }

                echo "</table>";
            } else {
                echo "<p>Tidak ada data yang ditemukan untuk hari ini.</p>";
            }

            // Menutup koneksi
            $conn->close();
            ?>

        </section>
    </div>

</body>

</html>
