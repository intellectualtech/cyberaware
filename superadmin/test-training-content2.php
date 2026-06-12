<?php
// Test training content page after fixes
session_start();
require_once 'c:/xampp/htdocs/cyberaware-new-Edits/config/database.php';

// Simulate superadmin login
$_SESSION['user_id'] = 6;
$_SESSION['username'] = 'superadmin';
$_SESSION['role'] = 'superadmin';
$_SESSION['full_name'] = 'Super Administrator';

try {
    $pdo = getDBConnection();
    
    echo "Testing training content page...\n\n";
    
    // Test the query from training-content.php
    $stmt = $pdo->prepare("SELECT id, title, description, category, difficulty as difficulty_level, estimated_minutes as duration_minutes, order_index, is_active FROM training_modules ORDER BY order_index ASC");
    $stmt->execute();
    $modules = $stmt->fetchAll();
    
    echo "✓ Query successful\n";
    echo "✓ Modules found: " . count($modules) . "\n";
    
    if (count($modules) > 0) {
        echo "\nFirst module:\n";
        $mod = $modules[0];
        echo "- ID: " . $mod['id'] . "\n";
        echo "- Title: " . $mod['title'] . "\n";
        echo "- Category: " . $mod['category'] . "\n";
        echo "- Difficulty: " . $mod['difficulty_level'] . "\n";
        echo "- Duration: " . $mod['duration_minutes'] . " min\n";
        echo "\n✓ All fields accessible\n";
    }
    
    echo "\n✓ Training content page should now work!\n";
    
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
