<?php
/**
 * Notification System Test Script
 * Tests all notification types and queue processing
 */

require_once '../config/database.php';
require_once '../includes/NotificationService.php';

echo "=== CyberAware Notification System Test ===\n\n";

try {
    $pdo = getDBConnection();
    $notificationService = new NotificationService($pdo);

    // Get a test user (first trainee)
    $stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE role = 'trainee' LIMIT 1");
    $stmt->execute();
    $test_user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$test_user) {
        echo "ERROR: No trainee users found in database\n";
        exit(1);
    }

    echo "Test User: {$test_user['full_name']} ({$test_user['email']})\n\n";

    // Test 1: Training Assigned
    echo "Test 1: Training Assigned Notification\n";
    $result = $notificationService->notifyTrainingAssigned(
        $test_user['id'],
        1,
        'Password Security Basics',
        'password',
        '10 min'
    );
    echo "Result: " . ($result ? "✓ Queued" : "✗ Failed") . "\n\n";

    // Test 2: Campaign Reminder
    echo "Test 2: Campaign Reminder Notification\n";
    $result = $notificationService->notifyCampaignReminder(
        $test_user['id'],
        1,
        'Q4 Security Awareness Campaign',
        'Complete all modules to earn bonus XP',
        75
    );
    echo "Result: " . ($result ? "✓ Queued" : "✗ Failed") . "\n\n";

    // Test 3: Deadline Approaching
    echo "Test 3: Deadline Approaching Notification\n";
    $deadline = date('Y-m-d', strtotime('+3 days'));
    $result = $notificationService->notifyDeadlineApproaching(
        $test_user['id'],
        1,
        'Phishing Module Deadline',
        $deadline,
        'module'
    );
    echo "Result: " . ($result ? "✓ Queued" : "✗ Failed") . "\n\n";

    // Test 4: Incident Update
    echo "Test 4: Incident Status Update Notification\n";
    $result = $notificationService->notifyIncidentUpdate(
        $test_user['id'],
        1,
        'Suspicious Email Report',
        'Under Investigation',
        'Your reported incident is being reviewed by our security team'
    );
    echo "Result: " . ($result ? "✓ Queued" : "✗ Failed") . "\n\n";

    // Test 5: Certificate Completion
    echo "Test 5: Certificate Completion Notification\n";
    $result = $notificationService->notifyCertificateCompletion(
        $test_user['id'],
        1,
        'Advanced Phishing Detection',
        95,
        date('Y-m-d H:i:s')
    );
    echo "Result: " . ($result ? "✓ Queued" : "✗ Failed") . "\n\n";

    // Check queue
    echo "=== Queue Status ===\n";
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM notification_queue GROUP BY status");
    $stmt->execute();
    $queue_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($queue_stats as $stat) {
        echo "{$stat['status']}: {$stat['count']}\n";
    }

    echo "\n=== Processing Queue ===\n";
    $sent = $notificationService->processQueue(50);
    echo "Notifications sent: $sent\n\n";

    // Check history
    echo "=== Notification History ===\n";
    $stmt = $pdo->prepare("
        SELECT type, status, COUNT(*) as count 
        FROM notification_history 
        WHERE user_id = ? 
        GROUP BY type, status
    ");
    $stmt->execute([$test_user['id']]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($history)) {
        echo "No notifications in history yet\n";
    } else {
        foreach ($history as $h) {
            echo "{$h['type']}: {$h['status']} ({$h['count']})\n";
        }
    }

    echo "\n=== Test Complete ===\n";
    echo "✓ All tests completed successfully\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
