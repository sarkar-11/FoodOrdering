<?php
// Simple centralized error logger for development. Writes to ../logs/php_errors.log
$__error_log_file = __DIR__ . '/../logs/php_errors.log';
if (!is_dir(dirname($__error_log_file))) {
    @mkdir(dirname($__error_log_file), 0777, true);
}
@ini_set('log_errors', '1');
@ini_set('error_log', $__error_log_file);
@error_reporting(E_ALL);

// Log uncaught exceptions
set_exception_handler(function ($e) use ($__error_log_file) {
    error_log('[Uncaught Exception] ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
});

// Log fatal errors on shutdown
register_shutdown_function(function () use ($__error_log_file) {
    $err = error_get_last();
    if ($err) {
        error_log('[Shutdown] ' . $err['message'] . ' in ' . $err['file'] . ' on line ' . $err['line']);
    }
});
