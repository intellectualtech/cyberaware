<?php
// Test password verification
require_once('c:/xampp/htdocs/cyberaware-new-Edits/config/database.php');

try {
    $conn = getDBConnection();
    
    // Get the superadmin user
    $stmt = $conn->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
    $stmt->execute(['superadmin']);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "✗ Superadmin user not found\n";
        exit;
    }
    
    echo "User found:\n";
    echo "Username: " . $user['username'] . "\n";
    echo "Password hash: " . $user['password_hash'] . "\n";
    echo "Hash length: " . strlen($user['password_hash']) . "\n\n";
    
    // Test with the password
    $password = 'Uncle@foddy1';
    echo "Testing password: '$password'\n";
    
    $isValid = password_verify($password, $user['password_hash']);
    echo "Password valid: " . ($isValid ? 'YES ✓' : 'NO ✗') . "\n\n";
    
    if (!$isValid) {
        echo "Password hash doesn't match. Let's generate a new hash:\n";
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        echo "New hash: " . $newHash . "\n";
        echo "\nUpdating password hash in database...\n";
        
        $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE username = 'superadmin'");
        $stmt->execute([$newHash]);
        echo "✓ Password hash updated!\n";
        
        // Verify again
        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE username = 'superadmin'");
        $stmt->execute();
        $updated = $stmt->fetch();
        
        $isValid2 = password_verify($password, $updated['password_hash']);
        echo "Password now valid: " . ($isValid2 ? 'YES ✓' : 'NO ✗') . "\n";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
