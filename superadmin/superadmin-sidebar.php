<?php
/**
 * Super Admin Sidebar Navigation
 * Included in super admin pages
 */

if (!function_exists('isLoggedIn')) {
    require_once '../config/database.php';
    require_once '../includes/functions.php';
}

// Check access
if (!isLoggedIn() || $_SESSION['role'] !== 'superadmin') {
    header('Location: ../pages/login.php');
    exit;
}

$current_page = $current_page ?? 'dashboard';
?>

<!-- This sidebar is integrated into the main dashboard layout -->
<!-- Included for consistency with other admin portals -->
