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

$name = $user['full_name'] ?? $user['username'] ?? 'Trainee';
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

    $total_attempts_all += $sess_stats['attempts'];

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
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --primary: #FF8C42;
            --primary-dark: #E67A2E;
            --primary-light: #FFF4ED;
            --success: #00A65A;
            --warning: #F39C12;
            --danger: #DD4B39;
            --dark: #2C2C2C;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-600: #475569;
            --gray-700: #334155;
            --sidebar-width: 260px;
        }

        * { margin:0; padding:0; box-sizing:border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--gray-100);
            color: var(--dark);
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
            background: white;
            border-bottom: 1px solid var(--gray-200);
            padding: 20px 32px;
            position: sticky;
            top: 0;
            z-index: 90;
        }

        .page-title { font-size: 24px; font-weight: 600; }
        .page-subtitle { font-size: 13px; color: var(--gray-600); margin-top: 4px; }

        .container { padding: 32px; }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .info-card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
        }

        .info-card i {
            font-size: 32px;
            color: var(--primary);
            margin-bottom: 12px;
        }

        .info-label { font-size: 14px; color: var(--gray-600); font-weight: 500; }
        .info-value { font-size: 18px; font-weight: 600; margin-top: 8px; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
        }

        .stat-card i {
            font-size: 28px;
            color: var(--primary);
            margin-bottom: 12px;
        }

        .stat-value { font-size: 32px; font-weight: 700; color: var(--dark); }
        .stat-label { font-size: 14px; color: var(--gray-600); margin-top: 8px; }

        .overall-card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 32px;
            text-align: center;
            margin-bottom: 32px;
        }

        .progress-container {
            margin: 24px 0;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 15px;
            font-weight: 600;
        }

        .progress-bar {
            height: 16px;
            background: var(--gray-200);
            border-radius: 8px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            border-radius: 8px;
            transition: width 1.5s ease;
        }

        .progress-percent {
            font-size: 48px;
            font-weight: 700;
            color: var(--primary);
            margin: 16px 0;
        }

        .risk-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 600;
        }

        .risk-low { background: #dcfce7; color: #166534; }
        .risk-medium { background: #fffbeb; color: #92400e; }
        .risk-high { background: #fee2e2; color: #991b1b; }

        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
        }

        .module-card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 24px;
        }

        .module-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .module-title {
            font-size: 18px;
            font-weight: 600;
        }

        .module-desc {
            font-size: 14px;
            color: var(--gray-600);
            margin-top: 8px;
        }

        .chapters-info {
            font-size: 14px;
            color: var(--gray-600);
            margin-bottom: 12px;
        }

        .module-progress {
            margin: 16px 0;
        }

        .module-progress-bar {
            height: 10px;
            background: var(--gray-200);
            border-radius: 5px;
            overflow: hidden;
            margin-top: 8px;
        }

        .module-progress-fill {
            height: 100%;
            background: var(--primary);
            border-radius: 5px;
            transition: width 0.8s ease;
        }

        .module-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 20px;
            text-align: center;
        }

        .stat-small {
            font-size: 20px;
            font-weight: 600;
            color: var(--primary-dark);
        }

        .stat-small-label {
            font-size: 13px;
            color: var(--gray-600);
            margin-top: 4px;
        }

        .start-btn {
            display: block;
            margin-top: 20px;
            padding: 12px;
            background: var(--primary);
            color: white;
            text-align: center;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.3s;
        }

        .start-btn:hover {
            background: var(--primary-dark);
        }

        .completed-badge {
            background: var(--success);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
    </style>
</head>
<body>

<?php include 'trainee-sidebar.php'; ?>

<main class="main-content">
    <header class="header">
        <div>
            <h2 class="page-title">My Training Progress</h2>
            <p class="page-subtitle">Hello <?= htmlspecialchars($name) ?>, track your security awareness journey</p>
        </div>
    </header>

    <div class="container">
        <!-- Department & Campaign Info -->
        <div class="info-grid">
            <div class="info-card">
                <i class="fas fa-building"></i>
                <div class="info-label">Department</div>
                <div class="info-value"><?= htmlspecialchars($department) ?></div>
            </div>

            <div class="info-card">
                <i class="fas fa-bullhorn"></i>
                <div class="info-label">Current Campaign</div>
                <div class="info-value"><?= htmlspecialchars($campaign_name) ?></div>
                <?php if ($current_campaign): ?>
                <div style="font-size:13px; color:var(--gray-600); margin-top:8px;">
                    <?= htmlspecialchars($campaign_dates) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Overall Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-chart-pie"></i>
                <div class="stat-value"><?= $overall_progress ?>%</div>
                <div class="stat-label">Overall Chapter Progress</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-tasks"></i>
                <div class="stat-value"><?= $global_completed_chapters ?> / <?= $global_total_chapters ?></div>
                <div class="stat-label">Chapters Completed</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-double"></i>
                <div class="stat-value"><?= $completed_modules_count ?> / <?= $total_modules ?></div>
                <div class="stat-label">Modules Completed</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-trophy"></i>
                <div class="stat-value"><?= $overall_avg_score ?>%</div>
                <div class="stat-label">Avg Score (Completed Modules)</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-shield-alt"></i>
                <div class="stat-value">
                    <div class="risk-badge risk-<?= $risk_class ?>">
                        <i class="fas fa-<?= $risk_class === 'low' ? 'check' : ($risk_class === 'medium' ? 'exclamation-triangle' : 'times') ?>"></i>
                        <?= $risk_level ?> Risk
                    </div>
                </div>
                <div class="stat-label">Security Risk Level</div>
            </div>
        </div>

        <!-- Overall Progress Visualization -->
        <div class="overall-card">
            <h3 style="font-size:22px; margin-bottom:20px;">
                <i class="fas fa-chart-line"></i> Overall Chapter Progress
            </h3>
            <p style="color:var(--gray-600); margin-bottom:20px;">
                Based on completed chapters across all modules (video watched + activity passed)
            </p>

            <div class="progress-container">
                <div class="progress-header">
                    <span>Completion Progress</span>
                    <span><?= $overall_progress ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $overall_progress ?>%"></div>
                </div>
                <div class="progress-percent"><?= $overall_progress ?>%</div>
            </div>
        </div>

        <!-- Module Breakdown -->
        <h3 style="font-size:22px; margin:40px 0 24px;">
            <i class="fas fa-list-alt"></i> Module Details
        </h3>

        <div class="modules-grid">
            <?php foreach ($modules as $module): 
                $mid = $module['id'];
                $det = $module_details[$mid];
            ?>
            <div class="module-card">
                <div class="module-header">
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

                <div class="chapters-info">
                    Chapters Completed: <strong><?= $det['comp_chap'] ?> / <?= $det['total_chap'] ?></strong>
                </div>

                <div class="module-progress">
                    <div style="display:flex; justify-content:space-between; font-size:14px; margin-bottom:8px;">
                        <span>Progress</span>
                        <span><?= $det['chap_progress'] ?>%</span>
                    </div>
                    <div class="module-progress-bar">
                        <div class="module-progress-fill" style="width: <?= $det['chap_progress'] ?>%"></div>
                    </div>
                </div>

                <div class="module-stats">
                    <div>
                        <div class="stat-small"><?= $det['attempts'] ?></div>
                        <div class="stat-small-label">Attempts</div>
                    </div>
                    <div>
                        <div class="stat-small">
                            <?= $det['best_score'] !== null ? $det['best_score'] . '%' : ($det['comp_chap'] > 0 ? 'In Progress' : 'Not Started') ?>
                        </div>
                        <div class="stat-small-label">Best Score</div>
                    </div>
                    <div>
                        <div class="stat-small"><?= htmlspecialchars($det['last_activity']) ?></div>
                        <div class="stat-small-label">Last Activity</div>
                    </div>
                </div>

                <a href="modules/<?= strtolower($module['code']) ?>.php" class="start-btn">
                    <?= $det['btn_text'] ?>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($current_campaign): ?>
        <div class="overall-card" style="margin-top:40px;">
            <h3 style="font-size:20px;">
                <i class="fas fa-bullhorn"></i> Current Campaign: <?= htmlspecialchars($campaign_name) ?>
            </h3>
            <p style="margin:16px 0; color:var(--gray-700);"><?= htmlspecialchars($campaign_desc) ?></p>
            <p style="color:var(--gray-600);">Duration: <?= htmlspecialchars($campaign_dates) ?></p>
        </div>
        <?php endif; ?>
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