<?php
// modules/attachments.php

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

// Assume module_id = 4 for Dangerous Attachments & Malware (change later)
$module_id = 4;

// Start new training session if none active
$session_id = null;
if (!isset($_SESSION['current_attachment_session'])) {
    $stmt = $pdo->prepare("
        INSERT INTO training_sessions 
        (user_id, module_id, started_at)
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$user_id, $module_id]);
    $session_id = $pdo->lastInsertId();
    $_SESSION['current_attachment_session'] = $session_id;
} else {
    $session_id = $_SESSION['current_attachment_session'];
}

// Handle user action
$feedback = null;
$feedback_class = '';
$show_takeaways = false;
$step_number = 1; // single decision step

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Correct actions: report or delete
    $is_correct = in_array($action, ['report', 'delete']);
    $points = $is_correct ? 10 : -10;

    // Log the action
    $stmt = $pdo->prepare("
        INSERT INTO training_actions 
        (session_id, step_number, action_type, is_correct, points, timestamp)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$session_id, $step_number, $action, $is_correct, $points]);

    // Feedback
    if ($is_correct) {
        $feedback = "Good security decision! You avoided opening a potentially dangerous attachment.";
        $feedback_class = 'success';
    } else {
        $feedback = "This attachment was malicious in the simulation.<br>Opening unknown attachments is one of the most common ways ransomware enters organizations.";
        $feedback_class = 'danger';
    }

    $show_takeaways = true;

    // Mark session as completed
    $stmt = $pdo->prepare("
        UPDATE training_sessions 
        SET completed_at = NOW(), 
            final_score = ?,
            duration_seconds = TIMESTAMPDIFF(SECOND, started_at, NOW())
        WHERE id = ?
    ");
    $stmt->execute([$points, $session_id]);

    // Clean up session
    unset($_SESSION['current_attachment_session']);
}

// Get previous performance stats for this module
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
    <title>Dangerous Attachments & Malware Simulation – CyberAware</title>
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

        .attachment-zone {
            padding: 1.8rem;
            text-align: center;
        }

        .attachment-file {
            display: inline-block;
            background: #fef3c7;
            border: 2px dashed #d97706;
            border-radius: 10px;
            padding: 1.5rem 3rem;
            margin: 1rem 0;
            font-weight: 600;
            color: #92400e;
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
    <h2>Dangerous Attachments & Malware Simulation</h2>

    <div class="email-preview">
        <div class="email-header">
            <strong>From:</strong> finance@partnercorp.co.zm<br>
            <strong>Subject:</strong> Outstanding Invoice #INV-2026010 – Immediate Payment Required
        </div>

        <div class="attachment-zone">
            <div class="attachment-file">
                Invoice_INV-2026010.pdf.exe
            </div>
            <p style="margin-top:1rem; color:#6b7280;">
                Double-click to view your invoice
            </p>
        </div>
    </div>

    <p style="text-align:center; font-weight:500; margin:1.5rem 0;">
        What would you do with this email and attachment?
    </p>

    <form method="POST" class="action-buttons">
        <button type="submit" name="action" value="open"    class="action-btn btn-danger">Open Attachment</button>
        <button type="submit" name="action" value="report"  class="action-btn btn-safe">Report as Suspicious</button>
        <button type="submit" name="action" value="delete"  class="action-btn btn-safe">Delete Email</button>
        <button type="submit" name="action" value="forward" class="action-btn btn-neutral">Forward to IT</button>
    </form>

    <?php if ($feedback): ?>
    <div class="feedback <?= $feedback_class ?>">
        <?= $feedback ?>
    </div>
    <?php endif; ?>

    <?php if ($show_takeaways): ?>
    <div class="takeaways">
        <h3>Key Red Flags – Malware & Attachments</h3>
        <ul style="padding-left:1.5rem; margin:1rem 0;">
            <li>Unexpected or unsolicited attachments</li>
            <li>Double extensions (especially .pdf.exe, .docx.js, etc.)</li>
            <li>Urgency in subject/body ("Immediate Payment Required")</li>
            <li>Sender domain slightly different from expected</li>
        </ul>
        <p style="margin-top:1.3rem; font-weight:500;">
            <strong>Real impact:</strong> One malicious attachment can encrypt all company files (ransomware),
            steal data, or install backdoors leading to months of recovery and huge costs.
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