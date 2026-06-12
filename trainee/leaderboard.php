<?php
// trainee/leaderboard.php - Full Leaderboard with Detailed Stats

require_once '../config/database.php';
require_once '../includes/functions.php';
require_login();

if ($_SESSION['role'] !== 'trainee') {
    header('Location: ../pages/login.php');
    exit;
}

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get current user info
$stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();

// Get filter parameters
$filter = $_GET['filter'] ?? 'all_time'; // all_time, this_week, this_month
$sort = $_GET['sort'] ?? 'xp'; // xp, accuracy, modules, streak

// Determine date range
$date_filter = '';
$date_params = [];
switch ($filter) {
    case 'this_week':
        $date_filter = "AND ts.completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case 'this_month':
        $date_filter = "AND ts.completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
}

// Build leaderboard query based on sort
$sort_clause = '';
switch ($sort) {
    case 'accuracy':
        $sort_clause = "ORDER BY avg_accuracy DESC, total_xp DESC";
        break;
    case 'modules':
        $sort_clause = "ORDER BY modules_completed DESC, avg_accuracy DESC";
        break;
    case 'streak':
        $sort_clause = "ORDER BY current_streak DESC, total_xp DESC";
        break;
    default: // xp
        $sort_clause = "ORDER BY total_xp DESC, avg_accuracy DESC";
}

// Get leaderboard data
$query = "
    SELECT 
        u.id,
        u.full_name,
        u.username,
        d.name AS department,
        COUNT(DISTINCT ts.id) as total_sessions,
        COUNT(DISTINCT CASE WHEN ts.final_score >= 70 THEN ts.id END) as modules_completed,
        COALESCE(AVG(ts.final_score), 0) as avg_accuracy,
        COALESCE(SUM(CASE WHEN ts.final_score >= 70 THEN 140 ELSE 0 END) + 
                 COALESCE(SUM(ts.final_score * 0.04), 0), 0) as total_xp,
        MAX(ts.completed_at) as last_activity,
        COUNT(DISTINCT umr.module_id) as modules_registered,
        COALESCE(MAX(ump.best_score), 0) as best_score,
        COALESCE(COUNT(DISTINCT CASE WHEN ia.action = 'phish' AND ia.is_correct = 1 THEN ia.id END), 0) as phishing_correct,
        COALESCE(COUNT(DISTINCT ia.id), 0) as phishing_total
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.id
    LEFT JOIN training_sessions ts ON u.id = ts.user_id $date_filter
    LEFT JOIN user_module_registrations umr ON u.id = umr.user_id
    LEFT JOIN user_module_progress ump ON u.id = ump.user_id
    LEFT JOIN inbox_actions ia ON u.id = ia.user_id
    WHERE u.role = 'trainee' AND u.is_active = 1
    GROUP BY u.id
    $sort_clause
    LIMIT 100
";

$leaderboard = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Get current user's rank
$user_rank = 1;
foreach ($leaderboard as $index => $user) {
    if ($user['id'] == $user_id) {
        $user_rank = $index + 1;
        break;
    }
}

// Get overall stats
$stats_query = "
    SELECT 
        COUNT(DISTINCT u.id) as total_trainees,
        COALESCE(AVG(ts.final_score), 0) as avg_platform_score,
        COUNT(DISTINCT ts.id) as total_sessions,
        COUNT(DISTINCT CASE WHEN ts.final_score >= 70 THEN ts.id END) as total_completions
    FROM users u
    LEFT JOIN training_sessions ts ON u.id = ts.user_id
    WHERE u.role = 'trainee' AND u.is_active = 1
";

$platform_stats = $pdo->query($stats_query)->fetch();

// Get top performers by category
$top_by_xp = $pdo->query("
    SELECT u.full_name, u.username,
           COALESCE(SUM(CASE WHEN ts.final_score >= 70 THEN 140 ELSE 0 END) + 
                    COALESCE(SUM(ts.final_score * 0.04), 0), 0) as total_xp
    FROM users u
    LEFT JOIN training_sessions ts ON u.id = ts.user_id
    WHERE u.role = 'trainee' AND u.is_active = 1
    GROUP BY u.id
    ORDER BY total_xp DESC
    LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);

