<?php
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /Foodordering/auth/login.php?msg=login_required");
        exit();
    }
}

function require_role($role) {
       if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
           header('Location: ../auth/login.php');
           exit;
       }
   }