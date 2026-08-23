<?php
session_start();
session_unset();
session_destroy();
require_once __DIR__ . '/../includes/config.php';
header("Location: " . APP_BASE_URL . "/auth/login.php");
exit();
?>