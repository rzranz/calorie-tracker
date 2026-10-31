<?php
/**
 * Core Business Logic, Calculations & Data Access Helper
 */

require_once __DIR__ . '/../config/database.php';

function getDb(): PDO {
    return Database::getConnection();
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
 * Determine BMI Category, badge class and description
 */
function getBMICategory(float $bmi): array {
    if ($bmi <= 0) {
        return ['slug' => 'unknown', 'title' => 'Tidak Diketahui', 'badge' => 'badge-secondary', 'color' => '#64748b'];
    }
    if ($bmi < 18.5) {
        return [
            'slug' => 'kurus',
            'title' => 'Kurus (Underweight)',
            'recommendation' => 'Disarankan program Bulking sehat untuk menaikkan massa otot dan mencapai berat badan ideal.',
            'default_goal' => 'bulking',
            'badge' => 'badge-blue',
            'color' => '#3b82f6'
        ];
    } elseif ($bmi < 25.0) {
        return [
            'slug' => 'ideal',
            'title' => 'Ideal (Normal)',
            'recommendation' => 'Kondisi fisik optimal. Anda dapat memilih Lean Bulking untuk menambah massa otot atau Maintenance.',
            'default_goal' => 'bulking',
            'badge' => 'badge-emerald',
            'color' => '#10b981'
        ];
    } elseif ($bmi < 30.0) {
        return [
            'slug' => 'gemuk',
            'title' => 'Gemuk (Overweight)',
            'recommendation' => 'Disarankan program Diet Defisit Kalori teratur disertai latihan beban & kardio.',
            'default_goal' => 'diet',
            'badge' => 'badge-amber',
            'color' => '#f59e0b'
        ];
    } else {
        return [
            'slug' => 'obesitas',
            'title' => 'Obesitas (Obese)',
            'recommendation' => 'Disarankan program Diet terstruktur, pengawasan asupan kalori harian, dan olahraga berkala.',
            'default_goal' => 'diet',
            'badge' => 'badge-rose',
            'color' => '#ef4444'
        ];
    }
}

/**
 * Calculate BMR using Mifflin-St Jeor formula
 */
function calculateBMR(float $weightKg, float $heightCm, int $age, string $gender = 'Laki-laki'): float {
    if ($weightKg <= 0 || $heightCm <= 0 || $age <= 0) return 0.0;
    
    // Mifflin-St Jeor Equation
    if (strtolower($gender) === 'perempuan' || strtolower($gender) === 'female') {
        return round((10 * $weightKg) + (6.25 * $heightCm) - (5 * $age) - 161, 1);
    }
    // Male formula
    return round((10 * $weightKg) + (6.25 * $heightCm) - (5 * $age) + 5, 1);
}

/**
 * Calculate Total Daily Energy Expenditure (TDEE)
 */
function calculateTDEE(float $bmr, string $activity = 'moderate'): float {
    $factors = [
        'sedentary'   => 1.2,    // Tidak aktif / kantor
        'light'       => 1.375,  // 1-3 hari olahraga ringan
        'moderate'    => 1.55,   // 3-5 hari olahraga sedang
        'active'      => 1.725,  // 6-7 hari olahraga berat
        'very_active' => 1.9     // Atlet / aktivitas fisik sangat tinggi
    ];
    $factor = $factors[$activity] ?? 1.55;
    return round($bmr * $factor);
}

/**
 * Calculate recommended daily calorie target based on goal
 */
function calculateCalorieTarget(float $tdee, string $goal = 'bulking'): int {
    switch (strtolower($goal)) {
        case 'bulking':
            return (int)round($tdee + 400); // Surplus kalori untuk hipertrofi otot
        case 'diet':
        case 'cutting':
            return (int)round(max(1200, $tdee - 450)); // Defisit moderat aman
        case 'maintenance':
        default:
            return (int)round($tdee);
    }
}

/**
 * Calculate macronutrient breakdown
 */
function calculateMacros(float $weightKg, int $targetCalories, string $goal = 'bulking'): array {
    // Protein target in grams
    $proteinPerKg = (strtolower($goal) === 'bulking') ? 2.0 : ((strtolower($goal) === 'diet') ? 1.8 : 1.5);
    $proteinGrams = round($weightKg * $proteinPerKg);
    $proteinCalories = $proteinGrams * 4;

    // Fat target: ~25% of total calories (9 kcal/g)
    $fatCalories = $targetCalories * 0.25;
    $fatGrams = round($fatCalories / 9);

    // Carbs: remaining calories (4 kcal/g)
    $carbCalories = max(0, $targetCalories - $proteinCalories - $fatCalories);
    $carbGrams = round($carbCalories / 4);

    return [
        'protein_g' => (int)$proteinGrams,
        'carbs_g'   => (int)$carbGrams,
        'fat_g'     => (int)$fatGrams,
    ];
}

/**
 * Save weigh-in record to database
 */
function saveWeightLog(
    float $weight,
    float $height,
    int $age = 25,
    string $gender = 'Laki-laki',
    string $activity = 'moderate',
    string $goal = 'bulking',
    ?string $date = null
): int {
    $db = getDb();
    $date = $date ?: date('Y-m-d');
    $bmi = calculateBMI($weight, $height);
    $cat = getBMICategory($bmi);
    $categorySlug = $cat['slug'];

    $bmr = calculateBMR($weight, $height, $age, $gender);
    $tdee = calculateTDEE($bmr, $activity);
    $calorieTarget = calculateCalorieTarget($tdee, $goal);

    $stmt = $db->prepare("
        INSERT INTO beratbadan (berat, tinggi, usia, gender, aktivitas, bmi, kategori, kalori_harian, target_program, tanggal)
        VALUES (:berat, :tinggi, :usia, :gender, :aktivitas, :bmi, :kategori, :kalori_harian, :target_program, :tanggal)
    ");

    $stmt->execute([
        ':berat'          => $weight,
        ':tinggi'         => $height,
        ':usia'           => $age,
        ':gender'         => $gender,
        ':aktivitas'      => $activity,
        ':bmi'            => $bmi,
        ':kategori'       => $categorySlug,
        ':kalori_harian'  => (string)$calorieTarget,
        ':target_program' => $goal,
        ':tanggal'        => $date
    ]);

    return (int)$db->lastInsertId();
}

/**
 * Get latest weigh-in record
 */
function getLatestWeightLog(): ?array {
    $db = getDb();
    $stmt = $db->query("SELECT * FROM beratbadan ORDER BY id_beratbadan DESC LIMIT 1");
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Get weight history list
 */
function getWeightHistory(int $limit = 30): array {
    $db = getDb();
    $stmt = $db->prepare("SELECT * FROM beratbadan ORDER BY tanggal DESC, id_beratbadan DESC LIMIT :limit");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Delete weight log by id
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
    ?string $date = null
): int {
    $db = getDb();
    $date = $date ?: date('Y-m-d');

    $stmt = $db->prepare("
        INSERT INTO hitung_makanan (makanan, berat, kalori, protein, karbohidrat, lemak, tanggal)
        VALUES (:makanan, :berat, :kalori, :protein, :karbohidrat, :lemak, :tanggal)
    ");

    $stmt->execute([
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
function getTodayFoodLogs(?string $date = null): array {
    $db = getDb();
    $date = $date ?: date('Y-m-d');
    $stmt = $db->prepare("SELECT * FROM hitung_makanan WHERE tanggal = :tanggal ORDER BY id_hitung DESC");
    $stmt->execute([':tanggal' => $date]);
    return $stmt->fetchAll();
}

/**
 * Get summary of food consumption for a specific date
 */
function getTodayFoodSummary(?string $date = null): array {
    $db = getDb();
    $date = $date ?: date('Y-m-d');

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
function resetTodayFoodLogs(?string $date = null): bool {
    $db = getDb();
    $date = $date ?: date('Y-m-d');
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
    ?string $date = null
): int {
    $db = getDb();
    $date = $date ?: date('Y-m-d');

    $stmt = $db->prepare("
        INSERT INTO olahraga_log (nama_olahraga, durasi_menit, repetisi, kalori_terbakar, tanggal)
        VALUES (:nama_olahraga, :durasi_menit, :repetisi, :kalori_terbakar, :tanggal)
    ");

    $stmt->execute([
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
function getTodayExerciseLogs(?string $date = null): array {
    $db = getDb();
    $date = $date ?: date('Y-m-d');
    $stmt = $db->prepare("SELECT * FROM olahraga_log WHERE tanggal = :tanggal ORDER BY id_olahraga DESC");
    $stmt->execute([':tanggal' => $date]);
    return $stmt->fetchAll();
}

/**
 * Get summary of exercise burn for a specific day
 */
function getTodayExerciseSummary(?string $date = null): array {
    $db = getDb();
    $date = $date ?: date('Y-m-d');

    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as workout_count,
            COALESCE(SUM(kalori_terbakar), 0) as total_burned,
            COALESCE(SUM(durasi_menit), 0) as total_duration
        FROM olahraga_log
        WHERE tanggal = :tanggal
    ");
    $stmt->execute([':tanggal' => $date]);
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
 * Offline Food Database for immediate local search & backup
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
 * Search CalorieNinjas API with offline database fallback
 */
function searchFoodNutrition(string $query): array {
    $apiKey = 'pCjiAjuWkNTVfyqKocoaTQ==Muah9DT1TKQXZchH';
    $results = [];

    // Try online API first if cURL is available
    if (function_exists('curl_init') && !empty($query)) {
        $ch = curl_init('https://api.calorieninjas.com/v1/nutrition?query=' . urlencode($query));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-Api-Key: ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4); // Fast timeout to avoid blocking
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

    // Match in local database as supplement or fallback
    $localDb = getOfflineFoodDatabase();
    $qLower = strtolower(trim($query));
    foreach ($localDb as $item) {
        if (strpos(strtolower($item['name']), $qLower) !== false) {
            $item['source'] = 'local';
            $results[] = $item;
        }
    }

    // If query is empty or still no results, suggest top 6 local items
    if (empty($results) && empty($query)) {
        return array_slice($localDb, 0, 6);
    }

    return $results;
}
