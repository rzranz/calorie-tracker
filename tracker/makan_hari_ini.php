<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulking - Program Meningkatkan Massa Otot</title>

    <!-- Memanggil CSS -->
    <link rel="stylesheet" href="../css/makandiet.css">
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
        <h2>Data Makan Hari Ini:</h2>

        <?php
        // Termasuk fungsi untuk mengambil data beratbadan
        include('function.php'); // Pastikan jalur relatif benar

        // Ambil data kalori harian terbaru
        $conn = connectDatabase();
        $query = "SELECT kalori_harian FROM beratbadan ORDER BY id_beratbadan DESC LIMIT 1";  // Mengambil kalori_harian berdasarkan id terbaru
        $result = $conn->query($query);

        // Menentukan batas kalori per hari
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $kaloriHarian = $row['kalori_harian'];  // Menyimpan kalori harian dari data terbaru
        } else {
            $kaloriHarian = 2000; // Default jika data tidak ditemukan
        }

        // Ambil data makanan dari database
        $query = "SELECT id_hitung, makanan, berat, kalori, protein, tanggal FROM hitung_makanan ORDER BY tanggal DESC";
        $result = $conn->query($query);

        $total_calories = 0;  // Variabel untuk menghitung total kalori yang dikonsumsi hari ini

        if ($result->num_rows > 0) {
            // Tampilkan data dalam tabel
            echo "<table class='food-table'>
                <thead>
                    <tr>
                        <th>id_hitung</th>
                        <th>makanan</th>
                        <th>berat (g)</th>
                        <th>kalori (kcal)</th>
                        <th>protein (g)</th>
                        <th>Tanggal</th>
                        <th>Action</th> <!-- Kolom untuk tombol delete -->
                    </tr>
                </thead>
                <tbody>";

            // Loop untuk menampilkan setiap baris data
            while ($row = $result->fetch_assoc()) {
                $total_calories += $row['kalori'];  // Menambahkan kalori setiap makanan ke total

                echo "<tr>
                    <td>" . $row['id_hitung'] . "</td>
                    <td>" . $row['makanan'] . "</td>
                    <td>" . $row['berat'] . "</td>
                    <td>" . $row['kalori'] . "</td>
                    <td>" . $row['protein'] . "</td>
                    <td>" . $row['tanggal'] . "</td>
                    <td><a href='deletediet.php?id=" . $row['id_hitung'] . "' class='delete-btn'>Delete</a></td> <!-- Tombol delete -->
                </tr>";
            }

            echo "</tbody>
            </table>";
        } else {
            echo "<p>Tidak ada data yang ditemukan.</p>";
        }

        // Menutup koneksi
        $conn->close();
        ?>

        <!-- Menampilkan peringatan jika kalori melebihi batas -->
        <div class="kalori-peringatan">
            <h3>Total Kalori: <?php echo $total_calories; ?> kcal</h3>
            <h3>Batas Kalori Hari Ini: <?php echo $kaloriHarian; ?> kcal</h3>

            <?php
            // Peringatan jika kalori melebihi batas
            if ($total_calories > $kaloriHarian) {
                echo "<p style='color: red;'>Anda sudah melebihi batas kalori hari ini!</p>";
            }
            ?>
        </div>

        <!-- Menampilkan tombol untuk menghapus semua data makanan hari ini, dipindahkan ke bawah -->
        <form action="hapussemuamakanan.php" method="POST">
            <button type="submit" class="reset-btn">Saya Belum Makan Hari Ini</button>
        </form>

    </div>

    </div>

</body>

</html>