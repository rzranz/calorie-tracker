<?php
// Menyambung ke database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "olahraga";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Cek apakah data POST diterima
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $serving_size = $_POST['serving_size'];
    $calories = $_POST['calories'];
    $protein = $_POST['protein'];

    // Debugging: Tampilkan data yang diterima
    echo "Received data: Name = $name, Serving Size = $serving_size, Calories = $calories, Protein = $protein";

    // Query untuk memasukkan data ke tabel hitung_makanan
    $sql = "INSERT INTO hitung_makanan (makanan, berat, kalori, protein,tanggal) 
            VALUES ('$name', '$serving_size', '$calories', '$protein',now())";

    if ($conn->query($sql) === TRUE) {
        echo "New record created successfully";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
} else {
    echo "No data received.";
}

$conn->close();
?>
