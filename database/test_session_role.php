<?php
// Test session role for superadmin
session_start();
require_once('c:/xampp/htdocs/cyberaware-new-Edits/config/database.php');

// Simulate login
$_SESSION['user_id'] = 1; // You'll need to get the actual superadmin ID
$_SESSION['username'] = 'superadmin';

$conn = getDBConnection();

// Get superadmin user
$stmt = $conn->prepare("SELECT id, username, role FROM users WHERE username = ?");
$stmt->execute(['superadmin']);
$user = $stmt->fetch();

if ($user) {
    echo "Superadmin user found:\n";
    echo "ID: " . $user['id'] . "\n";
    echo "Username: " . $user['username'] . "\n";
    echo "Role from DB: '" . $user['role'] . "'\n";
    echo "Role type: " . gettype($user['role']) . "\n";
    echo "Role length: " . strlen($user['role']) . "\n";
    echo "Role encoded: " . bin2hex($user['role']) . "\n";
    
    $_SESSION['role'] = $user['role'];
    
    echo "\nAfter setting session:\n";
    echo "Session role: '" . $_SESSION['role'] . "'\n";
    
    // Test comparison
    echo "\nTest comparisons:\n";
    echo "role === 'superadmin': " . (($user['role'] === 'superadmin') ? 'TRUE' : 'FALSE') . "\n";
    echo "strtolower(trim(role)) === 'superadmin': " . ((strtolower(trim($user['role'])) === 'superadmin') ? 'TRUE' : 'FALSE') . "\n";
    
    // Show raw bytes
    echo "\n\nRaw bytes analysis:\n";
    echo "Expected 'superadmin' bytes: " . bin2hex('superadmin') . "\n";
    echo "Actual role bytes: " . bin2hex($user['role']) . "\n";
} else {
    echo "Superadmin user NOT found\n";
}
?>
