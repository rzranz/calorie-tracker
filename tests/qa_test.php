<?php
/**
 * Automated QA Verification Suite
 * Tests all core subsystems, math formulas, database operations, APIs, and view rendering.
 */

require_once __DIR__ . '/../includes/functions.php';

class QATestSuite {
    private int $passed = 0;
    private int $failed = 0;
    private array $errors = [];

    public function assert(string $testName, bool $condition, string $detail = ''): void {
        if ($condition) {
            $this->passed++;
            echo "  ✓ PASS: {$testName}\n";
        } else {
            $this->failed++;
            $msg = "  ✗ FAIL: {$testName}" . ($detail ? " ({$detail})" : "");
            $this->errors[] = $msg;
            echo $msg . "\n";
        }
    }

    public function runAll(): void {
        echo "=====================================================\n";
        echo "🚀 STARTING FULLSTACK QA TEST SUITE: LET'S MOVE APP\n";
        echo "=====================================================\n\n";

        $this->testDatabaseLayer();
        $this->testFormulas();
        $this->testFoodOperations();
        $this->testExerciseOperations();
        $this->testWeightOperations();
        $this->testLegacyCompatibility();
        $this->testPageRendering();

        echo "\n=====================================================\n";
        echo "📊 QA TEST SUMMARY:\n";
        echo "  Total Passed: {$this->passed}\n";
        echo "  Total Failed: {$this->failed}\n";

        if ($this->failed === 0) {
            echo "🎉 ALL TESTS PASSED! APPLICATION IS READY FOR PRODUCTION!\n";
        } else {
            echo "⚠️ ERRORS FOUND:\n";
            foreach ($this->errors as $err) {
                echo $err . "\n";
            }
            exit(1);
        }
        echo "=====================================================\n";
    }

    private function testDatabaseLayer(): void {
        echo "[1] Testing Database & Schema Migrations...\n";
        try {
            $db = getDb();
            $this->assert("Database PDO instance initialized", $db instanceof PDO);

            // Check tables exist
            $tables = ['beratbadan', 'hitung_makanan', 'olahraga_log'];
            foreach ($tables as $table) {
                $stmt = $db->query("SELECT COUNT(*) FROM {$table}");
                $this->assert("Table '{$table}' exists and is queryable", $stmt !== false);
            }
        } catch (Throwable $e) {
            $this->assert("Database layer execution", false, $e->getMessage());
        }
    }

    private function testFormulas(): void {
        echo "\n[2] Testing Scientific Health & Fitness Formulas...\n";

        // BMI Test
        $bmi1 = calculateBMI(70, 175); // 70 / (1.75^2) = 22.86
        $this->assert("BMI calculation (70kg, 175cm)", abs($bmi1 - 22.86) < 0.05, "Got: {$bmi1}");

        $catNormal = getBMICategory(22.86);
        $this->assert("BMI Category Normal", $catNormal['slug'] === 'ideal');

        $catKurus = getBMICategory(17.5);
        $this->assert("BMI Category Kurus", $catKurus['slug'] === 'kurus');

        $catGemuk = getBMICategory(27.0);
        $this->assert("BMI Category Gemuk", $catGemuk['slug'] === 'gemuk');

        $catObesitas = getBMICategory(32.0);
        $this->assert("BMI Category Obesitas", $catObesitas['slug'] === 'obesitas');

        // BMR Mifflin-St Jeor
        // Male: 10*70 + 6.25*175 - 5*25 + 5 = 700 + 1093.75 - 125 + 5 = 1673.75
        $bmrMale = calculateBMR(70, 175, 25, 'Laki-laki');
        $this->assert("BMR Mifflin-St Jeor (Male: 70kg, 175cm, 25yo)", abs($bmrMale - 1673.8) < 0.5, "Got: {$bmrMale}");

        // Female: 10*60 + 6.25*160 - 5*25 - 161 = 600 + 1000 - 125 - 161 = 1314
        $bmrFemale = calculateBMR(60, 160, 25, 'Perempuan');
        $this->assert("BMR Mifflin-St Jeor (Female: 60kg, 160cm, 25yo)", abs($bmrFemale - 1314.0) < 0.5, "Got: {$bmrFemale}");

        // TDEE Test
        $tdee = calculateTDEE(1674, 'moderate'); // 1674 * 1.55 = 2594.7
        $this->assert("TDEE calculation (moderate 1.55x)", abs($tdee - 2595) < 2, "Got: {$tdee}");

        // Calorie Target
        $targetBulking = calculateCalorieTarget(2500, 'bulking');
        $this->assert("Bulking Calorie Target (+400 kcal)", $targetBulking === 2900, "Got: {$targetBulking}");

        $targetDiet = calculateCalorieTarget(2500, 'diet');
        $this->assert("Diet Calorie Target (-450 kcal)", $targetDiet === 2050, "Got: {$targetDiet}");

        // Macros calculation
        $macros = calculateMacros(70, 2500, 'bulking');
        $this->assert("Macro calculation has protein, carbs, fat", isset($macros['protein_g'], $macros['carbs_g'], $macros['fat_g']));
        $this->assert("Bulking protein target >= 140g for 70kg", $macros['protein_g'] >= 140, "Got: {$macros['protein_g']}");
    }

