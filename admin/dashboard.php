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
            
            /* Shadows & Effects */
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.15);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.2);
            --shadow-yellow: 0 0 20px rgba(255, 214, 10, 0.3);
            
            --sidebar-width: 260px;
            --radius: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, var(--dark-navy) 0%, var(--dark-slate) 100%);
            color: var(--white);
            line-height: 1.6;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, var(--dark-navy) 0%, var(--charcoal) 100%);
            border-bottom: 5px solid var(--cyber-yellow);
            padding: 30px 32px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 8px 32px rgba(255, 214, 10, 0.15);
        }

        .page-title {
            font-size: 32px;
            font-weight: 800;
            color: var(--cyber-yellow);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-title i {
            font-size: 36px;
            color: var(--cyber-gold);
        }

        .page-subtitle {
            font-size: 14px;
            color: rgba(255, 214, 10, 0.7);
            margin-top: 6px;
            font-weight: 500;
        }

        .container {
            padding: 32px;
            width: 100%;
        }

        /* Threat Level Indicator */
        .threat-banner {
            background: linear-gradient(135deg, rgba(255, 214, 10, 0.1) 0%, rgba(255, 214, 10, 0.05) 100%);
            border: 2px solid var(--cyber-yellow);
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 32px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .threat-indicator {
            width: 80px;
            height: 80px;
            border-radius: 12px;
            background: radial-gradient(circle, var(--cyber-yellow) 0%, var(--cyber-gold) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: var(--dark-navy);
            box-shadow: 0 0 30px rgba(255, 214, 10, 0.4);
        }

        .threat-content h3 {
            font-size: 20px;
            font-weight: 800;
            color: var(--cyber-yellow);
            margin-bottom: 6px;
        }

        .threat-content p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.8);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(255, 214, 10, 0.08) 0%, rgba(255, 214, 10, 0.02) 100%);
            border: 2px solid rgba(255, 214, 10, 0.3);
            border-left: 5px solid var(--cyber-yellow);
            border-radius: 14px;
            padding: 28px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255, 214, 10, 0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        .stat-card:hover {
            box-shadow: 0 12px 40px rgba(255, 214, 10, 0.25);
            transform: translateY(-8px);
            border-color: var(--cyber-gold);
            background: linear-gradient(135deg, rgba(255, 214, 10, 0.15) 0%, rgba(255, 214, 10, 0.05) 100%);
        }

        .stat-card.critical {
            border-left-color: var(--alert-red);
            border-color: rgba(239, 68, 68, 0.3);
        }

        .stat-card.warning {
            border-left-color: #F59E0B;
            border-color: rgba(245, 158, 11, 0.3);
        }

        .stat-card.success {
            border-left-color: var(--shield-green);
            border-color: rgba(16, 185, 129, 0.3);
        }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
            position: relative;
            z-index: 1;
        }

        .stat-label {
            font-size: 12px;
            font-weight: 700;
            color: rgba(255, 214, 10, 0.9);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }

        .stat-icon.primary { 
            background: linear-gradient(135deg, var(--cyber-yellow) 0%, var(--cyber-gold) 100%);
            color: var(--dark-navy);
            box-shadow: 0 4px 15px rgba(255, 214, 10, 0.4);
        }
        .stat-icon.success { 
            background: linear-gradient(135deg, var(--shield-green) 0%, #059669 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        .stat-icon.warning { 
            background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }
        .stat-icon.danger { 
            background: linear-gradient(135deg, var(--alert-red) 0%, #DC2626 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }

        .stat-value {
            font-size: 42px;
            font-weight: 900;
            color: var(--cyber-yellow);
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
            text-shadow: 0 0 10px rgba(255, 214, 10, 0.3);
        }

        .stat-description {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
            position: relative;
            z-index: 1;
        }

        /* Security Cards Grid */
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .action-card {
            background: linear-gradient(135deg, rgba(255, 214, 10, 0.08) 0%, rgba(255, 214, 10, 0.02) 100%);
            border: 2px solid rgba(255, 214, 10, 0.3);
            border-left: 6px solid var(--cyber-yellow);
            border-radius: 14px;
            padding: 32px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }

        .action-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255, 214, 10, 0.08) 0%, transparent 70%);
            border-radius: 50%;
        }

        .action-card:hover {
            border-color: var(--cyber-gold);
            box-shadow: 0 12px 40px rgba(255, 214, 10, 0.25);
            transform: translateY(-10px);
            background: linear-gradient(135deg, rgba(255, 214, 10, 0.15) 0%, rgba(255, 214, 10, 0.05) 100%);
        }

        .action-icon {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, var(--cyber-yellow) 0%, var(--cyber-gold) 100%);
            color: var(--dark-navy);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            margin: 0 auto 24px;
            box-shadow: 0 8px 24px rgba(255, 214, 10, 0.3);
            position: relative;
            z-index: 1;
        }

        .action-title {
            font-size: 20px;
            font-weight: 800;
            color: var(--cyber-yellow);
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
        }

        .action-description {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.75);
            position: relative;
            z-index: 1;
        }

        /* Campaign/Threat Assessment Card */
        .campaign-card {
            background: linear-gradient(135deg, rgba(255, 214, 10, 0.08) 0%, rgba(255, 214, 10, 0.02) 100%);
            border: 2px solid rgba(255, 214, 10, 0.3);
            border-left: 6px solid var(--cyber-yellow);
            border-radius: 14px;
            padding: 36px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }

        .campaign-card::before {
            content: '';
            position: absolute;
            top: -30%;
            right: -30%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255, 214, 10, 0.05) 0%, transparent 70%);
            border-radius: 50%;
        }

        .campaign-header {
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 2px solid rgba(255, 214, 10, 0.2);
            position: relative;
            z-index: 1;
        }

        .campaign-title {
            font-size: 24px;
            font-weight: 900;
            color: var(--cyber-yellow);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .campaign-title i {
            font-size: 28px;
            color: var(--cyber-gold);
        }

        .campaign-subtitle {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
        }

        .campaign-info {
            display: grid;
            gap: 20px;
            position: relative;
            z-index: 1;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: rgba(255, 214, 10, 0.05);
            border-radius: 10px;
            border-left: 4px solid var(--cyber-yellow);
            border: 1px solid rgba(255, 214, 10, 0.15);
        }

        .info-label {
            font-size: 14px;
            font-weight: 700;
            color: var(--cyber-yellow);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 600;
        }

        .progress-container {
            margin-top: 16px;
            position: relative;
            z-index: 1;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
        }

        .progress-label {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 600;
        }

        .progress-percent {
            font-size: 18px;
            font-weight: 900;
            color: var(--cyber-yellow);
            text-shadow: 0 0 15px rgba(255, 214, 10, 0.4);
        }

        .progress-bar {
            width: 100%;
            height: 14px;
            background: rgba(255, 214, 10, 0.08);
            border-radius: 10px;
            overflow: hidden;
            border: 2px solid rgba(255, 214, 10, 0.25);
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--cyber-yellow) 0%, var(--cyber-gold) 100%);
            border-radius: 10px;
            transition: width 0.3s ease;
            box-shadow: 0 0 10px rgba(255, 214, 10, 0.5);
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
                padding: 20px;
            }

            .page-title {
                font-size: 24px;
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
                <h2 class="page-title"><i class="fas fa-shield-alt"></i>Security Operations Center</h2>
                <p class="page-subtitle">Threat Assessment & Security Awareness Metrics</p>
            </div>
        </header>

        <div class="container">
            <!-- Threat Level Banner -->
            <div class="threat-banner">
                <div class="threat-indicator">
                    <i class="fas fa-exclamation"></i>
                </div>
                <div class="threat-content">
                    <h3>Current Threat Posture</h3>
                    <p>Monitor organizational security awareness and identify at-risk employees for targeted intervention</p>
                </div>
            </div>

            <!-- Security Metrics Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">🛡️ Protected Users</span>
                        <div class="stat-icon primary">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format($total_employees) ?></div>
                    <div class="stat-description">Active threat-aware users</div>
                </div>

                <div class="stat-card success">
                    <div class="stat-header">
                        <span class="stat-label">🎯 Active Exercises</span>
                        <div class="stat-icon success">
                            <i class="fas fa-bullseye"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $active_campaigns ?></div>
                    <div class="stat-description">Live security simulations</div>
                </div>

                <div class="stat-card <?= $avg_click_rate > 15 ? 'critical' : 'success' ?>">
                    <div class="stat-header">
                        <span class="stat-label">⚠️ Threat Fall Rate</span>
                        <div class="stat-icon <?= $avg_click_rate > 15 ? 'danger' : 'success' ?>">
                            <i class="fas fa-virus"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $avg_click_rate ?>%</div>
                    <div class="stat-description">Users vulnerable to phishing</div>
                </div>

                <div class="stat-card success">
                    <div class="stat-header">
                        <span class="stat-label">✓ Defense Rate</span>
                        <div class="stat-icon success">
                            <i class="fas fa-check-shield"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $avg_report_rate ?>%</div>
                    <div class="stat-description">Users reporting threats</div>
                </div>

                <div class="stat-card <?= $high_risk_users > 0 ? 'critical' : 'success' ?>">
                    <div class="stat-header">
                        <span class="stat-label">🔴 Critical Risk</span>
                        <div class="stat-icon <?= $high_risk_users > 0 ? 'danger' : 'success' ?>">
                            <i class="fas fa-user-times"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $high_risk_users ?></div>
                    <div class="stat-description">Users requiring immediate training</div>
                </div>
            </div>

            <!-- Threat Management Actions -->
            <div class="action-grid">
                <a href="campaigns.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-virus"></i>
                    </div>
                    <div class="action-title">Manage Threat Campaigns</div>
                    <div class="action-description">Launch, monitor & control active threat simulations</div>
                </a>

                <a href="department-scores.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-chart-radar"></i>
                    </div>
                    <div class="action-title">Risk Assessment Report</div>
                    <div class="action-description">Analyze threat exposure by department & role</div>
                </a>

                <a href="export-report.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-file-contract"></i>
                    </div>
                    <div class="action-title">Compliance Export</div>
                    <div class="action-description">Generate security compliance & audit reports</div>
                </a>
            </div>

            <!-- Current Campaign / Active Threat -->
            <div class="campaign-card">
                <div class="campaign-header">
                    <h3 class="campaign-title"><i class="fas fa-crosshairs"></i>Active Threat Simulation</h3>
                    <p class="campaign-subtitle">Current security exercise performance and completion metrics</p>
                </div>

                <div class="campaign-info">
                    <div class="info-row">
                        <span class="info-label">Campaign Name:</span>
                        <span class="info-value"><?= htmlspecialchars($campaign_name) ?></span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Simulation End Date:</span>
                        <span class="info-value"><?= htmlspecialchars($campaign_end_date) ?></span>
                    </div>

                    <div class="info-row">
                        <div style="flex: 1;">
                            <div class="info-label" style="margin-bottom: 10px;">User Completion Rate:</div>
                            <div class="progress-container">
                                <div class="progress-header">
                                    <span class="progress-label"><?= $campaign_completed_users ?> of <?= number_format($total_employees) ?> users trained</span>
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