<?php
if (!defined('APP_BASE_URL')) {
    require_once __DIR__ . '/config.php';
}

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . APP_BASE_URL . "/auth/login.php?msg=login_required");
        exit();
    }
}

function require_role($roles) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . APP_BASE_URL . "/auth/login.php?msg=login_required");
        exit();
    }

    if (is_string($roles)) {
        $roles = [$roles];
    }

    if (!in_array($_SESSION['role'], $roles, true)) {
        header("Location: " . APP_BASE_URL . "/auth/login.php?msg=unauthorized");
        exit();
    }
}