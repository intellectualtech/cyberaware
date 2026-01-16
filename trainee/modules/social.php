<?php
// modules/social.php - Chapter-Based Social Engineering Simulation (Improved Navigation UX)
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

$module_id = 3; // SOCIAL_001

// Load module title & description
$stmt = $pdo->prepare("SELECT title, description FROM training_modules WHERE id = ?");
$stmt->execute([$module_id]);
$module = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$module) {
    die("Module not found.");
}
$module_title = $module['title'] ?? 'Social Engineering Scenario';
$module_desc = $module['description'] ?? 'Learn to recognize and defend against manipulation tactics';

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

// Handle user choice
$feedback = null;
$feedback_class = '';
$show_takeaways = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['choice'])) {
    $choice = $_POST['choice'];
    $is_correct = ($choice === 'hangup');
    $points = $is_correct ? 15 : -10;

    // Feedback
    if ($is_correct) {
        $feedback = "Excellent choice! Never share sensitive information over unsolicited calls — hang up and report immediately.";
        $feedback_class = 'success';
    } else {
        $feedback = "This was a social engineering (vishing) attack.<br>Attackers use manipulation, urgency, and spoofing to extract information or access.";
        $feedback_class = 'danger';
    }

    // Create session if not exists (one session per module attempt)
    if (!isset($_SESSION['current_social_session'])) {
        $stmt = $pdo->prepare("
            INSERT INTO training_sessions (user_id, module_id, started_at, final_score)
            VALUES (?, ?, NOW(), 0)
        ");
        $stmt->execute([$user_id, $module_id]);
        $session_id = $pdo->lastInsertId();
        $_SESSION['current_social_session'] = $session_id;
    } else {
        $session_id = $_SESSION['current_social_session'];
    }

    // Log action
    $stmt = $pdo->prepare("
        INSERT INTO training_actions
        (session_id, step_number, action_type, action_value, is_correct, points, timestamp)
        VALUES (?, ?, 'social_action', ?, ?, ?, NOW())
    ");
    $stmt->execute([$session_id, $current_chapter_num, $choice, $is_correct ? 1 : 0, $points]);

    // Update session score
    $stmt = $pdo->prepare("
        UPDATE training_sessions
        SET final_score = final_score + ?
        WHERE id = ?
    ");
    $stmt->execute([$points, $session_id]);

    // Only mark chapter complete on correct choice
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
if (isset($_SESSION['current_social_session'])) {
    $session_id = $_SESSION['current_social_session'];
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
        unset($_SESSION['current_social_session']);
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

// Scenarios per chapter (vishing/social engineering phone calls)
$scenarios = [
    1 => [
        'title' => 'Urgent Call from "IT Support"',
        'description' => 'You receive a phone call from someone claiming to be from your company\'s IT department. They say your account has been compromised and they need your current password to "secure" it immediately. The caller ID shows your company\'s main number, and they sound professional.',
        'caller_name' => 'IT Support',
        'caller_number' => '+264 61 123 4567',
        'caller_label' => 'Company Main Line',
        'specific_flag' => 'Unsolicited request for password + urgency + spoofed company number',
    ],
    2 => [
        'title' => 'Call from "HR Department"',
        'description' => 'Someone calls claiming to be from HR, saying there\'s an issue with your payroll and they need to verify your bank account details over the phone to "prevent delayed payment". They know your employee ID and department.',
        'caller_name' => 'HR Department',
        'caller_number' => '+264 61 987 6543',
        'caller_label' => 'Internal HR',
        'specific_flag' => 'Request for financial details + knowledge of personal info + threat of payment delay',
    ],
    3 => [
        'title' => 'Call from "Executive Assistant"',
        'description' => 'A caller says they are the assistant to the CEO and the boss is in an urgent meeting but needs you to approve a wire transfer immediately. They ask for your authentication code from the banking app.',
        'caller_name' => 'CEO Assistant',
        'caller_number' => '+264 81 555 1234',
        'caller_label' => 'Executive Office',
        'specific_flag' => 'Impersonation of authority + extreme urgency + request for financial action',
    ],
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* Your existing <style> block - unchanged */
        :root {
            --primary: #FF8C42;
            --primary-dark: #E67A2E;
            --primary-light: #FFF4ED;
            --success: #10b981;
            --success-light: #d1fae5;
            --success-dark: #047857;
            --info: #3b82f6;
            --info-light: #eff6ff;
            --danger: #dc2626;
            --danger-light: #fef2f2;
            --warning: #f59e0b;
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
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            --radius: 8px;
            --sidebar-width: 260px;
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
            margin-left: var(--sidebar-width);
            padding: 2.5rem 2rem;
            min-height: 100vh;
        }

        .container { 
            max-width: 1100px; 
            margin: 0 auto; 
        }

        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: var(--radius);
            padding: 2.5rem 2rem;
            margin-bottom: 2.5rem;
            box-shadow: var(--shadow-md);
            color: white;
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
            border: 1px solid var(--gray-200);
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

        /* Phone Call Simulation */
        .phone-simulation {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            margin-bottom: 2rem;
            border: 1px solid var(--gray-200);
        }

        .phone-screen {
            background: linear-gradient(135deg, var(--gray-100) 0%, var(--gray-200) 100%);
            padding: 3rem 2rem;
            position: relative;
            overflow: hidden;
        }

        .phone-screen::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 140, 66, 0.1) 0%, transparent 70%);
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 0.6; }
        }

        .phone-content {
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .caller-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: white;
            box-shadow: 0 8px 24px rgba(255, 140, 66, 0.4);
        }

        .caller-info {
            color: var(--gray-800);
            margin-bottom: 2rem;
        }

        .caller-name {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--gray-900);
        }

        .caller-number {
            font-size: 1.1rem;
            color: var(--gray-700);
            margin-bottom: 0.3rem;
        }

        .caller-label {
            display: inline-block;
            background: rgba(255, 140, 66, 0.1);
            color: var(--primary-dark);
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            border: 1px solid rgba(255, 140, 66, 0.3);
        }

        .call-duration {
            color: var(--gray-600);
            font-size: 1rem;
            margin-top: 1rem;
        }

        /* Scenario Box */
        .scenario-box {
            background: white;
            padding: 2rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            margin-bottom: 2rem;
        }

        .scenario-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .scenario-title i {
            color: var(--primary);
        }

        .scenario-description {
            font-size: 1.05rem;
            line-height: 1.7;
            color: var(--gray-700);
            background: var(--gray-50);
            padding: 1.5rem;
            border-radius: 6px;
            border-left: 4px solid var(--primary);
        }

        /* Options Section */
        .options-section {
            background: white;
            padding: 2rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            margin-bottom: 2rem;
        }

        .options-section h3 {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            color: var(--gray-800);
            text-align: center;
        }

        .options-grid {
            display: grid;
            gap: 1rem;
        }

        @media (min-width: 768px) {
            .options-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .option-btn {
            padding: 1.5rem;
            background: var(--gray-50);
            border: 2px solid var(--gray-200);
            border-radius: var(--radius);
            text-align: left;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 1.05rem;
            color: var(--gray-800);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .option-btn i {
            font-size: 1.3rem;
            color: var(--gray-600);
            transition: all 0.2s;
        }

        .option-btn:hover {
            background: var(--primary-light);
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(255, 140, 66, 0.2);
        }

        .option-btn:hover i {
            color: var(--primary);
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

        /* Dynamic Content / Takeaways */
        .dynamic-content {
            margin-top: 3rem;
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

        .red-flags-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .red-flag-item {
            padding: 1.2rem;
            background: var(--gray-50);
            border-radius: 8px;
            border-left: 4px solid var(--primary);
            display: flex;
            align-items: start;
            gap: 1rem;
        }

        .red-flag-item i {
            color: var(--primary);
            font-size: 1.3rem;
            margin-top: 0.2rem;
        }

        .red-flag-item p {
            margin: 0;
            color: var(--gray-700);
            font-weight: 500;
        }

        .best-practice {
            padding: 1.5rem;
            background: linear-gradient(135deg, var(--success-light) 0%, #fff 100%);
            border-radius: 8px;
            border: 2px solid var(--success);
            margin-top: 1.5rem;
        }

        .best-practice strong {
            display: block;
            font-size: 1.1rem;
            color: var(--success-dark);
            margin-bottom: 0.7rem;
        }

        .best-practice p {
            margin: 0;
            color: var(--gray-700);
            line-height: 1.6;
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

        /* Mobile Responsive */
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                margin-bottom: 80px;
                padding: 1.5rem 1rem;
            }

            .options-grid {
                grid-template-columns: 1fr;
            }

            .red-flags-grid {
                grid-template-columns: 1fr;
            }
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

        <!-- Chapter Header -->
        <div class="page-header">
            <h2><i class="fas fa-user-secret"></i> Chapter <?= $current_chapter_num ?>: <?= htmlspecialchars($current_chapter['title']) ?></h2>
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
                        location.reload(); // Reload to ensure server-side logic (e.g., session complete) runs
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

        <!-- Phone Simulation -->
        <div class="phone-simulation">
            <div class="phone-screen">
                <div class="phone-content">
                    <div class="caller-avatar">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <div class="caller-info">
                        <div class="caller-name"><?= htmlspecialchars($scenario['caller_name']) ?></div>
                        <div class="caller-number"><?= htmlspecialchars($scenario['caller_number']) ?></div>
                        <div class="caller-label"><?= htmlspecialchars($scenario['caller_label']) ?></div>
                        <div class="call-duration">
                            <i class="fas fa-clock"></i> Incoming Call...
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scenario Box -->
        <div class="scenario-box">
            <div class="scenario-title">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($scenario['title']) ?>
            </div>
            <div class="scenario-description">
                <?= htmlspecialchars($scenario['description']) ?>
            </div>
        </div>

        <!-- Feedback -->
        <?php if ($feedback): ?>
        <div class="feedback <?= $feedback_class ?>">
            <i class="fas fa-<?= $feedback_class === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
            <div><?= $feedback ?></div>
        </div>
        <?php endif; ?>

        <!-- Options Section -->
        <div class="options-section">
            <h3>What would you do?</h3>
            <form method="POST">
                <div class="options-grid">
                    <button type="submit" name="choice" value="give" class="option-btn">
                        <i class="fas fa-key"></i>
                        <span>Provide the requested information to resolve the issue</span>
                    </button>
                    <button type="submit" name="choice" value="ask" class="option-btn">
                        <i class="fas fa-question-circle"></i>
                        <span>Ask for more details or verification</span>
                    </button>
                    <button type="submit" name="choice" value="hangup" class="option-btn">
                        <i class="fas fa-phone-slash"></i>
                        <span>Hang up immediately and report to security</span>
                    </button>
                    <button type="submit" name="choice" value="transfer" class="option-btn">
                        <i class="fas fa-exchange-alt"></i>
                        <span>Transfer or consult with a colleague/manager</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Key Takeaways (shown only after correct choice) -->
        <?php if ($show_takeaways): ?>
        <div class="dynamic-content">
            <h3><i class="fas fa-lightbulb"></i> Key Red Flags in Social Engineering</h3>
            <div class="red-flags-grid">
                <div class="red-flag-item">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p><strong>Specific in this scenario:</strong> <?= htmlspecialchars($scenario['specific_flag']) ?></p>
                </div>
                <div class="red-flag-item">
                    <i class="fas fa-phone-slash"></i>
                    <p>Unexpected unsolicited calls asking for sensitive information</p>
                </div>
                <div class="red-flag-item">
                    <i class="fas fa-clock"></i>
                    <p>Creating urgency or fear to pressure quick action</p>
                </div>
                <div class="red-flag-item">
                    <i class="fas fa-id-card"></i>
                    <p>Caller ID spoofing – numbers can be faked</p>
                </div>
            </div>

            <div class="best-practice">
                <strong><i class="fas fa-shield-alt"></i> Best Practice Response</strong>
                <p>Hang up immediately. Never engage or provide information. Verify by calling back using official contacts from your company directory. Report all suspicious calls to your security team. Legitimate staff will NEVER ask for passwords or sensitive details over the phone.</p>
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