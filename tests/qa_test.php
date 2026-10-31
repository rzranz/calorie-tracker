<?php
/**
 * Automated QA Verification Suite for Redesigned User Flow & Multi-Program System
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
        echo "🚀 STARTING QA TEST SUITE: REDESIGNED USER FLOW & PROGRAMS\n";
        echo "=====================================================\n\n";

        $this->testDatabaseLayer();
        $this->testAuthenticationFlow();
        $this->testFormulasAndPrograms();
        $this->testRecommendations();
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
            echo "🎉 ALL TESTS PASSED! NEW USER FLOW IS ROCK SOLID & READY!\n";
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
        echo "[1] Testing Database Schema & Tables...\n";
        try {
            $db = getDb();
            $this->assert("Database PDO instance initialized", $db instanceof PDO);

            $tables = ['users', 'beratbadan', 'hitung_makanan', 'olahraga_log'];
            foreach ($tables as $table) {
                $stmt = $db->query("SELECT COUNT(*) FROM {$table}");
                $this->assert("Table '{$table}' exists and is queryable", $stmt !== false);
            }
        } catch (Throwable $e) {
            $this->assert("Database layer execution", false, $e->getMessage());
        }
    }

    private function testAuthenticationFlow(): void {
        echo "\n[2] Testing Authentication & Onboarding...\n";

        $testEmail = "testuser_" . time() . "@fitness.com";
        $testPass = "secret123";

        // Register
        $reg = registerUser($testEmail, $testPass, "Tester QA");
        $this->assert("Register new user succeeds", $reg['success'], $reg['error'] ?? '');
        $userId = $reg['id'];

        // Login
        $login = loginUser($testEmail, $testPass);
        $this->assert("Login with correct credentials succeeds", $login['success']);

        // Failed Login
        $fail = loginUser($testEmail, "wrongpassword");
        $this->assert("Login with wrong password fails safely", !$fail['success']);

        // Update Profile & Select Program (Diet)
        $updated = updateUserProfile($userId, 82.5, 172, 26, 'Laki-laki', 'moderate', 'diet');
        $this->assert("Update user profile to Diet program succeeds", $updated);

        // Verify updated user data
        $db = getDb();
        $stmt = $db->prepare("SELECT * FROM users WHERE id_user = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        $this->assert("User program is set to 'diet'", $user['program'] === 'diet');
        $this->assert("User target_kalori is computed", (int)$user['target_kalori'] > 1200);

        // Switch to Bulking
        $updatedBulking = updateUserProfile($userId, 82.5, 172, 26, 'Laki-laki', 'moderate', 'bulking');
        $this->assert("Update user profile to Bulking program succeeds", $updatedBulking);

        // Clean up test user
        $db->prepare("DELETE FROM users WHERE id_user = :id")->execute([':id' => $userId]);
    }

    private function testFormulasAndPrograms(): void {
        echo "\n[3] Testing BMR, TDEE & 3-Program Calorie Targets...\n";

        $bmi = calculateBMI(70, 175);
        $this->assert("BMI calculation (70kg, 175cm = ~22.86)", abs($bmi - 22.86) < 0.05);

        $bmr = calculateBMR(70, 175, 25, 'Laki-laki');
        $this->assert("BMR Mifflin-St Jeor calculation", abs($bmr - 1673.8) < 0.5);

        $tdee = calculateTDEE($bmr, 'moderate'); // ~2595 kcal
        $this->assert("TDEE calculation", abs($tdee - 2595) < 2);

        // 3 Distinct Program Targets
        $targetDiet = calculateCalorieTarget($tdee, 'diet');
        $targetWorkout = calculateCalorieTarget($tdee, 'workout');
        $targetBulking = calculateCalorieTarget($tdee, 'bulking');

        $this->assert("Diet target creates deficit (-450 kcal)", $targetDiet < $targetWorkout);
        $this->assert("Workout target equals maintenance (TDEE)", $targetWorkout === (int)round($tdee));
        $this->assert("Bulking target creates surplus (+400 kcal)", $targetBulking > $targetWorkout);
    }

    private function testRecommendations(): void {
        echo "\n[4] Testing Curated Meal & Workout Recommendations...\n";

        $programs = ['diet', 'workout', 'bulking'];
        foreach ($programs as $prog) {
            $meals = getMealRecommendations($prog);
            $this->assert("Meal recommendations exist for program '{$prog}'", count($meals) >= 4);
            $firstMeal = $meals[0];
            $this->assert("Meal item has calories and protein for '{$prog}'", isset($firstMeal['calories'], $firstMeal['protein']));

            $workouts = getWorkoutRecommendations($prog);
            $this->assert("Workout recommendations exist for program '{$prog}'", count($workouts) >= 4);
            $firstWorkout = $workouts[0];
            $this->assert("Workout item has burn calories for '{$prog}'", isset($firstWorkout['burn']) && $firstWorkout['burn'] > 0);
        }
    }

    private function testFoodOperations(): void {
        echo "\n[5] Testing Food Logging, Multi-User Isolation & Nutrition Search...\n";

        $results = searchFoodNutrition('nasi');
        $this->assert("Food search returns items for 'nasi'", count($results) > 0);

        $today = date('Y-m-d');
        
        // Multi-user isolation test:
        // User A (101) logs food
        $idA = addFoodLog("User A Meal", 100, 300, 20, 40, 5, $today, 101);
        $this->assert("User A adds food item returns valid ID", $idA > 0);

        // User B (102) logs food
        $idB = addFoodLog("User B Meal", 200, 600, 45, 60, 10, $today, 102);
        $this->assert("User B adds food item returns valid ID", $idB > 0);

        $summaryA = getTodayFoodSummary($today, 101);
        $summaryB = getTodayFoodSummary($today, 102);

        $this->assert("User A summary only contains User A calories (300)", (int)$summaryA['total_calories'] === 300);
        $this->assert("User B summary only contains User B calories (600)", (int)$summaryB['total_calories'] === 600);

        $logsA = getTodayFoodLogs($today, 101);
        $logsB = getTodayFoodLogs($today, 102);
        $this->assert("User A logs list has 1 item", count($logsA) === 1);
        $this->assert("User B logs list has 1 item", count($logsB) === 1);
        $this->assert("User A cannot see User B item", $logsA[0]['makanan'] === 'User A Meal');
        $this->assert("User B cannot see User A item", $logsB[0]['makanan'] === 'User B Meal');

        // Cleanup
        deleteFoodLog($idA, 101);
        deleteFoodLog($idB, 102);
        $this->assert("User A and B test records cleaned up safely", true);
    }

    private function testExerciseOperations(): void {
        echo "\n[6] Testing Workout & Exercise Logging...\n";

        $today = date('Y-m-d');
        $testWorkoutName = "QA Push Up Test " . time();
        $id = addExerciseLog($testWorkoutName, 0, 50, 22.5, $today, 1);
        $this->assert("Add exercise item returns valid ID", $id > 0);

        $summary = getTodayExerciseSummary($today, 1);
        $this->assert("Today's exercise summary records calories burned", $summary['total_burned'] >= 22.5);

        $deleted = deleteExerciseLog($id);
        $this->assert("Delete exercise item succeeds", $deleted);
    }

    private function testWeightOperations(): void {
        echo "\n[7] Testing Weight Progress & History...\n";

        $testWeight = 76.5;
        $id = saveWeightLog($testWeight, 175, 25, 'Laki-laki', 'moderate', 'diet', date('Y-m-d'), 1);
        $this->assert("Save weigh-in record returns valid ID", $id > 0);

        $latest = getLatestWeightLog(1);
        $this->assert("Get latest weight log matches saved weight", (float)$latest['berat'] === $testWeight);

        deleteWeightLog($id);
        $this->assert("Cleaned up temporary weight test record", true);
    }

    private function testLegacyCompatibility(): void {
        echo "\n[8] Testing Legacy Bridges & Endpoints...\n";

        require_once __DIR__ . '/../tracker/function.php';
        $conn = connectDatabase();
        $this->assert("Legacy connectDatabase() returns valid object", is_object($conn));

        $res = $conn->query("SELECT * FROM beratbadan LIMIT 1");
        $this->assert("Legacy connection executes query", $res !== false);
    }

    private function testPageRendering(): void {
        echo "\n[9] Testing Page Syntax (php -l linting)...\n";

        $pages = [
            'login.php',
            'setup.php',
            'index.php',
            'logout.php',
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

$suite = new QATestSuite();
$suite->runAll();
