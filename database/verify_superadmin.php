<?php
// Verify superadmin user exists
require_once('c:/xampp/htdocs/cyberaware-new-Edits/config/database.php');

try {
    $conn = getDBConnection();
    
    // Check if superadmin exists
    $stmt = $conn->prepare("SELECT id, username, email, role, is_active FROM users WHERE username = ?");
    $stmt->execute(['superadmin']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✓ Superadmin user FOUND in database:\n";
        echo "ID: " . $user['id'] . "\n";
        echo "Username: " . $user['username'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "Role: " . $user['role'] . "\n";
        echo "Active: " . ($user['is_active'] ? 'Yes' : 'No') . "\n";
    } else {
        echo "✗ Superadmin user NOT found in database\n";
        echo "\nAll users in database:\n";
        $stmt = $conn->prepare("SELECT id, username, email, role FROM users LIMIT 10");
        $stmt->execute();
        $users = $stmt->fetchAll();
        foreach ($users as $u) {
            echo "- " . $u['username'] . " (" . $u['role'] . ")\n";
        }
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
