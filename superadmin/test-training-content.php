<?php
// Test training content page
session_start();
require_once 'c:/xampp/htdocs/cyberaware-new-Edits/config/database.php';

// Simulate superadmin login
$_SESSION['user_id'] = 6;
$_SESSION['username'] = 'superadmin';
$_SESSION['role'] = 'superadmin';
$_SESSION['full_name'] = 'Super Administrator';

try {
    $pdo = getDBConnection();
    
    echo "Database connection: SUCCESS\n";
    
    // Check if training_modules table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'training_modules'");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result) {
        echo "Training modules table: EXISTS\n";
        
        // Get all modules
        $stmt = $pdo->prepare("SELECT * FROM training_modules ORDER BY order_index ASC");
        $stmt->execute();
        $modules = $stmt->fetchAll();
        echo "Modules found: " . count($modules) . "\n";
        
        if (count($modules) > 0) {
            echo "\nFirst module:\n";
            $mod = $modules[0];
            echo "ID: " . $mod['id'] . "\n";
            echo "Title: " . $mod['title'] . "\n";
            echo "Category: " . $mod['category'] . "\n";
        }
    } else {
        echo "Training modules table: DOES NOT EXIST\n";
    }
    
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
