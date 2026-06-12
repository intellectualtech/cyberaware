<?php
/**
 * Notification Preferences Page
 * Allows trainees to manage their notification settings
 */

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/NotificationService.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'trainee') {
    header('Location: ../pages/login.php');
    exit;
}

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get notification preferences
$stmt = $pdo->prepare("SELECT * FROM user_notification_preferences WHERE user_id = ?");
$stmt->execute([$user_id]);
$prefs = $stmt->fetch(PDO::FETCH_ASSOC);

// Create default preferences if not exists
if (!$prefs) {
    $stmt = $pdo->prepare("INSERT INTO user_notification_preferences (user_id) VALUES (?)");
    $stmt->execute([$user_id]);
    $prefs = [
        'training_assigned' => 1,
        'campaign_reminder' => 1,
        'deadline_approaching' => 1,
        'incident_update' => 1,
        'certificate_completion' => 1,
        'email_frequency' => 'immediate'
    ];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $notificationService = new NotificationService($pdo);
        
        $preferences = [
            'training_assigned' => isset($_POST['training_assigned']) ? 1 : 0,
            'campaign_reminder' => isset($_POST['campaign_reminder']) ? 1 : 0,
            'deadline_approaching' => isset($_POST['deadline_approaching']) ? 1 : 0,
            'incident_update' => isset($_POST['incident_update']) ? 1 : 0,
            'certificate_completion' => isset($_POST['certificate_completion']) ? 1 : 0,
            'email_frequency' => $_POST['email_frequency'] ?? 'immediate'
        ];

        if ($notificationService->updatePreferences($user_id, $preferences)) {
            $message = 'Notification preferences updated successfully!';
            $message_type = 'success';
            $prefs = array_merge($prefs, $preferences);
        } else {
            $message = 'Error updating preferences';
            $message_type = 'danger';
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

// Get notification history
$stmt = $pdo->prepare("
    SELECT * FROM notification_history
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 20
");
$stmt->execute([$user_id]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Preferences – CyberAware</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 28px;
            color: #1A1A2E;
            margin: 0;
        }

        .header a {
            color: #888;
            text-decoration: none;
            font-size: 14px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,.05);
        }

        .card-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            font-weight: 700;
            color: #1A1A2E;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #FF8C42;
        }

        .card-title i {
            color: #FF8C42;
            font-size: 20px;
        }

        .preference-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
            margin-bottom: 12px;
        }

        .preference-label {
            flex: 1;
        }

        .preference-label h4 {
            font-size: 14px;
            font-weight: 600;
            color: #1A1A2E;
            margin: 0 0 4px 0;
        }

        .preference-label p {
            font-size: 12px;
            color: #888;
            margin: 0;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 28px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 28px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #FF8C42;
        }

        input:checked + .slider:before {
            transform: translateX(22px);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        select:focus {
            outline: none;
            border-color: #FF8C42;
            box-shadow: 0 0 0 3px rgba(255, 140, 66, 0.1);
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        button {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #FF8C42;
            color: white;
            flex: 1;
        }

        .btn-primary:hover {
            background: #e67e2f;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 140, 66, 0.3);
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .history-table th {
            background: #f9f9f9;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #eee;
            font-size: 13px;
        }

        .history-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .status-sent {
            background: #d1fae5;
            color: #065f46;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-failed {
            background: #fee2e2;
            color: #991b1b;
        }

        .type-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            background: #ede9fe;
            color: #5b21b6;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            .preference-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .button-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <a href="dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <h1>Notification Preferences</h1>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <div><?php echo $message; ?></div>
            </div>
        <?php endif; ?>

        <!-- Notification Settings -->
        <div class="card">
            <div class="card-title">
                <i class="fas fa-bell"></i> Notification Types
            </div>
            <form method="POST">
                <div class="preference-item">
                    <div class="preference-label">
                        <h4>Training Assigned</h4>
                        <p>Get notified when new training modules are assigned</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="training_assigned" <?php echo $prefs['training_assigned'] ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="preference-item">
                    <div class="preference-label">
                        <h4>Campaign Reminders</h4>
                        <p>Receive reminders about ongoing campaigns</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="campaign_reminder" <?php echo $prefs['campaign_reminder'] ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="preference-item">
                    <div class="preference-label">
                        <h4>Deadline Approaching</h4>
                        <p>Get alerts when deadlines are approaching</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="deadline_approaching" <?php echo $prefs['deadline_approaching'] ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="preference-item">
                    <div class="preference-label">
                        <h4>Incident Updates</h4>
                        <p>Receive updates on reported incidents</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="incident_update" <?php echo $prefs['incident_update'] ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="preference-item">
                    <div class="preference-label">
                        <h4>Certificate Completion</h4>
                        <p>Get notified when you earn certificates</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="certificate_completion" <?php echo $prefs['certificate_completion'] ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <label for="email_frequency">Email Frequency</label>
                    <select id="email_frequency" name="email_frequency">
                        <option value="immediate" <?php echo $prefs['email_frequency'] === 'immediate' ? 'selected' : ''; ?>>Immediate</option>
                        <option value="daily_digest" <?php echo $prefs['email_frequency'] === 'daily_digest' ? 'selected' : ''; ?>>Daily Digest</option>
                        <option value="weekly_digest" <?php echo $prefs['email_frequency'] === 'weekly_digest' ? 'selected' : ''; ?>>Weekly Digest</option>
                    </select>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Save Preferences
                    </button>
                </div>
            </form>
        </div>

        <!-- Notification History -->
        <div class="card">
            <div class="card-title">
                <i class="fas fa-history"></i> Recent Notifications
            </div>
            <?php if (!empty($history)): ?>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Sent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $notification): ?>
                        <tr>
                            <td>
                                <span class="type-badge"><?php echo ucfirst(str_replace('_', ' ', $notification['type'])); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars(substr($notification['subject'], 0, 50)); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $notification['status']; ?>">
                                    <?php echo ucfirst($notification['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y H:i', strtotime($notification['sent_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: #888; text-align: center; padding: 20px;">No notifications yet</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
