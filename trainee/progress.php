<?php
// trainee/progress.php - Learning Progress with Module Stats

require_once '../config/database.php';
require_once '../includes/functions.php';
require_login();

if ($_SESSION['role'] !== 'trainee') {
    header('Location: ../pages/login.php');
    exit;
}

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get user info
$stmt = $pdo->prepare("SELECT full_name, username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get all modules with progress - prioritize registered modules, then show all attempted
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(umr.module_id, ump.module_id) as module_id,
        COALESCE(umr.registration_order, 1) as registration_order,
        CASE 
            WHEN ump.passed = 1 THEN 'passed' 
            WHEN ump.id IS NOT NULL THEN 'in_progress'
            ELSE 'pending'
        END as status,
        ump.last_attempt_date as started_at,
        ump.passed_date as completed_at,
        tm.title,
        tm.code,
        tm.category,
        COALESCE(ump.total_attempts, 0) as total_attempts,
        COALESCE(ump.best_score, 0) as best_score,
        COALESCE(ump.average_score, 0) as average_score,
        COALESCE(ump.passed, 0) as passed,
        ump.passed_date,
        ump.last_attempt_date
    FROM training_modules tm
    LEFT JOIN user_module_registrations umr ON tm.id = umr.module_id AND umr.user_id = ?
    LEFT JOIN user_module_progress ump ON tm.id = ump.module_id AND ump.user_id = ?
    WHERE umr.user_id = ? OR ump.user_id = ?
    ORDER BY COALESCE(umr.registration_order, 999), ump.last_attempt_date DESC
");
$stmt->execute([$user_id, $user_id, $user_id, $user_id]);
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate overall stats
$total_registered = count($modules);
$total_passed = 0;
$total_attempts = 0;
$total_score = 0;
$score_count = 0;

foreach ($modules as $mod) {
    if ((int)$mod['passed'] === 1) {
        $total_passed++;
    }
    $total_attempts += (int)$mod['total_attempts'];
    if ($mod['average_score'] > 0) {
        $total_score += (float)$mod['average_score'];
        $score_count++;
    }
}

$overall_average = $score_count > 0 ? round($total_score / $score_count) : 0;
$completion_percentage = $total_registered > 0 ? round(($total_passed / $total_registered) * 100) : 0;
$mastery_level = $overall_average >= 80 ? 'Expert' : ($overall_average >= 70 ? 'Proficient' : ($overall_average >= 60 ? 'Competent' : 'Beginner'));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning Progress – CyberAware</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --orange: #FF8C42;
            --dark: #1A1A2E;
            --grey: #F5F5F5;
            --text: #333;
            --success: #10b981;
            --warning: #f59e0b;
        }
        body {
            font-family: 'Manrope', sans-serif;
            background: linear-gradient(135deg, #FFF4EC 0%, #fff 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding-bottom: 100px;
        }
        }
        .header {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        .header h1 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 28px;
            color: var(--dark);
            margin-bottom: 10px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            text-align: center;
        }
        .stat-value {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 32px;
            font-weight: 700;
            color: var(--orange);
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 13px;
            color: #888;
            text-transform: uppercase;
            font-weight: 600;
        }
        .progress-ring {
            width: 120px;
            height: 120px;
            margin: 0 auto 15px;
        }
        .progress-ring svg {
            transform: rotate(-90deg);
        }
        .progress-ring-circle {
            fill: none;
            stroke-width: 8;
        }
        .progress-ring-bg {
            stroke: #e5e7eb;
        }
        .progress-ring-fill {
            stroke: var(--orange);
            stroke-linecap: round;
            transition: stroke-dashoffset 0.35s;
        }
        .modules-section {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        .modules-section h2 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 20px;
            color: var(--dark);
            margin-bottom: 20px;
        }
        .module-item {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 20px;
            padding: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 15px;
            align-items: center;
        }
        .module-info h3 {
            font-size: 16px;
            color: var(--dark);
            margin-bottom: 8px;
        }
        .module-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            font-size: 13px;
            color: #888;
        }
        .module-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .module-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            text-align: center;
        }
        .stat-box {
            background: var(--grey);
            padding: 12px;
            border-radius: 8px;
        }
        .stat-box-value {
            font-weight: 700;
            font-size: 18px;
            color: var(--dark);
        }
        .stat-box-label {
            font-size: 11px;
            color: #888;
            text-transform: uppercase;
            margin-top: 4px;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }
        .status-passed {
            background: #d1fae5;
            color: #065f46;
        }
        .status-in-progress {
            background: #fef3c7;
            color: #92400e;
        }
        .status-pending {
            background: #e5e7eb;
            color: #374151;
        }
        .status-failed {
            background: #fee2e2;
            color: #991b1b;
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #888;
        }
        .empty-state i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 999px;
            border: none;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .btn-primary {
            background: var(--orange);
            color: white;
        }
        .btn-primary:hover {
            background: #E67A2E;
        }
    </style>
</head>
<body>

<?php include 'trainee-sidebar.php'; ?>

<div class="container">
    <div class="header">
        <h1>Your Learning Progress</h1>
        <p>Track your module completion, scores, and mastery level.</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= $total_passed ?>/<?= $total_registered ?></div>
            <div class="stat-label">Modules Passed</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $overall_average ?>%</div>
            <div class="stat-label">Average Score</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $total_attempts ?></div>
            <div class="stat-label">Total Attempts</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $mastery_level ?></div>
            <div class="stat-label">Mastery Level</div>
        </div>
    </div>

    <div class="modules-section">
        <h2>Module Progress</h2>
        
        <?php if (empty($modules)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>You haven't registered for any modules yet.</p>
                <a href="register-modules.php" class="btn btn-primary" style="margin-top: 15px;">
                    <i class="fas fa-plus"></i> Register for Modules
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($modules as $mod): ?>
                <div class="module-item">
                    <div class="module-info">
                        <h3><?= htmlspecialchars($mod['title']) ?></h3>
                        <div class="module-meta">
                            <span><i class="fas fa-hashtag"></i> <?= htmlspecialchars($mod['code']) ?></span>
                            <span><i class="fas fa-list-ol"></i> Module <?= $mod['registration_order'] ?></span>
                            <?php if ($mod['passed_date']): ?>
                                <span><i class="fas fa-calendar"></i> Passed <?= date('M j, Y', strtotime($mod['passed_date'])) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="module-stats">
                        <div class="stat-box">
                            <div class="stat-box-value"><?= (int)$mod['total_attempts'] ?></div>
                            <div class="stat-box-label">Attempts</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-box-value"><?= (int)$mod['best_score'] ?>%</div>
                            <div class="stat-box-label">Best Score</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-box-value"><?= round((float)$mod['average_score']) ?>%</div>
                            <div class="stat-box-label">Average</div>
                        </div>
                        <div>
                            <?php
                                $status_class = 'status-' . $mod['status'];
                                $status_icon = match($mod['status']) {
                                    'passed' => 'fa-check-circle',
                                    'in_progress' => 'fa-spinner',
                                    'failed' => 'fa-times-circle',
                                    default => 'fa-lock'
                                };
                                $status_text = ucfirst(str_replace('_', ' ', $mod['status']));
                            ?>
                            <span class="status-badge <?= $status_class ?>">
                                <i class="fas <?= $status_icon ?>"></i> <?= $status_text ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
