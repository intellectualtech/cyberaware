<?php
// modules/links.php - Chapter-Based Suspicious Links Simulation (Improved Navigation UX, No Color Changes)
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_login();
$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

// Load user data
$stmt = $pdo->prepare("SELECT id, username, full_name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    die("Error: User not found. Please contact administrator.");
}
$user_email = $user['email'] ?? ($user['username'] . '@company.com');

$module_id = 5; // LINK_001

// Load module title & description
$stmt = $pdo->prepare("SELECT title, description FROM training_modules WHERE id = ?");
$stmt->execute([$module_id]);
$module = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$module) {
    die("Module not found.");
}
$module_title = $module['title'] ?? 'Suspicious Links & Website Safety';
$module_desc = $module['description'] ?? 'Learn to identify and handle dangerous links safely';

// Load all chapters
$stmt = $pdo->prepare("
    SELECT id, chapter_number, title, description, content_html, video_path
    FROM module_chapters
    WHERE module_id = ?
    ORDER BY chapter_number
");
$stmt->execute([$module_id]);
$all_chapters = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($all_chapters)) {
    die("No chapters found for this module. Contact administrator.");
}
$total_chapters = count($all_chapters);

// Current chapter
$current_chapter_num = max(1, min($total_chapters, (int)($_GET['chapter'] ?? 1)));
$current_chapter = null;
foreach ($all_chapters as $ch) {
    if ($ch['chapter_number'] == $current_chapter_num) {
        $current_chapter = $ch;
        break;
    }
}
if (!$current_chapter) {
    die("Chapter not found.");
}
$chapter_id = $current_chapter['id'];
$video_path = $current_chapter['video_path'] ?? null;
$chapter_content = $current_chapter['content_html'] ?? '';

