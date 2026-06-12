<?php
/**
 * Subscription Expired Page
 * Displayed when organization subscription has expired
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get user and organization info
$stmt = $pdo->prepare("
    SELECT u.full_name, u.email, u.organization_id, o.name as org_name, o.contact_email
    FROM users u
    LEFT JOIN organizations o ON u.organization_id = o.id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get subscription info
$subscription = null;
if ($user['organization_id']) {
    $sub_stmt = $pdo->prepare("
        SELECT status, end_date, plan_type FROM subscriptions 
        WHERE organization_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $sub_stmt->execute([$user['organization_id']]);
    $subscription = $sub_stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Expired – CyberAware</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 60px 40px;
            text-align: center;
        }
        .icon {
            font-size: 80px;
            color: #f44336;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 32px;
            color: #333;
            margin-bottom: 10px;
        }
        .subtitle {
            font-size: 16px;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .info-box {
            background: #f5f7fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: left;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #666;
        }
        .info-value {
            color: #333;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background: #ffebee;
            color: #c62828;
            margin-bottom: 20px;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        .btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        .features {
            background: #f5f7fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: left;
        }
        .features h3 {
            font-size: 14px;
            color: #333;
            margin-bottom: 15px;
        }
        .feature-list {
            list-style: none;
            padding: 0;
        }
        .feature-list li {
            padding: 8px 0;
            color: #666;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .feature-list i {
            color: #4caf50;
        }
        .contact-info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #1976d2;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <i class="fas fa-exclamation-circle"></i>
        </div>

        <h1>Subscription Expired</h1>
        <p class="subtitle">
            Your organization's subscription has expired. Access to CyberAware has been temporarily disabled.
        </p>

        <div class="status-badge">
            <i class="fas fa-times-circle"></i> Access Disabled
        </div>

        <?php if ($subscription): ?>
            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">Organization:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['org_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Plan:</span>
                    <span class="info-value"><?php echo ucfirst($subscription['plan_type']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Expired On:</span>
                    <span class="info-value"><?php echo date('F j, Y', strtotime($subscription['end_date'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value" style="color: #f44336; font-weight: 600;">
                        <?php echo ucfirst($subscription['status']); ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>

        <div class="features">
            <h3><i class="fas fa-info-circle"></i> What Happens Now?</h3>
            <ul class="feature-list">
                <li><i class="fas fa-times"></i> All users have been locked out</li>
                <li><i class="fas fa-times"></i> Training modules are no longer accessible</li>
                <li><i class="fas fa-times"></i> Reports and analytics are disabled</li>
                <li><i class="fas fa-check"></i> Your data is safely stored</li>
                <li><i class="fas fa-check"></i> You can renew at any time</li>
            </ul>
        </div>

        <div class="contact-info">
            <i class="fas fa-phone"></i> Contact our sales team to renew your subscription or upgrade your plan.
        </div>

        <div class="button-group">
            <a href="mailto:<?php echo htmlspecialchars($user['contact_email'] ?? 'sales@cyberaware.com'); ?>" class="btn btn-primary">
                <i class="fas fa-envelope"></i> Contact Sales
            </a>
            <a href="logout.php" class="btn btn-secondary">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</body>
</html>