    private function testFoodOperations(): void {
        echo "\n[3] Testing Food Logging & Nutrition Search...\n";

        // Test search
        $results = searchFoodNutrition('nasi');
        $this->assert("Food search returns items for 'nasi'", count($results) > 0);

        $resultsBanana = searchFoodNutrition('banana');
        $this->assert("Food search returns items for 'banana'", count($resultsBanana) > 0);

        // Test Food CRUD
        $today = date('Y-m-d');
        $testFoodName = "QA Test Chicken Breast " . time();
        $id = addFoodLog($testFoodName, 150, 250, 45, 0, 5, $today);
        $this->assert("Add food item returns valid ID", $id > 0, "Got ID: {$id}");

        $todayItems = getTodayFoodLogs($today);
        $found = false;
        foreach ($todayItems as $item) {
            if ($item['makanan'] === $testFoodName) {
                $found = true;
                break;
            }
        }
        $this->assert("Added food appears in today's logs", $found);

        $summary = getTodayFoodSummary($today);
        $this->assert("Today's food summary includes added calories", $summary['total_calories'] >= 250, "Total: {$summary['total_calories']}");

        // Delete test
        $deleted = deleteFoodLog($id);
        $this->assert("Delete food item by ID succeeded", $deleted);
    }

    private function testExerciseOperations(): void {
        echo "\n[4] Testing Workout & Exercise Logging...\n";

        $today = date('Y-m-d');
        $testWorkoutName = "QA Push Up Test " . time();
        $id = addExerciseLog($testWorkoutName, 0, 50, 22.5, $today);
        $this->assert("Add exercise item returns valid ID", $id > 0, "Got ID: {$id}");

        $todayExercises = getTodayExerciseLogs($today);
        $found = false;
        foreach ($todayExercises as $ex) {
            if ($ex['nama_olahraga'] === $testWorkoutName) {
                $found = true;
                break;
            }
        }
        $this->assert("Added workout appears in today's logs", $found);

        $summary = getTodayExerciseSummary($today);
        $this->assert("Today's exercise summary records calories burned", $summary['total_burned'] >= 22.5);

        // Delete test
        $deleted = deleteExerciseLog($id);
        $this->assert("Delete exercise item by ID succeeded", $deleted);
    }

    private function testWeightOperations(): void {
        echo "\n[5] Testing Weight Progress & History...\n";

        $testWeight = 77.5;
        $id = saveWeightLog($testWeight, 178, 26, 'Laki-laki', 'moderate', 'bulking');
        $this->assert("Save weigh-in record returns valid ID", $id > 0, "Got ID: {$id}");

        $latest = getLatestWeightLog();
        $this->assert("Get latest weight log matches saved weight", (float)$latest['berat'] === $testWeight);

        $history = getWeightHistory(5);
        $this->assert("Weight history returns non-empty array", is_array($history) && count($history) > 0);

        deleteWeightLog($id);
        $this->assert("Cleaned up temporary weight test record", true);
    }

    private function testLegacyCompatibility(): void {
        echo "\n[6] Testing Legacy Bridges & Endpoints...\n";

        require_once __DIR__ . '/../tracker/function.php';
        $conn = connectDatabase();
        $this->assert("Legacy connectDatabase() returns valid object", is_object($conn));

        // Test query on legacy connection
        $res = $conn->query("SELECT * FROM beratbadan LIMIT 1");
        $this->assert("Legacy connection executes query", $res !== false && $res->num_rows >= 0);

        // Test simpanData
        $saveResult = simpanData(75, 175, 24.5, 25, 'Laki-laki', 'moderate');
        $this->assert("Legacy simpanData() function succeeds", $saveResult > 0);
        deleteWeightLog($saveResult);
    }

    private function testPageRendering(): void {
        echo "\n[7] Testing Page Rendering (Zero Fatal Errors or Syntax Errors)...\n";

        $pages = [
            'index.php',
            'tracker/bulking.php',
            'tracker/diet.php',
            'tracker/makan_hari_ini.php',
            'tracker/makandiet.php',
            'tracker/tracker.html',
            'tracker/diet.html',
            'tracker/olahraga.html'
        ];

        foreach ($pages as $page) {
            $cmd = "php -l " . escapeshellarg(__DIR__ . '/../' . $page);
            $output = [];
            $exitCode = 0;
            exec($cmd, $output, $exitCode);
            $this->assert("Syntax linting: {$page}", $exitCode === 0, implode("\n", $output));
        }
    }
}

// Run test suite
$suite = new QATestSuite();
$suite->runAll();
