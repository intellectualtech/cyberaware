<?php
// Check users table structure
require_once('c:/xampp/htdocs/cyberaware-new-Edits/config/database.php');

try {
    $conn = getDBConnection();
    
    // Get table structure
    $stmt = $conn->prepare("DESCRIBE users");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    
    echo "Users Table Columns:\n";
    echo str_repeat("=", 60) . "\n";
    foreach ($columns as $col) {
        echo "Field: " . $col['Field'] . "\n";
        echo "  Type: " . $col['Type'] . "\n";
        echo "  Null: " . $col['Null'] . "\n";
        echo "  Key: " . $col['Key'] . "\n";
        echo "  Default: " . ($col['Default'] ?? 'NULL') . "\n";
        echo "\n";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
