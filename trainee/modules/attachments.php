<?php
// modules/attachments.php - Chapter-Based Malware & Attachments Simulation (Improved Navigation UX)
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

$module_id = 4; // MALWARE_001

// Load module title & description
$stmt = $pdo->prepare("SELECT title, description FROM training_modules WHERE id = ?");
$stmt->execute([$module_id]);
$module = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$module) {
    die("Module not found.");
}
$module_title = $module['title'] ?? 'Dangerous Attachments & Malware Simulation';
$module_desc = $module['description'] ?? 'Learn to identify and avoid malicious file attachments';

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

// Handle user action on email
$feedback = null;
$feedback_class = '';
$show_takeaways = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $is_correct = in_array($action, ['report', 'delete']);
    $points = $is_correct ? 15 : -10;

    // Feedback
    if ($is_correct) {
        $feedback = "Excellent decision! Reporting or deleting suspicious attachments prevents malware infection.";
        $feedback_class = 'success';
    } else {
        $feedback = "This attachment was malicious.<br>Never open or forward unexpected executable files, macro documents, or suspicious archives.";
        $feedback_class = 'danger';
    }

    // Create session if not exists (one session per module attempt)
    if (!isset($_SESSION['current_attachment_session'])) {
        $stmt = $pdo->prepare("
            INSERT INTO training_sessions (user_id, module_id, started_at, final_score)
            VALUES (?, ?, NOW(), 0)
        ");
        $stmt->execute([$user_id, $module_id]);
        $session_id = $pdo->lastInsertId();
        $_SESSION['current_attachment_session'] = $session_id;
    } else {
        $session_id = $_SESSION['current_attachment_session'];
    }

    // Log action
    $stmt = $pdo->prepare("
        INSERT INTO training_actions
        (session_id, step_number, action_type, action_value, is_correct, points, timestamp)
        VALUES (?, ?, 'email_action', ?, ?, ?, NOW())
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
if (isset($_SESSION['current_attachment_session'])) {
    $session_id = $_SESSION['current_attachment_session'];
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
        unset($_SESSION['current_attachment_session']);
    }
} else {
    $next_enabled = $is_video_complete && $is_activity_complete;
}

// Module progress (completed chapters)
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

// Attachment scenarios per chapter (add more as needed)
$scenarios = [
    1 => ['name' => 'Invoice_INV-2026010.pdf.exe', 'size' => '124 KB', 'type' => 'Executable', 'warning' => 'This email contains an executable attachment disguised as a PDF'],
    2 => ['name' => 'HR_Update.docm', 'size' => '89 KB', 'type' => 'Macro-enabled Document', 'warning' => 'This email contains a macro-enabled document that can run malicious code'],
    3 => ['name' => 'Software_Update.zip', 'size' => '2.3 MB', 'type' => 'Archive', 'warning' => 'This email contains an archive that may contain hidden executables'],
];
$scenario = $scenarios[$current_chapter_num] ?? $scenarios[1];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?= htmlspecialchars($module_title) ?> - Chapter <?= $current_chapter_num ?> – CyberAware</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Style merged from original attachments.php (danger theme) + needed elements from phishing.php */
        :root {
            --cyber-yellow: #FFD60A;
            --cyber-gold: #FFC300;
            --dark-navy: #0F1419;
            --dark-slate: #1A1E2E;
            --primary: #FFD60A;
            --primary-dark: #FFC300;
            --success: #10b981;
            --success-light: #d1fae5;
            --danger: #dc2626;
            --danger-light: #fef2f2;
            --warning: #FFD60A;
            --warning-light: #FFF8DC;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --radius: 12px;
            --sidebar-width: 260px;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Inter', system-ui, sans-serif; background: var(--gray-50); color: var(--gray-800); line-height: 1.6; }
        .main-content { margin-left: var(--sidebar-width); padding: 2.5rem 2rem; min-height: 100vh; }
        .container { max-width: 1100px; margin: 0 auto; }
        .page-header {
            background: linear-gradient(135deg, var(--dark-navy), var(--dark-slate));
            border-radius: var(--radius);
            padding: 3rem 2.5rem;
            margin-bottom: 2.5rem;
            box-shadow: var(--shadow-md);
            color: white;
            text-align: center;
            border-left: 8px solid var(--cyber-yellow);
        }
        .page-header h2 { font-size: 2.4rem; font-weight: 700; margin-bottom: 0.8rem; }
        .page-header p { font-size: 1.2rem; opacity: 0.95; }
        .stats-bar { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: var(--radius); box-shadow: var(--shadow-sm); text-align: center; border-left: 5px solid var(--cyber-yellow); }
        .stat-card .stat-label { font-size: 0.9rem; color: var(--gray-600); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
        .stat-card .stat-value { font-size: 2.2rem; font-weight: 700; color: var(--cyber-yellow); margin: 0.5rem 0; }
        .module-progress { margin-bottom: 2rem; }
        .progress-bar { height: 10px; background: var(--gray-200); border-radius: 5px; overflow: hidden; margin-top: 0.5rem; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, var(--cyber-yellow), var(--cyber-gold)); width: <?= $module_progress ?>%; transition: width 0.8s ease; }
        .video-intro { margin-bottom: 2.5rem; border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow-md); }
        .video-intro video { width: 100%; max-height: 500px; object-fit: cover; }
        .email-wrapper { background: white; border-radius: var(--radius); box-shadow: var(--shadow-md); overflow: hidden; margin-bottom: 2rem; }
        .email-toolbar { background: var(--gray-100); padding: 1rem 1.5rem; border-bottom: 1px solid var(--gray-200); display: flex; gap: 1rem; align-items: center; }
        .email-toolbar-icon { width: 36px; height: 36px; background: var(--gray-200); border-radius: 6px; display: flex; align-items: center; justify-content: center; color: var(--gray-600); cursor: pointer; }
        .email-header { background: var(--gray-50); padding: 1.5rem 2rem; border-bottom: 2px solid var(--gray-200); }
        .email-header-row { display: flex; gap: 1rem; margin-bottom: 0.8rem; font-size: 1rem; }
        .email-header-label { font-weight: 600; color: var(--gray-700); min-width: 80px; }
        .email-subject { font-size: 1.5rem; font-weight: 700; padding: 1.5rem 2rem; background: white; border-bottom: 1px solid var(--gray-200); }
        .email-body { padding: 2.5rem 2rem; text-align: center; font-size: 1.1rem; line-height: 1.8; }
        .attachment-section { padding: 2.5rem 2rem; background: linear-gradient(to bottom, white, var(--gray-50)); }
        .attachment-warning-banner { background: var(--warning-light); border: 2px solid var(--cyber-yellow); border-radius: var(--radius); padding: 1.2rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem; color: #6B4C0F; font-weight: 600; }
        .attachment-item { max-width: 550px; margin: 0 auto; padding: 2rem; background: white; border: 3px dashed var(--danger); border-radius: var(--radius); text-align: center; cursor: pointer; transition: all 0.3s; }
        .attachment-item:hover { background: var(--danger-light); transform: scale(1.02); box-shadow: 0 10px 30px rgba(220, 38, 38, 0.2); }
        .attachment-icon { font-size: 4rem; color: var(--danger); margin-bottom: 1rem; }
        .attachment-name { font-size: 1.4rem; font-weight: 700; color: var(--danger); }
        .attachment-size { color: var(--gray-600); margin-top: 0.5rem; }
        .action-section { margin: 3rem 0; text-align: center; }
        .simulation-buttons { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; max-width: 800px; margin: 2rem auto; }
        .btn { padding: 1.2rem; font-size: 1.1rem; font-weight: 600; border-radius: var(--radius); border: none; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: center; gap: 0.8rem; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-danger:hover { background: #b91c1c; transform: translateY(-4px); }
        .btn-safe { background: var(--success); color: white; }
        .btn-safe:hover { background: #047857; transform: translateY(-4px); }
        .btn-neutral { background: var(--gray-300); color: var(--gray-800); }
        .btn-neutral:hover { background: var(--gray-400); }
        .feedback { padding: 2rem; border-radius: var(--radius); margin: 2rem 0; font-size: 1.2rem; line-height: 1.7; border-left: 6px solid; box-shadow: var(--shadow-md); display: flex; gap: 1.2rem; align-items: start; }
        .feedback.success { background: var(--success-light); border-color: var(--success); color: #166534; }
        .feedback.danger { background: var(--danger-light); border-color: var(--danger); color: #7f1d1d; }
        .feedback i { font-size: 2rem; margin-top: 0.3rem; }
        .dynamic-content { margin-top: 3rem; padding: 2.5rem; background: white; border-radius: var(--radius); box-shadow: var(--shadow-md); border: 1px solid var(--gray-200); }
        .dynamic-content h3 { font-size: 1.8rem; color: var(--danger); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.8rem; }
        .chapter-nav { display: flex; justify-content: space-between; align-items: center; margin: 3rem 0; padding: 1.5rem; background: white; border-radius: 12px; box-shadow: var(--shadow-sm); }
        .chapter-info { font-size: 1.2rem; font-weight: 600; }
        .nav-btn { padding: 0.9rem 1.8rem; background: var(--primary); color: var(--dark-navy); border: none; border-radius: 8px; font-weight: 600; cursor: pointer; opacity: 0.5; pointer-events: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .nav-btn.enabled { opacity: 1; pointer-events: auto; }
        .nav-btn.enabled:hover { background: var(--cyber-gold); }
        .nav-btn.prev { background: var(--gray-600); }
        .nav-btn.prev.enabled:hover { background: var(--gray-700); }
        @media (max-width: 992px) {
            .main-content { margin-left: 0; padding: 1.5rem; }
            .simulation-buttons { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<?php include '../trainee-sidebar.php'; ?>
<div class="main-content">
    <div class="container">
        <!-- Module Progress -->
        <div class="module-progress">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                <strong><?= htmlspecialchars($module_title) ?> Progress</strong>
                <span><?= $completed_chapters ?> / <?= $total_chapters ?> Chapters (<?= $module_progress ?>%)</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
        </div>

        <!-- Stats -->
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

        <!-- Chapter Header -->
        <div class="page-header">
            <h2><i class="fas fa-virus"></i> Chapter <?= $current_chapter_num ?>: <?= htmlspecialchars($current_chapter['title']) ?></h2>
            <?php if ($current_chapter['description']): ?>
            <p><?= htmlspecialchars($current_chapter['description']) ?></p>
            <?php endif; ?>
        </div>

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
                        location.reload(); // Ensure server-side completion logic runs (e.g., module auto-complete)
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

        <!-- Chapter Content -->
        <?php if ($chapter_content): ?>
        <div class="dynamic-content">
            <?= $chapter_content ?>
        </div>
        <?php endif; ?>

        <!-- Simulation Email -->
        <div class="email-wrapper">
            <div class="email-toolbar">
                <div class="email-toolbar-icon"><i class="fas fa-reply"></i></div>
                <div class="email-toolbar-icon"><i class="fas fa-reply-all"></i></div>
                <div class="email-toolbar-icon"><i class="fas fa-share"></i></div>
                <div class="email-toolbar-icon"><i class="fas fa-trash"></i></div>
            </div>
            <div class="email-header">
                <div class="email-header-row">
                    <span class="email-header-label">From:</span>
                    <span>Finance Department &lt;finance@partnercorp.co.zm&gt;</span>
                </div>
                <div class="email-header-row">
                    <span class="email-header-label">To:</span>
                    <span><?= htmlspecialchars($user_email) ?></span>
                </div>
                <div class="email-header-row">
                    <span class="email-header-label">Date:</span>
                    <span><?= date('l, F j, Y g:i A') ?></span>
                </div>
            </div>
            <div class="email-subject">
                Outstanding Invoice #INV-2026010 – Immediate Payment Required
            </div>
            <div class="email-body">
                <p>Dear Valued Partner,</p>
                <p>We have not received payment for the attached invoice. Please review and process payment within 24 hours to avoid late fees.</p>
                <p style="margin-top:1.5rem; font-weight:600; color:var(--danger);">Download and open the attachment to view details.</p>
            </div>
            <div class="attachment-section">
                <div class="attachment-warning-banner">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p><?= $scenario['warning'] ?></p>
                </div>
                <div class="attachment-item">
                    <i class="fas fa-file-alt attachment-icon"></i>
                    <div>
                        <div class="attachment-name"><?= htmlspecialchars($scenario['name']) ?></div>
                        <div class="attachment-size"><?= $scenario['size'] ?> • <?= $scenario['type'] ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action -->
        <div class="action-section">
            <h3>What would you do?</h3>
            <form method="POST" class="simulation-buttons">
                <button type="submit" name="action" value="open" class="btn btn-danger">
                    <i class="fas fa-folder-open"></i> Open Attachment
                </button>
                <button type="submit" name="action" value="report" class="btn btn-safe">
                    <i class="fas fa-flag"></i> Report to Security
                </button>
                <button type="submit" name="action" value="delete" class="btn btn-safe">
                    <i class="fas fa-trash"></i> Delete Email
                </button>
                <button type="submit" name="action" value="forward" class="btn btn-neutral">
                    <i class="fas fa-share"></i> Forward to Manager
                </button>
            </form>
        </div>

        <!-- Feedback -->
        <?php if ($feedback): ?>
        <div class="feedback <?= $feedback_class ?>">
            <i class="fas fa-<?= $feedback_class === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
            <div><?= $feedback ?></div>
        </div>
        <?php endif; ?>

        <!-- Key Takeaways (shown only after correct action) -->
        <?php if ($show_takeaways): ?>
        <div class="dynamic-content">
            <h3><i class="fas fa-lightbulb"></i> Key Learning Points</h3>
            <ul style="font-size:1.1rem; line-height:1.8; padding-left:1.5rem;">
                <li>Malicious attachments often use disguised extensions (e.g., .pdf.exe, .docm, .scr).</li>
                <li>Archives (.zip, .rar) can hide executables — never open unexpected ones.</li>
                <li>Urgency and unexpected requests are common social engineering tactics.</li>
                <li>Always enable "show file extensions" in your OS settings.</li>
                <li>Best actions: <strong>Report to IT/security team</strong> or <strong>delete immediately</strong>.</li>
                <li>Never open, forward, or reply to suspicious emails.</li>
            </ul>
            <div style="margin-top:2rem; padding:1.5rem; background:var(--danger-light); border-radius:var(--radius);">
                <strong><i class="fas fa-skull-crossbones"></i> Real-World Impact</strong>
                <p>Malware from attachments can lead to ransomware, data theft, full network compromise, and millions in losses.</p>
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
</div>
</body>
</html>