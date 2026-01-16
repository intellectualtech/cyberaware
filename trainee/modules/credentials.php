<?php
// modules/credentials.php

require_once '../../config/database.php';
require_once '../../includes/functions.php';

require_login(); // from your functions.php

// Get DB connection
$pdo = getDBConnection();

// Get current user
$user = get_current_user();
if (!$user) {
    session_destroy();
    header("Location: ../../pages/login.php?error=user_not_found");
    exit;
}

$user_id = $user['id'];

// Assume module_id = 2 for credential harvesting (change later when dynamic)
$module_id = 2;

// Start a new training session if not already active
$session_id = null;
if (!isset($_SESSION['current_credential_session'])) {
    $stmt = $pdo->prepare("
        INSERT INTO training_sessions 
        (user_id, module_id, started_at)
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$user_id, $module_id]);
    $session_id = $pdo->lastInsertId();
    $_SESSION['current_credential_session'] = $session_id;
} else {
    $session_id = $_SESSION['current_credential_session'];
}

// Handle user decision
$feedback = null;
$feedback_class = '';
$show_explanation = false;
$step_number = 1; // single decision step

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $decision = $_POST['decision'] ?? '';

    // Correct = 'no' (do NOT enter credentials)
    $is_correct = ($decision === 'no');
    $points = $is_correct ? 10 : -10;

    // Log the action
    $stmt = $pdo->prepare("
        INSERT INTO training_actions 
        (session_id, step_number, action_type, is_correct, points, timestamp)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$session_id, $step_number, $decision, $is_correct, $points]);

    // Feedback
    if ($is_correct) {
        $feedback = "Correct decision! You should <strong>never</strong> enter credentials on a suspicious or unexpected login page.";
        $feedback_class = 'success';
    } else {
        $feedback = "This was a simulated phishing login page.<br>Entering credentials here would give attackers access to your real account.";
        $feedback_class = 'danger';
    }

    $show_explanation = true;

    // Mark session as completed
    $stmt = $pdo->prepare("
        UPDATE training_sessions 
        SET completed_at = NOW(), 
            final_score = ?,
            duration_seconds = TIMESTAMPDIFF(SECOND, started_at, NOW())
        WHERE id = ?
    ");
    $stmt->execute([$points, $session_id]);

    // Clean up session to prevent duplicate submissions
    unset($_SESSION['current_credential_session']);
}

// Get previous stats for this module (for display)
$stmt = $pdo->prepare("
    SELECT AVG(final_score) as avg_score, COUNT(*) as attempts
    FROM training_sessions
    WHERE user_id = ? AND module_id = ?
");
$stmt->execute([$user_id, $module_id]);
$module_stats = $stmt->fetch() ?: ['avg_score' => null, 'attempts' => 0];

$module_avg = $module_stats['avg_score'] !== null ? round((float)$module_stats['avg_score']) : 0;
$module_attempts = $module_stats['attempts'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Credential Harvesting Awareness – CyberAware</title>
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
            line-height: 1.6;
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        .container { max-width: 900px; margin: 0 auto; }

        h2 { font-size: 2rem; font-weight: 700; text-align: center; margin-bottom: 2rem; }

        .warning-box {
            background: #fffbeb;
            border: 1px solid #f59e0b;
            border-radius: var(--radius);
            padding: 1.5rem;
            margin: 1.5rem 0;
            text-align: center;
            font-weight: 500;
            color: #92400e;
        }

        .login-simulation {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            padding: 2.5rem;
            max-width: 480px;
            margin: 2rem auto;
            border-top: 4px solid var(--primary);
        }

        .login-title {
            text-align: center;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.8rem;
            color: #111827;
        }

        .form-group {
            margin-bottom: 1.4rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--gray-700);
        }

        .form-group input {
            width: 100%;
            padding: 0.9rem;
            border: 1px solid var(--gray-300);
            border-radius: 6px;
            font-size: 1rem;
        }

        .fake-https {
            color: #16a34a;
            font-size: 0.9rem;
            margin: 0.5rem 0;
            text-align: center;
        }

        .decision-buttons {
            display: flex;
            gap: 1.2rem;
            justify-content: center;
            margin: 2.5rem 0;
        }

        .decision-btn {
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: var(--radius);
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-yes { background: var(--danger); color: white; }
        .btn-no  { background: var(--success); color: white; }

        .decision-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.15);
        }

        .feedback {
            padding: 1.4rem;
            border-radius: var(--radius);
            margin: 2rem 0;
            font-size: 1.05rem;
        }

        .feedback.success { background: #f0fdf4; color: #14532d; border-left: 5px solid var(--success); }
        .feedback.danger  { background: #fef2f2; color: #7f1d1d; border-left: 5px solid var(--danger); }

        .explanation {
            background: white;
            padding: 1.8rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
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
    <h2>Credential Harvesting Awareness</h2>

    <div class="warning-box">
        You just received an unexpected email asking you to log in to update your account.
        Here's what the login page looks like...
    </div>

    <div class="login-simulation">
        <div class="login-title">Login to Your Account</div>
        
        <div class="form-group">
            <label>Username or Email</label>
            <input type="text" placeholder="your.name@company.com" disabled>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" placeholder="••••••••••••" disabled>
        </div>

        <div class="fake-https">🔒 https://login-companny.co.zm</div>

        <div style="text-align:center; margin-top:1.5rem; color:#6b7280; font-size:0.9rem;">
            Forgot password? | Create account
        </div>
    </div>

    <p style="text-align:center; font-weight:500; margin:2rem 0 1.5rem;">
        Would you enter your real credentials on this page?
    </p>

    <form method="POST" class="decision-buttons">
        <button type="submit" name="decision" value="yes" class="decision-btn btn-yes">Yes, enter credentials</button>
        <button type="submit" name="decision" value="no" class="decision-btn btn-no">No, this looks suspicious</button>
    </form>

    <?php if ($feedback): ?>
    <div class="feedback <?= $feedback_class ?>">
        <?= $feedback ?>
    </div>
    <?php endif; ?>

    <?php if ($show_explanation): ?>
    <div class="explanation">
        <h3>Why This Page Is Suspicious – Red Flags</h3>
        <ul style="margin:1rem 0; padding-left:1.5rem;">
            <li>Unexpected login prompt (you weren't trying to log in)</li>
            <li>Slightly wrong domain (companny.co.zm instead of company.co.zm)</li>
            <li>HTTPS alone is not enough – always check the full URL</li>
            <li>Generic design that mimics real portals</li>
        </ul>
        <p style="margin-top:1.2rem; font-weight:500;">
            <strong>Real impact:</strong> Stolen credentials can lead to full account takeover, data theft,
            ransomware deployment and serious financial/business damage.
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