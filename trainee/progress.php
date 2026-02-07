<?php
// trainee/progress.php - My Progress Page (Updated for Chapter-Based Modules)

require_once '../config/database.php';
require_once '../includes/functions.php';

require_login();

// Get DB connection
$pdo = getDBConnection();

// Fetch user + department
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.full_name, u.email, d.name AS department_name
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Error: User not found. Please contact administrator.");
}

$department = $user['department_name'] ?? 'Not Assigned';

// Fetch assigned active campaign
$stmt = $pdo->prepare("
    SELECT c.id, c.name, c.description, c.start_date, c.end_date
    FROM campaigns c
    WHERE c.status = 'active'
      AND (c.target_all = 1
           OR EXISTS (
               SELECT 1 FROM campaign_departments cd
               WHERE cd.campaign_id = c.id
                 AND cd.department_id = (SELECT department_id FROM users WHERE id = ?)
           )
      )
    ORDER BY c.start_date DESC
    LIMIT 1
");
$stmt->execute([$user_id]);
$current_campaign = $stmt->fetch(PDO::FETCH_ASSOC);

$campaign_name = $current_campaign ? $current_campaign['name'] : 'No active campaign';
$campaign_desc = $current_campaign ? ($current_campaign['description'] ?: 'No description') : '';
$campaign_dates = '';
if ($current_campaign) {
    $start = $current_campaign['start_date'] ? date('M j, Y', strtotime($current_campaign['start_date'])) : 'Not set';
    $end = $current_campaign['end_date'] ? date('M j, Y', strtotime($current_campaign['end_date'])) : 'Ongoing';
    $campaign_dates = "$start – $end";
}

// Fetch all active modules
$stmt = $pdo->query("SELECT id, code, title, description, estimated_minutes FROM training_modules WHERE is_active = 1 ORDER BY id");
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_modules = count($modules);

// Global chapter stats
$global_total_chapters = 0;
$global_completed_chapters = 0;
$completed_modules_count = 0;
$overall_score_sum = 0;
$scored_modules_count = 0;
$total_attempts_all = 0;

// Per-module data
$module_details = [];

foreach ($modules as $module) {
    $mid = $module['id'];

    // Total chapters for this module
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM module_chapters WHERE module_id = ?");
    $stmt->execute([$mid]);
    $total_chap = (int)$stmt->fetchColumn();
    $global_total_chapters += $total_chap;

    // Completed chapters for this module
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM user_chapter_progress ucp
        JOIN module_chapters mc ON ucp.chapter_id = mc.id
        WHERE ucp.user_id = ? AND mc.module_id = ?
          AND ucp.watched_seconds = -1 AND ucp.completed_at IS NOT NULL
    ");
    $stmt->execute([$user_id, $mid]);
    $comp_chap = (int)$stmt->fetchColumn();
    $global_completed_chapters += $comp_chap;

    $chap_progress = $total_chap > 0 ? round(($comp_chap / $total_chap) * 100) : 0;
    $is_module_completed = ($total_chap > 0 && $comp_chap == $total_chap);

    if ($is_module_completed) {
        $completed_modules_count++;
    }

    // Session-based stats (attempts, scores from full module completions)
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as attempts,
            SUM(CASE WHEN completed_at IS NOT NULL THEN 1 ELSE 0 END) as passed_count,
            MAX(final_score) as best_score,
            MAX(completed_at) as last_completed
        FROM training_sessions
        WHERE user_id = ? AND module_id = ?
    ");
    $stmt->execute([$user_id, $mid]);
    $sess_stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
        'attempts' => 0,
        'passed_count' => 0,
        'best_score' => null,
        'last_completed' => null
    ];

    $total_attempts_all += (int)$sess_stats['attempts'];

    $best_score = null;
    if ($is_module_completed && $sess_stats['best_score'] !== null) {
        $best_score = round($sess_stats['best_score']);
        $overall_score_sum += $best_score;
        $scored_modules_count++;
    }

    $last_activity = 'Not Started';
    if ($sess_stats['attempts'] > 0 || $comp_chap > 0) {
        if ($sess_stats['last_completed']) {
            $last_activity = date('M j, Y', strtotime($sess_stats['last_completed']));
        } else {
            $last_activity = 'In Progress';
        }
    }

    $btn_text = 'Start Module';
    if ($comp_chap > 0) {
        $btn_text = $is_module_completed ? 'Review Module' : 'Continue Module';
    }

    $module_details[$mid] = [
        'total_chap' => $total_chap,
        'comp_chap' => $comp_chap,
        'chap_progress' => $chap_progress,
        'is_module_completed' => $is_module_completed,
        'attempts' => $sess_stats['attempts'],
        'best_score' => $best_score,
        'last_activity' => $last_activity,
        'btn_text' => $btn_text
    ];
}

