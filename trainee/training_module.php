<?php
/**
 * Training Module Router (Trainee Directory Version)
 * Maps database module IDs to actual module PHP files
 * Handles module loading, tracking, and XP management
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'trainee') {
    header('Location: ../pages/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$module_id = isset($_GET['module']) ? (int)$_GET['module'] : 0;

if (!$module_id) {
    header('Location: dashboard.php');
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Get module info from database
    $stmt = $pdo->prepare("SELECT * FROM training_modules WHERE id = ?");
    $stmt->execute([$module_id]);
    $module = $stmt->fetch();
    
    if (!$module) {
        header('Location: dashboard.php');
        exit;
    }
    
    // Map module codes to actual PHP files
    $module_files = [
        'PHISH_001' => 'modules/phishing.php',
        'CRED_001' => 'modules/credentials.php',
        'SOCIAL_001' => 'modules/social.php',
        'MALWARE_001' => 'modules/attachments.php',
        'LINK_001' => 'modules/links.php',
        'PASSWORD_001' => 'modules/password.php',
        'RANSOMWARE_001' => 'modules/ransomware.php'
    ];
    
    $module_file = $module_files[$module['code']] ?? null;
    
    if (!$module_file || !file_exists(__DIR__ . '/' . $module_file)) {
        // If file doesn't exist, redirect to dashboard
        header('Location: dashboard.php');
        exit;
    }
    
    // Update registration status to in_progress
    $stmt = $pdo->prepare("
        UPDATE user_module_registrations 
        SET status = 'in_progress', started_at = NOW()
        WHERE user_id = ? AND module_id = ?
    ");
    $stmt->execute([$user_id, $module_id]);
    
    // Make module_id available to the included file
    $_GET['module_id'] = $module_id;
    
    // Load the actual module file
    include $module_file;
    
} catch(Exception $e) {
    error_log("Module loading error: " . $e->getMessage());
    header('Location: dashboard.php');
    exit;
}
?>
