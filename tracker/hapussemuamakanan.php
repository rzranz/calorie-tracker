<?php
/**
 * Legacy Endpoint: hapussemuamakanan.php
 * Clears today's food logs and redirects back.
 */

require_once __DIR__ . '/../includes/functions.php';

resetTodayFoodLogs();

$referer = $_SERVER['HTTP_REFERER'] ?? '';
if (!empty($referer) && filter_var($referer, FILTER_VALIDATE_URL)) {
    header("Location: " . $referer);
} else {
    header("Location: makan_hari_ini.php");
}
exit();
