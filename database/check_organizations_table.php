<?php
// Check if organizations table exists
require_once('c:/xampp/htdocs/cyberaware-new-Edits/config/database.php');

try {
    $conn = getDBConnection();
    
    // Check if organizations table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'organizations'");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result) {
        echo "✓ Organizations table EXISTS\n\n";
        
        // Get table structure
        $stmt = $conn->prepare("DESCRIBE organizations");
        $stmt->execute();
        $columns = $stmt->fetchAll();
        
        echo "Table columns:\n";
        foreach ($columns as $col) {
            echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
        }
        
        // Count records
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM organizations");
        $stmt->execute();
        $count = $stmt->fetch();
        echo "\nRecords: " . $count['count'] . "\n";
    } else {
        echo "✗ Organizations table DOES NOT EXIST\n";
        echo "\nNeed to create it. Here's the SQL:\n";
        echo <<<SQL
CREATE TABLE organizations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    email VARCHAR(150),
    phone VARCHAR(20),
    address TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_org_name (name)
);
SQL;
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