$top_by_accuracy = $pdo->query("
    SELECT u.full_name, u.username,
           COALESCE(AVG(ts.final_score), 0) as avg_accuracy,
           COUNT(DISTINCT ts.id) as attempts
    FROM users u
    LEFT JOIN training_sessions ts ON u.id = ts.user_id
    WHERE u.role = 'trainee' AND u.is_active = 1 AND ts.id IS NOT NULL
    GROUP BY u.id
    HAVING attempts >= 3
    ORDER BY avg_accuracy DESC
    LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);

$top_by_modules = $pdo->query("
    SELECT u.full_name, u.username,
           COUNT(DISTINCT CASE WHEN ump.passed = 1 THEN ump.module_id END) as modules_completed
    FROM users u
    LEFT JOIN user_module_progress ump ON u.id = ump.user_id
    WHERE u.role = 'trainee' AND u.is_active = 1
    GROUP BY u.id
    ORDER BY modules_completed DESC
    LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard — CyberAware</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --orange: #FF8C42;
            --dark: #1A1A2E;
            --grey: #F5F5F5;
            --grey2: #E8E8E8;
            --green: #10B981;
            --blue: #3B82F6;
            --purple: #8B5CF6;
            --red: #EF4444;
        }
        body {
            font-family: 'Manrope', sans-serif;
            background: linear-gradient(135deg, #FFF4EC 0%, #fff 100%);
            color: var(--dark);
            min-height: 100vh;
        }
        h1, h2, h3 { font-family: 'Space Grotesk', sans-serif; }
        .main-content {
            padding: 32px 24px 80px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            flex-wrap: wrap;
            gap: 16px;
        }
        .page-header h1 {
            font-size: 32px;
            font-weight: 700;
        }
        .header-controls {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 10px 16px;
            border-radius: 8px;
            border: 2px solid var(--grey2);
            background: white;
            color: var(--dark);
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            font-size: 14px;
        }
        .btn:hover {
            border-color: var(--orange);
            color: var(--orange);
        }
        .btn.active {
            background: var(--orange);
            color: white;
            border-color: var(--orange);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-left: 4px solid var(--orange);
        }
        .stat-card h3 {
            font-size: 13px;
            color: #999;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .stat-card .value {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
        }
        .stat-card .subtext {
            font-size: 12px;
            color: #999;
            margin-top: 4px;
        }
        .user-rank-card {
            background: linear-gradient(135deg, var(--orange), #FFB070);
            color: white;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 32px;
            box-shadow: 0 8px 24px rgba(255, 140, 66, 0.3);
        }
        .user-rank-card h2 {
            font-size: 18px;
            margin-bottom: 16px;
        }
        .rank-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
        }
        .rank-item {
            background: rgba(255, 255, 255, 0.2);
            padding: 12px;
            border-radius: 8px;
        }
        .rank-item-label {
            font-size: 12px;
            opacity: 0.9;
            margin-bottom: 4px;
        }
        .rank-item-value {
            font-size: 24px;
            font-weight: 700;
        }
        .top-performers {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        .performer-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .performer-card h3 {
            font-size: 16px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .performer-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .performer-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px;
            background: var(--grey);
            border-radius: 8px;
        }
        .performer-rank {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--orange);
            color: white;
            font-weight: 700;
            font-size: 14px;
            margin-right: 12px;
            flex-shrink: 0;
        }
        .performer-name {
            flex: 1;
            font-weight: 600;
        }
        .performer-value {
            font-weight: 700;
            color: var(--orange);
        }
        .leaderboard-section {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .leaderboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .leaderboard-header h2 {
            font-size: 22px;
        }
        .table-responsive {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: var(--grey);
            padding: 14px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #666;
        }
        td {
            padding: 14px;
            border-bottom: 1px solid var(--grey2);
        }
        tr:hover {
            background: #fafafa;
        }
        tr.current-user {
            background: #fffbf0;
            border-left: 4px solid var(--orange);
        }
        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--orange);
            color: white;
            font-weight: 700;
            font-size: 14px;
        }
        .rank-badge.top3 {
            background: linear-gradient(135deg, #FFD700, #FFA500);
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--orange);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
        }
        .user-details h4 {
            font-size: 14px;
            margin-bottom: 2px;
        }
        .user-details p {
            font-size: 12px;
            color: #999;
        }
        .stat-value {
            font-weight: 700;
            color: var(--dark);
        }
        .stat-bar {
            width: 100%;
            height: 6px;
            background: var(--grey2);
            border-radius: 3px;
            overflow: hidden;
            margin-top: 4px;
        }
        .stat-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--orange), #FFB070);
            border-radius: 3px;
        }
        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: #999;
        }
        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.3;
        }
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .header-controls {
                width: 100%;
            }
            .btn {
                flex: 1;
                justify-content: center;
            }
            .top-performers {
                grid-template-columns: 1fr;
            }
            table {
                font-size: 12px;
            }
            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>

