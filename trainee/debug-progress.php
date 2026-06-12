<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    die('Not logged in');
}

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

echo "<h2>User Module Progress</h2>";
$stmt = $pdo->prepare("SELECT * FROM user_module_progress WHERE user_id = ? ORDER BY module_id");
$stmt->execute([$user_id]);
$progress = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($progress);
echo "</pre>";

echo "<h2>User Module Registrations</h2>";
$stmt = $pdo->prepare("SELECT * FROM user_module_registrations WHERE user_id = ? ORDER BY registration_order");
$stmt->execute([$user_id]);
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($registrations);
echo "</pre>";

echo "<h2>Combined View (Dashboard Query)</h2>";
$stmt = $pdo->prepare("
    SELECT 
        umr.module_id,
        umr.registration_order,
        umr.status,
        tm.title,
        tm.category,
        COALESCE(ump.passed, 0) as passed,
        COALESCE(ump.best_score, 0) as best_score,
        ump.total_attempts,
        ump.average_score
    FROM user_module_registrations umr
    JOIN training_modules tm ON umr.module_id = tm.id
    LEFT JOIN user_module_progress ump ON umr.user_id = ump.user_id AND umr.module_id = ump.module_id
    WHERE umr.user_id = ?
    ORDER BY umr.registration_order
");
$stmt->execute([$user_id]);
$combined = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($combined);
echo "</pre>";
?>
