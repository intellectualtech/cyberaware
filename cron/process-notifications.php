<?php
/**
 * Notification Queue Processor
 * Run this via cron job every 5 minutes: */5 * * * * php /path/to/process-notifications.php
 */

require_once '../config/database.php';
require_once '../includes/NotificationService.php';

try {
    $pdo = getDBConnection();
    $notificationService = new NotificationService($pdo);

    // Process up to 50 notifications per run
    $sent_count = $notificationService->processQueue(50);

    // Log the result
    $log_message = date('Y-m-d H:i:s') . " - Processed $sent_count notifications\n";
    file_put_contents(__DIR__ . '/notification_processor.log', $log_message, FILE_APPEND);

    echo "Success: $sent_count notifications sent\n";
} catch (Exception $e) {
    $error_message = date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n";
    file_put_contents(__DIR__ . '/notification_processor.log', $error_message, FILE_APPEND);
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
