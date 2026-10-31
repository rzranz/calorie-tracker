<?php
/**
 * Legacy Food Logging Gateway
 * Seamlessly delegates to api/food.php?action=add
 */

$_GET['action'] = 'add';
require_once __DIR__ . '/../api/food.php';
