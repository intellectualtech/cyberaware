<?php
/**
 * Training Module Router
 * Maps database module IDs to actual module PHP files
 * Handles module loading, tracking, and XP management
 */

require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'trainee') {
    header('Location: ../../pages/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$module_id = isset($_GET['module']) ? (int)$_GET['module'] : 0;

if (!$module_id) {
    header('Location: ../dashboard.php');
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Get module info from database
    $stmt = $pdo->prepare("SELECT * FROM training_modules WHERE id = ?");
    $stmt->execute([$module_id]);
    $module = $stmt->fetch();
    
    if (!$module) {
        header('Location: ../dashboard.php');
        exit;
    }
    
    // Map module codes to actual PHP files
    $module_files = [
        'PHISH_001' => 'phishing.php',
        'CRED_001' => 'credentials.php',
        'SOCIAL_001' => 'social.php',
        'MALWARE_001' => 'attachments.php',
        'LINK_001' => 'links.php',
        'PASSWORD_001' => 'password.php',
        'RANSOMWARE_001' => 'ransomware.php'
    ];
    
    $module_file = $module_files[$module['code']] ?? null;
    
    if (!$module_file || !file_exists(__DIR__ . '/' . $module_file)) {
        header('Location: ../dashboard.php');
        exit;
    }
    
    // Update registration status to in_progress
    $stmt = $pdo->prepare("
        UPDATE user_module_registrations 
        SET status = 'in_progress', started_at = NOW()
        WHERE user_id = ? AND module_id = ?
    ");
    $stmt->execute([$user_id, $module_id]);
    
    // Output module ID as a global variable for the module to use
    echo "<script>window.CYBERAWARE_MODULE_ID = " . (int)$module_id . ";</script>";
    
    // Load the actual module file
    include $module_file;
    
} catch(Exception $e) {
    error_log("Module loading error: " . $e->getMessage());
    header('Location: ../dashboard.php');
    exit;
}
?>
