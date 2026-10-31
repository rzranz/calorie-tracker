<?php
/**
 * Seeder Script: Populate Realistic Dummy Progress for Randi (user_id = 5)
 */

require_once __DIR__ . '/../includes/functions.php';

$db = getDb();

// 1. Identify Randi
$stmt = $db->query("SELECT * FROM users WHERE nama LIKE '%Randi%' OR email LIKE '%rzranz%' LIMIT 1");
$randi = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$randi) {
    die("User Randi tidak ditemukan!\n");
}

$userId = (int)$randi['id_user'];
echo "Menyiapkan dummy progress untuk user: {$randi['nama']} (ID: $userId, Email: {$randi['email']})\n";

// Update current profile to ensure consistent values
$db->prepare("UPDATE users SET berat = 86.0, tinggi = 180.0, usia = 22, gender = 'Laki-laki', aktivitas = 'moderate', program = 'workout', target_kalori = 2914, bmi = 26.54 WHERE id_user = :uid")
   ->execute([':uid' => $userId]);

// 2. Clear old test records for Randi to prevent duplicates
$db->prepare("DELETE FROM beratbadan WHERE user_id = :uid")->execute([':uid' => $userId]);
$db->prepare("DELETE FROM hitung_makanan WHERE user_id = :uid")->execute([':uid' => $userId]);
$db->prepare("DELETE FROM olahraga_log WHERE user_id = :uid")->execute([':uid' => $userId]);

// 3. Insert progressive weight history (10 weigh-ins showing healthy steady progress)
$weightProgress = [
    ['date' => '2026-10-12', 'weight' => 89.2, 'tdee' => 2962],
    ['date' => '2026-10-15', 'weight' => 88.7, 'tdee' => 2954],
    ['date' => '2026-10-18', 'weight' => 88.1, 'tdee' => 2944],
    ['date' => '2026-10-21', 'weight' => 87.6, 'tdee' => 2936],
    ['date' => '2026-10-24', 'weight' => 87.2, 'tdee' => 2930],
    ['date' => '2026-10-27', 'weight' => 86.8, 'tdee' => 2924],
    ['date' => '2026-10-29', 'weight' => 86.4, 'tdee' => 2918],
    ['date' => '2026-10-31', 'weight' => 86.1, 'tdee' => 2914],
    ['date' => '2026-11-01', 'weight' => 86.0, 'tdee' => 2914]
];

