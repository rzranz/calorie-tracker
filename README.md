# Let's Move — Modern Calorie & Fitness Tracker ⚡

Aplikasi web modern untuk manajemen nutrisi harian, pelacakan kalori (*calorie tracking*), kalkulator BMI & TDEE berbasis algoritma **Mifflin-St Jeor**, pencatatan aktivitas olahraga, serta panduan terstruktur untuk program **Bulking** dan **Diet / Cutting**.

Didesain ulang (*reengineered*) dengan arsitektur bersih (*Clean Architecture*), dual-database otomatis (SQLite *zero-config* & MySQL), API nutrisi terintegrasi (*CalorieNinjas* + katalog lokal), dan antarmuka responsif *developer-grade*.

---

## ✨ Fitur Utama

1. **Dashboard Kinerja & KPI Harian**:
   - **Target Kalori Harian**: Dihitung otomatis berdasarkan BMR & TDEE dengan penyesuaian target (Surplus Bulking, Defisit Diet, atau Maintenance).
   - **Kalori Masuk (Makanan)**: Total kalori dan makronutrisi (Protein, Karbohidrat, Lemak) dari makanan yang dikonsumsi hari ini.
   - **Kalori Terbakar (Olahraga)**: Perhitungan pembakaran kalori dari latihan beban, repetisi, atau kardio.
   - **Kalori Bersih (Net Calories)**: Menghitung secara presisi $\text{Kalori Bersih} = \text{Kalori Masuk} - \text{Kalori Terbakar}$, lengkap dengan status meter aman vs *overflow*.
2. **Kalkulator BMI, BMR & TDEE Terstandar**:
   - Menggunakan rumus klinis terpercaya **Mifflin-St Jeor** dengan faktor aktivitas fisik harian (Sedentari hingga Sangat Aktif).
   - Rekomendasi asupan protein spesifik sesuai berat badan pengguna (1.8–2.2g/kg untuk bulking, 1.6–2.0g/kg untuk diet).
   - Penyimpanan riwayat berat badan secara otomatis ke database.
3. **Pencatat Makanan & Nutrisi (Food Tracker)**:
   - Pencarian instan data nutrisi *real-time* via **CalorieNinjas API**.
   - Database lokal *fallback* kuliner Indonesia (Nasi Putih, Dada Ayam, Tempe, Tahu, Telur, Rendang, Oatmeal, dll.) sehingga pencarian tetap berfungsi meski tanpa koneksi internet.
   - Fitur **Tambah Makanan Manual (Custom Meal)** untuk resep atau menu khusus.
   - Hapus item makanan dan tombol *Reset Hari Ini* ("Saya Belum Makan Hari Ini").
4. **Catatan Olahraga & Pembakaran Kalori (Workout Logger)**:
   - Pencatatan latihan berbasis durasi (menit) atau repetisi (*reps*).
   - Preset pembakaran kalori: *Push Up, Sit Up, Pull Up, Squat, Lari/Jogging, Bersepeda, Berenang, Angkat Beban/Gym, Lompat Tali, HIIT Cardio, dan Jalan Kaki*.
   - Data latihan tersimpan persisten ke database dan langsung memotong kalori harian.
5. **Grafik & Analitik Tren (Progress Chart)**:
   - Visualisasi grafik interaktif perkembangan berat badan dan fluktuasi skor BMI dari waktu ke waktu menggunakan **Chart.js**.
   - Tabel riwayat pengukuran lengkap dengan tombol hapus data.
6. **Panduan Program Berbasis Bukti Ilmiah**:
   - **Program Bulking**: Protokol *lean bulking* (+300 hingga +500 kcal surplus), *progressive overload*, dan contoh menu tinggi protein.
   - **Program Diet**: Protokol defisit moderat (-400 hingga -500 kcal), proteksi massa otot, hidrasi, dan contoh menu *low-calorie dense*.

---

## 🛠️ Arsitektur & Teknologi

- **Backend**: PHP 7.4 / PHP 8.x (Berjalan mulus tanpa dependensi *framework* eksternal yang berat).
- **Database Layer**: Dual driver via PHP PDO:
  - **SQLite (Default)**: Berjalan otomatis *out-of-the-box* tanpa perlu menyalakan layanan MySQL (`data/calorie_tracker.sqlite`).
  - **MySQL (Opsional)**: Mendukung koneksi MySQL/MariaDB konvensional (Laragon, XAMPP, VPS) melalui *environment variables* atau `config/database.php`.
  - **Auto-Migration**: Pembuatan tabel (`beratbadan`, `hitung_makanan`, `olahraga_log`) dan *seeding* otomatis saat pertama kali aplikasi dijalankan.
- **Frontend**:
  - CSS kustom (*Modern Design System*) dengan token CSS variables, palet warna gelap berkelas (*Developer Aesthetic*), dan tipografi *Plus Jakarta Sans* & *JetBrains Mono*.
  - Desain responsif (*mobile, tablet, desktop*) dengan transisi halus dan ramah performa GPU.
  - Visualisasi data interaktif dengan **Chart.js**.
  - Notifikasi *Toast* non-intrusif (menggantikan `window.alert` bawaan browser).
- **Integrasi API**:
  - CalorieNinjas Nutrition API (`/api/food.php`).

