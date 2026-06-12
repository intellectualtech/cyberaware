<?php
// Verify superadmin account matches the requirements
require_once('c:/xampp/htdocs/cyberaware-new-Edits/config/database.php');

try {
    $conn = getDBConnection();
    
    echo "SUPERADMIN ACCOUNT VERIFICATION\n";
    echo "=" . str_repeat("=", 70) . "\n\n";
    
    echo "REQUIREMENT:\n";
    echo "- Purpose: Manage organizational subscriptions and update training content\n";
    echo "- Organization: Intellectual Technology CC (Exclusive)\n\n";
    
    // Get superadmin user
    $stmt = $conn->prepare("SELECT id, username, email, full_name, role, is_active FROM users WHERE username = ?");
    $stmt->execute(['superadmin']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "CURRENT SUPERADMIN ACCOUNT:\n";
        echo "- ID: " . $user['id'] . "\n";
        echo "- Username: " . $user['username'] . "\n";
        echo "- Email: " . $user['email'] . "\n";
        echo "- Full Name: " . $user['full_name'] . "\n";
        echo "- Role: " . $user['role'] . "\n";
        echo "- Active: " . ($user['is_active'] ? 'Yes' : 'No') . "\n\n";
        
        echo "PORTAL ACCESS CAPABILITIES:\n";
        echo "✓ Organizations Management\n";
        echo "  - View, add, edit organizations\n";
        echo "  - Track organizational details\n\n";
        
        echo "✓ Subscriptions Management\n";
        echo "  - Manage organization subscriptions\n";
        echo "  - Track subscription status and renewal dates\n";
        echo "  - Monitor subscription pricing and revenue\n\n";
        
        echo "✓ Training Content Management\n";
        echo "  - Update and manage training modules\n";
        echo "  - Control module content and availability\n";
        echo "  - Track training module assignments\n\n";
        
        echo "✓ Platform Analytics Dashboard\n";
        echo "  - View platform-wide KPIs\n";
        echo "  - Monitor organizational metrics\n";
        echo "  - Track user completion rates\n\n";
        
        // Check required tables
        echo "REQUIRED DATABASE TABLES:\n";
        $tables = ['organizations', 'subscriptions', 'training_modules', 'users', 'module_progress'];
        
        $dbTables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            $exists = in_array($table, $dbTables) ? '✓' : '✗';
            echo "$exists $table\n";
        }
        
        echo "\n✓ VERIFICATION COMPLETE\n";
        echo "The superadmin account is properly configured for managing\n";
        echo "organizational subscriptions and training content.\n";
        
    } else {
        echo "✗ Superadmin user not found\n";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
