<?php
/**
 * Legacy Compatibility Layer for tracker/function.php
 * Delegates to modern PDO database & functions to ensure zero breakage.
 */

require_once __DIR__ . '/../includes/functions.php';

function connectDatabase() {
    // Attempt standard mysqli if MySQL extension and server is active
    $servername = getenv('DB_HOST') ?: "localhost";
    $username   = getenv('DB_USERNAME') ?: "root";
    $password   = getenv('DB_PASSWORD') ?: "";
    $dbname     = getenv('DB_DATABASE') ?: "olahraga";

    if (extension_loaded('mysqli')) {
        try {
            $driver = new mysqli_driver();
            $driver->report_mode = MYSQLI_REPORT_OFF;
            $conn = @new mysqli($servername, $username, $password, $dbname);
            if (!$conn->connect_error) {
                return $conn;
            }
        } catch (Throwable $e) {
            // MySQL unavailable, proceed to PDO bridge
        }
    }

    // Return a lightweight PDO-to-mysqli bridge object so legacy scripts don't crash
    return new class {
        private PDO $pdo;
        public int $connect_errno = 0;
        public ?string $connect_error = null;

        public function __construct() {
            $this->pdo = getDb();
        }

        public function query(string $query) {
            try {
                $stmt = $this->pdo->query($query);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                return new class($rows) {
                    public int $num_rows;
                    private array $rows;
                    private int $cursor = 0;

                    public function __construct(array $rows) {
                        $this->rows = $rows;
                        $this->num_rows = count($rows);
                    }

                    public function fetch_assoc(): ?array {
                        if ($this->cursor < $this->num_rows) {
                            return $this->rows[$this->cursor++];
                        }
                        return null;
                    }
                };
            } catch (Throwable $e) {
                return false;
            }
        }

        public function prepare(string $query) {
            try {
                $pdoStmt = $this->pdo->prepare($query);
                return new class($pdoStmt) {
                    private PDOStatement $stmt;
                    private array $params = [];

                    public function __construct(PDOStatement $stmt) {
                        $this->stmt = $stmt;
                    }

                    public function bind_param(string $types, &...$vars): bool {
                        $this->params = $vars;
                        return true;
                    }

                    public function execute(): bool {
                        return $this->stmt->execute($this->params);
                    }

                    public function close(): bool {
                        return true;
                    }
                };
            } catch (Throwable $e) {
                return false;
            }
        }

        public function real_escape_string(string $str): string {
            return addslashes($str);
        }

        public function close(): void {}
    };
}

function simpanData($berat, $tinggi, $bmi, $usia, $gender, $aktivitas, $goal = 'bulking') {
    return saveWeightLog((float)$berat, (float)$tinggi, (int)$usia, (string)$gender, (string)$aktivitas, (string)$goal);
}
