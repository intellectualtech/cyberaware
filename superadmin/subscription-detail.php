<?php
/**
 * Subscription Detail View
 * View and manage individual subscription details
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'superadmin') {
    header('Location: ../index.php');
    exit;
}

$pdo = getDBConnection();
$superadmin_id = $_SESSION['user_id'];
$subscription_id = (int)($_GET['id'] ?? 0);
$message = '';
$message_type = '';

if ($subscription_id === 0) {
    header('Location: subscriptions.php');
    exit;
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'upgrade_plan') {
        $new_plan = trim($_POST['new_plan'] ?? '');
        
        if (in_array($new_plan, ['basic', 'professional', 'enterprise'])) {
            try {
                $stmt = $pdo->prepare("SELECT plan_type, status FROM subscriptions WHERE id = ?");
                $stmt->execute([$subscription_id]);
                $sub = $stmt->fetch();
                
                if ($sub) {
                    $update_stmt = $pdo->prepare("
                        UPDATE subscriptions 
                        SET plan_type = ?, status = 'active'
                        WHERE id = ?
                    ");
                    $update_stmt->execute([$new_plan, $subscription_id]);
                    
                    // Log action
                    $history_stmt = $pdo->prepare("
                        INSERT INTO subscription_history (
                            subscription_id, action, old_plan, new_plan, old_status, new_status, performed_by
                        ) VALUES (?, 'upgraded', ?, ?, ?, 'active', ?)
                    ");
                    $history_stmt->execute([$subscription_id, $sub['plan_type'], $new_plan, $sub['status'], $superadmin_id]);
                    
                    $message = 'Subscription upgraded successfully!';
                    $message_type = 'success';
                }
            } catch (Exception $e) {
                $message = 'Error: ' . $e->getMessage();
                $message_type = 'danger';
            }
        }
    } elseif ($action === 'suspend') {
        try {
            $stmt = $pdo->prepare("SELECT status FROM subscriptions WHERE id = ?");
            $stmt->execute([$subscription_id]);
            $sub = $stmt->fetch();
            
            if ($sub) {
                $update_stmt = $pdo->prepare("UPDATE subscriptions SET status = 'suspended' WHERE id = ?");
                $update_stmt->execute([$subscription_id]);
                
                $history_stmt = $pdo->prepare("
                    INSERT INTO subscription_history (
                        subscription_id, action, old_status, new_status, performed_by
                    ) VALUES (?, 'suspended', ?, 'suspended', ?)
                ");
                $history_stmt->execute([$subscription_id, $sub['status'], $superadmin_id]);
                
                $message = 'Subscription suspended!';
                $message_type = 'success';
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'danger';
        }
    } elseif ($action === 'reactivate') {
        try {
            $stmt = $pdo->prepare("SELECT status FROM subscriptions WHERE id = ?");
            $stmt->execute([$subscription_id]);
            $sub = $stmt->fetch();
            
            if ($sub) {
                $update_stmt = $pdo->prepare("UPDATE subscriptions SET status = 'active' WHERE id = ?");
                $update_stmt->execute([$subscription_id]);
                
                $history_stmt = $pdo->prepare("
                    INSERT INTO subscription_history (
                        subscription_id, action, old_status, new_status, performed_by
                    ) VALUES (?, 'reactivated', ?, 'active', ?)
                ");
                $history_stmt->execute([$subscription_id, $sub['status'], $superadmin_id]);
                
                $message = 'Subscription reactivated!';
                $message_type = 'success';
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Get subscription details
$stmt = $pdo->prepare("
    SELECT 
        s.*, 
        o.name as org_name, o.email as org_email, o.contact_person, o.contact_email,
        o.phone, o.address, o.city, o.state, o.country,
        DATEDIFF(s.end_date, NOW()) as days_remaining
    FROM subscriptions s
    JOIN organizations o ON s.organization_id = o.id
    WHERE s.id = ?
");
$stmt->execute([$subscription_id]);
$subscription = $stmt->fetch();

if (!$subscription) {
    header('Location: subscriptions.php');
    exit;
}

// Get subscription features
$features_stmt = $pdo->prepare("
    SELECT feature_name, feature_description 
    FROM subscription_features 
    WHERE plan_type = ? AND is_enabled = 1
    ORDER BY feature_name
");
$features_stmt->execute([$subscription['plan_type']]);
$features = $features_stmt->fetchAll();

// Get subscription history
$history_stmt = $pdo->prepare("
    SELECT 
        sh.*, 
        u.full_name as performed_by_name
    FROM subscription_history sh
    LEFT JOIN users u ON sh.performed_by = u.id
    WHERE sh.subscription_id = ?
    ORDER BY sh.created_at DESC
    LIMIT 20
");
$history_stmt->execute([$subscription_id]);
$history = $history_stmt->fetchAll();

// Get payments
$payments_stmt = $pdo->prepare("
    SELECT * FROM subscription_payments
    WHERE subscription_id = ?
    ORDER BY payment_date DESC
    LIMIT 10
");
$payments_stmt->execute([$subscription_id]);
$payments = $payments_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Details – CyberAware Super Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { margin-bottom: 30px; }
        .header a { color: #667eea; text-decoration: none; font-size: 14px; }
        .header h1 { font-size: 28px; color: #333; margin: 10px 0; }
        .grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden; }
        .card-header { background: #f8f9fa; padding: 20px; border-bottom: 1px solid #eee; }
        .card-header h2 { font-size: 16px; color: #333; }
        .card-body { padding: 20px; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .info-row:last-child { border-bottom: none; }
        .info-label { font-size: 12px; color: #666; font-weight: 600; text-transform: uppercase; }
        .info-value { font-size: 14px; color: #333; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-trial { background: #e3f2fd; color: #1976d2; }
        .badge-active { background: #e8f5e9; color: #388e3c; }
        .badge-expired { background: #ffebee; color: #c62828; }
        .badge-suspended { background: #fff3e0; color: #f57c00; }
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600; }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5568d3; }
        .btn-warning { background: #ff9800; color: white; }
        .btn-danger { background: #f44336; color: white; }
        .btn-success { background: #4caf50; color: white; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        .feature-list { list-style: none; padding: 0; }
        .feature-list li { padding: 10px 0; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 10px; }
        .feature-list li:last-child { border-bottom: none; }
        .feature-list i { color: #4caf50; }
        .history-item { padding: 15px; border-left: 4px solid #667eea; background: #f8f9fa; margin-bottom: 10px; border-radius: 4px; }
        .history-item .action { font-weight: 600; color: #333; }
        .history-item .time { font-size: 12px; color: #999; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: #666; margin-bottom: 5px; }
        .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px; }
        .button-group { display: flex; gap: 10px; margin-top: 20px; }
        @media (max-width: 900px) {
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="subscriptions.php"><i class="fas fa-arrow-left"></i> Back to Subscriptions</a>
            <h1>Subscription Details</h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="grid">
            <div>
                <!-- Subscription Info -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-info-circle"></i> Subscription Information</h2>
                    </div>
                    <div class="card-body">
                        <div class="info-row">
                            <div>
                                <div class="info-label">Organization</div>
                                <div class="info-value"><?php echo htmlspecialchars($subscription['org_name']); ?></div>
                            </div>
                        </div>

                        <div class="info-row">
                            <div>
                                <div class="info-label">Plan Type</div>
                                <div class="info-value"><?php echo ucfirst($subscription['plan_type']); ?></div>
                            </div>
                            <div>
                                <div class="info-label">Status</div>
                                <div class="info-value">
                                    <span class="badge badge-<?php echo $subscription['status']; ?>">
                                        <?php echo ucfirst($subscription['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="info-row">
                            <div>
                                <div class="info-label">Start Date</div>
                                <div class="info-value"><?php echo date('M d, Y H:i', strtotime($subscription['start_date'])); ?></div>
                            </div>
                            <div>
                                <div class="info-label">End Date</div>
                                <div class="info-value"><?php echo date('M d, Y H:i', strtotime($subscription['end_date'])); ?></div>
                            </div>
                        </div>

                        <div class="info-row">
                            <div>
                                <div class="info-label">Days Remaining</div>
                                <div class="info-value" style="color: <?php echo $subscription['days_remaining'] <= 7 ? '#ff9800' : ($subscription['days_remaining'] < 0 ? '#f44336' : '#333'); ?>; font-weight: 600;">
                                    <?php echo $subscription['days_remaining']; ?> days
                                </div>
                            </div>
                            <div>
                                <div class="info-label">Auto Renew</div>
                                <div class="info-value">
                                    <i class="fas fa-<?php echo $subscription['auto_renew'] ? 'check-circle' : 'times-circle'; ?>" style="color: <?php echo $subscription['auto_renew'] ? '#4caf50' : '#f44336'; ?>"></i>
                                </div>
                            </div>
                        </div>

                        <div class="info-row">
                            <div>
                                <div class="info-label">Max Users</div>
                                <div class="info-value"><?php echo $subscription['max_users']; ?></div>
                            </div>
                            <div>
                                <div class="info-label">Max Modules</div>
                                <div class="info-value"><?php echo $subscription['max_modules']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Organization Info -->
                <div class="card" style="margin-top: 20px;">
                    <div class="card-header">
                        <h2><i class="fas fa-building"></i> Organization Information</h2>
                    </div>
                    <div class="card-body">
                        <div class="info-row">
                            <div>
                                <div class="info-label">Email</div>
                                <div class="info-value"><?php echo htmlspecialchars($subscription['org_email']); ?></div>
                            </div>
                        </div>

                        <div class="info-row">
                            <div>
                                <div class="info-label">Contact Person</div>
                                <div class="info-value"><?php echo htmlspecialchars($subscription['contact_person'] ?? 'N/A'); ?></div>
                            </div>
                            <div>
                                <div class="info-label">Contact Email</div>
                                <div class="info-value"><?php echo htmlspecialchars($subscription['contact_email'] ?? 'N/A'); ?></div>
                            </div>
                        </div>

                        <div class="info-row">
                            <div>
                                <div class="info-label">Phone</div>
                                <div class="info-value"><?php echo htmlspecialchars($subscription['phone'] ?? 'N/A'); ?></div>
                            </div>
                        </div>

                        <div class="info-row">
                            <div>
                                <div class="info-label">Address</div>
                                <div class="info-value">
                                    <?php echo htmlspecialchars($subscription['address'] ?? 'N/A'); ?><br>
                                    <?php echo htmlspecialchars($subscription['city'] ?? ''); ?>, <?php echo htmlspecialchars($subscription['state'] ?? ''); ?> <?php echo htmlspecialchars($subscription['postal_code'] ?? ''); ?><br>
                                    <?php echo htmlspecialchars($subscription['country'] ?? ''); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Features -->
                <div class="card" style="margin-top: 20px;">
                    <div class="card-header">
                        <h2><i class="fas fa-star"></i> Included Features</h2>
                    </div>
                    <div class="card-body">
                        <ul class="feature-list">
                            <?php foreach ($features as $feature): ?>
                                <li>
                                    <i class="fas fa-check"></i>
                                    <div>
                                        <strong><?php echo htmlspecialchars($feature['feature_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($feature['feature_description']); ?></small>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- History -->
                <div class="card" style="margin-top: 20px;">
                    <div class="card-header">
                        <h2><i class="fas fa-history"></i> Subscription History</h2>
                    </div>
                    <div class="card-body">
                        <?php foreach ($history as $item): ?>
                            <div class="history-item">
                                <div class="action">
                                    <i class="fas fa-circle" style="font-size: 8px;"></i>
                                    <?php echo ucfirst(str_replace('_', ' ', $item['action'])); ?>
                                </div>
                                <?php if ($item['old_plan'] || $item['new_plan']): ?>
                                    <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                        <?php echo $item['old_plan'] ? 'From ' . ucfirst($item['old_plan']) : ''; ?>
                                        <?php echo $item['new_plan'] ? ' to ' . ucfirst($item['new_plan']) : ''; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($item['notes']): ?>
                                    <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                        <?php echo htmlspecialchars($item['notes']); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="time">
                                    <?php echo date('M d, Y H:i', strtotime($item['created_at'])); ?>
                                    <?php echo $item['performed_by_name'] ? ' by ' . htmlspecialchars($item['performed_by_name']) : ''; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Actions Sidebar -->
            <div>
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-cogs"></i> Actions</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($subscription['status'] !== 'cancelled'): ?>
                            <form method="POST" style="margin-bottom: 20px;">
                                <input type="hidden" name="action" value="upgrade_plan">
                                
                                <div class="form-group">
                                    <label>Upgrade Plan</label>
                                    <select name="new_plan" required>
                                        <option value="">-- Select Plan --</option>
                                        <?php if ($subscription['plan_type'] !== 'basic'): ?>
                                            <option value="basic">Basic</option>
                                        <?php endif; ?>
                                        <?php if ($subscription['plan_type'] !== 'professional'): ?>
                                            <option value="professional">Professional</option>
                                        <?php endif; ?>
                                        <?php if ($subscription['plan_type'] !== 'enterprise'): ?>
                                            <option value="enterprise">Enterprise</option>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-primary" style="width: 100%;">
                                    <i class="fas fa-arrow-up"></i> Upgrade Plan
                                </button>
                            </form>

                            <?php if ($subscription['status'] === 'suspended'): ?>
                                <form method="POST" style="margin-bottom: 20px;">
                                    <input type="hidden" name="action" value="reactivate">
                                    <button type="submit" class="btn btn-success" style="width: 100%;">
                                        <i class="fas fa-play"></i> Reactivate
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="POST" style="margin-bottom: 20px;">
                                    <input type="hidden" name="action" value="suspend">
                                    <button type="submit" class="btn btn-warning" style="width: 100%;" onclick="return confirm('Suspend this subscription?');">
                                        <i class="fas fa-pause"></i> Suspend
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Payments -->
                <?php if (!empty($payments)): ?>
                    <div class="card" style="margin-top: 20px;">
                        <div class="card-header">
                            <h2><i class="fas fa-receipt"></i> Recent Payments</h2>
                        </div>
                        <div class="card-body">
                            <?php foreach ($payments as $payment): ?>
                                <div style="padding: 10px 0; border-bottom: 1px solid #eee;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                        <strong><?php echo $payment['currency']; ?> <?php echo number_format($payment['amount'], 2); ?></strong>
                                        <span class="badge badge-<?php echo $payment['status']; ?>" style="background: <?php echo $payment['status'] === 'completed' ? '#e8f5e9' : '#fff3e0'; ?>; color: <?php echo $payment['status'] === 'completed' ? '#388e3c' : '#f57c00'; ?>;">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </div>
                                    <div style="font-size: 12px; color: #999;">
                                        <?php echo date('M d, Y', strtotime($payment['payment_date'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
