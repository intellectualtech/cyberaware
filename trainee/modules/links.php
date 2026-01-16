<?php
// modules/links.php - Suspicious Links & Website Safety Simulation

require_once '../../config/database.php';
require_once '../../includes/functions.php';

require_login();

// Get DB connection
$pdo = getDBConnection();

// Fetch user directly
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id, username, full_name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Error: User not found. Please contact administrator.");
}

$user_email = $user['email'] ?? ($user['username'] . '@company.com');

// Module ID for Suspicious Links & Website Safety
$module_id = 5;

// Start new training session if none active
$session_id = null;
if (!isset($_SESSION['current_links_session'])) {
    $stmt = $pdo->prepare("
        INSERT INTO training_sessions 
        (user_id, module_id, started_at)
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$user_id, $module_id]);
    $session_id = $pdo->lastInsertId();
    $_SESSION['current_links_session'] = $session_id;
} else {
    $session_id = $_SESSION['current_links_session'];
}

// Handle user action
$feedback = null;
$feedback_class = '';
$show_takeaways = false;
$step_number = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Correct actions: inspect or report
    $is_correct = in_array($action, ['inspect', 'report']);
    $points = $is_correct ? 10 : -10;

    // Log action
    $stmt = $pdo->prepare("
        INSERT INTO training_actions 
        (session_id, step_number, action_type, is_correct, points, timestamp)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$session_id, $step_number, $action, $is_correct ? 1 : 0, $points]);

    // Feedback
    if ($is_correct) {
        $feedback = "Smart choice! Always inspect links before clicking — you avoided a potential threat.";
        $feedback_class = 'success';
    } else {
        $feedback = "This link was malicious in the simulation.<br>Clicking shortened or suspicious links can lead to phishing sites, malware, or data theft.";
        $feedback_class = 'danger';
    }

    $show_takeaways = true;

    // Complete session
    $stmt = $pdo->prepare("
        UPDATE training_sessions 
        SET completed_at = NOW(), 
            final_score = ?,
            duration_seconds = TIMESTAMPDIFF(SECOND, started_at, NOW())
        WHERE id = ?
    ");
    $stmt->execute([$points, $session_id]);

    unset($_SESSION['current_links_session']);
}

// Previous stats for this module
$stmt = $pdo->prepare("
    SELECT AVG(final_score) as avg_score, COUNT(*) as attempts
    FROM training_sessions
    WHERE user_id = ? AND module_id = ?
");
$stmt->execute([$user_id, $module_id]);
$module_stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['avg_score' => 0, 'attempts' => 0];

$module_avg = round((float)$module_stats['avg_score']);
$module_attempts = $module_stats['attempts'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Suspicious Links & Website Safety – CyberAware</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #1d4ed8;
            --success: #15803d;
            --danger: #b91c1c;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-600: #475569;
            --gray-800: #1e293b;
            --shadow-md: 0 4px 16px rgba(0,0,0,0.1);
            --radius: 12px;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--gray-50);
            color: var(--gray-800);
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        .container { max-width: 900px; margin: 0 auto; }

        h2 { font-size: 2rem; font-weight: 700; text-align: center; margin-bottom: 2rem; }

        .email-preview {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            margin: 2rem 0;
        }

        .email-header {
            background: var(--gray-100);
            padding: 1.2rem 1.6rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .email-body {
            padding: 1.8rem;
            font-size: 1rem;
            line-height: 1.65;
        }

        .suspicious-link {
            display: inline-block;
            background: #eff6ff;
            padding: 0.8rem 1.2rem;
            border-radius: 8px;
            margin: 1rem 0;
            font-family: monospace;
            font-size: 1.1rem;
            color: var(--primary);
            border: 1px dashed var(--primary);
        }

        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 1.2rem;
            justify-content: center;
            margin: 2.5rem 0;
        }

        .action-btn {
            padding: 1rem 1.8rem;
            font-size: 1.05rem;
            font-weight: 600;
            border-radius: var(--radius);
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-danger   { background: var(--danger);  color: white; }
        .btn-safe     { background: var(--success); color: white; }
        .btn-neutral  { background: var(--gray-600); color: white; }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
        }

        .feedback, .takeaways {
            padding: 1.4rem;
            border-radius: var(--radius);
            margin: 2rem 0;
        }

        .feedback.success { background: #f0fdf4; border-left: 5px solid var(--success); }
        .feedback.danger  { background: #fef2f2; border-left: 5px solid var(--danger); }

        .takeaways {
            background: white;
            border: 1px solid var(--gray-200);
        }

        .stats {
            text-align: center;
            color: var(--gray-600);
            margin-top: 2rem;
            font-size: 1rem;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Suspicious Links & Website Safety Simulation</h2>

    <div class="email-preview">
        <div class="email-header">
            <strong>From:</strong> updates@micros0ft-support.com<br>
            <strong>To:</strong> <?= htmlspecialchars($user_email) ?><br>
            <strong>Subject:</strong> Your Microsoft Account Needs Attention
        </div>

        <div class="email-body">
            <p>Dear User,</p>

            <p>We detected unusual sign-in activity on your Microsoft account. To keep your account secure, please review and confirm your recent activity.</p>

            <p>Click the link below to verify your account:</p>

            <div class="suspicious-link">
                https://bit.ly/3X9kP2m
            </div>

            <p>If you don't recognize this activity, take action immediately.</p>

            <p>Thank you,<br>
            Microsoft Account Team</p>
        </div>
    </div>

    <p style="text-align:center; font-weight:500; margin:1.5rem 0;">
        What would you do with this link?
    </p>

    <form method="POST" class="action-buttons">
        <button type="submit" name="action" value="click"    class="action-btn btn-danger">Click the Link</button>
        <button type="submit" name="action" value="inspect"  class="action-btn btn-safe">Hover/Inspect URL</button>
        <button type="submit" name="action" value="report"   class="action-btn btn-safe">Report as Suspicious</button>
        <button type="submit" name="action" value="ignore"   class="action-btn btn-neutral">Ignore/Delete</button>
    </form>

    <?php if ($feedback): ?>
    <div class="feedback <?= $feedback_class ?>">
        <?= $feedback ?>
    </div>
    <?php endif; ?>

    <?php if ($show_takeaways): ?>
    <div class="takeaways">
        <h3>Key Red Flags – Suspicious Links</h3>
        <ul style="padding-left:1.5rem; margin:1rem 0;">
            <li>Shortened URLs (bit.ly, tinyurl) that hide the real destination</li>
            <li>Misspelled sender domain (micros0ft instead of microsoft)</li>
            <li>Urgency and threats ("unusual activity", "verify immediately")</li>
            <li>Unexpected requests to click links for account verification</li>
        </ul>
        <p style="margin-top:1.3rem; font-weight:500;">
            <strong>Real impact:</strong> Malicious links can lead to phishing sites that steal credentials,
            download malware, or redirect to scam pages causing financial loss or data breach.
        </p>
    </div>
    <?php endif; ?>

    <div class="stats">
        Your previous attempts on this module: <?= $module_attempts ?> • 
        Average score: <?= $module_avg ?>%
    </div>
</div>

</body>
</html>