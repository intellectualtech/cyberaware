<?php
// admin/department-scores.php - Department Performance Overview

require_once '../config/database.php';
require_once '../includes/functions.php';

require_admin(); // Only admins/managers

$pdo = getDBConnection();

// Fetch all departments with aggregated stats
$stmt = $pdo->query("
    SELECT 
        d.id,
        d.name,
        d.code,
        COUNT(u.id) as total_employees,
        AVG(ts.final_score) as avg_score,
        COUNT(ta.id) as total_actions,
        SUM(CASE WHEN ta.action_type = 'click' THEN 1 ELSE 0 END) as clicks,
        SUM(CASE WHEN ta.action_type = 'report' THEN 1 ELSE 0 END) as reports,
        SUM(CASE WHEN ts.final_score < 60 THEN 1 ELSE 0 END) as high_risk_count
    FROM departments d
    LEFT JOIN users u ON d.id = u.department_id AND u.role = 'trainee' AND u.is_active = 1
    LEFT JOIN training_sessions ts ON u.id = ts.user_id
    LEFT JOIN training_actions ta ON ts.id = ta.session_id AND ts.module_id = 1  -- phishing module
    GROUP BY d.id, d.name, d.code
    ORDER BY d.name
");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Current page for active highlight
$current_page = 'department-scores';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Scores – CyberAware Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #FF8C42;
            --primary-dark: #E67A2E;
            --primary-light: #FFF4ED;
            --primary-lighter: #FFEAD9;
            --success: #00A65A;
            --warning: #F39C12;
            --danger: #DD4B39;
            --dark: #2C2C2C;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #D4D4D4;
            --gray-400: #B8B8B8;
            --gray-500: #9E9E9E;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --white: #FFFFFF;
            --sidebar-width: 260px;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 16px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --radius: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--gray-100);
            color: var(--dark);
            line-height: 1.6;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        .header {
            background: var(--white);
            border-bottom: 1px solid var(--gray-200);
            padding: 20px 32px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .page-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--dark);
        }

        .page-subtitle {
            font-size: 13px;
            color: var(--gray-600);
            margin-top: 2px;
            font-weight: 400;
        }

        .container {
            padding: 32px;
            width: 100%;
        }

        /* Card Styles */
        .card {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            padding: 28px;
            margin-bottom: 32px;
        }

        .card-header {
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--gray-200);
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            color: var(--primary);
        }

        /* Table Styles */
        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        th {
            background: var(--gray-50);
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gray-700);
        }

        td {
            font-size: 14px;
            color: var(--gray-800);
        }

        tbody tr:hover {
            background: var(--gray-50);
        }

        /* Score Badges */
        .score-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            display: inline-block;
            letter-spacing: 0.3px;
        }

        .score-high {
            background: #E8F5E9;
            color: var(--success);
        }

        .score-medium {
            background: #FFF3E0;
            color: var(--warning);
        }

        .score-low {
            background: #FFEBEE;
            color: var(--danger);
        }

        /* Rate Colors */
        .rate-good {
            color: var(--success);
            font-weight: 600;
        }

        .rate-bad {
            color: var(--danger);
            font-weight: 600;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: var(--gray-600);
        }

        .empty-state i {
            font-size: 48px;
            color: var(--gray-400);
            margin-bottom: 16px;
        }

        .empty-state p {
            font-size: 16px;
            margin-bottom: 8px;
        }

        .empty-state small {
            font-size: 14px;
            color: var(--gray-500);
        }

        /* Info Box */
        .info-box {
            background: var(--primary-light);
            border-left: 4px solid var(--primary);
            padding: 16px 20px;
            border-radius: 6px;
            margin-top: 24px;
        }

        .info-box p {
            font-size: 14px;
            color: var(--gray-700);
            margin-bottom: 8px;
        }

        .info-box p:last-child {
            margin-bottom: 0;
        }

        .info-box strong {
            color: var(--primary-dark);
        }

        /* Summary Stats */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .summary-card {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }

        .summary-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
        }

        .summary-label {
            font-size: 13px;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                margin-bottom: 80px;
            }

            .container {
                padding: 20px;
            }

            .header {
                padding: 16px 20px;
            }

            .page-title {
                font-size: 20px;
            }

            .card {
                padding: 20px;
            }

            table {
                font-size: 13px;
            }

            th, td {
                padding: 12px 8px;
            }

            .summary-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 576px) {
            th, td {
                padding: 10px 6px;
                font-size: 12px;
            }

            .score-badge {
                font-size: 11px;
                padding: 4px 10px;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin-sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <header class="header">
            <div>
                <h2 class="page-title">Department Performance Scores</h2>
                <p class="page-subtitle">Analyze security awareness performance by department and team</p>
            </div>
        </header>

        <div class="container">
            <?php 
            // Calculate summary statistics
            $total_depts = count($departments);
            $total_employees = array_sum(array_column($departments, 'total_employees'));
            $avg_dept_score = $total_depts > 0 ? round(array_sum(array_column($departments, 'avg_score')) / $total_depts) : 0;
            $total_high_risk = array_sum(array_column($departments, 'high_risk_count'));
            ?>

            <!-- Summary Stats -->
            <div class="summary-grid">
                <div class="summary-card">
                    <div class="summary-value"><?= $total_depts ?></div>
                    <div class="summary-label">Total Departments</div>
                </div>
                <div class="summary-card">
                    <div class="summary-value"><?= number_format($total_employees) ?></div>
                    <div class="summary-label">Total Employees</div>
                </div>
                <div class="summary-card">
                    <div class="summary-value"><?= $avg_dept_score ?>%</div>
                    <div class="summary-label">Avg Department Score</div>
                </div>
                <div class="summary-card">
                    <div class="summary-value"><?= $total_high_risk ?></div>
                    <div class="summary-label">High-Risk Users</div>
                </div>
            </div>

            <!-- Department Performance Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-bar"></i>
                        Department Performance Breakdown
                    </h3>
                </div>

                <?php if (empty($departments)): ?>
                    <div class="empty-state">
                        <i class="fas fa-building"></i>
                        <p>No departments found</p>
                        <small>No training data available yet</small>
                    </div>
                <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Code</th>
                                <th>Employees</th>
                                <th>Avg Score</th>
                                <th>Click Rate</th>
                                <th>Report Rate</th>
                                <th>High-Risk</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($departments as $dept): 
                                $employees = $dept['total_employees'] ?? 0;
                                $avg_score = $dept['avg_score'] !== null ? round($dept['avg_score']) : 0;
                                $total_actions = $dept['total_actions'] ?? 0;
                                $click_rate = $total_actions > 0 ? round(($dept['clicks'] ?? 0) / $total_actions * 100, 1) : 0;
                                $report_rate = $total_actions > 0 ? round(($dept['reports'] ?? 0) / $total_actions * 100, 1) : 0;
                                $high_risk = $dept['high_risk_count'] ?? 0;

                                $score_class = $avg_score >= 80 ? 'high' : ($avg_score >= 60 ? 'medium' : 'low');
                                $click_class = $click_rate > 15 ? 'rate-bad' : 'rate-good';
                                $report_class = $report_rate > 20 ? 'rate-good' : 'rate-bad';
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($dept['name']) ?></strong></td>
                                <td><?= htmlspecialchars($dept['code'] ?? '-') ?></td>
                                <td><?= number_format($employees) ?></td>
                                <td>
                                    <span class="score-badge score-<?= $score_class ?>">
                                        <?= $avg_score ?>%
                                    </span>
                                </td>
                                <td class="<?= $click_class ?>">
                                    <i class="fas fa-<?= $click_rate > 15 ? 'arrow-up' : 'arrow-down' ?>"></i>
                                    <?= $click_rate ?>%
                                </td>
                                <td class="<?= $report_class ?>">
                                    <i class="fas fa-<?= $report_rate > 20 ? 'arrow-up' : 'arrow-down' ?>"></i>
                                    <?= $report_rate ?>%
                                </td>
                                <td>
                                    <?php if ($high_risk > 0): ?>
                                        <span style="color: var(--danger); font-weight: 600;">
                                            <i class="fas fa-exclamation-circle"></i> <?= $high_risk ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: var(--success);">
                                            <i class="fas fa-check-circle"></i> 0
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <!-- Info Box -->
                <div class="info-box">
                    <p><strong>Understanding the Metrics:</strong></p>
                    <p><i class="fas fa-info-circle"></i> <strong>High-Risk Users:</strong> Employees with an average final score below 60%</p>
                    <p><i class="fas fa-info-circle"></i> <strong>Click Rate:</strong> Percentage of phishing simulation emails clicked (lower is better)</p>
                    <p><i class="fas fa-info-circle"></i> <strong>Report Rate:</strong> Percentage of phishing emails reported (higher is better)</p>
                </div>
            </div>
        </div>
    </main>
</body>
</html>