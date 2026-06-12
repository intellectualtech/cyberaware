<?php
// Fix the superadmin role
require_once('c:/xampp/htdocs/cyberaware-new-Edits/config/database.php');

try {
    $conn = getDBConnection();
    
    // Update the superadmin role
    $stmt = $conn->prepare("UPDATE users SET role = 'superadmin' WHERE username = 'superadmin'");
    $stmt->execute();
    
    echo "✓ Superadmin role updated successfully!\n";
    
    // Verify
    $stmt = $conn->prepare("SELECT id, username, email, role, is_active FROM users WHERE username = ?");
    $stmt->execute(['superadmin']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "\nVerification:\n";
        echo "Username: " . $user['username'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "Role: " . $user['role'] . "\n";
        echo "Active: " . ($user['is_active'] ? 'Yes' : 'No') . "\n";
        echo "\nYou can now login with:\n";
        echo "Username: superadmin\n";
        echo "Password: Uncle@foddy1\n";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
