<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulking - Program Meningkatkan Massa Otot</title>

    <!-- Memanggil CSS -->
    <link rel="stylesheet" type="text/css" href="../css.php">
    
        
    </head>
    <body>
        <!-- Sidebar -->
        <div class="sidebar">
            <h2>Menu Bulking</h2>
            <a href="otot.php">Beranda</a>
            <a href="olahraga.php">olahraga</a>
            <a href="makanotot.php">makan hari ini</a>
        </div>


        <!-- Menampilkan Tabel Berat Badan -->
        <h2>Data Makan hari ini :</h2>

        <?php
        // Termasuk fungsi untuk mengambil data beratbadan
        include('function.php');

        // Ambil data beratbadan dari database
        $conn = connectDatabase();
        $query = "SELECT id_hitung,makanan, berat, kalori, protein ,tanggal FROM hitung_makanan ORDER BY tanggal DESC";
        $result = $conn->query($query);

        if ($result->num_rows > 0) {
            // Tampilkan data dalam tabel
            echo "<table border='1' cellpadding='10' cellspacing='0'>
                    <tr>
                        <th>id_hitung</th>
                        <th>makanan </th>
                        <th>berat</th>
                        <th>kalori</th>
                        <th>protein</th>
                        <th>Tanggal</th>
                    </tr>";

            // Loop untuk menampilkan setiap baris data
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>" . $row['id_hitung'] . "</td>
                        <td>" . $row['makanan'] . "</td>
                        <td>" . $row['berat'] . "</td>
                        <td>" . $row['kalori'] . "</td>
                        <td>" . $row['protein'] . "</td>
                        <td>" . $row['tanggal'] . "</td>
                    </tr>";
            }

            echo "</table>";
        } else {
            echo "<p>Tidak ada data yang ditemukan.</p>";
        }

        // Menutup koneksi
        $conn->close();
        ?>

    </div>

</body>
</html>
