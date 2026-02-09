<?php
// trainee/phish-inbox.php - Weekly Phish Inbox Lite
require_once '../config/database.php';
require_once '../includes/functions.php';
require_login();

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

$week_start = date('Y-m-d', strtotime('monday this week'));
$week_label = date('M j', strtotime($week_start)) . ' - ' . date('M j', strtotime($week_start . ' +6 days'));

$flash = null;
$flash_type = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_id'], $_POST['action'])) {
    $message_id = (int)$_POST['message_id'];
    $action = $_POST['action'];

    if (in_array($action, ['safe', 'phish'], true)) {
        $stmt = $pdo->prepare("
            SELECT im.id, im.is_phish
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
                $flash = 'Correct call. Nice spot.';
                $flash_type = 'success';
            } else {
                $flash = 'Not quite. Review the sender and language clues.';
                $flash_type = 'danger';
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
$stmt = $pdo->prepare("
    SELECT im.id, im.subject, im.sender_name, im.sender_email, im.body_html, im.has_attachment,
           im.attachment_name, im.is_phish,
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

$total_messages = count($inbox);
$answered = 0;
$correct = 0;
foreach ($inbox as $item) {
    if ($item['action']) {
        $answered++;
    }
    if ((int)$item['is_correct'] === 1) {
        $correct++;
    }
}

$accuracy = $answered > 0 ? round(($correct / $answered) * 100) : 0;
$goal = 5;
$mission_complete = $answered >= $goal;

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
            --shadow-sm: 0 8px 18px rgba(15, 23, 42, 0.08);
            --shadow-md: 0 18px 40px rgba(15, 23, 42, 0.12);
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
            max-width: 1200px;
            margin: 0 auto;
        }

        .hero {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            align-items: stretch;
            margin-bottom: 28px;
        }

        .hero-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 24px;
            border: 1px solid var(--line);
            box-shadow: var(--shadow-sm);
        }

        .hero-card h1 {
            font-size: clamp(24px, 3vw, 32px);
            margin-bottom: 8px;
        }

        .hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 12px;
        }

        .pill {
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(255, 140, 66, 0.12);
            color: var(--brand-dark);
            font-size: 12px;
            font-weight: 600;
        }

        .stat-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-top: 12px;
        }

        .stat {
            background: #f8fafc;
            border-radius: 12px;
            padding: 12px;
            font-size: 13px;
            color: var(--muted);
        }

        .stat strong {
            display: block;
            font-size: 18px;
            color: var(--ink);
        }

        .flash {
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 18px;
            font-size: 14px;
        }

        .flash.success { background: #ecfeff; color: #0f766e; }
        .flash.danger { background: #fef2f2; color: #b91c1c; }
        .flash.info { background: #eff6ff; color: #1d4ed8; }

        .inbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .mail-card {
            background: var(--surface);
            border-radius: var(--radius-md);
            border: 1px solid var(--line);
            box-shadow: var(--shadow-sm);
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .mail-header {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
        }

        .mail-title {
            font-weight: 700;
            font-size: 16px;
        }

        .mail-from {
            font-size: 13px;
            color: var(--muted);
        }

        .mail-body {
            font-size: 14px;
            color: var(--ink);
        }

        .mail-attach {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 999px;
            background: #fff7ed;
            color: #9a3412;
            font-size: 12px;
            font-weight: 600;
        }

        .mail-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 8px;
        }

        .btn {
            border: none;
            border-radius: 999px;
            padding: 10px 16px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn:hover { transform: translateY(-1px); }

        .btn.safe {
            background: rgba(20, 184, 166, 0.12);
            color: #0f766e;
        }

        .btn.phish {
            background: rgba(239, 68, 68, 0.12);
            color: #b91c1c;
        }

        .btn.locked {
            cursor: default;
            opacity: 0.7;
        }

        .result-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            color: var(--muted);
        }

        .result-badge.correct { color: #0f766e; }
        .result-badge.wrong { color: #b91c1c; }

        .empty {
            text-align: center;
            color: var(--muted);
            padding: 32px;
        }

        .mission {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 14px;
        }

        .mission-bar {
            height: 10px;
            border-radius: 999px;
            background: #e2e8f0;
            overflow: hidden;
        }

        .mission-bar span {
            display: block;
            height: 100%;
            background: linear-gradient(90deg, #FF8C42, #FFB070);
            width: 0;
        }

        .mission-status {
            font-size: 13px;
            color: var(--muted);
        }

        .mission-status strong { color: var(--ink); }
    </style>
</head>
<body>

<?php include 'trainee-sidebar.php'; ?>

<main class="main-content">
    <div class="container">
        <section class="hero">
            <div class="hero-card">
                <h1>Phish Inbox Lite</h1>
                <p>Tag each message as <strong>safe</strong> or <strong>phish</strong>. Weekly inboxes keep your instincts sharp.</p>
                <div class="hero-meta">
                    <span class="pill">Week: <?= htmlspecialchars($week_label) ?></span>
                    <span class="pill"><?= $total_messages ?> messages</span>
                    <span class="pill">Accuracy <?= $accuracy ?>%</span>
                </div>
            </div>
            <div class="hero-card">
                <h3>Weekly micro-mission</h3>
                <p>Tag <?= $goal ?> inbox messages this week to keep your streak alive.</p>
                <div class="mission">
                    <?php $progress_width = $goal > 0 ? min(100, round(($answered / $goal) * 100)) : 0; ?>
                    <div class="mission-bar"><span style="width: <?= $progress_width ?>%"></span></div>
                    <div class="mission-status">
                        <strong><?= $answered ?></strong> of <?= $goal ?> tagged
                        <?php if ($mission_complete): ?>
                            — Mission complete
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
            <div class="flash <?= htmlspecialchars($flash_type) ?>"><?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>

        <?php if (empty($inbox)): ?>
            <div class="empty">No inbox messages available. Ask your admin to seed the inbox.</div>
        <?php else: ?>
            <section class="inbox-grid">
                <?php foreach ($inbox as $item): ?>
                <article class="mail-card">
                    <div class="mail-header">
                        <div>
                            <div class="mail-title"><?= htmlspecialchars($item['subject']) ?></div>
                            <div class="mail-from"><?= htmlspecialchars($item['sender_name'] ?? 'Unknown') ?> • <?= htmlspecialchars($item['sender_email'] ?? '') ?></div>
                        </div>
                        <?php if ($item['action']): ?>
                            <div class="result-badge <?= (int)$item['is_correct'] === 1 ? 'correct' : 'wrong' ?>">
                                <i class="fas <?= (int)$item['is_correct'] === 1 ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
                                <?= (int)$item['is_correct'] === 1 ? 'Correct' : 'Review' ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mail-body"><?= $item['body_html'] ?></div>
                    <?php if ((int)$item['has_attachment'] === 1): ?>
                        <div class="mail-attach"><i class="fas fa-paperclip"></i> <?= htmlspecialchars($item['attachment_name'] ?? 'Attachment') ?></div>
                    <?php endif; ?>

                    <form method="POST" class="mail-actions">
                        <input type="hidden" name="message_id" value="<?= (int)$item['id'] ?>">
                        <button class="btn safe <?= $item['action'] ? 'locked' : '' ?>" type="submit" name="action" value="safe" <?= $item['action'] ? 'disabled' : '' ?>>
                            Mark safe
                        </button>
                        <button class="btn phish <?= $item['action'] ? 'locked' : '' ?>" type="submit" name="action" value="phish" <?= $item['action'] ? 'disabled' : '' ?>>
                            Mark phish
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
