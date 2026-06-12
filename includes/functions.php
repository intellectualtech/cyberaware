<?php
// includes/functions.php

if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('is_admin')) {
    function is_admin() {
        return is_logged_in() && ($_SESSION['role'] ?? '') === 'admin';
    }
}

if (!function_exists('require_login')) {
    function require_login() {
        if (!is_logged_in()) {
            header("Location: ../pages/login.php");
            exit;
        }
    }
}

if (!function_exists('require_admin')) {
    function require_admin() {
        require_login();
        if (!is_admin()) {
            header("Location: ../pages/access-denied.php");
            exit;
        }
    }
}

if (!function_exists('get_current_user')) {
    function get_current_user() {
        $pdo = getDBConnection();
        if (!is_logged_in()) return null;
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
