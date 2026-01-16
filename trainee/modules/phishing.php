<?php
// modules/phishing.php - Chapter-Based with Video Watch Tracking & Next Button Logic

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

// Progress tracking table must exist (user_chapter_progress)
$is_video_complete = false;
$is_activity_complete = false;

$stmt = $pdo->prepare("
    SELECT video_complete, activity_complete 
    FROM user_chapter_progress 
    WHERE user_id = ? AND chapter_id = ?
");
$stmt->execute([$user_id, $chapter_id]);
$progress = $stmt->fetch(PDO::FETCH_ASSOC);

if ($progress) {
    $is_video_complete = (bool)$progress['video_complete'];
    $is_activity_complete = (bool)$progress['activity_complete'];
} else {
    // Insert new progress row
    $stmt = $pdo->prepare("
        INSERT INTO user_chapter_progress (user_id, chapter_id, video_complete, activity_complete)
        VALUES (?, ?, 0, 0)
    ");
    $stmt->execute([$user_id, $chapter_id]);
}

// Handle activity completion (phishing simulation)
$feedback = null;
$feedback_class = '';
$show_takeaways = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // Random template for simulation
    $stmt = $pdo->prepare("SELECT id FROM phishing_templates WHERE module_id = ? AND is_active = 1 ORDER BY RAND() LIMIT 1");
    $stmt->execute([$module_id]);
    $template_id = $stmt->fetchColumn();

    if ($template_id) {
        $is_correct = ($action === 'report');
        $points = $is_correct ? 15 : -10;

        // Log action (you can expand training_actions table if needed)
        // For simplicity, mark activity complete
        $stmt = $pdo->prepare("
            UPDATE user_chapter_progress 
            SET activity_complete = 1 
            WHERE user_id = ? AND chapter_id = ?
        ");
        $stmt->execute([$user_id, $chapter_id]);

        $is_activity_complete = true;

        // Feedback
        if ($is_correct) {
            $feedback = "Excellent! You correctly reported the phishing email.";
            $feedback_class = 'success';
        } else {
            $feedback = "Incorrect. The safest action is to report suspicious emails.";
            $feedback_class = 'danger';
        }

        $show_takeaways = true;
    }
}

// AJAX endpoint for video complete
if (isset($_POST['mark_video_complete'])) {
    $stmt = $pdo->prepare("
        UPDATE user_chapter_progress 
        SET video_complete = 1 
        WHERE user_id = ? AND chapter_id = ?
    ");
    $stmt->execute([$user_id, $chapter_id]);
    echo json_encode(['success' => true]);
    exit;
}

// Check if next enabled
$next_enabled = $is_video_complete && $is_activity_complete;

// Previous stats (module level)
$stmt = $pdo->prepare("
    SELECT COUNT(*) as completed_chapters 
    FROM user_chapter_progress 
    WHERE user_id = ? AND module_id = ? AND video_complete = 1 AND activity_complete = 1
");
$stmt->execute([$user_id, $module_id]);
$completed_chapters = $stmt->fetchColumn() ?? 0;

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
        /* Your original styles unchanged - omitted for brevity, paste your full <style> here */
        /* ... (keep all your :root, body, .main-content, etc.) ... */

        .chapter-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 2rem 0;
            padding: 1rem;
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
        }

        .chapter-info {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .nav-btn {
            padding: 0.8rem 1.5rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            opacity: 0.5;
            pointer-events: none;
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

        .progress-bar {
            height: 8px;
            background: var(--gray-200);
            border-radius: 4px;
            overflow: hidden;
            margin: 1rem 0;
        }

        .progress-fill {
            height: 100%;
            background: var(--primary);
            width: <?= $module_progress ?>%;
            transition: width 0.5s ease;
        }
    </style>
</head>
<body>

<?php include '../trainee-sidebar.php'; ?>

<div class="main-content">
    <div class="container">

        <!-- Module Progress -->
        <div style="margin-bottom:2rem;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <strong><?= htmlspecialchars($module_title) ?></strong> Progress
                </div>
                <div><?= $completed_chapters ?> / <?= $total_chapters ?> Chapters Completed (<?= $module_progress ?>%)</div>
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

        <!-- Dynamic Video -->
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
            if (!videoWatched && video.currentTime > video.duration * 0.95) {
                // Mark as watched when 95% complete
                fetch('', {
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
                fetch('', {
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
            const activityComplete = <?= $is_activity_complete ? 'true' : 'false' ?>;
            if (videoWatched && activityComplete) {
                nextBtn.classList.add('enabled');
            }
        }

        // Initial check
        updateNextButton();
        </script>
        <?php endif; ?>

        <!-- Chapter Content (if any) -->
        <?php if ($current_chapter['content_html']): ?>
        <div class="dynamic-content">
            <?= $current_chapter['content_html'] ?>
        </div>
        <?php endif; ?>

        <!-- Phishing Simulation Activity -->
        <div class="email-wrapper">
            <!-- Your existing email simulation code here -->
            <!-- (Keep the random template, email display, etc.) -->
            <!-- ... (paste your email-wrapper, toolbar, header, subject, body, attachment) ... -->
        </div>

        <!-- Action Buttons (Activity) -->
        <div class="action-section">
            <h3>What would you do with this email?</h3>
            <form method="POST" class="simulation-buttons">
                <!-- Your buttons -->
            </form>
        </div>

        <!-- Feedback & Takeaways (your existing code) -->

        <!-- Chapter Navigation -->
        <div class="chapter-nav">
            <div class="chapter-info">
                Chapter <?= $current_chapter_num ?> of <?= $total_chapters ?>
            </div>
            <div>
                <?php if ($current_chapter_num > 1): ?>
                <a href="?chapter=<?= $current_chapter_num - 1 ?>" class="nav-btn prev enabled">
                    <i class="fas fa-arrow-left"></i> Previous
                </a>
                <?php endif; ?>

                <?php if ($current_chapter_num < $total_chapters): ?>
                <a href="?chapter=<?= $current_chapter_num + 1 ?>" 
                   class="nav-btn <?= $next_enabled ? 'enabled' : '' ?>" 
                   id="nextBtn">
                    Next <i class="fas fa-arrow-right"></i>
                </a>
                <?php else: ?>
                <a href="../dashboard.php" class="nav-btn enabled">
                    Complete Module <i class="fas fa-check"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

</body>
</html>