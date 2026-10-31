# Let's Move — Modern Calorie & Fitness Tracker

Aplikasi web modern untuk pelacakan kalori (*calorie tracking*), manajemen nutrisi harian, kalkulator BMI & TDEE berbasis algoritma klinis **Mifflin-St Jeor**, pencatatan aktivitas olahraga, serta rekomendasi latihan dan menu makanan terarah untuk 3 program: **Diet**, **Workout / Fitness**, dan **Bulking**.

Didesain ulang dengan alur baru yang **bersih, intuitif, dan ramah pemula** (*beginner-friendly*), arsitektur bersih (*Clean Service Architecture*), dual-database otomatis (SQLite *zero-config* & MySQL), dan antarmuka responsif *developer-grade* tanpa dekorasi stiker atau emoji template.

---

## Alur Pengguna (Beginner-Friendly Flow)

```
[Selamat Datang / Login] 
        │ (Cukup Email & Password / Masuk Cepat Akun Demo)
        ▼
[Kalkulator BMI & Onboarding]
        │ (Input Parameter: Berat, Tinggi, Usia, Gender, Aktivitas)
        │ (Kalkulasi Otomatis: BMI, BMR, TDEE)
        ▼
[Pilih Sasaran Program]
        ├── Program Diet (Defisit Kalori -450 kcal)
        ├── Program Workout (Maintenance & Kebugaran TDEE)
        └── Program Bulking (Surplus Anabolik +400 kcal)
        │
        ▼
[Dashboard Terpersonalisasi]
        ├── Status Kalori Harian & Budget Meter (Net Calories)
        ├── Saran Menu Makanan Akurat (+ 1-Click Catat)
        ├── Rekomendasi Olahraga & Pembakaran (+ 1-Click Catat)
        ├── Pencari Makanan Instan (CalorieNinjas + Katalog Lokal)
        ├── Pencatat Olahraga & Makanan Mandiri
        └── Grafik Tren Progres Berat Badan & BMI (Chart.js)
```

---

## Fitur Utama

1. **Autentikasi Minimalis (Email & Password)**:
   - Halaman Selamat Datang yang bersih, fokus, dan bebas gangguan.
   - Pendaftaran dan login akun hanya membutuhkan **Email dan Password**.
   - Dilengkapi tombol **Masuk Langsung (Akun Demo)** untuk pengujian dan evaluasi instan.

2. **Kalkulator BMI, BMR & TDEE Presisi**:
   - Menghitung Indeks Massa Tubuh (BMI) dan kategori berat badan (Kurus, Ideal, Gemuk, Obesitas).
   - Menghitung Basal Metabolic Rate (BMR) dengan rumus klinis terpercaya **Mifflin-St Jeor**.
   - Menghitung Total Daily Energy Expenditure (TDEE) berdasarkan 5 level aktivitas harian.
   - Memberikan rekomendasi program kebugaran yang paling cocok.

3. **Tiga Pilihan Program yang Menyesuaikan Dashboard**:
   - **Program Diet / Fat Loss**: Target defisit kalori terukur (-450 kcal/hari), saran menu padat gizi rendah kalori, dan jadwal kardio pembakar lemak.
   - **Program Workout / Fitness (Maintenance)**: Target kalori seimbang (TDEE), saran nutrisi penopang stamina, dan latihan *functional fitness*.
   - **Program Bulking / Hipertrofi**: Target surplus kalori anabolik (+400 kcal/hari), target protein tinggi (1.8–2.2g/kg BB), dan jadwal latihan beban progresif.
   - Fitur **Ganti Program Kapan Saja** langsung dari dashboard tanpa kehilangan riwayat pencatatan.

4. **Saran Makanan & Olahraga dengan Fitur "+ Catat Langsung"**:
   - Setiap program menyediakan rekomendasi makanan lengkap dengan gramatur, kalori, protein, karbohidrat, dan lemak.
   - Setiap program menyediakan rekomendasi latihan dengan estimasi kalori terbakar.
   - Cukup klik tombol **+ Catat** pada saran yang diinginkan, data langsung tersimpan ke jurnal harian secara real-time.

5. **Pencarian Nutrisi Makanan Cepat & Fleksibel**:
   - Terintegrasi dengan **CalorieNinjas API** untuk database makanan internasional.
   - Dilengkapi katalog *offline fallback* kuliner lokal (Nasi Putih, Dada Ayam, Tempe, Tahu, Telur, Rendang, dll.).
   - Fitur **Tambah Makanan Manual (Custom Meal)** untuk resep atau menu rumahan.

6. **Catatan Olahraga & Pengurangan Kalori Bersih**:
   - Catat latihan berdasarkan durasi (menit) atau repetisi (*reps*).
   - Preset kalori terstandar: *Push Up, Sit Up, Pull Up, Squat, Lari/Jogging, Bersepeda, Berenang, Angkat Beban/Gym, Lompat Tali, HIIT Cardio, dan Jalan Kaki*.
   - Data olahraga memotong kalori harian secara otomatis: Net Calories = Kalori Masuk - Kalori Terbakar.

