<?php
// admin/dashboard.php - Trainer / Admin Overview with Sidebar (FIXED - No Warnings)

require_once '../config/database.php';
require_once '../includes/functions.php';

require_admin(); // Ensure only admins/managers can access

// Get DB connection
$pdo = getDBConnection();

// =============================================
// Fetch Real Data from Database (SAFE - No array offset warnings)
// =============================================

// 1. Total Trained Employees
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'trainee' AND is_active = 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$total_employees = $row ? (int)$row['total'] : 0;

// 2. Active Campaigns
$stmt = $pdo->query("SELECT COUNT(*) as active FROM campaigns WHERE status = 'active'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$active_campaigns = $row ? (int)$row['active'] : 0;

// 3. Average Click & Report Rate (phishing module_id = 1)
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_actions,
        SUM(CASE WHEN action_type = 'clicked' THEN 1 ELSE 0 END) as clicks,
        SUM(CASE WHEN action_type = 'reported' THEN 1 ELSE 0 END) as reports
    FROM training_actions ta
    JOIN training_sessions ts ON ta.session_id = ts.id
    WHERE ts.module_id = 1
");
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$total_phish_actions = $row ? (int)$row['total_actions'] : 0;
$clicks = $row ? (int)$row['clicks'] : 0;
$reports = $row ? (int)$row['reports'] : 0;

$avg_click_rate = $total_phish_actions > 0 ? round(($clicks / $total_phish_actions) * 100, 1) : 0;
$avg_report_rate = $total_phish_actions > 0 ? round(($reports / $total_phish_actions) * 100, 1) : 0;

// 4. High Risk Users (risk_score > 60 or final_score < 60 - using summary table)
$stmt = $pdo->query("SELECT COUNT(*) as high_risk FROM user_training_summary WHERE risk_score > 60");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$high_risk_users = $row ? (int)$row['high_risk'] : 0;

// 5. Current Active Campaign
$stmt = $pdo->query("
    SELECT 
        c.name, 
        c.end_date,
        COUNT(DISTINCT ts.user_id) as completed_users
    FROM campaigns c 
    LEFT JOIN training_sessions ts ON ts.campaign_id = c.id AND ts.completed_at IS NOT NULL
    WHERE c.status = 'active' 
    GROUP BY c.id
    ORDER BY c.created_at DESC 
    LIMIT 1
");
$active_campaign_row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($active_campaign_row) {
    $campaign_name = $active_campaign_row['name'];
    $campaign_completed_users = (int)$active_campaign_row['completed_users'];
    $campaign_end_date = $active_campaign_row['end_date'] 
        ? date('F j, Y', strtotime($active_campaign_row['end_date'])) 
        : 'Not set';
} else {
    $campaign_name = 'No active campaign';
    $campaign_completed_users = 0;
    $campaign_end_date = 'Not set';
}

$campaign_progress = $total_employees > 0 
    ? round(($campaign_completed_users / $total_employees) * 100) 
    : 0;

// Current page for active highlight
$current_page = 'dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard – CyberAware</title>
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

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            padding: 24px;
            transition: all 0.2s;
        }

        .stat-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .stat-card.warning {
            border-left: 5px solid var(--warning);
        }

        .stat-card.success {
            border-left: 5px solid var(--success);
        }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .stat-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .stat-icon.primary { background: var(--primary-lighter); color: var(--primary); }
        .stat-icon.success { background: #E8F5E9; color: var(--success); }
        .stat-icon.warning { background: #FFF3E0; color: var(--warning); }
        .stat-icon.danger { background: #FFEBEE; color: var(--danger); }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 4px;
        }

        .stat-description {
            font-size: 13px;
            color: var(--gray-600);
        }

        /* Action Buttons */
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .action-card {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            padding: 24px;
            text-align: center;
            transition: all 0.2s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .action-card:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .action-icon {
            width: 56px;
            height: 56px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            margin: 0 auto 16px;
        }

        .action-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .action-description {
            font-size: 13px;
            color: var(--gray-600);
        }

        /* Campaign Card */
        .campaign-card {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            padding: 28px;
        }

        .campaign-header {
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--gray-200);
        }

        .campaign-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 4px;
        }

        .campaign-subtitle {
            font-size: 13px;
            color: var(--gray-600);
        }

        .campaign-info {
            display: grid;
            gap: 20px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            background: var(--gray-50);
            border-radius: 6px;
        }

        .info-label {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-700);
        }

        .info-value {
            font-size: 14px;
            color: var(--dark);
            font-weight: 500;
        }

        .progress-container {
            margin-top: 12px;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .progress-label {
            font-size: 13px;
            color: var(--gray-600);
            font-weight: 500;
        }

        .progress-percent {
            font-size: 14px;
            font-weight: 700;
            color: var(--primary);
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: var(--gray-200);
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                margin-bottom: 80px;
            }

            .container {
                padding: 20px;
            }

            .stats-grid,
            .action-grid {
                grid-template-columns: 1fr;
            }

            .header {
                padding: 16px 20px;
            }

            .page-title {
                font-size: 20px;
            }
        }

        @media (max-width: 576px) {
            .stat-value {
                font-size: 28px;
            }

            .campaign-card {
                padding: 20px;
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
                <h2 class="page-title">Training Platform Overview</h2>
                <p class="page-subtitle">Security Awareness & Phishing Simulation Dashboard</p>
            </div>
        </header>

        <div class="container">
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Trained Employees</span>
                        <div class="stat-icon primary">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format($total_employees) ?></div>
                    <div class="stat-description">Active trainees enrolled</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Active Campaigns</span>
                        <div class="stat-icon success">
                            <i class="fas fa-envelope"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $active_campaigns ?></div>
                    <div class="stat-description">Currently running</div>
                </div>

                <div class="stat-card <?= $avg_click_rate > 15 ? 'warning' : 'success' ?>">
                    <div class="stat-header">
                        <span class="stat-label">Avg. Click Rate</span>
                        <div class="stat-icon warning">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $avg_click_rate ?>%</div>
                    <div class="stat-description">Phishing simulation clicks</div>
                </div>

                <div class="stat-card success">
                    <div class="stat-header">
                        <span class="stat-label">Avg. Report Rate</span>
                        <div class="stat-icon success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $avg_report_rate ?>%</div>
                    <div class="stat-description">Users reporting threats</div>
                </div>

                <div class="stat-card <?= $high_risk_users > 0 ? 'warning' : '' ?>">
                    <div class="stat-header">
                        <span class="stat-label">High-Risk Users</span>
                        <div class="stat-icon danger">
                            <i class="fas fa-user-shield"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $high_risk_users ?></div>
                    <div class="stat-description">Risk score above 60</div>
                </div>
            </div>

            <!-- Action Cards -->
            <div class="action-grid">
                <a href="campaigns.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="action-title">Manage Campaigns</div>
                    <div class="action-description">Create, edit, and monitor training campaigns</div>
                </a>

                <a href="department-scores.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="action-title">View Department Scores</div>
                    <div class="action-description">Analyze performance by department and team</div>
                </a>

                <a href="export-report.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-download"></i>
                    </div>
                    <div class="action-title">Export Summary Report</div>
                    <div class="action-description">Download comprehensive analytics and insights</div>
                </a>
            </div>

            <!-- Current Campaign -->
            <div class="campaign-card">
                <div class="campaign-header">
                    <h3 class="campaign-title">Current Active Campaign</h3>
                    <p class="campaign-subtitle">Real-time campaign performance and completion status</p>
                </div>

                <div class="campaign-info">
                    <div class="info-row">
                        <span class="info-label">Campaign Name:</span>
                        <span class="info-value"><?= htmlspecialchars($campaign_name) ?></span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">End Date:</span>
                        <span class="info-value"><?= htmlspecialchars($campaign_end_date) ?></span>
                    </div>

                    <div class="info-row">
                        <div style="flex: 1;">
                            <div class="info-label" style="margin-bottom: 8px;">Campaign Progress:</div>
                            <div class="progress-container">
                                <div class="progress-header">
                                    <span class="progress-label"><?= $campaign_completed_users ?> of <?= number_format($total_employees) ?> employees completed</span>
                                    <span class="progress-percent"><?= $campaign_progress ?>%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $campaign_progress ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>