// Overall calculations
$overall_progress = $global_total_chapters > 0 ? round(($global_completed_chapters / $global_total_chapters) * 100) : 0;
$overall_avg_score = $scored_modules_count > 0 ? round($overall_score_sum / $scored_modules_count) : 0;

// Risk level logic
$risk_level = 'High';
$risk_class = 'high';
if ($overall_progress == 100) {
    if ($overall_avg_score >= 80) {
        $risk_level = 'Low';
        $risk_class = 'low';
    } else {
        $risk_level = 'Medium';
        $risk_class = 'medium';
    }
} elseif ($overall_progress >= 60) {
    $risk_level = 'Medium';
    $risk_class = 'medium';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Progress – CyberAware</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --brand: #FF8C42;
            --brand-soft: #FFC8A3;
            --ink: #111827;
            --muted: #6b7280;
            --surface: #ffffff;
            --line: rgba(15, 23, 42, 0.08);
            --shadow-sm: 0 8px 18px rgba(15, 23, 42, 0.08);
            --shadow-md: 0 18px 40px rgba(15, 23, 42, 0.12);
            --radius-lg: 22px;
            --radius-md: 16px;
            --sidebar-height: 72px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Manrope', 'Segoe UI', sans-serif;
            background: radial-gradient(1000px 560px at 90% -10%, #fff1e4 0%, transparent 60%),
                linear-gradient(140deg, #fff8f1 0%, #ffffff 100%);
            color: var(--ink);
            line-height: 1.6;
        }

        h1, h2, h3 {
            font-family: 'Space Grotesk', 'Segoe UI', sans-serif;
        }

        .main-content {
            margin-bottom: var(--sidebar-height);
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 28px 80px;
        }

        .hero {
            display: grid;
            gap: 24px;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            align-items: stretch;
            padding: 18px 0 30px;
        }

        .hero-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 26px;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(15, 23, 42, 0.06);
        }

        .hero-title {
            font-size: clamp(24px, 3vw, 36px);
            font-weight: 700;
        }

        .hero-subtitle {
            color: var(--muted);
            margin-top: 8px;
        }

        .pill-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 16px;
        }

        .pill {
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(255, 140, 66, 0.12);
            color: var(--ink);
            font-size: 12px;
            font-weight: 700;
            border: 1px solid rgba(255, 140, 66, 0.2);
        }

        .hero-panel {
            display: grid;
            gap: 16px;
        }

        .progress-card {
            background: #fff9f3;
            border-radius: var(--radius-md);
            padding: 20px;
            border: 1px solid rgba(255, 140, 66, 0.2);
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            font-weight: 700;
        }

        .progress-bar {
            height: 10px;
            background: #f4e6db;
            border-radius: 999px;
            overflow: hidden;
            margin: 12px 0;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--brand), #ff7a20);
            border-radius: 999px;
            transition: width 1.1s ease;
        }

        .progress-figure {
            font-size: 44px;
            font-weight: 800;
            color: var(--brand);
        }

        .risk-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .risk-low { background: rgba(255, 140, 66, 0.15); color: #9a4b10; }
        .risk-medium { background: rgba(255, 140, 66, 0.26); color: #9a4b10; }
        .risk-high { background: rgba(239, 68, 68, 0.15); color: #b91c1c; }

        .stats-row {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        }

        .stat-box {
            background: var(--surface);
            border-radius: 14px;
            padding: 16px;
            border: 1px solid rgba(15, 23, 42, 0.06);
            box-shadow: var(--shadow-sm);
        }

        .stat-box strong {
            display: block;
            font-size: 24px;
        }

        .section {
            margin-top: 36px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
            margin-bottom: 18px;
        }

        .section-title {
            font-size: 24px;
            font-weight: 700;
        }

        .section-subtitle {
            color: var(--muted);
            font-size: 14px;
        }

        .modules-grid {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        }

        .module-card {
            background: var(--surface);
            border-radius: var(--radius-md);
            padding: 22px;
            border: 1px solid rgba(15, 23, 42, 0.06);
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .module-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-md);
        }

        .module-title {
            font-size: 18px;
            font-weight: 700;
        }

        .module-desc {
            font-size: 13px;
            color: var(--muted);
            margin-top: 6px;
        }

        .module-progress-bar {
            height: 8px;
            background: #f4e6db;
            border-radius: 999px;
            overflow: hidden;
            margin-top: 10px;
        }

        .module-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--brand), #ff7a20);
            border-radius: 999px;
            transition: width 0.8s ease;
        }

        .module-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 14px;
            text-align: center;
            font-size: 12px;
            color: var(--muted);
        }

        .module-stats strong {
            display: block;
            font-size: 16px;
            color: var(--ink);
        }

        .start-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 16px;
            padding: 10px 14px;
            border-radius: 999px;
            background: var(--brand);
            color: white;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
        }

        .completed-badge {
            background: rgba(255, 140, 66, 0.16);
            color: #9a4b10;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }

        .campaign-card {
            margin-top: 24px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 248, 241, 0.9));
            border-radius: var(--radius-md);
            padding: 22px;
            border: 1px solid rgba(255, 140, 66, 0.18);
            box-shadow: var(--shadow-sm);
        }

        @media (max-width: 768px) {
            .container { padding: 24px 20px 80px; }
            .section-header { flex-direction: column; align-items: flex-start; }
            .module-stats { grid-template-columns: repeat(3, 1fr); }
        }
    </style>
