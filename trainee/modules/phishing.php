<?php
// modules/phishing.php - Full Chapter-Based Phishing Module (Adapted to Your Existing DB Structure)

require_once '../../config/database.php';
require_once '../../includes/functions.php';

require_login();

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id, username, full_name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Error: User not found. Please contact administrator.");
}

$user_email = $user['email'] ?? ($user['username'] . '@company.com');
$module_id = 1; // PHISHING

// Load module data
$stmt = $pdo->prepare("SELECT title, description FROM training_modules WHERE id = ?");
$stmt->execute([$module_id]);
$module = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$module) {
    die("Module not found.");
}

$module_title = $module['title'] ?? 'Phishing Email Simulation';
$module_desc = $module['description'] ?? 'Identify security threats and protect your organization from cyberattacks';

// Get all chapters for this module
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

// Determine current chapter
$current_chapter_num = (int)($_GET['chapter'] ?? 1);
if ($current_chapter_num < 1) $current_chapter_num = 1;
if ($current_chapter_num > $total_chapters) $current_chapter_num = $total_chapters;

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

// Progress tracking using your existing table columns
$is_video_complete = false; // We'll mark video complete by setting watched_seconds = -1
$is_activity_complete = false;

$stmt = $pdo->prepare("
    SELECT completed_at, watched_seconds 
    FROM user_chapter_progress 
    WHERE user_id = ? AND chapter_id = ?
");
$stmt->execute([$user_id, $chapter_id]);
$progress = $stmt->fetch(PDO::FETCH_ASSOC);

if ($progress) {
    $is_activity_complete = $progress['completed_at'] !== null;
    $is_video_complete = $progress['watched_seconds'] == -1; // Special value for "video finished"
} else {
    // Insert new progress row
    $stmt = $pdo->prepare("
        INSERT INTO user_chapter_progress (user_id, chapter_id, watched_seconds, completed_at)
        VALUES (?, ?, 0, NULL)
    ");
    $stmt->execute([$user_id, $chapter_id]);
}

// Handle phishing simulation activity
$feedback = null;
$feedback_class = '';
$show_takeaways = false;
$template = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // Random phishing template
    $stmt = $pdo->prepare("
        SELECT sender_name, sender_email, subject, body_html, has_attachment, attachment_name
        FROM phishing_templates 
        WHERE module_id = ? AND is_active = 1 
        ORDER BY RAND() 
        LIMIT 1
    ");
    $stmt->execute([$module_id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);

    $is_correct = ($action === 'report');
    $points = $is_correct ? 15 : -10;

    // Mark activity complete by setting completed_at
    $stmt = $pdo->prepare("
        UPDATE user_chapter_progress 
        SET completed_at = NOW() 
        WHERE user_id = ? AND chapter_id = ?
    ");
    $stmt->execute([$user_id, $chapter_id]);
    $is_activity_complete = true;

    // Feedback
    switch ($action) {
        case 'report':
            $feedback = "Excellent decision! You correctly identified and reported this phishing attempt.";
            $feedback_class = 'success';
            break;
        case 'click':
            $feedback = "This was a simulated phishing attempt.<br><strong>Never click suspicious links</strong> — even when they look legitimate.";
            $feedback_class = 'danger';
            break;
        case 'open':
        case 'delete':
            $feedback = "Good defensive instinct.<br>The safest and most helpful action is always to <strong>report</strong> suspicious emails.";
            $feedback_class = 'info';
            break;
        default:
            $feedback = "Action recorded.";
            $feedback_class = 'neutral';
    }

    $show_takeaways = true;
} else {
    // Load random template for display
    $stmt = $pdo->prepare("
        SELECT sender_name, sender_email, subject, body_html, has_attachment, attachment_name
        FROM phishing_templates 
        WHERE module_id = ? AND is_active = 1 
        ORDER BY RAND() 
        LIMIT 1
    ");
    $stmt->execute([$module_id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
}

// AJAX for video complete (set watched_seconds = -1 as marker)
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

// Next button enabled only if video finished AND activity completed
$next_enabled = $is_video_complete && $is_activity_complete;

// Module progress calculation
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM user_chapter_progress ucp
    JOIN module_chapters mc ON ucp.chapter_id = mc.id
    WHERE ucp.user_id = ? 
      AND mc.module_id = ? 
      AND ucp.watched_seconds = -1 
      AND ucp.completed_at IS NOT NULL
");
$stmt->execute([$user_id, $module_id]);
$completed_chapters = (int)$stmt->fetchColumn();

$module_progress = $total_chapters > 0 ? round(($completed_chapters / $total_chapters) * 100) : 0;
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* Your full original <style> from the provided code - unchanged */
        :root {
            --cyber-yellow: #FF8C42;
            --primary: #FF8C42;
            --cyber-gold: #FF8C42;
            --white: #FFFFFF;
            --cyber-light: #F3F4F6;
            --dark-navy: #111827;
            --dark-slate: #374151;
            --charcoal: #6B7280;
            --shield-green: #10B981;
            --alert-red: #EF4444;
            --info-blue: #3B82F6;
            --success: #10b981;
            --success-light: #d1fae5;
            --success-dark: #047857;
            --info: #3b82f6;
            --info-light: #eff6ff;
            --danger: #dc2626;
            --danger-light: #fef2f2;
            --warning: var(--cyber-yellow);
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-yellow: 0 0 20px rgba(var(--primary-rgb),0.3);
            --radius: 8px;
            --sidebar-width: 260px;
            --sidebar-height: 72px;
        }

        * { margin:0; padding:0; box-sizing:border-box; }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--gray-50);
            color: var(--gray-800);
            line-height: 1.6;
            min-height: 100vh;
        }

        .main-content {
            margin-bottom: var(--sidebar-height);
            padding: 2.5rem 2rem;
            min-height: 100vh;
        }

        .container { 
            max-width: 1100px; 
            margin: 0 auto; 
        }

        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, var(--dark-navy) 0%, var(--dark-slate) 100%);
            border-radius: var(--radius);
            padding: 2.5rem 2rem;
            margin-bottom: 2.5rem;
            box-shadow: var(--shadow-md);
            color: white;
            border-left: 8px solid var(--cyber-yellow);
        }

        .page-header h2 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: white;
        }

        .page-header p {
            font-size: 1.1rem;
            opacity: 0.95;
            margin: 0;
        }

        /* Stats Bar */
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
            border-left: 5px solid var(--cyber-yellow);
            transition: all 0.2s;
        }

        .stat-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
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
            color: var(--primary);
        }

        /* Email Container */
        .email-wrapper {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .email-toolbar {
            background: var(--gray-100);
            padding: 0.8rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .email-toolbar-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            background: var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-600);
            cursor: pointer;
            transition: all 0.2s;
        }

        .email-toolbar-icon:hover {
            background: var(--gray-300);
        }

        .email-container {
            background: white;
            font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
        }

        .email-header {
            background: var(--gray-50);
            padding: 1.5rem 2rem;
            border-bottom: 2px solid var(--gray-200);
        }

        .email-header-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 0.7rem;
            font-size: 0.95rem;
        }

        .email-header-row:last-child {
            margin-bottom: 0;
        }

        .email-header-label {
            font-weight: 600;
            color: var(--gray-700);
            min-width: 80px;
        }

        .email-header-value {
            color: var(--gray-800);
            flex: 1;
        }

        .email-subject {
            background: white;
            font-size: 1.4rem;
            font-weight: 700;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--gray-200);
            color: var(--gray-800);
        }

        .email-body {
            padding: 2rem;
            font-size: 1.05rem;
            line-height: 1.7;
            color: var(--gray-800);
        }

        .fake-link {
            color: #2563eb;
            text-decoration: underline;
            font-weight: 500;
            cursor: pointer;
        }

        .attachment-section {
            padding: 0 2rem 2rem 2rem;
        }

        .attachment-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.2rem;
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .attachment-item:hover {
            background: var(--gray-100);
            border-color: var(--primary);
        }

        .attachment-icon {
            width: 42px;
            height: 42px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .attachment-info {
            flex: 1;
        }

        .attachment-name {
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 0.2rem;
        }

        .attachment-size {
            font-size: 0.85rem;
            color: var(--gray-600);
        }

        /* Feedback */
        .feedback {
            padding: 1.5rem 2rem;
            border-radius: var(--radius);
            margin: 2rem 0;
            font-size: 1.1rem;
            line-height: 1.6;
            border-left: 5px solid;
            display: flex;
            gap: 1rem;
            align-items: start;
            box-shadow: var(--shadow-sm);
        }

        .feedback i {
            font-size: 1.5rem;
            margin-top: 0.2rem;
        }

        .feedback.success { 
            background: #f0fdf4; 
            border-color: #22c55e; 
            color: #14532d; 
        }

        .feedback.success i { color: #22c55e; }

        .feedback.danger  { 
            background: #fef2f2; 
            border-color: #ef4444;  
            color: #7f1d1d; 
        }

        .feedback.danger i { color: #ef4444; }

        .feedback.info    { 
            background: #f0f9ff; 
            border-color: #0ea5e9;    
            color: #164e63; 
        }

        .feedback.info i { color: #0ea5e9; }

        /* Action Buttons */
        .action-section {
            background: white;
            padding: 2rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            margin-bottom: 2rem;
        }

        .action-section h3 {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            color: var(--gray-800);
            text-align: center;
        }

        .simulation-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .simulation-buttons button {
            padding: 1.1rem 1.5rem;
            font-size: 1.05rem;
            font-weight: 600;
            border-radius: var(--radius);
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.7rem;
        }

        .btn-report {
            background: #22c55e;
            color: white;
            border-color: #22c55e;
        }

        .btn-report:hover {
            background: #16a34a;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(34, 197, 94, 0.3);
        }

        .btn-click {
            background: #ef4444;
            color: white;
            border-color: #ef4444;
        }

        .btn-click:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(239, 68, 68, 0.3);
        }

        .btn-other {
            background: white;
            color: var(--gray-700);
            border-color: var(--gray-300);
        }

        .btn-other:hover {
            background: var(--gray-50);
            border-color: var(--gray-400);
            transform: translateY(-2px);
        }

        /* Chapter Navigation & Progress */
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

        .nav-btn.enabled:hover {
            background: var(--primary-dark);
        }

        .nav-btn.prev {
            background: var(--gray-600);
        }

        .nav-btn.prev.enabled:hover {
            background: var(--gray-700);
        }

        .module-progress {
            margin-bottom: 2rem;
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
            max-height: 560px;
            object-fit: contain;
            background: black;
        }

        .dynamic-content {
            margin: 3rem 0;
            padding: 2.5rem;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
        }

        .dynamic-content h3 {
            font-size: 1.8rem;
            color: var(--primary-dark);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        /* Mobile */
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

        <!-- Module Overall Progress -->
        <div class="module-progress">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                <strong><?= htmlspecialchars($module_title) ?></strong> Progress
                <span><?= $completed_chapters ?> / <?= $total_chapters ?> Chapters (<?= $module_progress ?>%)</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
        </div>

        <!-- Chapter Header -->
        <div class="page-header">
            <h2>Chapter <?= $current_chapter_num ?>: <?= htmlspecialchars($current_chapter['title']) ?></h2>
            <?php if ($current_chapter['description']): ?>
            <p><?= htmlspecialchars($current_chapter['description']) ?></p>
            <?php endif; ?>
        </div>

        <!-- Chapter Intro Video -->
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

        video.addEventListener('timeupdate', function() {
            if (!videoWatched && video.currentTime >= video.duration * 0.95) {
                fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'mark_video_complete=1'
                }).then(() => {
                    videoWatched = true;
                    updateNextButton();
                });
            }
        });

        video.addEventListener('ended', function() {
            if (!videoWatched) {
                fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'mark_video_complete=1'
                }).then(() => {
                    videoWatched = true;
                    updateNextButton();
                });
            }
        });

        function updateNextButton() {
            const nextBtn = document.getElementById('nextBtn');
            if (nextBtn && videoWatched && <?= $is_activity_complete ? 'true' : 'false' ?>) {
                nextBtn.classList.add('enabled');
            }
        }

        updateNextButton();
        </script>
        <?php endif; ?>

        <!-- Additional Chapter Content (from admin) -->
        <?php if ($chapter_content): ?>
        <div class="dynamic-content">
            <?= $chapter_content ?>
        </div>
        <?php endif; ?>

        <!-- Phishing Simulation (Activity) -->
        <?php if ($template): ?>
        <div class="email-wrapper">
            <div class="email-toolbar">
                <div class="email-toolbar-icon"><i class="fas fa-reply"></i></div>
                <div class="email-toolbar-icon"><i class="fas fa-reply-all"></i></div>
                <div class="email-toolbar-icon"><i class="fas fa-share"></i></div>
                <div class="email-toolbar-icon"><i class="fas fa-trash"></i></div>
                <div class="email-toolbar-icon" style="margin-left: auto;"><i class="fas fa-ellipsis-v"></i></div>
            </div>

            <div class="email-container">
                <div class="email-header">
                    <div class="email-header-row">
                        <span class="email-header-label">From:</span>
                        <span class="email-header-value"><?= htmlspecialchars($template['sender_name'] . ' <' . $template['sender_email'] . '>') ?></span>
                    </div>
                    <div class="email-header-row">
                        <span class="email-header-label">To:</span>
                        <span class="email-header-value"><?= htmlspecialchars($user_email) ?></span>
                    </div>
                    <div class="email-header-row">
                        <span class="email-header-label">Date:</span>
                        <span class="email-header-value"><?= date('l, F j, Y g:i A') ?></span>
                    </div>
                </div>

                <div class="email-subject">
                    <?= htmlspecialchars($template['subject']) ?>
                </div>

                <div class="email-body">
                    <?= $template['body_html'] ?>
                </div>

                <?php if ($template['has_attachment']): ?>
                <div class="attachment-section">
                    <div class="attachment-item">
                        <div class="attachment-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="attachment-info">
                            <div class="attachment-name"><?= htmlspecialchars($template['attachment_name']) ?></div>
                            <div class="attachment-size">124 KB</div>
                        </div>
                        <i class="fas fa-download" style="color: var(--gray-600);"></i>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-section">
            <h3>What would you do with this email?</h3>
            <form method="POST" class="simulation-buttons">
                <button type="submit" name="action" value="open" class="btn-other">
                    <i class="fas fa-envelope-open"></i> Open Email
                </button>
                <button type="submit" name="action" value="click" class="btn-click">
                    <i class="fas fa-mouse-pointer"></i> Click Link
                </button>
                <button type="submit" name="action" value="report" class="btn-report">
                    <i class="fas fa-flag"></i> Report Phishing
                </button>
                <button type="submit" name="action" value="delete" class="btn-other">
                    <i class="fas fa-trash-alt"></i> Delete Email
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Feedback -->
        <?php if ($feedback): ?>
        <div class="feedback <?= $feedback_class ?>">
            <i class="fas fa-<?= $feedback_class === 'success' ? 'check-circle' : ($feedback_class === 'danger' ? 'exclamation-triangle' : 'info-circle') ?>"></i>
            <div><?= $feedback ?></div>
        </div>
        <?php endif; ?>

        <!-- Dynamic Takeaways -->
        <?php if ($show_takeaways || $dynamic_content): ?>
        <div class="dynamic-content">
            <h3><i class="fas fa-lightbulb"></i> Key Learning Points</h3>
            <?= $dynamic_content ?: '
            <div class="red-flags-grid">
                <div class="red-flag-item">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Strong urgency & time pressure tactics</p>
                </div>
                <div class="red-flag-item">
                    <i class="fas fa-envelope"></i>
                    <p>Suspicious sender domain or email address</p>
                </div>
                <div class="red-flag-item">
                    <i class="fas fa-question-circle"></i>
                    <p>Unexpected requests for action or information</p>
                </div>
                <div class="red-flag-item">
                    <i class="fas fa-ban"></i>
                    <p>Threats of account suspension or penalties</p>
                </div>
                ' . ($template['has_attachment'] ?? false ? '
                <div class="red-flag-item">
                    <i class="fas fa-paperclip"></i>
                    <p>Dangerous file attachments disguised as documents</p>
                </div>' : '') . '
            </div>

            <div class="impact">
                <strong><i class="fas fa-building"></i> Real-World Business Impact</strong>
                <p>A single successful phishing attack can lead to credential theft, ransomware deployment, weeks of operational downtime, financial losses in the millions, and severe reputational damage to your organization.</p>
            </div>' ?>
        </div>
        <?php endif; ?>

        <!-- Chapter Navigation -->
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
                   class="nav-btn <?= $next_enabled ? 'enabled' : '' ?>" 
                   id="nextBtn">
                    Next Chapter <i class="fas fa-arrow-right"></i>
                </a>
                <?php else: ?>
                <a href="../dashboard.php" class="nav-btn enabled">
                    Complete Module <i class="fas fa-check-circle"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

</body>
</html>