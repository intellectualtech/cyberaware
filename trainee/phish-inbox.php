<?php
// trainee/phish-inbox.php - Enhanced Interactive Phishing Inbox
require_once '../config/database.php';
require_once '../includes/functions.php';
require_login();

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

$week_start = date('Y-m-d', strtotime('monday this week'));
$week_label = date('M j', strtotime($week_start)) . ' - ' . date('M j', strtotime($week_start . ' +6 days'));

$flash = null;
$flash_type = 'info';
$flash_detail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_id'], $_POST['action'])) {
    $message_id = (int)$_POST['message_id'];
    $action = $_POST['action'];

    if (in_array($action, ['safe', 'phish'], true)) {
        $stmt = $pdo->prepare("
            SELECT im.id, im.is_phish, im.subject, im.sender_email
            FROM inbox_assignments ia
            JOIN inbox_messages im ON im.id = ia.message_id
            WHERE ia.user_id = ? AND ia.week_start = ? AND ia.message_id = ?
        ");
        $stmt->execute([$user_id, $week_start, $message_id]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($message) {
            $is_correct = ((int)$message['is_phish'] === ($action === 'phish' ? 1 : 0)) ? 1 : 0;

            $stmt = $pdo->prepare("
                INSERT INTO inbox_actions (user_id, message_id, week_start, action, is_correct)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    action = VALUES(action),
                    is_correct = VALUES(is_correct),
                    created_at = NOW()
            ");
            $stmt->execute([$user_id, $message_id, $week_start, $action, $is_correct]);

            if ($is_correct) {
                $flash = '<i class="fas fa-check"></i> Correct! Great instincts.';
                $flash_type = 'success';
                if ((int)$message['is_phish'] === 1) {
                    $flash_detail = 'You correctly identified this as phishing. Notice the suspicious sender and urgency language.';
                } else {
                    $flash_detail = 'You correctly identified this as legitimate. The sender domain and content are authentic.';
                }
            } else {
                $flash = '✗ Not quite right. Let\'s review.';
                $flash_type = 'danger';
                if ((int)$message['is_phish'] === 1) {
                    $flash_detail = 'This was actually phishing. Look for red flags: suspicious sender, urgency tactics, and requests for credentials.';
                } else {
                    $flash_detail = 'This was actually legitimate. The sender is verified and the content is authentic.';
                }
            }
        }
    }
}

// Assign inbox messages for the week if needed
$desired_count = 6;
$stmt = $pdo->prepare("SELECT COUNT(*) FROM inbox_assignments WHERE user_id = ? AND week_start = ?");
$stmt->execute([$user_id, $week_start]);
$assigned_count = (int)$stmt->fetchColumn();

if ($assigned_count < $desired_count) {
    $stmt = $pdo->query("SELECT id, is_phish FROM inbox_messages WHERE is_active = 1");
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $phish_ids = [];
    $safe_ids = [];
    foreach ($messages as $msg) {
        if ((int)$msg['is_phish'] === 1) {
            $phish_ids[] = (int)$msg['id'];
        } else {
            $safe_ids[] = (int)$msg['id'];
        }
    }

    shuffle($phish_ids);
    shuffle($safe_ids);

    $phish_target = min((int)ceil($desired_count / 2), count($phish_ids));
    $safe_target = min($desired_count - $phish_target, count($safe_ids));

    $selected = array_slice($phish_ids, 0, $phish_target);
    $selected = array_merge($selected, array_slice($safe_ids, 0, $safe_target));

    if (count($selected) < $desired_count) {
        $remaining = array_values(array_diff(array_merge($phish_ids, $safe_ids), $selected));
        $selected = array_merge($selected, array_slice($remaining, 0, $desired_count - count($selected)));
    }

    if (!empty($selected)) {
        $insert = $pdo->prepare("
            INSERT IGNORE INTO inbox_assignments (user_id, message_id, week_start)
            VALUES (?, ?, ?)
        ");
        foreach ($selected as $message_id) {
            $insert->execute([$user_id, $message_id, $week_start]);
        }
    }
}

// Load inbox messages and actions
// Try to select with difficulty column, fall back if it doesn't exist
try {
    $stmt = $pdo->prepare("
        SELECT im.id, im.subject, im.sender_name, im.sender_email, im.body_html, im.has_attachment,
               im.attachment_name, im.is_phish, COALESCE(im.difficulty, 'easy') as difficulty,
               ia.week_start,
               act.action, act.is_correct
        FROM inbox_assignments ia
        JOIN inbox_messages im ON im.id = ia.message_id
        LEFT JOIN inbox_actions act
            ON act.user_id = ia.user_id
            AND act.message_id = ia.message_id
            AND act.week_start = ia.week_start
        WHERE ia.user_id = ? AND ia.week_start = ?
        ORDER BY im.is_phish DESC, im.id
    ");
    $stmt->execute([$user_id, $week_start]);
    $inbox = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fallback if difficulty column doesn't exist
    $stmt = $pdo->prepare("
        SELECT im.id, im.subject, im.sender_name, im.sender_email, im.body_html, im.has_attachment,
               im.attachment_name, im.is_phish, 'easy' as difficulty,
               ia.week_start,
               act.action, act.is_correct
        FROM inbox_assignments ia
        JOIN inbox_messages im ON im.id = ia.message_id
        LEFT JOIN inbox_actions act
            ON act.user_id = ia.user_id
            AND act.message_id = ia.message_id
            AND act.week_start = ia.week_start
        WHERE ia.user_id = ? AND ia.week_start = ?
        ORDER BY im.is_phish DESC, im.id
    ");
    $stmt->execute([$user_id, $week_start]);
    $inbox = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$total_messages = count($inbox);
$answered = 0;
$correct = 0;
$streak = 0;
$last_correct = false;

foreach ($inbox as $item) {
    if ($item['action']) {
        $answered++;
    }
    if ((int)$item['is_correct'] === 1) {
        $correct++;
        if ($last_correct || $streak === 0) {
            $streak++;
        }
    } else {
        $streak = 0;
    }
    $last_correct = (int)$item['is_correct'] === 1;
}

$accuracy = $answered > 0 ? round(($correct / $answered) * 100) : 0;
$goal = 5;
$mission_complete = $answered >= $goal;

// Get leaderboard for this week
$stmt = $pdo->prepare("
    SELECT u.full_name, COUNT(DISTINCT ia.message_id) as tagged, 
           SUM(CASE WHEN act.is_correct = 1 THEN 1 ELSE 0 END) as correct
    FROM users u
    JOIN inbox_assignments ia ON ia.user_id = u.id
    LEFT JOIN inbox_actions act ON act.user_id = u.id AND act.message_id = ia.message_id AND act.week_start = ia.week_start
    WHERE ia.week_start = ? AND u.role = 'trainee'
    GROUP BY u.id
    ORDER BY correct DESC, tagged DESC
    LIMIT 5
");
$stmt->execute([$week_start]);
$leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Phish Inbox Lite – CyberAware</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --brand: #FF8C42;
            --brand-dark: #E67A2E;
            --surface: #ffffff;
            --ink: #0f172a;
            --muted: #6b7280;
            --line: rgba(15, 23, 42, 0.08);
            --success: #14b8a6;
            --danger: #ef4444;
            --warning: #f59e0b;
            --shadow-sm: 0 8px 18px rgba(15, 23, 42, 0.08);
            --shadow-md: 0 18px 40px rgba(15, 23, 42, 0.12);
            --shadow-lg: 0 25px 50px rgba(15, 23, 42, 0.15);
            --radius-lg: 20px;
            --radius-md: 14px;
            --sidebar-height: 72px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Manrope', 'Segoe UI', sans-serif;
            background: radial-gradient(1200px 700px at 15% -10%, #fff1e4 0%, transparent 60%),
                linear-gradient(130deg, #fff8f1 0%, #ffffff 100%);
            color: var(--ink);
            line-height: 1.6;
            min-height: 100vh;
        }

        h1, h2, h3 {
            font-family: 'Space Grotesk', 'Segoe UI', sans-serif;
        }

        .main-content {
            margin-bottom: var(--sidebar-height);
            min-height: 100vh;
            padding: 32px 24px 80px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 32px;
        }

        .hero-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 28px;
            border: 1px solid var(--line);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }

        .hero-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .hero-card h1 {
            font-size: clamp(24px, 3vw, 32px);
            margin-bottom: 8px;
        }

        .hero-card p {
            color: var(--muted);
            margin-bottom: 16px;
        }

        .hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 12px;
        }

        .pill {
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255, 140, 66, 0.12);
            color: var(--brand-dark);
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .stat-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 12px;
            margin-top: 16px;
        }

        .stat {
            background: #f8fafc;
            border-radius: 12px;
            padding: 14px;
            font-size: 13px;
            color: var(--muted);
            text-align: center;
        }

        .stat strong {
            display: block;
            font-size: 20px;
            color: var(--ink);
            margin-bottom: 4px;
        }

        .flash {
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 24px;
            font-size: 14px;
            border-left: 4px solid;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .flash.success { 
            background: #ecfdf5; 
            color: #065f46;
            border-color: #10b981;
        }

        .flash.danger { 
            background: #fef2f2; 
            color: #991b1b;
            border-color: #ef4444;
        }

        .flash-detail {
            font-size: 13px;
            margin-top: 8px;
            opacity: 0.9;
        }

        .mission {
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin-top: 16px;
        }

        .mission-bar {
            height: 12px;
            border-radius: 999px;
            background: #e2e8f0;
            overflow: hidden;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
        }

        .mission-bar span {
            display: block;
            height: 100%;
            background: linear-gradient(90deg, #FF8C42, #FFB070);
            width: 0;
            transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 0 10px rgba(255, 140, 66, 0.4);
        }

        .mission-status {
            font-size: 13px;
            color: var(--muted);
        }

        .mission-status strong { color: var(--ink); }

        .streak-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #ff6b6b, #ff4757);
            color: white;
            padding: 10px 16px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 14px;
            margin-top: 8px;
        }

        .streak-badge i {
            font-size: 18px;
            animation: flicker 0.6s ease-in-out infinite;
        }

        @keyframes flicker {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }

        .inbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .mail-card {
            background: var(--surface);
            border-radius: var(--radius-md);
            border: 2px solid var(--line);
            box-shadow: var(--shadow-sm);
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .mail-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--brand), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .mail-card:hover {
            border-color: var(--brand);
            box-shadow: var(--shadow-md);
            transform: translateY(-4px);
        }

        .mail-card:hover::before {
            opacity: 1;
        }

        .mail-card.answered {
            opacity: 0.85;
        }

        .mail-header {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
        }

        .mail-title {
            font-weight: 700;
            font-size: 16px;
            line-height: 1.4;
        }

        .mail-from {
            font-size: 13px;
            color: var(--muted);
            margin-top: 4px;
        }

        .mail-difficulty {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .mail-difficulty.easy {
            background: #d1fae5;
            color: #065f46;
        }

        .mail-difficulty.medium {
            background: #fef3c7;
            color: #92400e;
        }

        .mail-difficulty.hard {
            background: #fee2e2;
            color: #991b1b;
        }

        .mail-body {
            font-size: 14px;
            color: var(--ink);
            line-height: 1.5;
            max-height: 80px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .mail-attach {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 8px;
            background: #fff7ed;
            color: #9a3412;
            font-size: 12px;
            font-weight: 600;
            width: fit-content;
        }

        .mail-attach.dangerous {
            background: #fee2e2;
            color: #991b1b;
        }

        .mail-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 8px;
        }

        .btn {
            border: none;
            border-radius: 8px;
            padding: 10px 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 13px;
            flex: 1;
            min-width: 100px;
        }

        .btn:hover:not(:disabled) { 
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .btn.safe {
            background: rgba(20, 184, 166, 0.12);
            color: #0f766e;
            border: 2px solid rgba(20, 184, 166, 0.3);
        }

        .btn.safe:hover:not(:disabled) {
            background: rgba(20, 184, 166, 0.2);
            border-color: #14b8a6;
        }

        .btn.phish {
            background: rgba(239, 68, 68, 0.12);
            color: #b91c1c;
            border: 2px solid rgba(239, 68, 68, 0.3);
        }

        .btn.phish:hover:not(:disabled) {
            background: rgba(239, 68, 68, 0.2);
            border-color: #ef4444;
        }

        .btn:disabled {
            cursor: default;
            opacity: 0.6;
        }

        .result-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            padding: 6px 10px;
            border-radius: 6px;
        }

        .result-badge.correct { 
            background: #d1fae5;
            color: #065f46;
        }

        .result-badge.wrong { 
            background: #fee2e2;
            color: #991b1b;
        }

        .leaderboard-section {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 24px;
            border: 1px solid var(--line);
            box-shadow: var(--shadow-sm);
            margin-bottom: 32px;
        }

        .leaderboard-section h3 {
            margin-bottom: 16px;
            font-size: 18px;
        }

        .leaderboard-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .leaderboard-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: #f8fafc;
            border-radius: 10px;
            transition: all 0.2s ease;
        }

        .leaderboard-item:hover {
            background: #f1f5f9;
        }

        .leaderboard-rank {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--brand);
            color: white;
            font-weight: 700;
            font-size: 14px;
            margin-right: 12px;
            flex-shrink: 0;
        }

        .leaderboard-name {
            flex: 1;
            font-weight: 600;
            color: var(--ink);
        }

        .leaderboard-score {
            display: flex;
            gap: 16px;
            font-size: 13px;
            color: var(--muted);
        }

        .leaderboard-score strong {
            color: var(--ink);
        }

        .empty {
            text-align: center;
            color: var(--muted);
            padding: 48px 32px;
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--line);
        }

        .empty i {
            font-size: 48px;
            opacity: 0.3;
            margin-bottom: 16px;
            display: block;
        }

        @media (max-width: 768px) {
            .header-section {
                grid-template-columns: 1fr;
            }

            .inbox-grid {
                grid-template-columns: 1fr;
            }

            .mail-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<?php include 'trainee-sidebar.php'; ?>

<main class="main-content">
    <div class="container">
        <section class="header-section">
            <div class="hero-card">
                <h1>🎣 Phish Inbox</h1>
                <p>Tag each message as <strong>safe</strong> or <strong>phish</strong>. Weekly challenges keep your instincts sharp.</p>
                <div class="hero-meta">
                    <span class="pill"><i class="fas fa-calendar"></i> Week: <?= htmlspecialchars($week_label) ?></span>
                    <span class="pill"><i class="fas fa-envelope"></i> <?= $total_messages ?> messages</span>
                    <span class="pill"><i class="fas fa-bullseye"></i> <?= $accuracy ?>% accuracy</span>
                </div>
                <?php if ($streak > 0): ?>
                    <div class="streak-badge">
                        <i class="fas fa-fire"></i> <?= $streak ?> correct in a row!
                    </div>
                <?php endif; ?>
            </div>

            <div class="hero-card">
                <h3>📋 Weekly Mission</h3>
                <p>Tag <?= $goal ?> inbox messages this week to complete your mission.</p>
                <div class="mission">
                    <?php $progress_width = $goal > 0 ? min(100, round(($answered / $goal) * 100)) : 0; ?>
                    <div class="mission-bar"><span style="width: <?= $progress_width ?>%"></span></div>
                    <div class="mission-status">
                        <strong><?= $answered ?></strong> of <?= $goal ?> tagged
                        <?php if ($mission_complete): ?>
                            <span style="color: #10b981; margin-left: 8px;"><i class="fas fa-check"></i> Mission complete!</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="stat-row">
                    <div class="stat"><strong><?= $answered ?></strong>Tagged</div>
                    <div class="stat"><strong><?= $correct ?></strong>Correct</div>
                    <div class="stat"><strong><?= $total_messages - $answered ?></strong>Remaining</div>
                </div>
            </div>
        </section>

        <?php if ($flash): ?>
            <div class="flash <?= htmlspecialchars($flash_type) ?>">
                <strong><?= htmlspecialchars($flash) ?></strong>
                <?php if ($flash_detail): ?>
                    <div class="flash-detail"><?= htmlspecialchars($flash_detail) ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($leaderboard)): ?>
            <section class="leaderboard-section">
                <h3><i class="fas fa-trophy"></i> Weekly Leaderboard</h3>
                <div class="leaderboard-list">
                    <?php foreach ($leaderboard as $index => $leader): ?>
                        <div class="leaderboard-item">
                            <div style="display: flex; align-items: center; flex: 1;">
                                <div class="leaderboard-rank"><?= $index + 1 ?></div>
                                <div class="leaderboard-name"><?= htmlspecialchars($leader['full_name'] ?? 'Anonymous') ?></div>
                            </div>
                            <div class="leaderboard-score">
                                <span><strong><?= (int)$leader['correct'] ?></strong> correct</span>
                                <span><strong><?= (int)$leader['tagged'] ?></strong> tagged</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if (empty($inbox)): ?>
            <div class="empty">
                <i class="fas fa-inbox"></i>
                <p><strong>No inbox messages available</strong></p>
                <p style="font-size: 13px; margin-top: 8px;">Ask your admin to seed the inbox with phishing scenarios.</p>
            </div>
        <?php else: ?>
            <section class="inbox-grid">
                <?php foreach ($inbox as $item): ?>
                <article class="mail-card <?= $item['action'] ? 'answered' : '' ?>">
                    <div class="mail-header">
                        <div style="flex: 1;">
                            <div class="mail-title"><?= htmlspecialchars($item['subject']) ?></div>
                            <div class="mail-from">
                                <strong><?= htmlspecialchars($item['sender_name'] ?? 'Unknown') ?></strong>
                                <br>
                                <span style="font-size: 12px;"><?= htmlspecialchars($item['sender_email'] ?? '') ?></span>
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 8px; align-items: flex-end;">
                            <?php if ($item['action']): ?>
                                <div class="result-badge <?= (int)$item['is_correct'] === 1 ? 'correct' : 'wrong' ?>">
                                    <i class="fas <?= (int)$item['is_correct'] === 1 ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
                                    <?= (int)$item['is_correct'] === 1 ? 'Correct' : 'Review' ?>
                                </div>
                            <?php endif; ?>
                            <div class="mail-difficulty <?= strtolower($item['difficulty'] ?? 'easy') ?>">
                                <?= htmlspecialchars($item['difficulty'] ?? 'Easy') ?>
                            </div>
                        </div>
                    </div>

                    <div class="mail-body"><?= htmlspecialchars(strip_tags($item['body_html'])) ?></div>

                    <?php if ((int)$item['has_attachment'] === 1): ?>
                        <div class="mail-attach <?= in_array(strtolower(pathinfo($item['attachment_name'], PATHINFO_EXTENSION)), ['exe', 'bat', 'scr', 'vbs', 'com']) ? 'dangerous' : '' ?>">
                            <i class="fas fa-paperclip"></i> 
                            <?= htmlspecialchars($item['attachment_name'] ?? 'Attachment') ?>
                            <?php if (in_array(strtolower(pathinfo($item['attachment_name'], PATHINFO_EXTENSION)), ['exe', 'bat', 'scr', 'vbs', 'com'])): ?>
                                <span style="margin-left: 4px;"><i class="fas fa-exclamation-triangle"></i></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="mail-actions">
                        <input type="hidden" name="message_id" value="<?= (int)$item['id'] ?>">
                        <button class="btn safe <?= $item['action'] ? 'locked' : '' ?>" type="submit" name="action" value="safe" <?= $item['action'] ? 'disabled' : '' ?>>
                            <i class="fas fa-check"></i> Safe
                        </button>
                        <button class="btn phish <?= $item['action'] ? 'locked' : '' ?>" type="submit" name="action" value="phish" <?= $item['action'] ? 'disabled' : '' ?>>
                            <i class="fas fa-exclamation-triangle"></i> Phish
                        </button>
                    </form>
                </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </div>
</main>

</body>
</html>
