<?php
/**
 * Root Food Logging Gateway (Clean Code Proxy)
 * Seamlessly delegates to api/food.php?action=add
 */

$_GET['action'] = 'add';
require_once __DIR__ . '/api/food.php';