<?php include 'trainee-sidebar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-trophy"></i> Leaderboard</h1>
        <div class="header-controls">
            <a href="?filter=all_time&sort=<?= $sort ?>" class="btn <?= $filter === 'all_time' ? 'active' : '' ?>">
                <i class="fas fa-infinity"></i> All Time
            </a>
            <a href="?filter=this_month&sort=<?= $sort ?>" class="btn <?= $filter === 'this_month' ? 'active' : '' ?>">
                <i class="fas fa-calendar"></i> This Month
            </a>
            <a href="?filter=this_week&sort=<?= $sort ?>" class="btn <?= $filter === 'this_week' ? 'active' : '' ?>">
                <i class="fas fa-calendar-week"></i> This Week
            </a>
        </div>
    </div>

    <!-- Platform Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>👥 Total Trainees</h3>
            <div class="value"><?= (int)$platform_stats['total_trainees'] ?></div>
        </div>
        <div class="stat-card">
            <h3><i class="fas fa-chart-bar"></i> Platform Avg Score</h3>
            <div class="value"><?= round($platform_stats['avg_platform_score']) ?>%</div>
        </div>
        <div class="stat-card">
            <h3><i class="fas fa-book"></i> Total Sessions</h3>
            <div class="value"><?= (int)$platform_stats['total_sessions'] ?></div>
        </div>
        <div class="stat-card">
            <h3><i class="fas fa-check"></i> Completions</h3>
            <div class="value"><?= (int)$platform_stats['total_completions'] ?></div>
        </div>
    </div>

    <!-- Your Rank -->
    <div class="user-rank-card">
        <h2>Your Performance</h2>
        <div class="rank-info">
            <div class="rank-item">
                <div class="rank-item-label">Your Rank</div>
                <div class="rank-item-value">#<?= $user_rank ?></div>
            </div>
            <div class="rank-item">
                <div class="rank-item-label">Total Trainees</div>
                <div class="rank-item-value"><?= count($leaderboard) ?></div>
            </div>
            <div class="rank-item">
                <div class="rank-item-label">Percentile</div>
                <div class="rank-item-value"><?= round((($user_rank - 1) / max(1, count($leaderboard))) * 100) ?>%</div>
            </div>
        </div>
    </div>

    <!-- Top Performers -->
    <div class="top-performers">
        <div class="performer-card">
            <h3><i class="fas fa-bolt" style="color: var(--orange);"></i> Top XP Earners</h3>
            <div class="performer-list">
                <?php foreach ($top_by_xp as $index => $performer): ?>
                    <div class="performer-item">
                        <div style="display: flex; align-items: center; flex: 1;">
                            <div class="performer-rank"><?= $index + 1 ?></div>
                            <div class="performer-name"><?= htmlspecialchars($performer['full_name']) ?></div>
                        </div>
                        <div class="performer-value"><?= round($performer['total_xp']) ?> XP</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="performer-card">
            <h3><i class="fas fa-bullseye" style="color: var(--blue);"></i> Highest Accuracy</h3>
            <div class="performer-list">
                <?php foreach ($top_by_accuracy as $index => $performer): ?>
                    <div class="performer-item">
                        <div style="display: flex; align-items: center; flex: 1;">
                            <div class="performer-rank"><?= $index + 1 ?></div>
                            <div class="performer-name"><?= htmlspecialchars($performer['full_name']) ?></div>
                        </div>
                        <div class="performer-value"><?= round($performer['avg_accuracy']) ?>%</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="performer-card">
            <h3><i class="fas fa-book" style="color: var(--green);"></i> Most Modules</h3>
            <div class="performer-list">
                <?php foreach ($top_by_modules as $index => $performer): ?>
                    <div class="performer-item">
                        <div style="display: flex; align-items: center; flex: 1;">
                            <div class="performer-rank"><?= $index + 1 ?></div>
                            <div class="performer-name"><?= htmlspecialchars($performer['full_name']) ?></div>
                        </div>
                        <div class="performer-value"><?= (int)$performer['modules_completed'] ?> modules</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Main Leaderboard -->
    <div class="leaderboard-section">
        <div class="leaderboard-header">
            <h2>Full Leaderboard</h2>
            <div style="display: flex; gap: 8px;">
                <a href="?filter=<?= $filter ?>&sort=xp" class="btn <?= $sort === 'xp' ? 'active' : '' ?>">
                    <i class="fas fa-bolt"></i> XP
                </a>
                <a href="?filter=<?= $filter ?>&sort=accuracy" class="btn <?= $sort === 'accuracy' ? 'active' : '' ?>">
                    <i class="fas fa-bullseye"></i> Accuracy
                </a>
                <a href="?filter=<?= $filter ?>&sort=modules" class="btn <?= $sort === 'modules' ? 'active' : '' ?>">
                    <i class="fas fa-book"></i> Modules
                </a>
            </div>
        </div>

        <?php if (empty($leaderboard)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No leaderboard data available yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">Rank</th>
                            <th>User</th>
                            <th>Department</th>
                            <th style="text-align: right;">XP</th>
                            <th style="text-align: right;">Accuracy</th>
                            <th style="text-align: right;">Modules</th>
                            <th style="text-align: right;">Sessions</th>
                            <th style="text-align: right;">Phishing</th>
                            <th style="text-align: right;">Last Activity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leaderboard as $index => $user): ?>
                        <tr <?= $user['id'] == $user_id ? 'class="current-user"' : '' ?>>
                            <td>
                                <div class="rank-badge <?= $index < 3 ? 'top3' : '' ?>">
                                    <?= $index + 1 ?>
                                </div>
                            </td>
                            <td>
                                <div class="user-info">
                                    <div class="user-avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
                                    <div class="user-details">
                                        <h4><?= htmlspecialchars($user['full_name']) ?></h4>
                                        <p>@<?= htmlspecialchars($user['username']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($user['department'] ?: '—') ?></td>
                            <td style="text-align: right;">
                                <div class="stat-value"><?= round($user['total_xp']) ?></div>
                            </td>
                            <td style="text-align: right;">
                                <div class="stat-value"><?= round($user['avg_accuracy']) ?>%</div>
                                <div class="stat-bar">
                                    <div class="stat-bar-fill" style="width: <?= $user['avg_accuracy'] ?>%;"></div>
                                </div>
                            </td>
                            <td style="text-align: right;">
                                <div class="stat-value"><?= (int)$user['modules_completed'] ?>/<?= (int)$user['modules_registered'] ?></div>
                            </td>
                            <td style="text-align: right;">
                                <div class="stat-value"><?= (int)$user['total_sessions'] ?></div>
                            </td>
                            <td style="text-align: right;">
                                <div class="stat-value">
                                    <?= (int)$user['phishing_correct'] ?>/<?= (int)$user['phishing_total'] ?>
                                </div>
                                <?php if ($user['phishing_total'] > 0): ?>
                                    <div class="stat-bar">
                                        <div class="stat-bar-fill" style="width: <?= round(($user['phishing_correct'] / $user['phishing_total']) * 100) ?>%;"></div>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right; font-size: 12px; color: #999;">
                                <?= $user['last_activity'] ? date('M d', strtotime($user['last_activity'])) : '—' ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

</body>
</html>
