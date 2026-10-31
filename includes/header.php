<?php
/**
 * Shared Header Component
 */
require_once __DIR__ . '/functions.php';

$currentUser = getCurrentUser();

if (!isset($pageTitle)) {
    $pageTitle = "Let's Move • Modern Fitness & Calorie Tracker";
}
if (!isset($activeTab)) {
    $activeTab = 'dashboard';
}

$isSubdir = strpos($_SERVER['SCRIPT_NAME'] ?? '', '/tracker/') !== false;
$rootPath = $isSubdir ? '../' : './';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= $rootPath ?>css/modern.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>
    <header class="navbar">
        <div class="app-container navbar-inner">
            <div style="display: flex; align-items: center; gap: 12px;">
                <a href="<?= $rootPath ?>index.php" class="brand-logo">
                    <div class="brand-icon">⚡</div>
                    <span>Let's Move</span>
                </a>
                <?php if ($currentUser && !empty($currentUser['program'])): ?>
                    <span class="badge badge-emerald" style="text-transform: uppercase;">
                        <?= htmlspecialchars($currentUser['program']) ?>
                    </span>
                <?php endif; ?>
            </div>

            <button class="nav-mobile-toggle" id="navToggleBtn" aria-label="Toggle navigation">
                ☰
            </button>

            <ul class="nav-links" id="mainNavLinks">
                <li>
                    <a href="<?= $rootPath ?>index.php" class="nav-link <?= ($activeTab === 'dashboard') ? 'active' : '' ?>">
                        📊 Dashboard
                    </a>
                </li>
                <li>
                    <a href="<?= $rootPath ?>setup.php" class="nav-link <?= ($activeTab === 'setup') ? 'active' : '' ?>">
                        ⚖️ Kalkulator BMI & Program
                    </a>
                </li>
                <li>
                    <a href="<?= $rootPath ?>tracker/makan_hari_ini.php" class="nav-link <?= ($activeTab === 'food') ? 'active' : '' ?>">
                        🥗 Makan Hari Ini
                    </a>
                </li>
                <li>
                    <a href="<?= $rootPath ?>tracker/olahraga.html" class="nav-link <?= ($activeTab === 'exercise') ? 'active' : '' ?>">
                        🏋️ Olahraga
                    </a>
                </li>
                <?php if ($currentUser): ?>
                    <li>
                        <a href="<?= $rootPath ?>logout.php" class="nav-link" style="color: #fda4af;">
                            🚪 Keluar (<?= htmlspecialchars(explode('@', $currentUser['email'])[0]) ?>)
                        </a>
                    </li>
                <?php else: ?>
                    <li>
                        <a href="<?= $rootPath ?>login.php" class="nav-link active">
                            Masuk / Daftar
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </header>

    <div class="toast-container" id="toastContainer"></div>
    <main style="flex: 1;">
