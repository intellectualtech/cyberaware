<?php
// Execute the superadmin user creation script
require_once('c:/xampp/htdocs/cyberaware-new-Edits/config/database.php');

try {
    $conn = getDBConnection();
    
    // Read the SQL script with full path
    $sqlFile = 'c:/xampp/htdocs/cyberaware-new-Edits/database/add_superadmin_user.sql';
    $sqlScript = file_get_contents($sqlFile);
    
    if ($sqlScript === false) {
        die("Error: Could not read file " . $sqlFile);
    }
    
    // Execute the SQL script
    $conn->exec($sqlScript);
    
    echo "✓ Success! Superadmin user has been created.\n";
    echo "Username: superadmin\n";
    echo "Password: Uncle@foddy1\n";
    echo "Email: superadmin@intellectualtechnology.com.na\n";
    echo "Role: superadmin\n";
    echo "\nYou can now login at: http://localhost/cyberaware-new-Edits/login.php\n";
    
} catch(PDOException $e) {
    echo "Error creating superadmin user: " . $e->getMessage();
}
?>