// Progress for this chapter
$stmt = $pdo->prepare("
    SELECT completed_at, watched_seconds
    FROM user_chapter_progress
    WHERE user_id = ? AND chapter_id = ?
");
$stmt->execute([$user_id, $chapter_id]);
$progress = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$progress) {
    // Insert new progress
    $stmt = $pdo->prepare("
        INSERT INTO user_chapter_progress (user_id, chapter_id, watched_seconds, completed_at)
        VALUES (?, ?, 0, NULL)
    ");
    $stmt->execute([$user_id, $chapter_id]);
    $is_video_complete = false;
    $is_activity_complete = false;
} else {
    $is_video_complete = ($progress['watched_seconds'] == -1);
    $is_activity_complete = ($progress['completed_at'] !== null);
}

// AJAX mark video complete
if (isset($_POST['mark_video_complete'])) {
    $stmt = $pdo->prepare("
        UPDATE user_chapter_progress
        SET watched_seconds = -1
        WHERE user_id = ? AND chapter_id = ?
    ");
    $stmt->execute([$user_id, $chapter_id]);
    echo json_encode(['success' => true]);
    exit;
}

// Handle user action
$feedback = null;
$feedback_class = '';
$show_takeaways = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $is_correct = in_array($action, ['inspect', 'report']);
    $points = $is_correct ? 15 : -10;

    // Feedback
    if ($is_correct) {
        $feedback = "Smart choice! Always inspect links or report them — you avoided a potential threat.";
        $feedback_class = 'success';
    } else {
        $feedback = "This link was malicious in the simulation.<br>Clicking or ignoring suspicious links can lead to phishing sites, malware, or data theft.";
        $feedback_class = 'danger';
    }

    // Create session if not exists (one session per module attempt)
    if (!isset($_SESSION['current_links_session'])) {
        $stmt = $pdo->prepare("
            INSERT INTO training_sessions (user_id, module_id, started_at, final_score)
            VALUES (?, ?, NOW(), 0)
        ");
        $stmt->execute([$user_id, $module_id]);
        $session_id = $pdo->lastInsertId();
        $_SESSION['current_links_session'] = $session_id;
    } else {
        $session_id = $_SESSION['current_links_session'];
    }

    // Log action
    $stmt = $pdo->prepare("
        INSERT INTO training_actions
        (session_id, step_number, action_type, action_value, is_correct, points, timestamp)
        VALUES (?, ?, 'link_action', ?, ?, ?, NOW())
    ");
    $stmt->execute([$session_id, $current_chapter_num, $action, $is_correct ? 1 : 0, $points]);

    // Update session score
    $stmt = $pdo->prepare("
        UPDATE training_sessions
        SET final_score = final_score + ?
        WHERE id = ?
    ");
    $stmt->execute([$points, $session_id]);

    // Only mark chapter complete on correct action
    if ($is_correct) {
        $stmt = $pdo->prepare("
            UPDATE user_chapter_progress
            SET completed_at = NOW()
            WHERE user_id = ? AND chapter_id = ?
        ");
        $stmt->execute([$user_id, $chapter_id]);
        $is_activity_complete = true;
    }

    $show_takeaways = $is_activity_complete;
}

// Auto-complete module session when finishing last chapter correctly
if (isset($_SESSION['current_links_session'])) {
    $session_id = $_SESSION['current_links_session'];
    $stmt = $pdo->prepare("SELECT completed_at FROM training_sessions WHERE id = ?");
    $stmt->execute([$session_id]);
    $session_completed = $stmt->fetchColumn();

    $next_enabled = $is_video_complete && $is_activity_complete;
    if ($session_completed === null && $current_chapter_num === $total_chapters && $next_enabled) {
        $stmt = $pdo->prepare("
            UPDATE training_sessions
            SET completed_at = NOW(),
                duration_seconds = TIMESTAMPDIFF(SECOND, started_at, NOW()),
                passed = 1
            WHERE id = ?
        ");
        $stmt->execute([$session_id]);
        unset($_SESSION['current_links_session']);
    }
} else {
    $next_enabled = $is_video_complete && $is_activity_complete;
}

// Module progress
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM user_chapter_progress ucp
    JOIN module_chapters mc ON ucp.chapter_id = mc.id
    WHERE ucp.user_id = ? AND mc.module_id = ? AND ucp.watched_seconds = -1 AND ucp.completed_at IS NOT NULL
");
$stmt->execute([$user_id, $module_id]);
$completed_chapters = (int)$stmt->fetchColumn();
$module_progress = $total_chapters > 0 ? round(($completed_chapters / $total_chapters) * 100) : 0;

// Stats for this module
$stmt = $pdo->prepare("
    SELECT
        COUNT(*) as attempts,
        AVG(final_score) as avg_score,
        SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) as passed_count
    FROM training_sessions
    WHERE user_id = ? AND module_id = ?
");
$stmt->execute([$user_id, $module_id]);
$module_stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['attempts' => 0, 'avg_score' => 0, 'passed_count' => 0];
$module_attempts = (int)$module_stats['attempts'];
$module_passed = (int)$module_stats['passed_count'];
$module_avg = round((float)$module_stats['avg_score']);

// Link scenarios per chapter
$scenarios = [
    1 => [
        'sender' => 'updates@micros0ft-support.com',
        'subject' => 'Your Microsoft Account Needs Attention',
        'link' => 'https://bit.ly/3X9kP2m',
        'link_type' => 'Shortened URL (bit.ly)',
        'red_flag' => 'Shortened URLs hide the real destination',
    ],
    2 => [
        'sender' => 'paypal-security@paypaI.com',
        'subject' => 'Unusual Activity Detected – Verify Now',
        'link' => 'https://www.paypal-security.com/verify',
        'link_type' => 'Misspelled Domain',
        'red_flag' => 'Domain misspelling: "paypaI" (with capital I instead of l)',
    ],
    3 => [
        'sender' => 'hr@company-internal.co',
        'subject' => 'Mandatory Employee Survey',
        'link' => 'https://company.com/survey?id=update2026&token=secure',
        'link_type' => 'Suspicious Parameters',
        'red_flag' => 'Unexpected parameters in an otherwise legitimate-looking domain',
    ],
];
$scenario = $scenarios[$current_chapter_num] ?? $scenarios[1];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($module_title) ?> - Chapter <?= $current_chapter_num ?> – CyberAware</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --cyber-yellow: #FFD60A;
            --cyber-gold: #FFC300;
            --dark-navy: #0F1419;
            --dark-slate: #1A1E2E;
            --primary: #FFD60A;
            --primary-dark: #FFC300;
            --primary-light: #FFF8DC;
            --primary-lighter: #FFF8DC;
            --success: #00A65A;
            --warning: #F39C12;
            --danger: #DD4B39;
            --dark: #2C2C2C;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --white: #FFFFFF;
            --sidebar-width: 260px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 16px rgba(0,0,0,0.1);
            --radius: 14px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--gray-100);
            color: var(--dark);
            line-height: 1.6;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            padding: 2rem;
        }

        @media (max-width: 992px) {
            .main-content { margin-left: 0; padding-bottom: 80px; }
        }

        .header {
            background: var(--white);
            border-bottom: 1px solid var(--gray-200);
            padding: 20px 32px;
            position: sticky;
            top: 0;
            z-index: 90;
        }

        .page-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--dark);
        }

        .page-subtitle {
            font-size: 13px;
            color: var(--gray-600);
            margin-top: 2px;
        }

        .container { padding: 32px; }

        .module-progress {
            margin-bottom: 2rem;
            background: var(--white);
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            border-left: 5px solid var(--cyber-yellow);
        }

        .progress-bar {
            height: 10px;
            background: var(--gray-200);
            border-radius: 5px;
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .progress-fill {
            height: 100%;
            background: var(--primary);
            width: <?= $module_progress ?>%;
            transition: width 0.8s ease;
        }

        .video-intro {
            margin-bottom: 2.5rem;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }

        .video-intro video {
            width: 100%;
            max-height: 500px;
            object-fit: cover;
        }

        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            text-align: center;
        }

        .stat-card .stat-label {
            font-size: 0.9rem;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .simulation-card {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 32px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 32px;
        }

        .simulation-card h3 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 24px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .simulation-card h3 i {
            color: var(--primary);
            font-size: 22px;
        }

        .email-preview {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: 10px;
            overflow: hidden;
            margin: 20px 0;
            box-shadow: var(--shadow-sm);
        }

        .email-header {
            background: var(--gray-50);
            padding: 16px 20px;
            border-bottom: 1px solid var(--gray-200);
            font-size: 14px;
        }

        .email-header strong { color: var(--dark); }

        .email-body {
            padding: 24px;
            font-size: 15px;
            line-height: 1.7;
        }

        .suspicious-link {
            display: inline-block;
            background: var(--primary-lighter);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 20px 0;
            font-family: monospace;
            font-size: 16px;
            color: var(--primary-dark);
            border: 2px dashed var(--primary);
            font-weight: 600;
        }

        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            justify-content: center;
            margin: 32px 0;
        }

        .action-btn {
            padding: 14px 28px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            min-width: 200px;
        }

        .btn-danger   { background: var(--danger);  color: white; }
        .btn-safe     { background: var(--success); color: white; }
        .btn-neutral  { background: var(--gray-600); color: white; }

        .action-btn:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .feedback {
            padding: 20px;
            border-radius: 10px;
            margin: 32px 0;
            font-size: 16px;
            line-height: 1.6;
            border-left: 6px solid;
        }

        .feedback.success { background: #E8F5E9; border-color: var(--success); color: var(--success); }
        .feedback.danger  { background: #FFEBEE; border-color: var(--danger);  color: var(--danger); }

        .dynamic-content {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 28px;
            box-shadow: var(--shadow-sm);
        }

        .dynamic-content h3 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dynamic-content h3 i { color: var(--primary); }

        .dynamic-content ul {
            padding-left: 24px;
            margin: 16px 0;
        }

        .dynamic-content li {
            margin: 12px 0;
            padding-left: 8px;
        }

        .impact {
            margin-top: 24px;
            padding: 20px;
            background: var(--primary-lighter);
            border-radius: 10px;
            border-left: 4px solid var(--primary);
            font-weight: 500;
            font-size: 15px;
        }

        .chapter-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 3rem 0;
            padding: 1.5rem;
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
        }

        .chapter-info {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .nav-btn {
            padding: 0.9rem 1.8rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            opacity: 0.5;
            pointer-events: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-btn.enabled {
            opacity: 1;
            pointer-events: auto;
        }

        .nav-btn.enabled:hover { background: var(--primary-dark); }

        .nav-btn.prev { background: var(--gray-600); }
        .nav-btn.prev.enabled:hover { background: var(--gray-700); }
    </style>
</head>
<body>
<?php include '../trainee-sidebar.php'; ?>

<main class="main-content">
    <header class="header">
        <div>
            <h2 class="page-title"><?= htmlspecialchars($module_title) ?></h2>
            <p class="page-subtitle"><?= htmlspecialchars($module_desc) ?></p>
        </div>
    </header>

    <div class="container">
        <!-- Module Progress -->
        <div class="module-progress">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                <strong>Module Progress</strong>
                <span><?= $completed_chapters ?> / <?= $total_chapters ?> Chapters (<?= $module_progress ?>%)</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
        </div>

        <!-- Stats Bar -->
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-label">Your Attempts</div>
                <div class="stat-value"><?= $module_attempts ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Times Passed</div>
                <div class="stat-value"><?= $module_passed ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Average Score</div>
                <div class="stat-value"><?= $module_avg ?></div>
            </div>
        </div>

        <!-- Chapter Title -->
        <header class="header">
            <div>
                <h2 class="page-title">Chapter <?= $current_chapter_num ?>: <?= htmlspecialchars($current_chapter['title']) ?></h2>
                <?php if ($current_chapter['description']): ?>
                <p class="page-subtitle"><?= htmlspecialchars($current_chapter['description']) ?></p>
                <?php endif; ?>
            </div>
        </header>

        <!-- Chapter Video -->
        <?php if ($video_path): ?>
        <div class="video-intro">
            <video id="chapterVideo" controls controlsList="nodownload">
                <source src="../../<?= htmlspecialchars($video_path) ?>" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </div>
        <script>
            const video = document.getElementById('chapterVideo');
            let videoWatched = <?= $is_video_complete ? 'true' : 'false' ?>;
            const activityComplete = <?= $is_activity_complete ? 'true' : 'false' ?>;

            function markVideoComplete() {
                fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'mark_video_complete=1'
                }).then(response => response.json())
                .then(data => {
                    if (data.success) {
                        videoWatched = true;
                        updateForwardButton();
                        location.reload(); // Ensure server-side completion logic runs
                    }
                });
            }

            video.addEventListener('timeupdate', function() {
                if (!videoWatched && video.currentTime >= video.duration * 0.95) {
                    markVideoComplete();
                }
            });

            video.addEventListener('ended', function() {
                if (!videoWatched) {
                    markVideoComplete();
                }
            });

            function updateForwardButton() {
                const forwardBtn = document.querySelector('.chapter-nav .nav-btn:not(.prev)');
                if (forwardBtn && videoWatched && activityComplete) {
                    forwardBtn.classList.add('enabled');
                }
            }

            updateForwardButton();
        </script>
        <?php endif; ?>

        <!-- Chapter Lesson Content -->
        <?php if ($chapter_content): ?>
        <div class="dynamic-content">
            <?= $chapter_content ?>
        </div>
        <?php endif; ?>

        <!-- Simulation Scenario -->
        <div class="simulation-card">
            <h3><i class="fas fa-link"></i> Simulation Scenario</h3>

            <div class="email-preview">
                <div class="email-header">
                    <strong>From:</strong> <?= htmlspecialchars($scenario['sender']) ?><br>
                    <strong>To:</strong> <?= htmlspecialchars($user_email) ?><br>
                    <strong>Subject:</strong> <?= htmlspecialchars($scenario['subject']) ?>
                </div>

                <div class="email-body">
                    <p>Dear User,</p>
                    <p>We detected unusual activity on your account. To keep it secure, please review and confirm immediately.</p>
                    <p>Click the link below to verify:</p>
                    <div class="suspicious-link">
                        <?= htmlspecialchars($scenario['link']) ?>
                    </div>
                    <p>If you don't recognize this, act now.</p>
                    <p>Thank you,<br>Account Security Team</p>
                </div>
            </div>

            <p style="text-align:center; font-weight:600; font-size:18px; margin:32px 0;">
                What would you do with this link?
            </p>

            <form method="POST" class="action-buttons">
                <button type="submit" name="action" value="click"   class="action-btn btn-danger">Click the Link</button>
                <button type="submit" name="action" value="inspect" class="action-btn btn-safe">Hover/Inspect URL</button>
                <button type="submit" name="action" value="report"  class="action-btn btn-safe">Report as Suspicious</button>
                <button type="submit" name="action" value="ignore"  class="action-btn btn-neutral">Ignore/Delete</button>
            </form>
        </div>

        <!-- Feedback -->
        <?php if ($feedback): ?>
        <div class="feedback <?= $feedback_class ?>">
            <?= $feedback ?>
        </div>
        <?php endif; ?>

        <!-- Key Takeaways (shown only after correct action) -->
        <?php if ($show_takeaways): ?>
        <div class="dynamic-content">
            <h3><i class="fas fa-lightbulb"></i> Key Learning Points – Red Flags</h3>
            <ul>
                <li><strong>Specific red flag in this scenario:</strong> <?= htmlspecialchars($scenario['red_flag']) ?></li>
                <li>Always hover over links to reveal the true destination before clicking</li>
                <li>Be wary of shortened URLs, misspelled domains, and unexpected urgency</li>
                <li>Legitimate organizations rarely send unsolicited verification links</li>
                <li>Best actions: <strong>Inspect/Hover</strong> or <strong>Report to IT</strong></li>
            </ul>
            <div class="impact">
                <strong>Real-world business impact:</strong><br>
                Malicious links can lead to phishing sites that steal credentials, download malware, or redirect to scam pages causing financial loss or data breach.
            </div>
        </div>
        <?php endif; ?>

        <!-- Chapter Navigation - Improved: Always show forward button (Next or Complete Module), disabled until ready -->
        <div class="chapter-nav">
            <div class="chapter-info">
                Chapter <?= $current_chapter_num ?> of <?= $total_chapters ?>
            </div>
            <div>
                <?php if ($current_chapter_num > 1): ?>
                <a href="?chapter=<?= $current_chapter_num - 1 ?>" class="nav-btn prev enabled">
                    <i class="fas fa-arrow-left"></i> Previous Chapter
                </a>
                <?php endif; ?>

                <?php if ($current_chapter_num < $total_chapters): ?>
                <a href="?chapter=<?= $current_chapter_num + 1 ?>"
                   class="nav-btn <?= $next_enabled ? 'enabled' : '' ?>">
                    Next Chapter <i class="fas fa-arrow-right"></i>
                </a>
                <?php else: ?>
                <a href="../dashboard.php"
                   class="nav-btn <?= $next_enabled ? 'enabled' : '' ?>">
                    Complete Module <i class="fas fa-check-circle"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
</body>
</html>