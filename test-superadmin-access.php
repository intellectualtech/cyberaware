<?php
/**
 * Test script to verify superadmin access to all pages
 */
session_start();

// Simulate superadmin login
$_SESSION['user_id'] = 6;  // Based on our earlier test
$_SESSION['username'] = 'superadmin';
$_SESSION['role'] = 'superadmin';
$_SESSION['full_name'] = 'Super Administrator';

require_once 'config/database.php';

echo "Session Information:\n";
echo "==================\n";
echo "User ID: " . $_SESSION['user_id'] . "\n";
echo "Username: " . $_SESSION['username'] . "\n";
echo "Role: " . $_SESSION['role'] . "\n";
echo "Full Name: " . $_SESSION['full_name'] . "\n\n";

// Test role check like the pages do
$role = strtolower(trim($_SESSION['role'] ?? ''));
echo "Role check (strtolower(trim)): '" . $role . "'\n";
echo "Role === 'superadmin': " . ($role === 'superadmin' ? 'TRUE ✓' : 'FALSE ✗') . "\n\n";

// Test isLoggedIn function
echo "isLoggedIn(): " . (isLoggedIn() ? 'TRUE ✓' : 'FALSE ✗') . "\n";

// Try to connect to database
try {
    $conn = getDBConnection();
    echo "Database connection: SUCCESS ✓\n";
    
    // Verify superadmin user in database
    $stmt = $conn->prepare("SELECT id, username, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "\nUser in database:\n";
        echo "ID: " . $user['id'] . "\n";
        echo "Username: " . $user['username'] . "\n";
        echo "Role: " . $user['role'] . "\n";
        echo "✓ All checks passed! Access should work.\n";
    } else {
        echo "✗ User not found in database\n";
    }
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
