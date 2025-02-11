<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulking - Program Meningkatkan Massa Otot</title>

    <!-- Memanggil CSS -->
    <link rel="stylesheet" href="../css/diet.css"> <!-- CSS utama -->
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Menu Bulking</h2>
        <a href="bulking.php">Beranda</a>
        <a href="tracker.html">Program Bulking</a>
        <a href="olahraga.html">Program Exercise</a>
        <a href="makan_hari_ini.php">Makan Hari Ini</a>
    </div>

    <!-- Konten Utama -->
    <div class="main-content">
        <section class="program-intro">
            <div class="card">
                <h1>Program Bulking</h1>
                <p>Program Bulking bertujuan untuk meningkatkan massa otot dengan cara yang sehat melalui pengaturan pola makan yang kaya kalori dan latihan fisik yang tepat. Berikut adalah beberapa tips dan rekomendasi untuk memulai program bulking Anda:</p>
            </div>
            <div class="image">
                <img src="../img/gym.jpg" alt="Program Bulking" class="diet-img">
            </div>
        </section>

        <section class="program-steps">
            <div class="card">
                <h2>Langkah-langkah Program Bulking</h2>
                <ul>
                    <li><strong>Konsumsi Kalori Lebih Banyak:</strong> Untuk meningkatkan massa otot, Anda perlu mengkonsumsi kalori lebih banyak daripada yang Anda bakar. Pilihlah makanan dengan kalori tinggi dan bernutrisi.</li>
                    <li><strong>Protein untuk Membangun Otot:</strong> Protein sangat penting untuk proses sintesis otot. Konsumsilah sekitar 1.6-2.2 gram protein per kilogram berat badan per hari.</li>
                    <li><strong>Karbohidrat untuk Energi:</strong> Karbohidrat memberikan energi yang dibutuhkan untuk latihan yang intens. Pilih karbohidrat kompleks seperti nasi merah, quinoa, dan kentang.</li>
                    <li><strong>Lemak Sehat untuk Hormon:</strong> Lemak sehat membantu produksi hormon yang mendukung pertumbuhan otot. Konsumsilah lemak tak jenuh dari sumber seperti alpukat, kacang-kacangan, dan minyak zaitun.</li>
                    <li><strong>Latihan Beban (Strength Training):</strong> Fokuskan latihan Anda pada latihan kekuatan untuk meningkatkan massa otot secara efektif. Latihan seperti squat, deadlift, dan bench press adalah pilihan yang baik.</li>
                </ul>
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