<?php

// Application configuration
// Adjust APP_BASE_URL if this app is deployed under a different folder.
define('APP_BASE_URL', '/food_ordering_system');

// Automatically build APP_URL from the current request host when possible.
// This helps avoid hardcoded localhost values during local or deployed testing.
if (php_sapi_name() !== 'cli' && isset($_SERVER['HTTP_HOST'])) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    define('APP_URL', $scheme . $_SERVER['HTTP_HOST'] . APP_BASE_URL);
} else {
    define('APP_URL', 'http://localhost' . APP_BASE_URL);
}

define('APP_NAME', 'DokoBites');

// Enable centralized error logging for runtime issues
if (file_exists(__DIR__ . '/error_handler.php')) {
    require_once __DIR__ . '/error_handler.php';
}
