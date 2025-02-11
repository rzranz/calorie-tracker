# calorie-tracker
# Bulking Program Website

## Deskripsi

Website ini adalah platform untuk membantu pengguna yang ingin meningkatkan massa otot dengan mengikuti program bulking dan diet yang sehat. Program ini mencakup informasi mengenai pola makan yang kaya kalori, latihan beban, dan tips lainnya untuk mendukung pertumbuhan otot yang efektif.

Website ini terdiri dari beberapa halaman dengan konten sebagai berikut:
- **Kalkulator BMI**: Mengharapkan agar pengguna bisa menghitung kalori yang dibutuhkan perharinya dan juga berat badannya ideal atau tidak.
- **Program Bulking**: Menjelaskan langkah-langkah yang perlu diikuti untuk memulai program bulking.
- **Program Diet**: Menjelaskan langkah-langkah yang perlu diikuti untuk memulai program Diet.
- **Makan Hari Ini**: Menyediakan perhitungan kalori untuk mendukung program bulking dan diet ini.
- **Data Berat Badan**: Menampilkan data berat badan terkini yang pengguna masukan dan terhubung dengan database.

## Fitur

1. **Kalkualtor BMI**: Memberikan Perhitungan Automatis berdasarkan kalkulator BMI dan juga menunjukan kalori perharinya.
1. **Program Bulking**: Memberikan langkah-langkah praktis dalam memulai program bulking.
2. **Pengelolaan Data Berat Badan**: Menampilkan data berat badan terbaru pengguna dan menghitung BMI.

## Prasyarat

Sebelum menjalankan proyek ini, pastikan Anda sudah menginstal perangkat lunak berikut:
- PHP 7.x atau lebih tinggi
- MySQL untuk database
- Web server seperti Apache atau Nginx

## Instalasi

1. Clone repository ini ke komputer Anda:
   ```bash
   git clone https://github.com/username/bulking-program.git
2. Masuk ke CMD atau sejenisnya
   `cd bulking-program`
3. Buat database di MySQL dan impor file SQL yang sudah disediakan
4. masuk ke Database > function.php
   `$conn = new mysqli('localhost', 'username', 'password', 'database_name');`
5. Jalankan server menggunakan PHP atau melalui Apache/Nginx.
6. buka di localhost
   `http://localhost/namaaplikasi`
## Struktur Direktori
/bulking-program
│
├── /css              # Folder untuk file CSS
│   └── diet.css      # CSS utama untuk styling halaman
│
├── /img              # Folder untuk gambar
│   └── bulking.jpg   # Gambar terkait bulking
│
├── /js               # Folder untuk file JavaScript (jika ada)
│
├── /includes         # Folder untuk file PHP yang digunakan bersama
│   └── function.php  # File untuk koneksi ke database dan fungsi umum lainnya
│
├── bulking.php       # Halaman utama program bulking
└── index.php         # Halaman utama yang menampilkan overview
  
   