$insWeight = $db->prepare("
    INSERT INTO beratbadan (user_id, berat, tinggi, usia, gender, aktivitas, bmi, kategori, kalori_harian, target_program, tanggal)
    VALUES (:uid, :berat, 180, 22, 'Laki-laki', 'moderate', :bmi, 'gemuk', :kalori, 'workout', :tanggal)
");

foreach ($weightProgress as $w) {
    $bmi = round($w['weight'] / ((180/100) * (180/100)), 2);
    $insWeight->execute([
        ':uid'     => $userId,
        ':berat'   => $w['weight'],
        ':bmi'     => $bmi,
        ':kalori'  => (string)$w['tdee'],
        ':tanggal' => $w['date']
    ]);
}
echo "✓ " . count($weightProgress) . " catatan histori berat badan & tren grafik berhasil dibuat.\n";

// 4. Insert Food Logs (Today: 2026-11-01 and Yesterday: 2026-10-31)
$insFood = $db->prepare("
    INSERT INTO hitung_makanan (user_id, makanan, berat, kalori, protein, karbohidrat, lemak, tanggal)
    VALUES (:uid, :makanan, :berat, :kalori, :protein, :karbohidrat, :lemak, :tanggal)
");

$foodToday = [
    // Sarapan
    ['Oatmeal Pisang & Susu Rendah Lemak', 250, 360.0, 16.5, 58.0, 6.0],
    ['Telur Rebus (2 butir)', 100, 155.0, 13.0, 1.1, 10.6],
    // Makan Siang
    ['Nasi Merah Porsi Sedang', 150, 165.0, 3.8, 34.0, 1.2],
    ['Dada Ayam Panggang Bumbu Kuning', 180, 297.0, 55.8, 2.0, 6.5],
    ['Sayur Brokoli & Wortel Rebus', 120, 45.0, 3.2, 8.0, 0.5],
    // Camilan Sore (Pre-Workout)
    ['Pisang Ambon', 120, 105.0, 1.3, 27.0, 0.3],
    ['Tempe Bacem Bakar', 100, 180.0, 15.0, 12.0, 7.0],
    // Makan Malam
    ['Nasi Putih Porsi Sedang', 150, 195.0, 4.0, 42.0, 0.5],
    ['Ikan Gurame Bakar', 150, 240.0, 32.0, 2.0, 11.0],
    ['Tahu Rebus', 100, 78.0, 8.5, 1.8, 4.8]
];

foreach (['2026-10-31', '2026-11-01'] as $date) {
    foreach ($foodToday as $f) {
        $insFood->execute([
            ':uid'         => $userId,
            ':makanan'     => $f[0],
            ':berat'       => $f[1],
            ':kalori'      => $f[2],
            ':protein'     => $f[3],
            ':karbohidrat' => $f[4],
            ':lemak'       => $f[5],
            ':tanggal'     => $date
        ]);
    }
}
echo "✓ " . (count($foodToday) * 2) . " jurnal makanan hari ini dan kemarin berhasil dicatat.\n";

// 5. Insert Workout Logs (Today: 2026-11-01 and Yesterday: 2026-10-31)
$insWorkout = $db->prepare("
    INSERT INTO olahraga_log (user_id, nama_olahraga, durasi_menit, repetisi, kalori_terbakar, tanggal)
    VALUES (:uid, :nama, :durasi, :repetisi, :terbakar, :tanggal)
");

$workoutsToday = [
    ['Lari (Jogging)', 30.0, 0, 300.0],
    ['Angkat Beban', 45.0, 0, 292.5],
    ['Push Up', 0.0, 60, 27.0]
];

$workoutsYesterday = [
    ['Bersepeda Sedang', 40.0, 0, 340.0],
    ['Pull Up', 0.0, 35, 35.0],
    ['Squat Bodyweight', 0.0, 80, 40.0]
];

foreach ($workoutsToday as $w) {
    $insWorkout->execute([
        ':uid'      => $userId,
        ':nama'     => $w[0],
        ':durasi'   => $w[1],
        ':repetisi' => $w[2],
        ':terbakar' => $w[3],
        ':tanggal'  => '2026-11-01'
    ]);
}

foreach ($workoutsYesterday as $w) {
    $insWorkout->execute([
        ':uid'      => $userId,
        ':nama'     => $w[0],
        ':durasi'   => $w[1],
        ':repetisi' => $w[2],
        ':terbakar' => $w[3],
        ':tanggal'  => '2026-10-31'
    ]);
}
echo "✓ " . (count($workoutsToday) + count($workoutsYesterday)) . " sesi latihan berhasil dicatat.\n";

echo "\n=== RINGKASAN PROGRESS BARU RANDI ===\n";
$todayFood = NutritionService::getTodayFoodSummary(null, $userId);
$todayEx = WorkoutService::getTodayExerciseSummary(null, $userId);
$history = FitnessService::getWeightHistory(15, $userId);

$netCal = max(0, $todayFood['total_calories'] - $todayEx['total_burned']);
$persen = round(($netCal / $randi['target_kalori']) * 100);

echo "Target Kalori: {$randi['target_kalori']} kcal\n";
echo "Kalori Masuk: {$todayFood['total_calories']} kcal ({$todayFood['item_count']} item)\n";
echo "Kalori Terbakar: {$todayEx['total_burned']} kcal ({$todayEx['workout_count']} sesi)\n";
echo "Kalori Bersih (Net): $netCal kcal ($persen% dari target harian)\n";
echo "Protein Hari Ini: {$todayFood['total_protein']} g (Target minimum: 129g)\n";
echo "Total Titik Data Timbangan: " . count($history) . " data poin (dari {$history[count($history)-1]['berat']} kg -> {$history[0]['berat']} kg)\n";
echo "STATUS: SUKSES PENUH!\n";
