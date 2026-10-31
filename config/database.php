<?php
/**
 * Database Configuration & Connection Provider
 * Supports SQLite (default, zero-configuration) and MySQL (optional via config/env).
 */

class Database {
    private static ?PDO $instance = null;
    private static string $driver = 'sqlite'; // 'sqlite' or 'mysql'

    public static function getConnection(): PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }

        // Determine driver based on environment or configuration
        $dbDriver = getenv('DB_CONNECTION') ?: 'sqlite';
        self::$driver = strtolower($dbDriver);

        try {
            if (self::$driver === 'mysql') {
                $host = getenv('DB_HOST') ?: '127.0.0.1';
                $port = getenv('DB_PORT') ?: '3306';
                $dbname = getenv('DB_DATABASE') ?: 'olahraga';
                $username = getenv('DB_USERNAME') ?: 'root';
                $password = getenv('DB_PASSWORD') ?: '';

                $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
                self::$instance = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } else {
                // SQLite Default
                $dataDir = __DIR__ . '/../data';
                if (!is_dir($dataDir)) {
                    mkdir($dataDir, 0755, true);
                }

                $dbPath = $dataDir . '/calorie_tracker.sqlite';
                self::$instance = new PDO("sqlite:" . $dbPath, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);

                // Enable foreign keys and write-ahead logging in SQLite for performance
                self::$instance->exec('PRAGMA foreign_keys = ON;');
                self::$instance->exec('PRAGMA journal_mode = WAL;');
            }

            // Run automatic table schema migrations and initial seeding
            self::runMigrations(self::$instance, self::$driver);

        } catch (PDOException $e) {
            // If MySQL fails, gracefully fallback to SQLite so the app NEVER crashes
            if (self::$driver === 'mysql') {
                error_log("MySQL connection failed: " . $e->getMessage() . ". Falling back to SQLite.");
                self::$driver = 'sqlite';
                $dataDir = __DIR__ . '/../data';
                if (!is_dir($dataDir)) {
                    mkdir($dataDir, 0755, true);
                }
                $dbPath = $dataDir . '/calorie_tracker.sqlite';
                self::$instance = new PDO("sqlite:" . $dbPath, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                self::$instance->exec('PRAGMA foreign_keys = ON;');
                self::runMigrations(self::$instance, 'sqlite');
            } else {
                die("Database Error: " . htmlspecialchars($e->getMessage()));
            }
        }

        return self::$instance;
    }

    public static function getDriver(): string {
        return self::$driver;
    }

    private static function runMigrations(PDO $pdo, string $driver): void {
        if ($driver === 'sqlite') {
            // Table: users
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id_user INTEGER PRIMARY KEY AUTOINCREMENT,
                    email TEXT UNIQUE NOT NULL,
                    password TEXT NOT NULL,
                    nama TEXT DEFAULT '',
                    program TEXT DEFAULT 'bulking',
                    target_kalori INTEGER DEFAULT 2400,
                    berat REAL DEFAULT 70,
                    tinggi REAL DEFAULT 175,
                    usia INTEGER DEFAULT 25,
                    gender TEXT DEFAULT 'Laki-laki',
                    aktivitas TEXT DEFAULT 'moderate',
                    bmi REAL DEFAULT 22.86,
                    created_at TEXT NOT NULL
                )
            ");

            // Table: beratbadan
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS beratbadan (
                    id_beratbadan INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER DEFAULT 1,
                    berat REAL NOT NULL,
                    tinggi REAL NOT NULL,
                    usia INTEGER DEFAULT 25,
                    gender TEXT DEFAULT 'Laki-laki',
                    aktivitas TEXT DEFAULT 'moderate',
                    bmi REAL NOT NULL,
                    kategori TEXT NOT NULL,
                    kalori_harian TEXT NOT NULL,
                    target_program TEXT DEFAULT 'bulking',
                    tanggal TEXT NOT NULL
                )
            ");

            // Table: hitung_makanan
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS hitung_makanan (
                    id_hitung INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER DEFAULT 1,
                    makanan TEXT NOT NULL,
                    berat REAL NOT NULL,
                    kalori REAL NOT NULL,
                    protein REAL NOT NULL,
                    karbohidrat REAL DEFAULT 0,
                    lemak REAL DEFAULT 0,
                    tanggal TEXT NOT NULL
                )
            ");

            // Table: olahraga_log
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS olahraga_log (
                    id_olahraga INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER DEFAULT 1,
                    nama_olahraga TEXT NOT NULL,
                    durasi_menit REAL DEFAULT 0,
                    repetisi INTEGER DEFAULT 0,
                    kalori_terbakar REAL NOT NULL,
                    tanggal TEXT NOT NULL
                )
            ");

            // Auto-check and migrate missing columns in existing SQLite tables
            self::ensureSqliteColumn($pdo, 'beratbadan', 'user_id', 'INTEGER DEFAULT 1');
            self::ensureSqliteColumn($pdo, 'hitung_makanan', 'user_id', 'INTEGER DEFAULT 1');
            self::ensureSqliteColumn($pdo, 'olahraga_log', 'user_id', 'INTEGER DEFAULT 1');

            // Auto-seed demo user if empty
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
            $userCount = (int)($stmt->fetch()['count'] ?? 0);
            if ($userCount === 0) {
                $hash = password_hash('password123', PASSWORD_DEFAULT);
                $ins = $pdo->prepare("
                    INSERT INTO users (email, password, nama, program, target_kalori, berat, tinggi, usia, gender, aktivitas, bmi, created_at)
                    VALUES (?, ?, ?, 'bulking', 2600, 70, 175, 24, 'Laki-laki', 'moderate', 22.86, ?)
                ");
                $ins->execute(['demo@calorietracker.com', $hash, 'Demo User', date('Y-m-d H:i:s')]);
            }

            // Seed initial data for beratbadan if empty
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM beratbadan");
            $row = $stmt->fetch();
            if ($row && (int)$row['count'] === 0) {
                $initialData = [
                    [1, 94, 182, 28, 'gemuk', '1880', '2025-02-05'],
                    [1, 80, 170, 28, 'gemuk', '1600', '2025-02-05'],
                    [1, 70, 180, 22, 'ideal', '2442', '2025-02-05'],
                    [1, 70, 185, 20, 'ideal', '2773', '2025-02-05'],
                    [1, 80, 182, 24, 'ideal', '2976', '2025-02-09'],
                    [1, 82, 182, 25, 'ideal', '3017', '2025-02-09'],
                    [1, 80, 178, 25, 'gemuk', '3318', '2025-02-11'],
                    [1, 80, 180, 25, 'ideal', '2650', '2025-02-11'],
                    [1, 78, 180, 24.1, 'ideal', '2580', date('Y-m-d')]
                ];

                $insertStmt = $pdo->prepare("
                    INSERT INTO beratbadan (user_id, berat, tinggi, bmi, kategori, kalori_harian, target_program, tanggal)
                    VALUES (?, ?, ?, ?, ?, ?, 'bulking', ?)
                ");
                foreach ($initialData as $data) {
                    $insertStmt->execute($data);
                }
            }

        } else {
            // MySQL migrations
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `users` (
                    `id_user` int(11) NOT NULL AUTO_INCREMENT,
                    `email` varchar(150) NOT NULL UNIQUE,
                    `password` varchar(255) NOT NULL,
                    `nama` varchar(100) DEFAULT '',
                    `program` varchar(50) DEFAULT 'bulking',
                    `target_kalori` int(11) DEFAULT 2400,
                    `berat` decimal(5,2) DEFAULT 70,
                    `tinggi` int(11) DEFAULT 175,
                    `usia` int(11) DEFAULT 25,
                    `gender` varchar(20) DEFAULT 'Laki-laki',
                    `aktivitas` varchar(50) DEFAULT 'moderate',
                    `bmi` decimal(5,2) DEFAULT 22.86,
                    `created_at` datetime NOT NULL,
                    PRIMARY KEY (`id_user`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `beratbadan` (
                    `id_beratbadan` int(11) NOT NULL AUTO_INCREMENT,
                    `user_id` int(11) DEFAULT 1,
                    `berat` decimal(5,2) NOT NULL,
                    `tinggi` int(11) NOT NULL,
                    `usia` int(11) DEFAULT 25,
                    `gender` varchar(20) DEFAULT 'Laki-laki',
                    `aktivitas` varchar(50) DEFAULT 'moderate',
                    `bmi` decimal(5,2) NOT NULL,
                    `kategori` varchar(50) NOT NULL,
                    `kalori_harian` varchar(50) NOT NULL,
                    `target_program` varchar(50) DEFAULT 'bulking',
                    `tanggal` date NOT NULL,
                    PRIMARY KEY (`id_beratbadan`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `hitung_makanan` (
                    `id_hitung` int(11) NOT NULL AUTO_INCREMENT,
                    `user_id` int(11) DEFAULT 1,
                    `makanan` varchar(100) NOT NULL,
                    `berat` decimal(6,2) NOT NULL,
                    `kalori` decimal(6,2) NOT NULL,
                    `protein` decimal(6,2) NOT NULL,
                    `karbohidrat` decimal(6,2) DEFAULT 0,
                    `lemak` decimal(6,2) DEFAULT 0,
                    `tanggal` date NOT NULL,
                    PRIMARY KEY (`id_hitung`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `olahraga_log` (
                    `id_olahraga` int(11) NOT NULL AUTO_INCREMENT,
                    `user_id` int(11) DEFAULT 1,
                    `nama_olahraga` varchar(100) NOT NULL,
                    `durasi_menit` decimal(6,2) DEFAULT 0,
                    `repetisi` int(11) DEFAULT 0,
                    `kalori_terbakar` decimal(6,2) NOT NULL,
                    `tanggal` date NOT NULL,
                    PRIMARY KEY (`id_olahraga`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            // Seed demo user if empty
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
            if ((int)($stmt->fetch()['count'] ?? 0) === 0) {
                $hash = password_hash('password123', PASSWORD_DEFAULT);
                $ins = $pdo->prepare("
                    INSERT INTO users (email, password, nama, program, target_kalori, berat, tinggi, usia, gender, aktivitas, bmi, created_at)
                    VALUES (?, ?, ?, 'bulking', 2600, 70, 175, 24, 'Laki-laki', 'moderate', 22.86, NOW())
                ");
                $ins->execute(['demo@calorietracker.com', $hash, 'Demo User']);
            }
        }
    }

    private static function ensureSqliteColumn(PDO $pdo, string $table, string $column, string $definition): void {
        try {
            $stmt = $pdo->query("PRAGMA table_info({$table})");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $hasColumn = false;
            foreach ($columns as $col) {
                if ($col['name'] === $column) {
                    $hasColumn = true;
                    break;
                }
            }
            if (!$hasColumn) {
                $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
            }
        } catch (Throwable $e) {
            // Table might not exist yet, handled by CREATE TABLE
        }
    }
}
