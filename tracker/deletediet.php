<?php
/**
 * Legacy Endpoint: deletediet.php
 * Safely deletes a food log entry and redirects back.
 */

require_once __DIR__ . '/../includes/functions.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($id > 0) {
        deleteFoodLog($id);
    }
}

// Redirect back cleanly
$referer = $_SERVER['HTTP_REFERER'] ?? '';
if (!empty($referer) && filter_var($referer, FILTER_VALIDATE_URL)) {
    header("Location: " . $referer);
} else {
    header("Location: makandiet.php");
}
exit();
