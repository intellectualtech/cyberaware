<?php
// Add superadmin to the role enum
require_once('c:/xampp/htdocs/cyberaware-new-Edits/config/database.php');

try {
    $conn = getDBConnection();
    
    // Modify the role enum to include 'superadmin'
    $sql = "ALTER TABLE users MODIFY role enum('trainee','admin','manager','compliance','superadmin') NOT NULL DEFAULT 'trainee'";
    
    echo "Modifying role enum...\n";
    $conn->exec($sql);
    echo "✓ Role enum updated to include 'superadmin'\n\n";
    
    // Now update the superadmin user's role
    $stmt = $conn->prepare("UPDATE users SET role = 'superadmin' WHERE username = 'superadmin'");
    $stmt->execute();
    echo "✓ Superadmin role set successfully!\n\n";
    
    // Verify
    $stmt = $conn->prepare("SELECT id, username, email, role, is_active FROM users WHERE username = ?");
    $stmt->execute(['superadmin']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "Verification:\n";
        echo "Username: " . $user['username'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "Role: " . $user['role'] . "\n";
        echo "Active: " . ($user['is_active'] ? 'Yes' : 'No') . "\n";
        echo "\n✓ You can now login with:\n";
        echo "Username: superadmin\n";
        echo "Password: Uncle@foddy1\n";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
