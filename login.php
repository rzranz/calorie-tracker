<?php
/**
 * Welcome & Authentication Page (Login / Register)
 * Clean, modern, beginner-friendly interface.
 */

require_once __DIR__ . '/includes/functions.php';

startUserSession();

// If already logged in, redirect to dashboard or setup
$currentUser = getCurrentUser();
if ($currentUser) {
    if (empty($currentUser['berat']) || empty($currentUser['tinggi'])) {
        header("Location: setup.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

$error = null;
$success = null;
$activeTab = $_GET['tab'] ?? 'login';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['auth_action'] ?? 'login';
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $nama = trim($_POST['nama'] ?? '');

    if ($action === 'demo') {
        // Instant 1-Click Demo Login
        $res = loginUser('demo@calorietracker.com', 'password123');
        if ($res['success']) {
            header("Location: index.php");
            exit();
        } else {
            // Auto register demo if missing
            registerUser('demo@calorietracker.com', 'password123', 'Demo User');
            loginUser('demo@calorietracker.com', 'password123');
            header("Location: index.php");
            exit();
        }
    } elseif ($action === 'register') {
        $res = registerUser($email, $password, $nama);
        if ($res['success']) {
            // First time user goes to setup / BMI calculator
            header("Location: setup.php");
            exit();
        } else {
            $error = $res['error'];
            $activeTab = 'register';
        }
    } else {
        // Standard Login
        $res = loginUser($email, $password);
        if ($res['success']) {
            $user = $res['user'];
            if (empty($user['berat']) || empty($user['tinggi'])) {
                header("Location: setup.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $error = $res['error'];
            $activeTab = 'login';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selamat Datang • Let's Move</title>
    <link rel="stylesheet" href="css/modern.css">
</head>
<body style="min-height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center; background: radial-gradient(circle at top, #162032 0%, #0b0f19 100%); padding: 24px;">

    <div style="width: 100%; max-width: 440px; margin: auto;">
        <!-- Brand Header -->
        <div style="text-align: center; margin-bottom: 28px;">
            <div style="width: 52px; height: 52px; background: linear-gradient(135deg, #10b981 0%, #06b6d4 100%); border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 0 24px rgba(16, 185, 129, 0.4); margin-bottom: 14px; font-size: 1.5rem; color: #fff;">
                ⚡
            </div>
            <h1 style="font-size: 1.8rem; font-weight: 800; color: var(--text-main); letter-spacing: -0.03em; margin-bottom: 6px;">
                Selamat Datang di Let's Move
            </h1>
            <p style="font-size: 0.92rem; color: var(--text-muted); line-height: 1.5;">
                Pelacak kalori dan panduan kebugaran personal yang simpel, terarah, dan ramah pemula.
            </p>
        </div>

        <!-- Auth Card -->
        <div class="card" style="padding: 30px; box-shadow: var(--shadow-lg); border: 1px solid var(--border-default);">
            <!-- Tab Selector -->
            <div style="display: flex; gap: 8px; margin-bottom: 24px; background: var(--bg-surface-elevated); padding: 4px; border-radius: var(--radius-md);">
                <a href="login.php?tab=login" style="flex: 1; text-align: center; padding: 10px; border-radius: var(--radius-sm); font-size: 0.9rem; font-weight: 600; text-decoration: none; transition: all 0.2s; <?= ($activeTab === 'login') ? 'background: var(--bg-card); color: var(--text-main); box-shadow: var(--shadow-sm);' : 'color: var(--text-muted);' ?>">
                    Masuk (Login)
                </a>
                <a href="login.php?tab=register" style="flex: 1; text-align: center; padding: 10px; border-radius: var(--radius-sm); font-size: 0.9rem; font-weight: 600; text-decoration: none; transition: all 0.2s; <?= ($activeTab === 'register') ? 'background: var(--bg-card); color: var(--text-main); box-shadow: var(--shadow-sm);' : 'color: var(--text-muted);' ?>">
                    Daftar Akun Baru
                </a>
            </div>

            <?php if (!empty($error)): ?>
                <div style="background: rgba(244, 63, 94, 0.15); border: 1px solid rgba(244, 63, 94, 0.4); color: #fda4af; padding: 12px 16px; border-radius: var(--radius-md); margin-bottom: 18px; font-size: 0.88rem;">
                    ⚠️ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($activeTab === 'register'): ?>
                <!-- Register Form -->
                <form method="POST" action="login.php?tab=register">
                    <input type="hidden" name="auth_action" value="register">

                    <div class="form-group">
                        <label class="form-label" for="reg_nama">Nama Lengkap / Panggilan</label>
                        <input type="text" name="nama" id="reg_nama" class="form-control" placeholder="Contoh: Budi Santoso" required autofocus>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="reg_email">Alamat Email</label>
                        <input type="email" name="email" id="reg_email" class="form-control" placeholder="nama@email.com" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="reg_password">Password</label>
                        <input type="password" name="password" id="reg_password" class="form-control" placeholder="Minimal 6 karakter" minlength="6" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" style="margin-top: 10px; padding: 12px;">
                        Lanjut ke Kalkulator BMI →
                    </button>
                </form>
            <?php else: ?>
                <!-- Login Form -->
                <form method="POST" action="login.php?tab=login">
                    <input type="hidden" name="auth_action" value="login">

                    <div class="form-group">
                        <label class="form-label" for="log_email">Alamat Email</label>
                        <input type="email" name="email" id="log_email" class="form-control" placeholder="nama@email.com" required autofocus value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="log_password">Password</label>
                        <input type="password" name="password" id="log_password" class="form-control" placeholder="Masukkan password Anda" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" style="margin-top: 10px; padding: 12px;">
                        Masuk ke Akun Saya
                    </button>
                </form>

                <!-- 1-Click Demo Login for Instant Evaluation -->
                <div style="margin-top: 20px; padding-top: 18px; border-top: 1px solid var(--border-subtle); text-align: center;">
                    <form method="POST" action="login.php">
                        <input type="hidden" name="auth_action" value="demo">
                        <button type="submit" class="btn btn-secondary btn-block" style="font-size: 0.88rem;">
                            🚀 Coba Masuk Instan (Akun Demo)
                        </button>
                    </form>
                    <span style="font-size: 0.78rem; color: var(--text-dim); margin-top: 8px; display: block;">
                        Klik untuk mencoba langsung tanpa perlu mengisi form pendaftaran.
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <div style="text-align: center; margin-top: 20px; font-size: 0.82rem; color: var(--text-dim);">
            Let's Move • Calorie & Fitness Management
        </div>
    </div>

</body>
</html>
