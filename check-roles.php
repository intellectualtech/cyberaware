<?php
require_once 'config/database.php';

$pdo = getDBConnection();

// Get all unique roles
$stmt = $pdo->query("SELECT DISTINCT role FROM users ORDER BY role");
$roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "<h2>Unique Roles in Database:</h2>";
echo "<pre>";
print_r($roles);
echo "</pre>";

// Get all users with their roles
$stmt = $pdo->query("SELECT id, username, full_name, role FROM users ORDER BY role, username");
$users = $stmt->fetchAll();

echo "<h2>All Users:</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th></tr>";
foreach ($users as $user) {
    echo "<tr>";
    echo "<td>" . $user['id'] . "</td>";
    echo "<td>" . $user['username'] . "</td>";
    echo "<td>" . $user['full_name'] . "</td>";
    echo "<td>" . $user['role'] . "</td>";
    echo "</tr>";
}
echo "</table>";
?>
