<?php
/**
 * Core Business Logic, Calculations & Authentication Helper
 */

require_once __DIR__ . '/../config/database.php';

function startUserSession(): void {
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
}

function getDb(): PDO {
    return Database::getConnection();
}

/**
 * Get currently logged-in user array or null
 */
function getCurrentUser(): ?array {
    startUserSession();
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    
    $db = getDb();
    $stmt = $db->prepare("SELECT * FROM users WHERE id_user = :id LIMIT 1");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $user ?: null;
}

/**
 * Enforce authentication: redirect to login if not authenticated
 */
function requireAuth(): array {
    $user = getCurrentUser();
    if (!$user) {
        header("Location: login.php");
        exit();
    }
    return $user;
}

/**
 * Register a new user
 */
function registerUser(string $email, string $password, string $nama = ''): array {
    $email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Format email tidak valid.'];
    }

    if (strlen($password) < 6) {
        return ['success' => false, 'error' => 'Password minimal 6 karakter.'];
    }

    $db = getDb();
    // Check existing email
    $check = $db->prepare("SELECT id_user FROM users WHERE email = :email LIMIT 1");
    $check->execute([':email' => $email]);
    if ($check->fetch()) {
        return ['success' => false, 'error' => 'Email ini sudah terdaftar. Silakan login.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("
        INSERT INTO users (email, password, nama, program, target_kalori, berat, tinggi, usia, gender, aktivitas, bmi, created_at)
        VALUES (:email, :password, :nama, 'diet', 2000, 70, 170, 25, 'Laki-laki', 'moderate', 24.22, :created_at)
    ");

    $stmt->execute([
        ':email'      => $email,
        ':password'   => $hash,
        ':nama'       => trim($nama) ?: explode('@', $email)[0],
        ':created_at' => date('Y-m-d H:i:s')
    ]);

    $newId = (int)$db->lastInsertId();
    
    // Auto login
    startUserSession();
    $_SESSION['user_id'] = $newId;

    return ['success' => true, 'id' => $newId];
}

/**
 * Login user
 */
function loginUser(string $email, string $password): array {
    $email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
    $db = getDb();

    $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'error' => 'Email atau password salah. Silakan coba lagi.'];
    }

    startUserSession();
    $_SESSION['user_id'] = (int)$user['id_user'];

    return ['success' => true, 'user' => $user];
}

/**
 * Logout user
 */
