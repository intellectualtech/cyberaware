<?php
/**
 * Subscription Reminders Cron Job
 * Run this script daily to send expiry reminders and handle expired subscriptions
 * 
 * Add to crontab:
 * 0 9 * * * php /path/to/subscription-reminders.php
 */

require_once '../config/database.php';
require_once '../includes/subscription-manager.php';

// Prevent direct web access
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

try {
    $pdo = getDBConnection();
    $manager = new SubscriptionManager($pdo);
    
    echo "[" . date('Y-m-d H:i:s') . "] Starting subscription reminder job...\n";
    
    // Send expiry reminders
    if ($manager->sendExpiryReminders()) {
        echo "[" . date('Y-m-d H:i:s') . "] Expiry reminders sent successfully.\n";
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] Error sending expiry reminders.\n";
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] Subscription reminder job completed.\n";
    
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] Error: " . $e->getMessage() . "\n";
    exit(1);
}

exit(0);