</head>
<body>

<?php include 'trainee-sidebar.php'; ?>

<main class="main-content">
    <div class="container">
        <section class="hero">
            <div class="hero-card">
                <h1 class="hero-title">Learning progress</h1>
                <p class="hero-subtitle">Track your momentum, build mastery, and keep your training streak alive.</p>
                <div class="pill-row">
                    <span class="pill"><?= htmlspecialchars($department) ?></span>
                    <span class="pill"><?= htmlspecialchars($campaign_name) ?></span>
                    <span class="pill"><?= $global_completed_chapters ?> chapters complete</span>
                </div>
            </div>
            <div class="hero-card hero-panel">
                <div class="progress-card">
                    <div class="progress-header">
                        <span>Overall mastery</span>
                        <span><?= $overall_progress ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= $overall_progress ?>%"></div>
                    </div>
                    <div class="progress-figure"><?= $overall_progress ?>%</div>
                </div>
                <div class="risk-badge risk-<?= $risk_class ?>">
                    <i class="fas fa-<?= $risk_class === 'low' ? 'shield-alt' : ($risk_class === 'medium' ? 'exclamation-triangle' : 'alert-circle') ?>"></i>
                    Risk level: <?= $risk_level ?>
                </div>
                <div class="stats-row">
                    <div class="stat-box">
                        <strong><?= $completed_modules_count ?>/<?= $total_modules ?></strong>
                        Modules complete
                    </div>
                    <div class="stat-box">
                        <strong><?= $global_completed_chapters ?>/<?= $global_total_chapters ?></strong>
                        Chapters done
                    </div>
                    <div class="stat-box">
                        <strong><?= $overall_avg_score ?>%</strong>
                        Avg score
                    </div>
                    <div class="stat-box">
                        <strong><?= $total_attempts_all ?></strong>
                        Attempts
                    </div>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="section-header">
                <div>
                    <div class="section-title">Module map</div>
                    <div class="section-subtitle">Pick up the next lesson or revisit a completed one.</div>
                </div>
            </div>

            <div class="modules-grid">
                <?php foreach ($modules as $module):
                    $mid = $module['id'];
                    $det = $module_details[$mid];
                ?>
                <div class="module-card">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:10px;">
                        <div>
                            <div class="module-title"><?= htmlspecialchars($module['title']) ?></div>
                            <?php if ($module['description']): ?>
                            <div class="module-desc"><?= htmlspecialchars($module['description']) ?></div>
                            <?php endif; ?>
                        </div>
                        <?php if ($det['is_module_completed']): ?>
                        <span class="completed-badge">Completed</span>
                        <?php endif; ?>
                    </div>

                    <div style="margin-top:12px; font-size:12px; color:var(--muted);">
                        <?= $det['comp_chap'] ?> / <?= $det['total_chap'] ?> chapters
                    </div>

                    <div class="module-progress-bar">
                        <div class="module-progress-fill" style="width: <?= $det['chap_progress'] ?>%"></div>
                    </div>

                    <div class="module-stats">
                        <div><strong><?= $det['attempts'] ?></strong>Attempts</div>
                        <div><strong><?= $det['best_score'] !== null ? $det['best_score'] . '%' : ($det['comp_chap'] > 0 ? 'In progress' : 'New') ?></strong>Best</div>
                        <div><strong><?= htmlspecialchars($det['last_activity']) ?></strong>Last</div>
                    </div>

                    <a href="modules/<?= strtolower($module['code']) ?>.php" class="start-btn">
                        <?= $det['btn_text'] ?>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <?php if ($current_campaign): ?>
        <section class="section">
            <div class="campaign-card">
                <h3><i class="fas fa-rocket"></i> Current exercise</h3>
                <p style="margin-top:8px; color: var(--muted);">
                    <?= htmlspecialchars($campaign_desc) ?>
                </p>
                <p style="margin-top:8px; font-weight:600;">Duration: <?= htmlspecialchars($campaign_dates) ?></p>
            </div>
        </section>
        <?php endif; ?>
    </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.progress-fill, .module-progress-fill').forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => bar.style.width = width, 100);
    });
});
</script>

</body>
</html>