function logoutUser(): void {
    startUserSession();
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Update user onboarding / BMI profile & chosen program
 */
function updateUserProfile(
    int $userId,
    float $weight,
    float $height,
    int $age,
    string $gender,
    string $activity,
    string $program
): bool {
    $db = getDb();
    $bmi = calculateBMI($weight, $height);
    $bmr = calculateBMR($weight, $height, $age, $gender);
    $tdee = calculateTDEE($bmr, $activity);
    $targetCalories = calculateCalorieTarget($tdee, $program);

    $stmt = $db->prepare("
        UPDATE users
        SET berat = :berat,
            tinggi = :tinggi,
            usia = :usia,
            gender = :gender,
            aktivitas = :aktivitas,
            program = :program,
            target_kalori = :target_kalori,
            bmi = :bmi
        WHERE id_user = :id
    ");

    $ok = $stmt->execute([
        ':berat'         => $weight,
        ':tinggi'        => $height,
        ':usia'          => $age,
        ':gender'        => $gender,
        ':aktivitas'     => $activity,
        ':program'       => $program,
        ':target_kalori' => $targetCalories,
        ':bmi'           => $bmi,
        ':id'            => $userId
    ]);

    // Save historical weigh-in
    saveWeightLog($weight, $height, $age, $gender, $activity, $program, date('Y-m-d'), $userId);

    return $ok;
}

/**
 * Calculate BMI
 */
function calculateBMI(float $weightKg, float $heightCm): float {
    if ($weightKg <= 0 || $heightCm <= 0) return 0.0;
    $heightM = $heightCm / 100.0;
    return round($weightKg / ($heightM * $heightM), 2);
}

/**
 * Determine BMI Category
 */
function getBMICategory(float $bmi): array {
    if ($bmi <= 0) {
        return ['slug' => 'unknown', 'title' => 'Tidak Diketahui', 'badge' => 'badge-secondary', 'color' => '#64748b'];
    }
    if ($bmi < 18.5) {
        return [
            'slug' => 'kurus',
            'title' => 'Kurus (Underweight)',
            'recommendation' => 'Sangat disarankan program Bulking terarah untuk menaikkan massa otot dan daya tahan tubuh.',
            'default_goal' => 'bulking',
            'badge' => 'badge-blue',
            'color' => '#3b82f6'
        ];
    } elseif ($bmi < 25.0) {
        return [
            'slug' => 'ideal',
            'title' => 'Ideal (Normal)',
            'recommendation' => 'Berat badan Anda sangat proporsional. Anda bebas memilih program Bulking (tambah massa otot) atau Workout (menjaga kebugaran & stamina).',
            'default_goal' => 'workout',
            'badge' => 'badge-emerald',
            'color' => '#10b981'
        ];
    } elseif ($bmi < 30.0) {
        return [
            'slug' => 'gemuk',
            'title' => 'Kelebihan Berat Badan (Overweight)',
            'recommendation' => 'Sangat disarankan program Diet Defisit Kalori teratur disertai latihan beban ringan & kardio.',
            'default_goal' => 'diet',
            'badge' => 'badge-amber',
            'color' => '#f59e0b'
        ];
    } else {
        return [
            'slug' => 'obesitas',
            'title' => 'Obesitas (Obese)',
            'recommendation' => 'Sangat disarankan program Diet terstruktur untuk mengurangi risiko kesehatan kardiovaskular secara bertahap.',
            'default_goal' => 'diet',
            'badge' => 'badge-rose',
            'color' => '#ef4444'
        ];
    }
}

/**
 * Calculate BMR using Mifflin-St Jeor equation
 */
function calculateBMR(float $weightKg, float $heightCm, int $age, string $gender = 'Laki-laki'): float {
    if ($weightKg <= 0 || $heightCm <= 0 || $age <= 0) return 0.0;
    
    if (strtolower($gender) === 'perempuan' || strtolower($gender) === 'female') {
        return round((10 * $weightKg) + (6.25 * $heightCm) - (5 * $age) - 161, 1);
    }
    return round((10 * $weightKg) + (6.25 * $heightCm) - (5 * $age) + 5, 1);
}

/**
 * Calculate TDEE
 */
function calculateTDEE(float $bmr, string $activity = 'moderate'): float {
    $factors = [
        'sedentary'   => 1.2,
        'light'       => 1.375,
        'moderate'    => 1.55,
        'active'      => 1.725,
        'very_active' => 1.9
    ];
    $factor = $factors[$activity] ?? 1.55;
    return round($bmr * $factor);
}

/**
 * Calculate Calorie Target based on chosen program
 */
function calculateCalorieTarget(float $tdee, string $goal = 'bulking'): int {
    switch (strtolower($goal)) {
        case 'bulking':
            return (int)round($tdee + 400); // Surplus anabolik
        case 'diet':
        case 'cutting':
            return (int)round(max(1200, $tdee - 450)); // Defisit lemak
        case 'workout':
        case 'maintenance':
        default:
            return (int)round($tdee); // Pemeliharaan berat badan stabil
    }
}

/**
 * Macro Recommendations
 */
function calculateMacros(float $weightKg, int $targetCalories, string $goal = 'bulking'): array {
    $proteinPerKg = 1.5;
    if (strtolower($goal) === 'bulking') {
        $proteinPerKg = 2.0;
    } elseif (strtolower($goal) === 'diet') {
        $proteinPerKg = 1.8;
    }

    $proteinGrams = round($weightKg * $proteinPerKg);
    $proteinCalories = $proteinGrams * 4;

    $fatCalories = $targetCalories * 0.25;
    $fatGrams = round($fatCalories / 9);

    $carbCalories = max(0, $targetCalories - $proteinCalories - $fatCalories);
    $carbGrams = round($carbCalories / 4);

    return [
        'protein_g' => (int)$proteinGrams,
        'carbs_g'   => (int)$carbGrams,
        'fat_g'     => (int)$fatGrams,
    ];
}

/**
 * Save weigh-in record
 */
function saveWeightLog(
    float $weight,
    float $height,
    int $age = 25,
    string $gender = 'Laki-laki',
    string $activity = 'moderate',
    string $goal = 'bulking',
    ?string $date = null,
    int $userId = 1
): int {
    $db = getDb();
    $date = $date ?: date('Y-m-d');
    $bmi = calculateBMI($weight, $height);
    $cat = getBMICategory($bmi);

    $bmr = calculateBMR($weight, $height, $age, $gender);
    $tdee = calculateTDEE($bmr, $activity);
    $calorieTarget = calculateCalorieTarget($tdee, $goal);

    $stmt = $db->prepare("
        INSERT INTO beratbadan (user_id, berat, tinggi, usia, gender, aktivitas, bmi, kategori, kalori_harian, target_program, tanggal)
        VALUES (:user_id, :berat, :tinggi, :usia, :gender, :aktivitas, :bmi, :kategori, :kalori_harian, :target_program, :tanggal)
    ");

    $stmt->execute([
        ':user_id'        => $userId,
        ':berat'          => $weight,
        ':tinggi'         => $height,
        ':usia'           => $age,
        ':gender'         => $gender,
        ':aktivitas'      => $activity,
        ':bmi'            => $bmi,
        ':kategori'       => $cat['slug'],
        ':kalori_harian'  => (string)$calorieTarget,
        ':target_program' => $goal,
        ':tanggal'        => $date
    ]);

    return (int)$db->lastInsertId();
}

/**
 * Get latest weigh-in log for user
 */
function getLatestWeightLog(?int $userId = null): ?array {
    $db = getDb();
    if ($userId !== null) {
        $stmt = $db->prepare("SELECT * FROM beratbadan WHERE user_id = :uid ORDER BY id_beratbadan DESC LIMIT 1");
        $stmt->execute([':uid' => $userId]);
    } else {
        $stmt = $db->query("SELECT * FROM beratbadan ORDER BY id_beratbadan DESC LIMIT 1");
    }
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Get weight history list
 */
function getWeightHistory(int $limit = 30, ?int $userId = null): array {
    $db = getDb();
    if ($userId !== null) {
        $stmt = $db->prepare("SELECT * FROM beratbadan WHERE user_id = :uid ORDER BY tanggal DESC, id_beratbadan DESC LIMIT :limit");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
    } else {
        $stmt = $db->prepare("SELECT * FROM beratbadan ORDER BY tanggal DESC, id_beratbadan DESC LIMIT :limit");
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Delete weight record
 */
function deleteWeightLog(int $id): bool {
    $db = getDb();
    $stmt = $db->prepare("DELETE FROM beratbadan WHERE id_beratbadan = :id");
    return $stmt->execute([':id' => $id]);
}

/**
 * Add food log entry
 */
function addFoodLog(
    string $name,
    float $servingSizeG,
    float $calories,
    float $protein,
    float $carbs = 0,
    float $fat = 0,
    ?string $date = null,
    int $userId = 1
): int {
    $db = getDb();
    $date = $date ?: date('Y-m-d');

    $stmt = $db->prepare("
        INSERT INTO hitung_makanan (user_id, makanan, berat, kalori, protein, karbohidrat, lemak, tanggal)
        VALUES (:user_id, :makanan, :berat, :kalori, :protein, :karbohidrat, :lemak, :tanggal)
    ");

    $stmt->execute([
        ':user_id'     => $userId,
        ':makanan'     => trim($name),
        ':berat'       => round($servingSizeG, 2),
        ':kalori'      => round($calories, 2),
        ':protein'     => round($protein, 2),
        ':karbohidrat' => round($carbs, 2),
        ':lemak'       => round($fat, 2),
        ':tanggal'     => $date
    ]);

    return (int)$db->lastInsertId();
}

/**
 * Get today's consumed food logs
 */
function getTodayFoodLogs(?string $date = null, ?int $userId = null): array {
    $db = getDb();
    $date = $date ?: date('Y-m-d');
    if ($userId !== null) {
        $stmt = $db->prepare("SELECT * FROM hitung_makanan WHERE tanggal = :tanggal AND user_id = :uid ORDER BY id_hitung DESC");
        $stmt->execute([':tanggal' => $date, ':uid' => $userId]);
    } else {
        $stmt = $db->prepare("SELECT * FROM hitung_makanan WHERE tanggal = :tanggal ORDER BY id_hitung DESC");
        $stmt->execute([':tanggal' => $date]);
    }
    return $stmt->fetchAll();
}

/**
 * Get summary of food consumption for a specific date
 */
function getTodayFoodSummary(?string $date = null, ?int $userId = null): array {
    $db = getDb();
    $date = $date ?: date('Y-m-d');

    if ($userId !== null) {
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as item_count,
                COALESCE(SUM(kalori), 0) as total_calories,
                COALESCE(SUM(protein), 0) as total_protein,
                COALESCE(SUM(karbohidrat), 0) as total_carbs,
                COALESCE(SUM(lemak), 0) as total_fat
            FROM hitung_makanan
            WHERE tanggal = :tanggal AND user_id = :uid
        ");
        $stmt->execute([':tanggal' => $date, ':uid' => $userId]);
    } else {
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as item_count,
                COALESCE(SUM(kalori), 0) as total_calories,
                COALESCE(SUM(protein), 0) as total_protein,
                COALESCE(SUM(karbohidrat), 0) as total_carbs,
                COALESCE(SUM(lemak), 0) as total_fat
            FROM hitung_makanan
            WHERE tanggal = :tanggal
        ");
        $stmt->execute([':tanggal' => $date]);
    }
    $row = $stmt->fetch();

    return [
        'item_count'     => (int)($row['item_count'] ?? 0),
        'total_calories' => round((float)($row['total_calories'] ?? 0), 1),
        'total_protein'  => round((float)($row['total_protein'] ?? 0), 1),
        'total_carbs'    => round((float)($row['total_carbs'] ?? 0), 1),
        'total_fat'      => round((float)($row['total_fat'] ?? 0), 1)
    ];
}

/**
 * Delete a single food entry
 */
function deleteFoodLog(int $id): bool {
    $db = getDb();
    $stmt = $db->prepare("DELETE FROM hitung_makanan WHERE id_hitung = :id");
    return $stmt->execute([':id' => $id]);
}

/**
 * Clear/reset food entries for a specific day
 */
function resetTodayFoodLogs(?string $date = null, ?int $userId = null): bool {
    $db = getDb();
    $date = $date ?: date('Y-m-d');
    if ($userId !== null) {
        $stmt = $db->prepare("DELETE FROM hitung_makanan WHERE tanggal = :tanggal AND user_id = :uid");
        return $stmt->execute([':tanggal' => $date, ':uid' => $userId]);
    }
    $stmt = $db->prepare("DELETE FROM hitung_makanan WHERE tanggal = :tanggal");
    return $stmt->execute([':tanggal' => $date]);
}

/**
 * Add exercise log entry
 */
function addExerciseLog(
    string $name,
    float $durationMinutes,
    int $repetitions,
    float $caloriesBurned,
    ?string $date = null,
    int $userId = 1
): int {
    $db = getDb();
    $date = $date ?: date('Y-m-d');

    $stmt = $db->prepare("
        INSERT INTO olahraga_log (user_id, nama_olahraga, durasi_menit, repetisi, kalori_terbakar, tanggal)
        VALUES (:user_id, :nama_olahraga, :durasi_menit, :repetisi, :kalori_terbakar, :tanggal)
    ");

    $stmt->execute([
        ':user_id'         => $userId,
        ':nama_olahraga'   => trim($name),
        ':durasi_menit'    => round($durationMinutes, 1),
        ':repetisi'        => $repetitions,
        ':kalori_terbakar' => round($caloriesBurned, 1),
        ':tanggal'         => $date
    ]);

    return (int)$db->lastInsertId();
}

/**
 * Get exercise logs for a specific day
 */
function getTodayExerciseLogs(?string $date = null, ?int $userId = null): array {
    $db = getDb();
    $date = $date ?: date('Y-m-d');
    if ($userId !== null) {
        $stmt = $db->prepare("SELECT * FROM olahraga_log WHERE tanggal = :tanggal AND user_id = :uid ORDER BY id_olahraga DESC");
        $stmt->execute([':tanggal' => $date, ':uid' => $userId]);
    } else {
        $stmt = $db->prepare("SELECT * FROM olahraga_log WHERE tanggal = :tanggal ORDER BY id_olahraga DESC");
        $stmt->execute([':tanggal' => $date]);
    }
    return $stmt->fetchAll();
}

/**
 * Get summary of exercise burn for a specific day
 */
function getTodayExerciseSummary(?string $date = null, ?int $userId = null): array {
    $db = getDb();
    $date = $date ?: date('Y-m-d');

    if ($userId !== null) {
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as workout_count,
                COALESCE(SUM(kalori_terbakar), 0) as total_burned,
                COALESCE(SUM(durasi_menit), 0) as total_duration
            FROM olahraga_log
            WHERE tanggal = :tanggal AND user_id = :uid
        ");
        $stmt->execute([':tanggal' => $date, ':uid' => $userId]);
    } else {
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as workout_count,
                COALESCE(SUM(kalori_terbakar), 0) as total_burned,
                COALESCE(SUM(durasi_menit), 0) as total_duration
            FROM olahraga_log
            WHERE tanggal = :tanggal
        ");
        $stmt->execute([':tanggal' => $date]);
    }
    $row = $stmt->fetch();

    return [
        'workout_count'  => (int)($row['workout_count'] ?? 0),
        'total_burned'   => round((float)($row['total_burned'] ?? 0), 1),
        'total_duration' => round((float)($row['total_duration'] ?? 0), 1)
    ];
}

/**
 * Delete a single exercise log entry
 */
function deleteExerciseLog(int $id): bool {
    $db = getDb();
    $stmt = $db->prepare("DELETE FROM olahraga_log WHERE id_olahraga = :id");
    return $stmt->execute([':id' => $id]);
}

/**
 * Curated Meal Recommendations per Program
 */
function getMealRecommendations(string $program = 'bulking'): array {
    $program = strtolower($program);

    if ($program === 'diet') {
        return [
            ['name' => 'Dada Ayam Rebus & Brokoli', 'portion' => '150g ayam + 100g brokoli', 'calories' => 220, 'protein' => 45, 'carbs' => 6, 'fat' => 2.5, 'tag' => 'Tinggi Protein Rendah Kalori'],
            ['name' => '2 Butir Telur Rebus (1 Utuh + 1 Putih)', 'portion' => '100g', 'calories' => 95, 'protein' => 10, 'carbs' => 0.8, 'fat' => 5.2, 'tag' => 'Sarapan Sehat'],
            ['name' => 'Ikan Nila Panggang Sambal Matah', 'portion' => '150g', 'calories' => 190, 'protein' => 28, 'carbs' => 2, 'fat' => 4.5, 'tag' => 'Makan Siang Defisit'],
            ['name' => 'Oatmeal Plain + Potongan Apel', 'portion' => '1 mangkuk (150g)', 'calories' => 140, 'protein' => 4, 'carbs' => 28, 'fat' => 1.8, 'tag' => 'Serat Tinggi Menahan Lapar'],
            ['name' => 'Tahu Putih Kukus + Sayur Bayam Bening', 'portion' => '1 porsi (200g)', 'calories' => 115, 'protein' => 12, 'carbs' => 8, 'fat' => 4.0, 'tag' => 'Makan Malam Ringan']
        ];
    } elseif ($program === 'workout') {
        return [
            ['name' => 'Nasi Merah + Dada Ayam Panggang', 'portion' => '150g nasi + 130g ayam', 'calories' => 380, 'protein' => 35, 'carbs' => 42, 'fat' => 4.2, 'tag' => 'Bahan Bakar Stamina'],
            ['name' => 'Telur Dadar Jamur & Tomat', 'portion' => '2 butir telur (120g)', 'calories' => 185, 'protein' => 14, 'carbs' => 3.5, 'fat' => 11, 'tag' => 'Energi Otot'],
            ['name' => 'Pisang Ambon + Susu Rendah Lemak', 'portion' => '1 buah + 200ml', 'calories' => 195, 'protein' => 8, 'carbs' => 34, 'fat' => 3.0, 'tag' => 'Pre-Workout Booster'],
            ['name' => 'Tempe & Tahu Bacem Bakar', 'portion' => '100g tempe + 100g tahu', 'calories' => 260, 'protein' => 21, 'carbs' => 16, 'fat' => 11, 'tag' => 'Protein Nabati'],
            ['name' => 'Ikan Gurame Bakar Bumbu Kuning', 'portion' => '150g', 'calories' => 210, 'protein' => 26, 'carbs' => 4, 'fat' => 6.5, 'tag' => 'Pemulihan Fisik']
        ];
    } else {
        // Bulking
        return [
            ['name' => 'Nasi Putih + Dada Ayam + Tempe Goreng', 'portion' => '200g nasi + 150g ayam + 50g tempe', 'calories' => 595, 'protein' => 52, 'carbs' => 62, 'fat' => 14, 'tag' => 'Porsi Utama Anabolik'],
            ['name' => 'Oatmeal Jumbo + Selai Kacang + Pisang', 'portion' => '80g oats + 1 pisang + 1 sdm selai', 'calories' => 420, 'protein' => 14, 'carbs' => 68, 'fat' => 12, 'tag' => 'Sarapan Padat Kalori'],
            ['name' => 'Daging Sapi Rendang + Nasi Putih', 'portion' => '100g rendang + 200g nasi', 'calories' => 540, 'protein' => 32, 'carbs' => 58, 'fat' => 19, 'tag' => 'Makan Siang Otot'],
            ['name' => 'Whey Protein Shake + Susu Sapi Murni', 'portion' => '1 scoop + 250ml susu', 'calories' => 280, 'protein' => 32, 'carbs' => 14, 'fat' => 6.5, 'tag' => 'Sintesis Protein Cepat'],
            ['name' => 'Ubi Jalar Rebus + Telur Rebus 3 Butir', 'portion' => '150g ubi + 3 butir telur', 'calories' => 365, 'protein' => 20, 'carbs' => 34, 'fat' => 16, 'tag' => 'Camilan Malam']
        ];
    }
}

/**
 * Curated Workout Recommendations per Program
 */
function getWorkoutRecommendations(string $program = 'bulking'): array {
    $program = strtolower($program);

    if ($program === 'diet') {
        return [
            ['name' => 'Lari / Jogging Kardio', 'intensity' => 'Sedang (10.0 kcal/menit)', 'target' => '30 - 40 Menit', 'burn' => 350, 'desc' => 'Membakar simpanan lemak tubuh secara optimal dalam zona detak jantung aerobik.'],
            ['name' => 'Lompat Tali (Skipping)', 'intensity' => 'Tinggi (11.0 kcal/menit)', 'target' => '15 Menit Interval', 'burn' => 165, 'desc' => 'Kardio pembakar kalori tinggi dalam waktu singkat dan melatih koordinasi.'],
            ['name' => 'HIIT Circuit Workout', 'intensity' => 'Tinggi (12.0 kcal/menit)', 'target' => '20 Menit', 'burn' => 240, 'desc' => 'Kombinasi burpee, jumping jack, dan mountain climber untuk efek afterburn.'],
            ['name' => 'Jalan Kaki Santai (Brisk Walk)', 'intensity' => 'Ringan (4.0 kcal/menit)', 'target' => '45 Menit (8.000 langkah)', 'burn' => 180, 'desc' => 'Aman untuk persendian, membantu membakar lemak tanpa memicu rasa lapar berlebihan.']
        ];
    } elseif ($program === 'workout') {
        return [
            ['name' => 'Full Body Functional Circuit', 'intensity' => 'Sedang (8.0 kcal/menit)', 'target' => '30 Menit', 'burn' => 240, 'desc' => 'Melatih kelincahan, pengencangan seluruh tubuh, dan kekuatan inti (core).'],
            ['name' => 'Berenang Bebas / Gaya Dada', 'intensity' => 'Sedang-Tinggi (9.0 kcal/menit)', 'target' => '30 Menit', 'burn' => 270, 'desc' => 'Melatih kapasitas paru-paru dan seluruh otot tubuh tanpa beban tumpuan sendi.'],
            ['name' => 'Bersepeda Luar / Statis', 'intensity' => 'Sedang (8.5 kcal/menit)', 'target' => '30 Menit', 'burn' => 255, 'desc' => 'Menguatkan otot kaki, betis, dan membakar kalori secara konsisten.'],
            ['name' => 'Bodyweight Calisthenics Routine', 'intensity' => 'Repetisi', 'target' => 'Push Up 40x, Sit Up 50x, Squat 50x', 'burn' => 75, 'desc' => 'Mempertahankan kekencangan dan tonus otot di rumah tanpa alat.']
        ];
    } else {
        // Bulking
        return [
            ['name' => 'Latihan Beban: Push Day (Dada, Bahu, Triceps)', 'intensity' => 'Beban Berat (6.5 kcal/menit)', 'target' => '45 - 60 Menit', 'burn' => 320, 'desc' => 'Bench Press, Incline Dumbbell Press, Shoulder Press dengan prinsip beban progresif.'],
            ['name' => 'Latihan Beban: Pull Day (Punggung & Biceps)', 'intensity' => 'Beban Berat (6.5 kcal/menit)', 'target' => '45 - 60 Menit', 'burn' => 310, 'desc' => 'Deadlift, Lat Pulldown, Barbell Rows, Barbell Curl untuk melebarkan otot sayap.'],
            ['name' => 'Latihan Beban: Leg Day (Kaki & Core)', 'intensity' => 'Beban Berat (7.5 kcal/menit)', 'target' => '45 - 50 Menit', 'burn' => 350, 'desc' => 'Barbell Squat, Romanian Deadlift, Leg Press untuk merangsang produksi hormon anabolik.'],
            ['name' => 'Latihan Repetisi: Pull Up & Push Up Ekstra', 'intensity' => 'Repetisi Beban Tubuh', 'target' => 'Pull Up 30x + Push Up 60x', 'burn' => 60, 'desc' => 'Finisher latihan untuk hipertrofi dan kekuatan fungsional tubuh bagian atas.']
        ];
    }
}

/**
 * Offline Food Database
 */
function getOfflineFoodDatabase(): array {
    return [
        ['name' => 'Nasi Putih (Cooked White Rice)', 'serving_size_g' => 150, 'calories' => 195, 'protein_g' => 4.0, 'carbohydrates_total_g' => 43.0, 'fat_total_g' => 0.4],
        ['name' => 'Nasi Merah (Brown Rice)', 'serving_size_g' => 150, 'calories' => 165, 'protein_g' => 3.8, 'carbohydrates_total_g' => 35.0, 'fat_total_g' => 1.2],
        ['name' => 'Dada Ayam Panggang (Grilled Chicken Breast)', 'serving_size_g' => 100, 'calories' => 165, 'protein_g' => 31.0, 'carbohydrates_total_g' => 0.0, 'fat_total_g' => 3.6],
        ['name' => 'Dada Ayam Rebus (Boiled Chicken Breast)', 'serving_size_g' => 100, 'calories' => 150, 'protein_g' => 30.5, 'carbohydrates_total_g' => 0.0, 'fat_total_g' => 2.8],
        ['name' => 'Telur Ayam Utuh Rebus (Whole Boiled Egg)', 'serving_size_g' => 50, 'calories' => 77, 'protein_g' => 6.3, 'carbohydrates_total_g' => 0.6, 'fat_total_g' => 5.3],
        ['name' => 'Putih Telur Rebus (Egg White)', 'serving_size_g' => 33, 'calories' => 17, 'protein_g' => 3.6, 'carbohydrates_total_g' => 0.2, 'fat_total_g' => 0.1],
        ['name' => 'Tempe Goreng (Fried Tempeh)', 'serving_size_g' => 50, 'calories' => 118, 'protein_g' => 7.0, 'carbohydrates_total_g' => 5.2, 'fat_total_g' => 7.5],
        ['name' => 'Tempe Bacem / Rebus (Tempeh)', 'serving_size_g' => 50, 'calories' => 96, 'protein_g' => 9.5, 'carbohydrates_total_g' => 4.5, 'fat_total_g' => 5.0],
        ['name' => 'Tahu Putih / Rebus (Tofu)', 'serving_size_g' => 100, 'calories' => 76, 'protein_g' => 8.0, 'carbohydrates_total_g' => 1.9, 'fat_total_g' => 4.8],
        ['name' => 'Tahu Goreng (Fried Tofu)', 'serving_size_g' => 100, 'calories' => 115, 'protein_g' => 9.2, 'carbohydrates_total_g' => 2.5, 'fat_total_g' => 7.8],
        ['name' => 'Daging Sapi Panggang (Lean Beef)', 'serving_size_g' => 100, 'calories' => 210, 'protein_g' => 26.0, 'carbohydrates_total_g' => 0.0, 'fat_total_g' => 11.5],
        ['name' => 'Rendang Daging Sapi (Beef Rendang)', 'serving_size_g' => 100, 'calories' => 280, 'protein_g' => 22.0, 'carbohydrates_total_g' => 6.0, 'fat_total_g' => 19.0],
        ['name' => 'Ikan Salmon Panggang (Grilled Salmon)', 'serving_size_g' => 100, 'calories' => 206, 'protein_g' => 22.0, 'carbohydrates_total_g' => 0.0, 'fat_total_g' => 12.3],
        ['name' => 'Ikan Gurame / Nila Bakar (Grilled Fish)', 'serving_size_g' => 100, 'calories' => 128, 'protein_g' => 20.0, 'carbohydrates_total_g' => 0.0, 'fat_total_g' => 4.5],
        ['name' => 'Oatmeal (Rolled Oats cooked)', 'serving_size_g' => 100, 'calories' => 71, 'protein_g' => 2.5, 'carbohydrates_total_g' => 12.0, 'fat_total_g' => 1.5],
        ['name' => 'Whey Protein Shake (1 Scoop)', 'serving_size_g' => 30, 'calories' => 120, 'protein_g' => 24.0, 'carbohydrates_total_g' => 2.0, 'fat_total_g' => 1.5],
        ['name' => 'Pisang (Banana)', 'serving_size_g' => 100, 'calories' => 89, 'protein_g' => 1.1, 'carbohydrates_total_g' => 22.8, 'fat_total_g' => 0.3],
        ['name' => 'Apel (Apple)', 'serving_size_g' => 100, 'calories' => 52, 'protein_g' => 0.3, 'carbohydrates_total_g' => 13.8, 'fat_total_g' => 0.2],
        ['name' => 'Alpukat (Avocado)', 'serving_size_g' => 100, 'calories' => 160, 'protein_g' => 2.0, 'carbohydrates_total_g' => 8.5, 'fat_total_g' => 14.7],
        ['name' => 'Ubi Rebus (Boiled Sweet Potato)', 'serving_size_g' => 100, 'calories' => 86, 'protein_g' => 1.6, 'carbohydrates_total_g' => 20.1, 'fat_total_g' => 0.1],
        ['name' => 'Kentang Rebus (Boiled Potato)', 'serving_size_g' => 100, 'calories' => 87, 'protein_g' => 1.9, 'carbohydrates_total_g' => 20.0, 'fat_total_g' => 0.1],
        ['name' => 'Susu Sapi Low Fat (Low Fat Milk)', 'serving_size_g' => 200, 'calories' => 102, 'protein_g' => 6.8, 'carbohydrates_total_g' => 9.8, 'fat_total_g' => 4.0],
        ['name' => 'Brokoli Rebus (Boiled Broccoli)', 'serving_size_g' => 100, 'calories' => 35, 'protein_g' => 2.4, 'carbohydrates_total_g' => 7.2, 'fat_total_g' => 0.4]
    ];
}

/**
 * Search CalorieNinjas API with fallback
 */
function searchFoodNutrition(string $query): array {
    $apiKey = 'pCjiAjuWkNTVfyqKocoaTQ==Muah9DT1TKQXZchH';
    $results = [];

    if (function_exists('curl_init') && !empty($query)) {
        $ch = curl_init('https://api.calorieninjas.com/v1/nutrition?query=' . urlencode($query));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Api-Key: ' . $apiKey]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $json = json_decode($response, true);
            if (isset($json['items']) && is_array($json['items']) && count($json['items']) > 0) {
                foreach ($json['items'] as $item) {
                    $results[] = [
                        'name'                   => ucfirst($item['name']),
                        'serving_size_g'         => (float)($item['serving_size_g'] ?? 100),
                        'calories'               => (float)($item['calories'] ?? 0),
                        'protein_g'              => (float)($item['protein_g'] ?? 0),
                        'carbohydrates_total_g'  => (float)($item['carbohydrates_total_g'] ?? 0),
                        'fat_total_g'            => (float)($item['fat_total_g'] ?? 0),
                        'source'                 => 'calorieninjas'
                    ];
                }
            }
        }
    }

    $localDb = getOfflineFoodDatabase();
    $qLower = strtolower(trim($query));
    foreach ($localDb as $item) {
        if (strpos(strtolower($item['name']), $qLower) !== false) {
            $item['source'] = 'local';
            $results[] = $item;
        }
    }

    if (empty($results) && empty($query)) {
        return array_slice($localDb, 0, 6);
    }

    return $results;
}