7. **Grafik & Analisis Tren Visual (Chart.js)**:
   - Visualisasi grafik interaktif perkembangan berat badan dan fluktuasi skor BMI dari waktu ke waktu.

---

## Arsitektur & Pola Desain (Clean Code)

- **Backend**: Native PHP 8.x / PHP 7.4 mengadopsi Service Pattern:
  - `AuthService`: Manajemen registrasi, verifikasi password hash, dan sesi.
  - `FitnessService`: Kalkulasi fisiologis (BMI, BMR Mifflin-St Jeor, TDEE, makro, dan kurasi rekomendasi).
  - `NutritionService`: Pencarian nutrisi hybrid, pencatatan jurnal makanan per pengguna, dan rekap harian.
  - `WorkoutService`: Perhitungan MET kalori terbakar, pencatatan sesi latihan, dan rekap aktivitas.
- **Database Layer**: Dual-Driver via PHP PDO:
  - **SQLite (Default)**: Berjalan otomatis *zero-config* (`data/calorie_tracker.sqlite`).
  - **MySQL (Opsional)**: Mendukung koneksi MySQL/MariaDB konvensional (Laragon, XAMPP, VPS).
  - **Auto-Migration & Auto-Seeding**: Pembuatan skema tabel dan kolom secara otomatis saat aplikasi dijalankan.
- **Frontend Design System**:
  - Mengikuti standar *Anti-Slop UI*: bebas stiker emoji yang kekanak-kanakan, menggunakan tipografi modern (*Plus Jakarta Sans* & *JetBrains Mono* untuk tabular figures), palet OLED matte gelap, dan double-bezel cards.
  - Notifikasi *Toast* non-intrusif.
  - Grafik tren interaktif dengan **Chart.js**.

---

## Cara Menjalankan Aplikasi

### Metode 1: PHP Built-in Server (Paling Cepat — Zero Config)
Buka Terminal / PowerShell di folder proyek ini:

```bash
php -S localhost:8000
```
Buka browser Anda dan akses:
**`http://localhost:8000`**

*Aplikasi akan menampilkan halaman selamat datang. Anda dapat login dengan akun demo (1-klik) atau mendaftar akun baru.*

---

### Akun Demo Bawaan:
- **Email**: `demo@calorietracker.com`
- **Password**: `password123`
- Atau cukup klik tombol **Masuk Langsung (Akun Demo)** di halaman login.

---

## 📂 Struktur Direktori

```text
calorie-tracker/
├── api/                        # RESTful JSON API Endpoints
│   ├── food.php                # Endpoint pencarian, tambah, hapus, & reset makanan
│   ├── exercise.php            # Endpoint logging olahraga & kalkulasi pembakaran
│   └── weight.php              # Endpoint riwayat berat badan & grafik
├── config/
│   └── database.php            # Konfigurasi PDO, Dual DB, migrasi skema & seeding otomatis
├── css/
│   ├── modern.css              # Sistem desain modern, tema gelap, kartu & layout
│   ├── diet.css                # Legacy styling
│   ├── kalkulator.css          # Legacy styling
│   └── olahraga.css            # Legacy styling
├── data/                       # Direktori database SQLite lokal (.gitignore)
├── img/                        # Aset gambar (gym.jpg, diet.jpg)
├── includes/
│   ├── functions.php           # Logika bisnis: BMR, TDEE, auth, makro, saran makanan & latihan
│   ├── header.php              # Navigasi atas dengan badge program aktif
│   └── footer.php              # Komponen footer & script toast
├── js/
│   ├── scriptt.js              # Pencarian nutrisi & komunikasi API
│   ├── script.js               # Alias kompatibilitas
│   ├── olahraga.js             # Logging olahraga & kalkulasi pembakaran
│   └── index.js                # Helper interaktif
├── tests/
│   └── qa_test.php             # Automated test suite (53/53 pengujian lolos)
├── login.php                   # Halaman Selamat Datang / Login & Register
├── setup.php                   # Onboarding: Kalkulator BMI & Pemilihan Program
├── index.php                   # Dashboard terpersonalisasi (Diet, Workout, Bulking)
├── logout.php                  # Script logout pengguna
├── olahraga.sql                # Skema dan sample data MySQL
├── .gitignore                  # Git ignore (mengecualikan .agents dan database lokal)
└── README.md                   # Dokumentasi teknis proyek
```

---

## 🧪 Verifikasi & Quality Assurance (QA)

Aplikasi telah lulus pengujian otomatis menyeluruh:
- Autentikasi pendaftaran, login benar, penolakan password salah.
- Pembaruan profil fisik dan pergantian program (*Diet, Workout, Bulking*).
- Akurasi kalkulasi BMR (Mifflin-St Jeor), TDEE, BMI, dan alokasi makronutrisi.
- Ketersediaan saran makanan dan rekomendasi latihan untuk setiap program.
- Operasi CRUD makanan dan olahraga per pengguna.
- Linting sintaks bebas error (`php -l`) di seluruh file.

Jalankan pengujian kapan saja:
```bash
php tests/qa_test.php
```

---

## 📄 Lisensi
Proyek ini dibuat untuk keperluan portofolio pengembangan web fullstack profesional.
