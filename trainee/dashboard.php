<?php
// trainee/dashboard.php - Trainee Dashboard with Department & Assigned Campaign

require_once '../config/database.php';

// Get connection safely
try {
    $pdo = getDBConnection();
} catch (Exception $e) {
    die("Database connection failed. Check config/database.php. Error: " . htmlspecialchars($e->getMessage()));
}

// Login check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SESSION['role'] !== 'trainee') {
    header("Location: ../pages/home.php?error=access_denied");
    exit;
}

// Fetch user data + department
$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.full_name, u.last_login, d.name AS department_name
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.id
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header("Location: ../login.php?error=user_not_found");
        exit;
    }

    $name = $user['full_name'] ?? $user['username'] ?? 'Trainee';
    $department = $user['department_name'] ?? 'Not Assigned';

    // Progress stats
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_sessions,
            SUM(CASE WHEN completed_at IS NOT NULL THEN 1 ELSE 0 END) as completed_sessions,
            AVG(final_score) as avg_score,
            MAX(completed_at) as last_activity
        FROM training_sessions
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
        'total_sessions' => 0,
        'completed_sessions' => 0,
        'avg_score' => null,
        'last_activity' => null
    ];

    $completed = (int)$stats['completed_sessions'];
    $total_modules = 5; // Adjust if you add more modules
    $progress_percent = $total_modules > 0 ? round(($completed / $total_modules) * 100) : 0;

    $last_activity = $stats['last_activity']
        ? date('M j, Y g:i A', strtotime($stats['last_activity']))
        : 'No activity yet';

    $understanding = $stats['avg_score'] !== null ? round((float)$stats['avg_score']) : 0;

    $recommendation = "You're making good progress — keep going!";
    if ($understanding < 50) {
        $recommendation = "Your current understanding needs strengthening. Start with <strong>Phishing Recognition</strong>.";
    } elseif ($understanding < 75) {
        $recommendation = "Solid foundation! Try <strong>Social Engineering Scenarios</strong> or <strong>Fake Login Pages</strong> next.";
    } else {
        $recommendation = "Excellent results! You're doing great.";
    }

    $risk_level = $understanding >= 80 ? 'Low' : ($understanding >= 60 ? 'Medium' : 'High');
    $risk_class = strtolower($risk_level);

    // Fetch assigned active campaign(s)
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
    ");
    $stmt->execute([$user_id]);
    $assigned_campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $current_campaign = !empty($assigned_campaigns) ? $assigned_campaigns[0] : null;
    $campaign_name = $current_campaign ? $current_campaign['name'] : 'No active campaign assigned';
    $campaign_desc = $current_campaign ? ($current_campaign['description'] ?: 'No description') : '';
    $campaign_dates = '';
    if ($current_campaign) {
        $start = $current_campaign['start_date'] ? date('M j, Y', strtotime($current_campaign['start_date'])) : 'Not set';
        $end = $current_campaign['end_date'] ? date('M j, Y', strtotime($current_campaign['end_date'])) : 'Ongoing';
        $campaign_dates = "$start – $end";
    }
} catch (Exception $e) {
    die("Database error: " . htmlspecialchars($e->getMessage()));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CyberAware - Dashboard</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

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
            --gray-300: #cbd5e1;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --white: #FFFFFF;
            --sidebar-width: 260px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 16px rgba(0,0,0,0.1);
            --radius: 14px;
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

        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            padding: 2rem;
        }

        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                padding-bottom: 80px;
            }
        }

        .header {
            background: var(--white);
            border-bottom: 1px solid var(--gray-200);
            padding: 20px 32px;
            position: sticky;
            top: 0;
            z-index: 90;
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
        }

        .container {
            padding: 32px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: var(--primary-lighter);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
        }

        .stat-icon i {
            font-size: 24px;
            color: var(--primary);
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
        }

        .stat-label {
            font-size: 13px;
            color: var(--gray-600);
            font-weight: 500;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
        }

        @media (min-width: 992px) {
            .dashboard-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        .card {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 28px;
            box-shadow: var(--shadow-sm);
        }

        .card h3 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 24px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card h3 i {
            color: var(--primary);
            font-size: 22px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .info-item {
            background: var(--gray-50);
            padding: 16px;
            border-radius: 10px;
            text-align: center;
        }

        .info-item i {
            font-size: 28px;
            color: var(--primary);
            margin-bottom: 12px;
        }

        .info-label {
            font-size: 13px;
            color: var(--gray-600);
            font-weight: 500;
        }

        .info-value {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
            margin-top: 4px;
        }

        .progress-section {
            margin: 20px 0;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            font-weight: 600;
            color: var(--gray-700);
            font-size: 14px;
        }

        .progress-bar-outer {
            height: 12px;
            background: var(--gray-200);
            border-radius: 6px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            border-radius: 6px;
            transition: width 1.2s ease;
        }

        .progress-value {
            text-align: center;
            font-size: 42px;
            font-weight: 700;
            color: var(--primary);
            margin: 20px 0;
        }

        .meta-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 20px;
        }

        .meta-item {
            background: var(--gray-50);
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            color: var(--gray-600);
        }

        .meta-item strong {
            display: block;
            color: var(--dark);
            font-size: 16px;
            margin-top: 4px;
        }

        .risk-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 20px;
        }

        .risk-badge i {
            font-size: 16px;
        }

        .risk-low { background: #E8F5E9; color: var(--success); }
        .risk-medium { background: #FFF3E0; color: var(--warning); }
        .risk-high { background: #FFEBEE; color: var(--danger); }

        .module-list {
            list-style: none;
        }

        .module-list li {
            margin: 12px 0;
        }

        .module-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            background: var(--gray-50);
            border-radius: 10px;
            text-decoration: none;
            color: var(--gray-800);
            font-weight: 500;
            font-size: 15px;
            border-left: 4px solid var(--primary);
            transition: all 0.3s;
        }

        .module-link i {
            font-size: 20px;
            color: var(--primary);
        }

        .module-link:hover {
            background: var(--primary);
            color: white;
            transform: translateX(8px);
            box-shadow: 0 4px 12px rgba(255, 140, 66, 0.3);
        }

        .module-link:hover i {
            color: white;
        }

        .advice-box {
            margin-top: 24px;
            padding: 20px;
            background: var(--primary-light);
            border-radius: 10px;
            border-left: 4px solid var(--primary);
            line-height: 1.7;
            font-size: 14px;
        }

        .advice-box strong {
            color: var(--primary-dark);
            display: block;
            margin-bottom: 8px;
            font-size: 15px;
        }

        .campaign-info {
            background: var(--gray-50);
            padding: 20px;
            border-radius: 10px;
            margin-top: 16px;
        }

        .campaign-info h4 {
            margin: 0 0 12px 0;
            font-size: 16px;
            color: var(--dark);
        }

        .campaign-info p {
            margin: 8px 0;
            font-size: 14px;
            color: var(--gray-700);
        }
    </style>
</head>
<body>

<?php include 'trainee-sidebar.php'; ?>

<main class="main-content">
    <header class="header">
        <div>
            <h2 class="page-title">Welcome back, <?= htmlspecialchars($name) ?>!</h2>
            <p class="page-subtitle">Your personal security awareness training dashboard</p>
        </div>
    </header>

    <div class="container">
        <!-- Department & Campaign Info -->
        <div class="info-grid">
            <div class="info-item">
                <i class="fas fa-building"></i>
                <div class="info-label">Your Department</div>
                <div class="info-value"><?= htmlspecialchars($department) ?></div>
            </div>

            <div class="info-item">
                <i class="fas fa-bullhorn"></i>
                <div class="info-label">Assigned Campaign</div>
                <div class="info-value"><?= htmlspecialchars($campaign_name) ?></div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="stat-value"><?= $completed ?></div>
                <div class="stat-label">Modules Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-value"><?= $understanding ?>%</div>
                <div class="stat-label">Understanding Score</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="stat-value"><?= $progress_percent ?>%</div>
                <div class="stat-label">Overall Progress</div>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- Progress Card -->
            <div class="card">
                <h3>
                    <i class="fas fa-chart-pie"></i>
                    Your Current Status
                </h3>

                <div class="progress-section">
                    <div class="progress-header">
                        <span>Overall Understanding</span>
                        <span><?= $understanding ?>%</span>
                    </div>
                    <div class="progress-bar-outer">
                        <div class="progress-fill" style="width: <?= $understanding ?>%"></div>
                    </div>
                    <div class="progress-value"><?= $understanding ?>%</div>
                </div>

                <div class="meta-info">
                    <div class="meta-item">
                        <span>Completed Sessions</span>
                        <strong><?= $completed ?> / <?= $total_modules ?></strong>
                    </div>
                    <div class="meta-item">
                        <span>Last Activity</span>
                        <strong><?= htmlspecialchars($last_activity) ?></strong>
                    </div>
                </div>

                <div class="risk-badge risk-<?= $risk_class ?>">
                    <i class="fas fa-shield-alt"></i>
                    Risk Level: <?= $risk_level ?>
                </div>

                <!-- Assigned Campaign Details -->
                <?php if ($current_campaign): ?>
                <div class="campaign-info">
                    <h4>Current Campaign Details</h4>
                    <p><strong>Description:</strong> <?= htmlspecialchars($campaign_desc) ?></p>
                    <p><strong>Duration:</strong> <?= htmlspecialchars($campaign_dates) ?></p>
                </div>
                <?php else: ?>
                <div class="campaign-info">
                    <p>No active campaign is currently assigned to your department.</p>
                    <p>Contact your administrator for more information.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Modules Card -->
            <div class="card">
                <h3>
                    <i class="fas fa-book-open"></i>
                    Training Modules
                </h3>
                <ul class="module-list">
                    <li>
                        <a href="modules/phishing.php" class="module-link">
                            <i class="fas fa-envelope"></i>
                            <span>Phishing Recognition</span>
                        </a>
                    </li>
                    <li>
                        <a href="modules/credentials.php" class="module-link">
                            <i class="fas fa-key"></i>
                            <span>Fake Login Pages</span>
                        </a>
                    </li>
                    <li>
                        <a href="modules/social.php" class="module-link">
                            <i class="fas fa-phone-alt"></i>
                            <span>Social Engineering</span>
                        </a>
                    </li>
                    <li>
                        <a href="modules/attachments.php" class="module-link">
                            <i class="fas fa-paperclip"></i>
                            <span>Dangerous Attachments</span>
                        </a>
                    </li>
                    <li>
                        <a href="modules/links.php" class="module-link">
                            <i class="fas fa-link"></i>
                            <span>Suspicious Links</span>
                        </a>
                    </li>
                </ul>

                <div class="advice-box">
                    <strong><i class="fas fa-lightbulb"></i> Personal Recommendation:</strong>
                    <?= $recommendation ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const progressFill = document.querySelector('.progress-fill');
    if (progressFill) {
        const targetWidth = progressFill.style.width;
        progressFill.style.width = '0%';
        setTimeout(() => {
            progressFill.style.width = targetWidth;
        }, 100);
    }
});
</script>

</body>
</html>