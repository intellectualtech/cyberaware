<?php
/**
 * Employee Analytics Profile Page
 * Displays comprehensive analytics for individual employees
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

require_admin();

$pdo = getDBConnection();
$user_id = (int)($_GET['id'] ?? 0);

if ($user_id <= 0) {
    header('Location: manage-users.php');
    exit;
}

// Fetch employee details
$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.full_name, u.email, u.role, u.created_at, d.name as department
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.id
    WHERE u.id = ? AND u.role = 'trainee'
");
$stmt->execute([$user_id]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    header('Location: manage-users.php');
    exit;
}

// Fetch risk score trend (last 12 months)
$stmt = $pdo->prepare("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, AVG(risk_score) as avg_risk
    FROM user_training_summary
    WHERE user_id = ?
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
");
$stmt->execute([$user_id]);
$risk_trend = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

// Fetch training completion history
$stmt = $pdo->prepare("
    SELECT ts.id, tm.title, ts.completed_at, ts.final_score, ts.created_at
    FROM training_sessions ts
    JOIN training_modules tm ON ts.module_id = tm.id
    WHERE ts.user_id = ?
    ORDER BY ts.created_at DESC
    LIMIT 10
");
$stmt->execute([$user_id]);
$training_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch phishing simulation attempts
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_attempts,
        SUM(CASE WHEN ta.action_type = 'clicked' THEN 1 ELSE 0 END) as clicks,
        SUM(CASE WHEN ta.action_type = 'reported' THEN 1 ELSE 0 END) as reports
    FROM training_actions ta
    JOIN training_sessions ts ON ta.session_id = ts.id
    WHERE ts.user_id = ? AND ts.module_id = 1
");
$stmt->execute([$user_id]);
$phishing_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch failed incident interactions
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_failed,
        SUM(CASE WHEN action_type = 'clicked' THEN 1 ELSE 0 END) as failed_clicks
    FROM training_actions ta
    JOIN training_sessions ts ON ta.session_id = ts.id
    WHERE ts.user_id = ? AND ta.action_type IN ('clicked', 'failed')
");
$stmt->execute([$user_id]);
$failed_interactions = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch current risk score
$stmt = $pdo->prepare("
    SELECT risk_score, final_score FROM user_training_summary
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt->execute([$user_id]);
$current_score = $stmt->fetch(PDO::FETCH_ASSOC);

// Determine risk level
$risk_score = $current_score['risk_score'] ?? 50;
if ($risk_score < 30) {
    $risk_level = 'Low';
    $risk_color = '#10b981';
} elseif ($risk_score < 60) {
    $risk_level = 'Medium';
    $risk_color = '#f59e0b';
} else {
    $risk_level = 'High';
    $risk_color = '#ef4444';
}

// Fetch recommended modules based on weak areas
$stmt = $pdo->prepare("
    SELECT tm.id, tm.title, tm.code, COUNT(ts.id) as attempts, AVG(ts.final_score) as avg_score
    FROM training_modules tm
    LEFT JOIN training_sessions ts ON tm.id = ts.module_id AND ts.user_id = ?
    WHERE tm.is_active = 1
    GROUP BY tm.id
    HAVING avg_score < 70 OR avg_score IS NULL
    ORDER BY avg_score ASC
    LIMIT 5
");
$stmt->execute([$user_id]);
$recommended_modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate performance metrics
$total_trainings = count($training_history);
$completed_trainings = count(array_filter($training_history, fn($t) => $t['completed_at'] !== null));
$completion_rate = $total_trainings > 0 ? round(($completed_trainings / $total_trainings) * 100) : 0;

$phishing_total = $phishing_stats['total_attempts'] ?? 0;
$phishing_clicks = $phishing_stats['clicks'] ?? 0;
$phishing_reports = $phishing_stats['reports'] ?? 0;
$phishing_rate = $phishing_total > 0 ? round(($phishing_clicks / $phishing_total) * 100) : 0;

$current_page = 'manage-users';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Profile – <?= htmlspecialchars($employee['full_name']) ?> – CyberAware</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #FF8C42;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --sidebar-width: 260px;
            --sidebar-height: 72px;
            --brand: #FF8C42;
            --surface: #ffffff;
            --ink: #0f172a;
            --muted: #6b7280;
            --line: rgba(15, 23, 42, 0.12);
            --shadow-sm: 0 8px 18px rgba(15, 23, 42, 0.08);
            --shadow-md: 0 18px 40px rgba(15, 23, 42, 0.12);
            --radius-lg: 20px;
            --radius-md: 14px;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, #EE8E46 0%, #E67A2E 100%); 
            margin: 0;
        }
        
        .main-content { 
            margin-bottom: var(--sidebar-height); 
            padding: 2rem; 
        }
        
        .page-header {
            background: rgba(17, 24, 39, 0.9);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 18px;
            padding: 24px 28px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title { 
            font-size: 28px; 
            font-weight: 700; 
            color: #f8fafc;
            margin: 0;
        }

        .page-subtitle {
            color: #94a3b8;
            margin-top: 6px;
            font-size: 14px;
        }

        .back-btn {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.3);
        }
        
        .container { max-width: 1400px; margin: 0 auto; }
        
        .card { 
            background: white; 
            padding: 2rem; 
            border-radius: 12px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }

        .employee-header {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 2rem;
            align-items: center;
            margin-bottom: 2rem;
        }

        .employee-avatar {
            width: 100px;
            height: 100px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary) 0%, #E67A2E 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
            box-shadow: 0 4px 12px rgba(255, 140, 66, 0.3);
        }

        .employee-info h2 {
            font-size: 24px;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .employee-info p {
            color: #666;
            font-size: 14px;
            margin: 0.25rem 0;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .metric-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-left: 4px solid var(--primary);
        }

        .metric-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .metric-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .metric-description {
            font-size: 13px;
            color: #999;
        }

        .risk-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 0.5rem;
        }

        .risk-low { background: #d1fae5; color: #065f46; }
        .risk-medium { background: #fef3c7; color: #92400e; }
        .risk-high { background: #fee2e2; color: #991b1b; }

        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i {
            color: var(--primary);
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f9fafb;
            padding: 1rem;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            border-bottom: 1px solid #e5e7eb;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
        }

        tr:hover {
            background: #f9fafb;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }

        .module-card {
            background: #f9fafb;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid var(--primary);
            margin-bottom: 1rem;
        }

        .module-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .module-code {
            font-size: 12px;
            color: #999;
            margin-bottom: 0.5rem;
        }

        .module-score {
            font-size: 14px;
            color: #666;
        }

        .btn {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: #E67A2E;
            transform: translateY(-2px);
        }

        @media (max-width: 992px) {
            .main-content { margin-left: 0; }
            .employee-header { grid-template-columns: 1fr; }
            .page-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
        }
    </style>
</head>
<body>
    <?php include 'admin-sidebar.php'; ?>

    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <div>
                    <div class="page-title"><i class="fas fa-user-circle"></i> Employee Analytics Profile</div>
                    <div class="page-subtitle">Comprehensive performance and security awareness analytics</div>
                </div>
                <a href="manage-users.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Users</a>
            </div>

            <!-- Employee Header -->
            <div class="card">
                <div class="employee-header">
                    <div class="employee-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="employee-info">
                        <h2><?= htmlspecialchars($employee['full_name']) ?></h2>
                        <p><strong>Username:</strong> <?= htmlspecialchars($employee['username']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($employee['email']) ?></p>
                        <p><strong>Department:</strong> <?= htmlspecialchars($employee['department'] ?? 'Not Assigned') ?></p>
                        <p><strong>Member Since:</strong> <?= date('M j, Y', strtotime($employee['created_at'])) ?></p>
                        <div class="risk-badge risk-<?= strtolower($risk_level) ?>">
                            Risk Level: <strong><?= $risk_level ?></strong> (Score: <?= $risk_score ?>)
                        </div>
                    </div>
                </div>
            </div>

            <!-- Key Metrics -->
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-label"><i class="fas fa-graduation-cap"></i> Training Completion</div>
                    <div class="metric-value"><?= $completion_rate ?>%</div>
                    <div class="metric-description"><?= $completed_trainings ?> of <?= $total_trainings ?> completed</div>
                </div>

                <div class="metric-card">
                    <div class="metric-label"><i class="fas fa-fish"></i> Phishing Vulnerability</div>
                    <div class="metric-value"><?= $phishing_rate ?>%</div>
                    <div class="metric-description"><?= $phishing_clicks ?> clicks out of <?= $phishing_total ?> attempts</div>
                </div>

                <div class="metric-card">
                    <div class="metric-label"><i class="fas fa-exclamation-circle"></i> Failed Interactions</div>
                    <div class="metric-value"><?= $failed_interactions['total_failed'] ?? 0 ?></div>
                    <div class="metric-description">Security incidents missed</div>
                </div>

                <div class="metric-card">
                    <div class="metric-label"><i class="fas fa-chart-line"></i> Average Score</div>
                    <div class="metric-value"><?= round($current_score['final_score'] ?? 0) ?>%</div>
                    <div class="metric-description">Overall training performance</div>
                </div>
            </div>

            <!-- Risk Score Trend Chart -->
            <?php if (!empty($risk_trend)): ?>
            <div class="card">
                <h3 class="section-title"><i class="fas fa-chart-area"></i> Risk Score Trend</h3>
                <div class="chart-container">
                    <canvas id="riskChart"></canvas>
                </div>
            </div>
            <?php endif; ?>

            <!-- Training History -->
            <div class="card">
                <h3 class="section-title"><i class="fas fa-history"></i> Training Completion History</h3>
                <?php if (!empty($training_history)): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Module</th>
                                <th>Started</th>
                                <th>Completed</th>
                                <th>Score</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($training_history as $training): ?>
                            <tr>
                                <td><?= htmlspecialchars($training['title']) ?></td>
                                <td><?= date('M j, Y', strtotime($training['created_at'])) ?></td>
                                <td><?= $training['completed_at'] ? date('M j, Y', strtotime($training['completed_at'])) : '—' ?></td>
                                <td><?= $training['final_score'] ? round($training['final_score']) . '%' : '—' ?></td>
                                <td>
                                    <?php if ($training['completed_at']): ?>
                                        <span class="badge badge-success">Completed</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">In Progress</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p style="color: #999; text-align: center; padding: 2rem;">No training history available</p>
                <?php endif; ?>
            </div>

            <!-- Phishing Simulation Summary -->
            <div class="card">
                <h3 class="section-title"><i class="fas fa-fish"></i> Phishing Simulation Summary</h3>
                <div class="metrics-grid">
                    <div class="metric-card">
                        <div class="metric-label">Total Attempts</div>
                        <div class="metric-value"><?= $phishing_total ?></div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-label">Clicks (Failed)</div>
                        <div class="metric-value" style="color: #ef4444;"><?= $phishing_clicks ?></div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-label">Reports (Successful)</div>
                        <div class="metric-value" style="color: #10b981;"><?= $phishing_reports ?></div>
                    </div>
                </div>
            </div>

            <!-- Recommended Modules -->
            <div class="card">
                <h3 class="section-title"><i class="fas fa-lightbulb"></i> Recommended Remediation Modules</h3>
                <?php if (!empty($recommended_modules)): ?>
                <div>
                    <?php foreach ($recommended_modules as $module): ?>
                    <div class="module-card">
                        <div class="module-title"><?= htmlspecialchars($module['title']) ?></div>
                        <div class="module-code">Code: <?= htmlspecialchars($module['code']) ?></div>
                        <div class="module-score">
                            <?php if ($module['avg_score']): ?>
                                Current Score: <strong><?= round($module['avg_score']) ?>%</strong> 
                                (<?= $module['attempts'] ?> attempts)
                            <?php else: ?>
                                <strong>Not yet attempted</strong>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p style="color: #999; text-align: center; padding: 2rem;">No remediation modules recommended at this time</p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        // Risk Score Trend Chart
        <?php if (!empty($risk_trend)): ?>
        const riskCtx = document.getElementById('riskChart').getContext('2d');
        const riskChart = new Chart(riskCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_map(fn($t) => $t['month'], $risk_trend)) ?>,
                datasets: [{
                    label: 'Risk Score',
                    data: <?= json_encode(array_map(fn($t) => round($t['avg_risk']), $risk_trend)) ?>,
                    borderColor: '#FF8C42',
                    backgroundColor: 'rgba(255, 140, 66, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: '#FF8C42',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        labels: { font: { size: 13 }, color: '#666' }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: { color: '#666' },
                        grid: { color: '#e5e7eb' }
                    },
                    x: {
                        ticks: { color: '#666' },
                        grid: { color: '#e5e7eb' }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
