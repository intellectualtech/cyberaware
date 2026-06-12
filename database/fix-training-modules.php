<?php
// Fix training modules table
require_once('c:/xampp/htdocs/cyberaware-new-Edits/config/database.php');

try {
    $conn = getDBConnection();
    
    echo "Checking training_modules table structure...\n\n";
    
    // Get table structure
    $stmt = $conn->prepare("DESCRIBE training_modules");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    
    echo "Current columns:\n";
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    // Check if order_index exists
    $hasOrderIndex = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'order_index') {
            $hasOrderIndex = true;
            break;
        }
    }
    
    if (!$hasOrderIndex) {
        echo "\n✗ order_index column NOT found, adding it...\n";
        $conn->exec("ALTER TABLE training_modules ADD COLUMN order_index INT DEFAULT 0 AFTER title");
        echo "✓ order_index column added\n";
    } else {
        echo "\n✓ order_index column already exists\n";
    }
    
    // Now test the query
    echo "\n\nTesting query: SELECT * FROM training_modules ORDER BY order_index ASC\n";
    $stmt = $conn->prepare("SELECT * FROM training_modules ORDER BY order_index ASC");
    $stmt->execute();
    $modules = $stmt->fetchAll();
    echo "✓ Query successful - Found " . count($modules) . " modules\n";
    
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
