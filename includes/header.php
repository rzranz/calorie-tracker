<?php
/**
 * Shared Header Component
 */
if (!isset($pageTitle)) {
    $pageTitle = "Let's Move • Modern Fitness & Calorie Tracker";
}
if (!isset($activeTab)) {
    $activeTab = 'dashboard';
}

// Calculate root relative path depending on caller directory depth
$isSubdir = strpos($_SERVER['SCRIPT_NAME'] ?? '', '/tracker/') !== false;
$rootPath = $isSubdir ? '../' : './';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Aplikasi pencatat kalori harian, kalkulator BMI/TDEE, pelacak nutrisi makanan, dan catatan olahraga terstruktur.">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Modern Design System -->
    <link rel="stylesheet" href="<?= $rootPath ?>css/modern.css">
    
    <!-- Chart.js for Visual Progress Analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>
    <!-- Top Navigation Bar -->
    <header class="navbar">
        <div class="app-container navbar-inner">
            <a href="<?= $rootPath ?>index.php" class="brand-logo">
                <div class="brand-icon">⚡</div>
                <span>Let's Move</span>
                <span class="brand-badge">Pro</span>
            </a>

            <button class="nav-mobile-toggle" id="navToggleBtn" aria-label="Toggle navigation menu">
                ☰
            </button>

            <ul class="nav-links" id="mainNavLinks">
                <li>
                    <a href="<?= $rootPath ?>index.php" class="nav-link <?= ($activeTab === 'dashboard') ? 'active' : '' ?>">
                        📊 Dashboard
                    </a>
                </li>
                <li>
                    <a href="<?= $rootPath ?>index.php#calculator" class="nav-link <?= ($activeTab === 'calculator') ? 'active' : '' ?>">
                        ⚖️ Kalkulator BMI & TDEE
                    </a>
                </li>
                <li>
                    <a href="<?= $rootPath ?>tracker/makan_hari_ini.php" class="nav-link <?= ($activeTab === 'food') ? 'active' : '' ?>">
                        🥗 Makan Hari Ini
                    </a>
                </li>
                <li>
                    <a href="<?= $rootPath ?>tracker/olahraga.html" class="nav-link <?= ($activeTab === 'exercise') ? 'active' : '' ?>">
                        🏋️ Catatan Olahraga
                    </a>
                </li>
                <li>
                    <a href="<?= $rootPath ?>tracker/bulking.php" class="nav-link <?= ($activeTab === 'bulking') ? 'active' : '' ?>">
                        💪 Program Bulking
                    </a>
                </li>
                <li>
                    <a href="<?= $rootPath ?>tracker/diet.php" class="nav-link <?= ($activeTab === 'diet') ? 'active' : '' ?>">
                        🥑 Program Diet
                    </a>
                </li>
            </ul>
        </div>
    </header>

    <!-- Toast Container for Notifications -->
    <div class="toast-container" id="toastContainer"></div>

    <main style="flex: 1;">
