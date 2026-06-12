<?php
// trainee/certificate.php - Certificate of Completion

require_once '../config/database.php';
require_once '../includes/functions.php';
require_login();

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];
$module_id = (int)($_GET['module'] ?? 0);

// Get user info
$stmt = $pdo->prepare("SELECT full_name, username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get module info
$stmt = $pdo->prepare("SELECT id, title, code FROM training_modules WHERE id = ?");
$stmt->execute([$module_id]);
$module = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if user completed this module
$stmt = $pdo->prepare("
    SELECT COUNT(*) as completed
    FROM training_sessions
    WHERE user_id = ? AND module_id = ? AND completed_at IS NOT NULL
");
$stmt->execute([$user_id, $module_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$is_completed = (int)$result['completed'] > 0;

// Get completion date
$stmt = $pdo->prepare("
    SELECT MAX(completed_at) as completion_date
    FROM training_sessions
    WHERE user_id = ? AND module_id = ? AND completed_at IS NOT NULL
");
$stmt->execute([$user_id, $module_id]);
$completion = $stmt->fetch(PDO::FETCH_ASSOC);
$completion_date = $completion['completion_date'] ? date('F j, Y', strtotime($completion['completion_date'])) : 'N/A';

// Get average score
$stmt = $pdo->prepare("
    SELECT AVG(final_score) as avg_score
    FROM training_sessions
    WHERE user_id = ? AND module_id = ? AND completed_at IS NOT NULL
");
$stmt->execute([$user_id, $module_id]);
$score_result = $stmt->fetch(PDO::FETCH_ASSOC);
$avg_score = $score_result['avg_score'] ? round((float)$score_result['avg_score']) : 0;

// Get all completed modules for overview
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT module_id) as total_completed
    FROM training_sessions
    WHERE user_id = ? AND completed_at IS NOT NULL
");
$stmt->execute([$user_id]);
$overview = $stmt->fetch(PDO::FETCH_ASSOC);
$total_completed = (int)$overview['total_completed'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Completion – CyberAware</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --orange: #FF8C42;
            --dark: #1A1A2E;
            --grey: #F5F5F5;
            --text: #333;
        }
        body {
            font-family: 'Manrope', sans-serif;
            background: linear-gradient(135deg, #FFF4EC 0%, #fff 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .certificate-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            max-width: 900px;
            width: 100%;
            padding: 60px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .certificate-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 8px;
            background: linear-gradient(90deg, var(--orange), #FFB070);
        }
        .cert-header {
            margin-bottom: 30px;
        }
        .cert-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .cert-logo-icon {
            width: 50px;
            height: 50px;
            background: var(--orange);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }
        .cert-logo span {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
        }
        .cert-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 32px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 10px;
        }
        .cert-subtitle {
            font-size: 16px;
            color: #888;
            margin-bottom: 40px;
        }
        .cert-content {
            margin: 40px 0;
            padding: 40px 0;
            border-top: 2px solid var(--grey);
            border-bottom: 2px solid var(--grey);
        }
        .cert-text {
            font-size: 16px;
            color: var(--text);
            margin-bottom: 20px;
            line-height: 1.8;
        }
        .cert-name {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 28px;
            font-weight: 700;
            color: var(--orange);
            margin: 20px 0;
        }
        .cert-module {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark);
            margin: 20px 0;
        }
        .cert-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .detail-box {
            background: var(--grey);
            padding: 20px;
            border-radius: 12px;
        }
        .detail-label {
            font-size: 12px;
            color: #888;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .detail-value {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark);
        }
        .cert-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid var(--grey);
        }
        .cert-footer p {
            font-size: 12px;
            color: #aaa;
            margin: 10px 0;
        }
        .cert-footer strong {
            color: var(--text);
        }
        .actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 24px;
            border-radius: 999px;
            border: none;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .btn-primary {
            background: var(--orange);
            color: white;
        }
        .btn-primary:hover {
            background: #E67A2E;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: var(--grey);
            color: var(--text);
        }
        .btn-secondary:hover {
            background: #e8e8e8;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .status-badge.completed {
            background: #d1fae5;
            color: #065f46;
        }
        .status-badge.incomplete {
            background: #fee2e2;
            color: #991b1b;
        }
        .incomplete-message {
            background: #fef2f2;
            border: 2px solid #fecaca;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            color: #991b1b;
        }
        @media print {
            body { background: white; }
            .actions { display: none; }
            .certificate-container { box-shadow: none; }
        }
    </style>
</head>
<body>

<div class="certificate-container">
    <div class="cert-header">
        <div class="cert-logo">
            <div class="cert-logo-icon"><i class="fas fa-shield-alt"></i></div>
            <span>CyberAware</span>
        </div>
        <div class="cert-title">Certificate of Completion</div>
        <div class="cert-subtitle">Security Awareness Training</div>
    </div>

    <?php if ($is_completed): ?>
        <div class="status-badge completed"><i class="fas fa-check"></i> Completed</div>

        <div class="cert-content">
            <p class="cert-text">This is to certify that</p>
            <div class="cert-name"><?= htmlspecialchars($user['full_name'] ?: $user['username']) ?></div>
            <p class="cert-text">has successfully completed the training module</p>
            <div class="cert-module"><?= htmlspecialchars($module['title'] ?? 'Training Module') ?></div>
            <p class="cert-text">on <?= htmlspecialchars($completion_date) ?></p>
        </div>

        <div class="cert-details">
            <div class="detail-box">
                <div class="detail-label">Module Code</div>
                <div class="detail-value"><?= htmlspecialchars($module['code'] ?? 'N/A') ?></div>
            </div>
            <div class="detail-box">
                <div class="detail-label">Average Score</div>
                <div class="detail-value"><?= $avg_score ?>%</div>
            </div>
            <div class="detail-box">
                <div class="detail-label">Completion Date</div>
                <div class="detail-value"><?= htmlspecialchars($completion_date) ?></div>
            </div>
            <div class="detail-box">
                <div class="detail-label">Total Modules Completed</div>
                <div class="detail-value"><?= $total_completed ?></div>
            </div>
        </div>

        <div class="cert-footer">
            <p>This certificate is issued by <strong>CyberAware</strong> as proof of successful completion of security awareness training.</p>
            <p>Certificate ID: <strong><?= htmlspecialchars($user['username']) ?>-MOD<?= $module_id ?>-<?= date('Ymd') ?></strong></p>
            <p>Issued on <?= date('F j, Y') ?></p>
        </div>

    <?php else: ?>
        <div class="status-badge incomplete">✗ Not Completed</div>
        <div class="incomplete-message">
            <i class="fas fa-info-circle"></i>
            <strong>Module Not Completed</strong><br>
            You haven't completed this module yet. Complete the training to earn your certificate.
        </div>
    <?php endif; ?>

    <div class="actions">
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        <?php if ($is_completed): ?>
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-download"></i> Download Certificate
            </button>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
