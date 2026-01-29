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
            /* Primary Brand Colors */
            --cyber-yellow: #FFD60A;
            --cyber-gold: #FFC300;
            --cyber-light: #FFF8DC;
            --white: #FFFFFF;
            
            /* Security Dark Tones */
            --dark-navy: #0F1419;
            --dark-slate: #1A1E2E;
            --charcoal: #2D3142;
            
            /* Accent Colors */
            --shield-green: #10B981;
            --alert-red: #EF4444;
            --info-blue: #3B82F6;
            
            /* Light Theme for Trainee Learning Platform */
            --light-bg: #F8F9FF;
            --light-accent: #F3F5FF;
            --card-bg: #FFFFFF;
            --text-dark: #1A1E2E;
            --text-muted: #5A6B7C;
            --border-light: rgba(15, 20, 25, 0.06);
            
            --sidebar-width: 260px;
        }

        * { margin:0; padding:0; box-sizing:border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, var(--light-bg) 0%, var(--light-accent) 100%);
            color: var(--dark-navy);
            line-height: 1.6;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            padding: 0;
        }

        @media (max-width: 992px) {
            .main-content { margin-left: 0; padding-bottom: 80px; }
        }

        .header {
            background: linear-gradient(135deg, var(--white) 0%, #F8FAFB 100%);
            border-bottom: 1px solid rgba(15, 20, 25, 0.08);
            padding: 28px 40px;
            position: sticky;
            top: 0;
            z-index: 90;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
        }

        .page-title { 
            font-size: 32px; 
            font-weight: 700; 
            color: var(--dark-navy);
            letter-spacing: -0.5px;
        }
        .page-subtitle { 
            font-size: 15px; 
            color: var(--text-muted); 
            margin-top: 6px; 
            font-weight: 400;
        }

        .container { padding: 40px 40px; }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 48px;
        }

        .info-card {
            background: var(--white);
            border: 2px solid rgba(255, 214, 10, 0.1);
            border-radius: 16px;
            padding: 28px;
            text-align: center;
            box-shadow: 0 2px 16px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .info-card:hover {
            border-color: var(--cyber-yellow);
            box-shadow: 0 8px 32px rgba(255, 214, 10, 0.12);
            transform: translateY(-4px);
        }

        .info-card i {
            font-size: 40px;
            color: var(--cyber-yellow);
            margin-bottom: 14px;
            display: block;
        }

        .info-label { 
            font-size: 13px; 
            color: var(--text-muted); 
            font-weight: 600; 
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 8px;
        }
        .info-value { 
            font-size: 24px; 
            font-weight: 700; 
            color: var(--dark-navy);
            margin-top: 8px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--white);
            border: 2px solid var(--border-light);
            border-radius: 14px;
            padding: 28px;
            text-align: center;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--cyber-yellow), var(--cyber-gold));
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
        }

        .stat-card i {
            font-size: 28px;
            color: var(--cyber-yellow);
            margin-bottom: 12px;
            display: block;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, rgba(255, 214, 10, 0.12) 0%, rgba(255, 195, 0, 0.06) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }

        .stat-value { 
            font-size: 40px; 
            font-weight: 800; 
            color: var(--cyber-yellow); 
            line-height: 1;
        }
        .stat-label { 
            font-size: 13px; 
            color: var(--text-muted); 
            font-weight: 600;
            margin-top: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .overall-card {
            background: var(--white);
            border: 2px solid var(--border-light);
            border-radius: 18px;
            padding: 40px;
            text-align: center;
            margin-bottom: 32px;
            box-shadow: 0 2px 16px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
        }

        .overall-card:hover {
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
        }

        .overall-card h3 {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 16px;
            color: var(--dark-navy);
            display: flex;
            align-items: center;
            gap: 14px;
            letter-spacing: -0.3px;
            justify-content: center;
        }

        .overall-card h3 i {
            color: var(--cyber-yellow);
            font-size: 32px;
        }

        .overall-card p {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 16px;
        }

        .progress-container {
            margin: 24px 0;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 15px;
            font-weight: 700;
            color: var(--dark-navy);
        }

        .progress-bar {
            height: 10px;
            background: #E8EDF5;
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--cyber-yellow), var(--cyber-gold));
            border-radius: 10px;
            transition: width 1.2s ease;
            box-shadow: 0 0 8px rgba(255, 214, 10, 0.4);
        }

        .progress-percent {
            font-size: 64px;
            font-weight: 800;
            color: var(--cyber-yellow);
            margin: 32px 0;
            text-shadow: 0 2px 8px rgba(255, 214, 10, 0.15);
            line-height: 1;
        }

        .risk-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .risk-badge i {
            font-size: 18px;
        }

        .risk-low { 
            background: rgba(16, 185, 129, 0.12);
            color: var(--shield-green);
        }
        .risk-medium { 
            background: rgba(245, 158, 11, 0.12);
            color: #F59E0B;
        }
        .risk-high { 
            background: rgba(239, 68, 68, 0.12);
            color: var(--alert-red);
        }

        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 24px;
        }

        .module-card {
            background: var(--white);
            border: 2px solid var(--border-light);
            border-radius: 18px;
            padding: 32px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 16px rgba(0, 0, 0, 0.06);
        }

        .module-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
        }

        .module-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .module-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--dark-navy);
            margin-bottom: 8px;
        }

        .module-desc {
            font-size: 14px;
            color: var(--text-muted);
            margin-top: 8px;
            line-height: 1.5;
        }

        .chapters-info {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 16px;
            font-weight: 600;
        }

        .module-progress {
            margin: 20px 0;
        }

        .module-progress-bar {
            height: 10px;
            background: #E8EDF5;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 8px;
        }

        .module-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--cyber-yellow), var(--cyber-gold));
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
            font-size: 22px;
            font-weight: 700;
            color: var(--cyber-yellow);
        }

        .stat-small-label {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 8px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .start-btn {
            display: block;
            margin-top: 24px;
            padding: 14px 24px;
            background: linear-gradient(135deg, var(--cyber-yellow) 0%, var(--cyber-gold) 100%);
            color: var(--dark-navy);
            text-align: center;
            border-radius: 10px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 15px;
            box-shadow: 0 4px 15px rgba(255, 214, 10, 0.2);
            border: none;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .start-btn:hover {
            background: linear-gradient(135deg, var(--cyber-gold) 0%, var(--cyber-yellow) 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 214, 10, 0.3);
        }

        .completed-badge {
            background: rgba(16, 185, 129, 0.15);
            color: var(--shield-green);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        @media (max-width: 992px) {
            .container {
                padding: 24px 24px;
            }

            .stats-grid,
            .info-grid {
                grid-template-columns: 1fr;
            }

            .modules-grid {
                grid-template-columns: 1fr;
            }

            .module-stats {
                grid-template-columns: repeat(3, 1fr);
            }

            .overall-card {
                padding: 28px;
            }

            .page-title {
                font-size: 26px;
            }

            .module-title {
                font-size: 20px;
            }
        }

        @media (max-width: 576px) {
            .page-title {
                font-size: 22px;
            }

            .progress-percent {
                font-size: 48px;
            }

            .stat-value {
                font-size: 32px;
            }

            .overall-card h3 {
                font-size: 22px;
            }

            .module-title {
                font-size: 18px;
            }

            .module-stats {
                grid-template-columns: repeat(3, 1fr);
                gap: 12px;
            }

            .stat-small {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>

<?php include 'trainee-sidebar.php'; ?>

<main class="main-content">
    <header class="header">
        <div>
            <h2 class="page-title">Learning Progress</h2>
            <p class="page-subtitle">Track your cybersecurity training achievements</p>
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
                <div style="font-size:13px; color:var(--text-muted); margin-top:8px;">
                    <?= htmlspecialchars($campaign_dates) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Overall Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <div class="stat-value"><?= $overall_progress ?>%</div>
                <div class="stat-label">Overall Progress</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="stat-value"><?= $global_completed_chapters ?>/<?= $global_total_chapters ?></div>
                <div class="stat-label">Chapters Done</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="stat-value"><?= $completed_modules_count ?>/<?= $total_modules ?></div>
                <div class="stat-label">Courses Done</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-value"><?= $overall_avg_score ?>%</div>
                <div class="stat-label">Avg Score</div>
            </div>
        </div>

        <!-- Overall Progress Visualization -->
        <div class="overall-card">
            <h3>
                <i class="fas fa-chart-line"></i> Learning Journey
            </h3>
            <p>
                Your progress across all courses and chapters
            </p>

            <div class="progress-container">
                <div class="progress-header">
                    <span>Overall Proficiency</span>
                    <span><?= $overall_progress ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $overall_progress ?>%"></div>
                </div>
                <div class="progress-percent"><?= $overall_progress ?>%</div>
            </div>

            <div class="risk-badge risk-<?= $risk_class ?>" style="margin-top: 28px;">
                <i class="fas fa-<?= $risk_class === 'low' ? 'shield-alt' : ($risk_class === 'medium' ? 'exclamation-triangle' : 'alert-circle') ?>"></i>
                Risk Level: <?= $risk_level ?>
            </div>
        </div>

        <!-- Module Breakdown -->
        <h3 style="font-size: 26px; font-weight: 700; margin: 48px 0 32px; color: var(--dark-navy); display: flex; align-items: center; gap: 14px; letter-spacing: -0.3px;">
            <i class="fas fa-book" style="color: var(--cyber-yellow); font-size: 32px;"></i> Course Breakdown
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
                    Progress: <strong><?= $det['comp_chap'] ?> / <?= $det['total_chap'] ?> Chapters</strong>
                </div>

                <div class="module-progress">
                    <div style="display:flex; justify-content:space-between; font-size:14px; font-weight: 600; margin-bottom:8px; color: var(--dark-navy);">
                        <span>Proficiency</span>
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
        <div class="overall-card" style="margin-top: 48px;">
            <h3>
                <i class="fas fa-rocket"></i> Current Exercise
            </h3>
            <p style="margin: 16px 0;"><?= htmlspecialchars($campaign_desc) ?></p>
            <p style="color: var(--text-muted);">Duration: <?= htmlspecialchars($campaign_dates) ?></p>
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