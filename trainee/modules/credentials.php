<?php
// modules/credentials.php - Chapter-Based Credential Harvesting Simulation (Improved Navigation UX)
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

$module_id = 2; // CRED_001

// Load module title & description
$stmt = $pdo->prepare("SELECT title, description FROM training_modules WHERE id = ?");
$stmt->execute([$module_id]);
$module = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$module) {
    die("Module not found.");
}
$module_title = $module['title'] ?? 'Credential Harvesting Awareness';
$module_desc = $module['description'] ?? 'Learn to identify fake login pages and protect your credentials';

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

// Handle user decision
$feedback = null;
$feedback_class = '';
$show_takeaways = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['decision'])) {
    $decision = $_POST['decision'];
    $is_correct = ($decision === 'no');
    $points = $is_correct ? 15 : -10;

    // Feedback
    if ($is_correct) {
        $feedback = "Excellent choice! Never enter credentials on unexpected or suspicious login pages.";
        $feedback_class = 'success';
    } else {
        $feedback = "This was a simulated credential harvesting page.<br>Entering your credentials would give attackers full access to your account.";
        $feedback_class = 'danger';
    }

    // Create session if not exists (one session per module attempt)
    if (!isset($_SESSION['current_credential_session'])) {
        $stmt = $pdo->prepare("
            INSERT INTO training_sessions (user_id, module_id, started_at, final_score)
            VALUES (?, ?, NOW(), 0)
        ");
        $stmt->execute([$user_id, $module_id]);
        $session_id = $pdo->lastInsertId();
        $_SESSION['current_credential_session'] = $session_id;
    } else {
        $session_id = $_SESSION['current_credential_session'];
    }

    // Log action
    $stmt = $pdo->prepare("
        INSERT INTO training_actions
        (session_id, step_number, action_type, action_value, is_correct, points, timestamp)
        VALUES (?, ?, 'credential_decision', ?, ?, ?, NOW())
    ");
    $stmt->execute([$session_id, $current_chapter_num, $decision, $is_correct ? 1 : 0, $points]);

    // Update session score
    $stmt = $pdo->prepare("
        UPDATE training_sessions
        SET final_score = final_score + ?
        WHERE id = ?
    ");
    $stmt->execute([$points, $session_id]);

    // Only mark chapter complete on correct decision
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
if (isset($_SESSION['current_credential_session'])) {
    $session_id = $_SESSION['current_credential_session'];
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
        unset($_SESSION['current_credential_session']);
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

// Scenarios per chapter (expand as needed)
$scenarios = [
    1 => [
        'scenario_text' => 'You received an urgent email warning that your account will be suspended unless you log in immediately to verify your details.',
        'scheme' => 'https',
        'domain' => 'login-companny.co.zm',
        'path' => '/secure/verify',
        'has_lock' => true,
        'specific_flag' => 'Domain misspelling: "companny" instead of "company"',
    ],
    2 => [
        'scenario_text' => 'An email from "IT Support" asks you to log in to complete a required security update.',
        'scheme' => 'http',
        'domain' => 'company.co.zm',
        'path' => '/login',
        'has_lock' => false,
        'specific_flag' => 'No HTTPS connection (missing padlock icon)',
    ],
    3 => [
        'scenario_text' => 'You clicked a link in a shared document that directs you to this login page for "continued access".',
        'scheme' => 'https',
        'domain' => '192.168.1.100',
        'path' => '/signin',
        'has_lock' => true,
        'specific_flag' => 'IP address used instead of a proper domain name',
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
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --cyber-yellow: #FF8C42;
            --primary: #FF8C42;
            --primary-rgb: 255,140,66;
            --cyber-gold: #FF8C42;
            --white: #FFFFFF;
            --cyber-light: #F7F7FB;
            --dark-navy: #111827;
            --dark-slate: #374151;
            --success: #FF8C42;
            --success-light: #FFF1E4;
            --danger: #dc2626;
            --danger-light: #fef2f2;
            --warning: #FF8C42;
            --warning-light: #FFF4ED;
            --grey-50: #F3F4F6;
            --grey-100: #E5E7EB;
            --grey-200: #D1D5DB;
            --grey-300: #C8CBD1;
            --grey-600: #4b5563;
            --grey-700: #374151;
            --grey-800: #1f2937;
            --shadow-sm: 0 1px 2px 0 rgba(0,0,0,0.05);
            --shadow-md: 0 6px 18px rgba(0,0,0,0.09);
            --radius: 12px;
            --sidebar-width: 260px;
            --sidebar-height: 72px;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Manrope', system-ui, sans-serif;
            background: radial-gradient(1000px 520px at 85% -10%, #fff1e4 0%, transparent 60%),
                linear-gradient(135deg, #fff9f3 0%, #ffffff 100%);
            color: var(--gray-800);
            line-height: 1.6;
        }
        .main-content { margin-bottom: var(--sidebar-height); padding: 2.5rem 2rem; min-height: 100vh; }
        .container { max-width: 1100px; margin: 0 auto; }
        .page-header {
            background: linear-gradient(135deg, #fff3ea 0%, #ffffff 100%);
            border-radius: var(--radius);
            padding: 3rem 2.5rem;
            margin-bottom: 2.5rem;
            box-shadow: var(--shadow-md);
            color: var(--dark-navy);
            text-align: center;
            border: 1px solid rgba(255,140,66,0.2);
        }
        .page-header h2 { font-size: 2.4rem; font-weight: 700; margin-bottom: 0.8rem; color: var(--dark-navy); }
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
        .scenario-box {
            background: linear-gradient(135deg, var(--warning-light), white);
            border: 2px solid var(--cyber-yellow);
            border-radius: var(--radius);
            padding: 2rem;
            margin-bottom: 2.5rem;
            box-shadow: var(--shadow-sm);
        }
        .scenario-box h3 { font-size: 1.4rem; color: #92400e; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.8rem; }
        .browser-mockup { background: white; border-radius: var(--radius); box-shadow: var(--shadow-md); overflow: hidden; margin-bottom: 2rem; }
        .browser-header { background: var(--gray-100); padding: 1rem; display: flex; align-items: center; gap: 1rem; }
        .browser-dots { display: flex; gap: 0.5rem; }
        .browser-dot { width: 12px; height: 12px; border-radius: 50%; }
        .dot-red { background: #ef4444; }
        .dot-yellow { background: var(--cyber-yellow); }
        .dot-green { background: #FF8C42; }
        .browser-url-bar {
            flex: 1; background: white; border: 1px solid var(--gray-300); border-radius: 6px;
            padding: 0.6rem 1rem; display: flex; align-items: center; gap: 0.6rem; font-size: 0.95rem;
        }
        .url-lock { color: #FF8C42; }
        .url-domain { color: var(--danger); font-weight: 600; }
        .login-page { background: var(--gray-50); padding: 4rem 2rem; display: flex; justify-content: center; }
        .login-box {
            background: white; border-radius: var(--radius); box-shadow: var(--shadow-md);
            padding: 3rem; width: 100%; max-width: 460px; border-left: 5px solid var(--cyber-yellow);
        }
        .login-logo { text-align: center; margin-bottom: 2rem; }
        .login-logo i { font-size: 3.5rem; color: var(--primary); }
        .login-title { text-align: center; font-size: 1.8rem; font-weight: 700; margin-bottom: 0.5rem; }
        .login-subtitle { text-align: center; color: var(--gray-600); margin-bottom: 2.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
        .form-group input {
            width: 100%; padding: 1rem; border: 2px solid var(--gray-300);
            border-radius: 6px; font-size: 1.1rem;
        }
        .login-btn {
            width: 100%; padding: 1.2rem; background: var(--primary); color: white;
            border: none; border-radius: 6px; font-size: 1.1rem; font-weight: 600; margin-top: 1rem;
        }
        .action-section { margin: 3rem 0; text-align: center; }
        .decision-buttons { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; max-width: 800px; margin: 2rem auto; }
        .btn { padding: 1.3rem; font-size: 1.1rem; font-weight: 600; border-radius: var(--radius); border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.8rem; }
        .btn-yes { background: var(--danger); color: white; }
        .btn-yes:hover { background: #b91c1c; transform: translateY(-4px); }
        .btn-no { background: var(--success); color: white; }
        .btn-no:hover { background: #ff7a20; transform: translateY(-4px); }
        .feedback { padding: 2rem; border-radius: var(--radius); margin: 2rem 0; font-size: 1.2rem; line-height: 1.7; border-left: 6px solid; box-shadow: var(--shadow-md); display: flex; gap: 1.2rem; align-items: start; }
        .feedback.success { background: var(--success-light); border-color: var(--success); color: #166534; }
        .feedback.danger { background: var(--danger-light); border-color: var(--danger); color: #7f1d1d; }
        .feedback i { font-size: 2rem; margin-top: 0.3rem; }
        .dynamic-content { margin-top: 3rem; padding: 2.5rem; background: white; border-radius: var(--radius); box-shadow: var(--shadow-md); border: 1px solid var(--gray-200); }
        .dynamic-content h3 { font-size: 1.8rem; color: var(--warning); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.8rem; }
        .chapter-nav { display: flex; justify-content: space-between; align-items: center; margin: 3rem 0; padding: 1.5rem; background: white; border-radius: 12px; box-shadow: var(--shadow-sm); }
        .chapter-info { font-size: 1.2rem; font-weight: 600; }
        .nav-btn { padding: 0.9rem 1.8rem; background: var(--primary); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; opacity: 0.5; pointer-events: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .nav-btn.enabled { opacity: 1; pointer-events: auto; }
        .nav-btn.enabled:hover { background: var(--primary-dark); }
        .nav-btn.prev { background: var(--gray-600); }
        .nav-btn.prev.enabled:hover { background: var(--gray-700); }
        @media (max-width: 992px) {
            .main-content { margin-left: 0; padding: 1.5rem; }
            .decision-buttons { grid-template-columns: 1fr; }
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
            <h2><i class="fas fa-user-lock"></i> Chapter <?= $current_chapter_num ?>: <?= htmlspecialchars($current_chapter['title']) ?></h2>
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

        <!-- Scenario -->
        <div class="scenario-box">
            <h3><i class="fas fa-exclamation-triangle"></i> Scenario</h3>
            <p><?= htmlspecialchars($scenario['scenario_text']) ?> Here's the login page the link directs you to:</p>
        </div>

        <!-- Browser Mockup -->
        <div class="browser-mockup">
            <div class="browser-header">
                <div class="browser-dots">
                    <div class="browser-dot dot-red"></div>
                    <div class="browser-dot dot-yellow"></div>
                    <div class="browser-dot dot-green"></div>
                </div>
                <div class="browser-url-bar">
                    <span><?= htmlspecialchars($scenario['scheme']) ?>://</span>
                    <?php if ($scenario['has_lock']): ?>
                    <i class="fas fa-lock url-lock"></i>
                    <?php endif; ?>
                    <span class="url-domain"><?= htmlspecialchars($scenario['domain']) ?></span>
                    <span><?= htmlspecialchars($scenario['path']) ?></span>
                </div>
            </div>

            <div class="login-page">
                <div class="login-box">
                    <div class="login-logo">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="login-title">Sign In to Your Account</div>
                    <div class="login-subtitle">Please enter your credentials to continue</div>

                    <div class="form-group">
                        <label>Username or Email</label>
                        <input type="text" placeholder="your.name@company.com" disabled>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" placeholder="••••••••••••" disabled>
                    </div>

                    <button class="login-btn" disabled>Sign In</button>
                </div>
            </div>
        </div>

        <!-- User Decision -->
        <div class="action-section">
            <h3>Would you enter your real credentials on this page?</h3>
            <form method="POST" class="decision-buttons">
                <button type="submit" name="decision" value="yes" class="btn btn-yes">
                    <i class="fas fa-key"></i> Yes – Enter Credentials
                </button>
                <button type="submit" name="decision" value="no" class="btn btn-no">
                    <i class="fas fa-ban"></i> No – Do Not Enter
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

        <!-- Key Takeaways (shown only after correct decision) -->
        <?php if ($show_takeaways): ?>
        <div class="dynamic-content">
            <h3><i class="fas fa-lightbulb"></i> Key Learning Points</h3>
            <ul style="font-size:1.1rem; line-height:1.8; padding-left:1.5rem;">
                <li><strong>Specific red flag in this scenario:</strong> <?= htmlspecialchars($scenario['specific_flag']) ?></li>
                <li>Always verify the full URL before entering credentials – hover over links in emails.</li>
                <li>Legitimate services rarely ask for login via unexpected emails.</li>
                <li>Check for HTTPS (padlock), correct domain spelling, and no IP addresses.</li>
                <li>Urgency and threats are common tactics to bypass caution.</li>
                <li>Best action: close the page and report the suspicious email to IT/security.</li>
            </ul>
            <div style="margin-top:2rem; padding:1.5rem; background:var(--warning-light); border-radius:var(--radius); border-left:4px solid var(--warning);">
                <strong><i class="fas fa-skull-crossbones"></i> Real-World Impact</strong>
                <p>Credential theft can lead to account takeover, data breaches, financial fraud, and lateral movement across the entire organization.</p>
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