---

## 🚀 Cara Menjalankan Aplikasi

### Metode 1: PHP Built-in Server (Paling Cepat — Zero Config)
Pastikan komputer Anda telah terinstal PHP (PHP 7.4 atau PHP 8.x). Buka Terminal / PowerShell di folder proyek ini:

```bash
php -S localhost:8000
```
Buka browser dan akses:
👉 **`http://localhost:8000`**

*Aplikasi akan langsung membuat database SQLite lokal dan siap digunakan seketika tanpa perlu konfigurasi tambahan.*

---

### Metode 2: Menggunakan Laragon / XAMPP / Apache

1. Pindahkan atau buat *symlink* folder proyek ini ke direktori web server Anda (misal `C:\laragon\www\calorie-tracker` atau `C:\xampp\htdocs\calorie-tracker`).
2. Jika ingin menggunakan MySQL:
   - Buat database baru bernama `olahraga`.
   - Impor file `olahraga.sql` melalui phpMyAdmin / HeidiSQL.
   - Atur environment variable atau ubah driver di `config/database.php`:
     ```php
     private static string $driver = 'mysql';
     ```
3. Buka browser di `http://localhost/calorie-tracker`.

---

## 📂 Struktur Direktori

```text
calorie-tracker/
├── api/                        # RESTful JSON API Endpoints
│   ├── food.php                # Endpoint pencarian, tambah, hapus, & ringkasan makanan
│   ├── exercise.php            # Endpoint logging olahraga & kalkulasi kalori terbakar
│   └── weight.php              # Endpoint riwayat berat badan & data grafik
├── config/
│   └── database.php            # Konfigurasi PDO, Dual DB (SQLite/MySQL), & migrasi otomatis
├── css/
│   ├── modern.css              # Sistem desain modern, tema gelap, komponen & responsivitas
│   ├── diet.css                # Legacy styling
│   ├── kalkulator.css          # Legacy styling
│   └── olahraga.css            # Legacy styling
├── data/                       # Direktori penyimpanan database SQLite lokal
├── img/                        # Aset grafis (gym.jpg, diet.jpg)
├── includes/
│   ├── functions.php           # Logika bisnis: BMR, TDEE, makro, query database
│   ├── header.php              # Komponen navigasi atas & stylesheet
│   └── footer.php              # Komponen footer & script toast
├── js/
│   ├── scriptt.js              # Logika pencarian nutrisi makanan & integrasi API
│   ├── script.js               # Alias kompatibilitas
│   ├── olahraga.js             # Logika kalkulasi olahraga & persistensi ke backend
│   └── index.js                # Helper animasi & UI
├── tracker/                    # Modul pelacak (kompatibel penuh dengan link lama)
│   ├── bulking.php             # Halaman program bulking
│   ├── diet.php                # Halaman program diet
│   ├── makan_hari_ini.php      # Halaman pencatat makanan hari ini
│   ├── makandiet.php           # Alias kompatibilitas
│   ├── olahraga.html           # Halaman catatan olahraga
│   ├── tracker.html            # Halaman pelacak makanan
│   ├── diet.html               # Halaman pelacak makanan diet
│   ├── function.php            # Bridge kompatibilitas database legacy
│   ├── addfood.php             # Endpoint legacy tambah makanan
│   ├── deletediet.php          # Endpoint legacy hapus makanan
│   └── hapussemuamakanan.php   # Endpoint legacy reset makanan
├── tests/
│   └── qa_test.php             # Automated test suite untuk verifikasi QA
├── index.php                   # Dashboard utama & Kalkulator BMI/TDEE
├── olahraga.sql                # Skema dan sample data MySQL
├── .gitignore                  # Git ignore (mengecualikan .agents dan file lokal)
└── README.md                   # Dokumentasi teknis proyek
```

---

## 🧪 Verifikasi & Quality Assurance (QA)

Aplikasi ini dilengkapi dengan *test suite* mandiri untuk memverifikasi seluruh komponen sistem:
- Koneksi database & migrasi otomatis tabel.
- Validasi akurasi rumus BMR (Mifflin-St Jeor), TDEE, BMI, dan alokasi makronutrisi.
- CRUD data makanan (*Add, Search, Summary, Delete, Reset*).
- CRUD data olahraga (*Add, Duration/Reps calculation, Delete*).
- Riwayat berat badan dan struktur data grafik.
- Integritas *endpoint legacy* (`addfood.php`, `deletediet.php`, dll.).

Jalankan pengujian kapan saja via terminal:
```bash
php tests/qa_test.php
```

---

## 🔒 Keamanan & Praktik Terbaik

- **100% Prepared Statements (PDO)**: Mencegah celah kerentanan SQL Injection.
- **Input Sanitization**: Validasi ketat pada tipe data numerik dan teks sebelum diproses.
- **Resilient Fallback**: Sistem *fail-safe* otomatis beralih ke database SQLite lokal jika layanan MySQL tidak aktif, sehingga aplikasi tidak akan mengalami *fatal crash*.

---

## 📄 Lisensi
Proyek ini dibuat untuk keperluan portofolio pengembangan web fullstack profesional. Bebas digunakan dan dikembangkan kembali.
