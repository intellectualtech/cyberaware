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
            /* Primary Brand Colors */
            --cyber-yellow: #FF8C42;
            --primary: #FF8C42;
            --cyber-gold: #FF8C42;
            --white: #FFFFFF;
            --cyber-light: #F3F4F6;
            
            /* Security Dark Tones */
            --dark-navy: #111827;
            --dark-slate: #374151;
            --charcoal: #6B7280;
            
            /* Accent Colors */
            --shield-green: #10B981;
            --alert-red: #EF4444;
            --info-blue: #3B82F6;
            
            /* Shadows & Effects */
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 6px 18px rgba(0, 0, 0, 0.09);
            --shadow-lg: 0 12px 40px rgba(0, 0, 0, 0.12);
            --shadow-accent: 0 6px 24px rgba(var(--primary-rgb),0.12);
            
            --sidebar-width: 260px;
            --sidebar-height: 72px;
            --radius: 14px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #F8F9FF 0%, #F3F5FF 100%);
            color: var(--dark-navy);
            line-height: 1.6;
        }

        .main-content {
            margin-bottom: var(--sidebar-height);
            min-height: 100vh;
            padding: 0;
        }

        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                padding-bottom: calc(var(--sidebar-height) + 8px);
            }
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
            color: #5A6B7C;
            margin-top: 6px;
            font-weight: 400;
        }

        .container {
            padding: 40px 40px;
        }

        /* Hero Section */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 48px;
        }

        .info-item {
            background: var(--white);
            padding: 28px;
            border-radius: 16px;
            text-align: center;
            border: 2px solid rgba(var(--primary-rgb),0.1);
            box-shadow: 0 2px 16px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .info-item:hover {
            border-color: var(--cyber-yellow);
            box-shadow: 0 8px 32px rgba(var(--primary-rgb),0.12);
            transform: translateY(-4px);
        }

        .info-item i {
            font-size: 40px;
            color: var(--cyber-yellow);
            margin-bottom: 14px;
            display: block;
        }

        .info-label {
            font-size: 13px;
            color: #6B7C8F;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 8px;
        }

        .info-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark-navy);
        }

        /* Stats Grid - Learning Progress */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--white);
            border: 2px solid rgba(15, 20, 25, 0.06);
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

        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, rgba(var(--primary-rgb),0.12) 0%, rgba(var(--primary-rgb),0.06) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }

        .stat-icon i {
            font-size: 28px;
            color: var(--cyber-yellow);
        }

        .stat-value {
            font-size: 40px;
            font-weight: 800;
            color: var(--cyber-yellow);
            line-height: 1;
        }

        .stat-label {
            font-size: 13px;
            color: #6B7C8F;
            font-weight: 600;
            margin-top: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Main Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 32px;
            margin-bottom: 40px;
        }

        @media (min-width: 992px) {
            .dashboard-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

/* Progress Card - Learning Path (LinkedIn-style) */
        .card {
            background: var(--white);
            border-radius: 12px;
            padding: 24px;
            box-shadow: var(--shadow-md);
            transition: all 0.25s ease;
            border: 1px solid var(--border-light);
        }

        .card:hover { box-shadow: var(--shadow-lg); transform: translateY(-6px); }

        .card h3 { display:none; }

        /* Local LinkedIn-like header used inside markup */
        .card .card-header { margin-bottom: 14px; }
        .card .card-header .avatar { background: linear-gradient(135deg, rgba(var(--primary-rgb),0.08), rgba(var(--primary-rgb),0.02)); color:var(--primary); width:44px; height:44px; border-radius:50%; display:grid; place-items:center; font-size:18px; }
        .card .card-meta .title { font-size: 16px; font-weight: 700; }
        .card .card-meta .muted { font-size: 13px; color: var(--grey-500); }

        .card .card-body { padding: 8px 0 12px; }
        .card .card-actions { display:flex; gap:10px; justify-content:flex-end; }
        .module-list { padding:0; list-style:none; margin:0; display:flex; flex-direction:column; gap:10px; }
        .module-list li { margin:0; }
        .module-link { display:flex; align-items:center; justify-content:space-between; gap:12px; text-decoration:none; color:var(--text-dark); padding:10px 12px; border-radius:10px; }
        .module-link .left { display:flex; align-items:center; gap:12px; }
        .module-link .left i { font-size:18px; color:var(--primary); }
        .module-link:hover { box-shadow: var(--shadow-sm); transform: translateY(-3px); }

        /* Progress Visualization */
        .progress-section {
            margin: 32px 0;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            font-weight: 700;
            color: var(--dark-navy);
            font-size: 15px;
        }

        .progress-bar-outer {
            height: 10px;
            background: #E8EDF5;
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--cyber-yellow) 0%, var(--cyber-gold) 100%);
            border-radius: 10px;
            transition: width 1.2s ease;
            box-shadow: 0 0 8px rgba(var(--primary-rgb),0.4);
        }

        .progress-value {
            text-align: center;
            font-size: 64px;
            font-weight: 800;
            color: var(--cyber-yellow);
            margin: 32px 0;
            text-shadow: 0 2px 8px rgba(var(--primary-rgb),0.15);
            line-height: 1;
        }

        .meta-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 32px;
        }

        .meta-item {
            background: linear-gradient(135deg, #F8FAFB 0%, #F3F5FF 100%);
            padding: 20px;
            border-radius: 12px;
            font-size: 14px;
            color: #6B7C8F;
            border-left: 4px solid var(--cyber-yellow);
        }

        .meta-item strong {
            display: block;
            color: var(--dark-navy);
            font-size: 18px;
            margin-top: 8px;
            font-weight: 700;
        }

        /* Risk Badge */
        .risk-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
            margin-top: 28px;
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
            color: var(--cyber-yellow);
        }
        .risk-high { 
            background: rgba(239, 68, 68, 0.12);
            color: var(--alert-red);
        }

        /* Course Modules List */
        .module-list {
            list-style: none;
        }

        .module-list li {
            margin: 12px 0;
        }

        .module-link {
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 20px 24px;
            background: linear-gradient(135deg, #F8FAFB 0%, #F3F5FF 100%);
            border-radius: 12px;
            text-decoration: none;
            color: var(--dark-navy);
            font-weight: 600;
            font-size: 15px;
            border: 2px solid transparent;
            border-left: 5px solid var(--cyber-yellow);
            transition: all 0.3s ease;
        }

        .module-link i {
            font-size: 24px;
            color: var(--cyber-yellow);
            min-width: 28px;
            text-align: center;
        }

        .module-link:hover {
            background: linear-gradient(135deg, var(--cyber-yellow) 0%, var(--cyber-gold) 100%);
            color: var(--dark-navy);
            border-left-color: var(--dark-navy);
            transform: translateX(6px);
            box-shadow: 0 8px 24px rgba(var(--primary-rgb),0.2);
        }

        .module-link:hover i {
            color: var(--dark-navy);
        }

        /* Recommendation Box */
        .advice-box {
            margin-top: 32px;
            padding: 24px;
            background: linear-gradient(135deg, rgba(var(--primary-rgb),0.08) 0%, rgba(var(--primary-rgb),0.03) 100%);
            border-radius: 12px;
            border-left: 5px solid var(--cyber-yellow);
            line-height: 1.8;
            font-size: 15px;
            color: #2C3E50;
        }

        .advice-box strong {
            color: var(--dark-navy);
            display: block;
            margin-bottom: 12px;
            font-size: 15px;
            font-weight: 700;
        }

        /* Campaign/Current Exercise Info */
        .campaign-info {
            background: linear-gradient(135deg, #F8FAFB 0%, #F3F5FF 100%);
            padding: 24px;
            border-radius: 12px;
            margin-top: 28px;
            border-left: 5px solid var(--cyber-yellow);
            border: 2px solid rgba(var(--primary-rgb),0.15);
        }

        .campaign-info h4 {
            margin: 0 0 16px 0;
            font-size: 16px;
            color: var(--dark-navy);
            font-weight: 700;
        }

        .campaign-info p {
            margin: 10px 0;
            font-size: 14px;
            color: #5A6B7C;
            font-weight: 500;
            line-height: 1.6;
        }

        @media (max-width: 992px) {
            .container {
                padding: 24px 24px;
            }

            .stats-grid,
            .info-grid {
                grid-template-columns: 1fr;
            }

            .meta-info {
                grid-template-columns: 1fr;
            }

            .card {
                padding: 28px;
            }

            .page-title {
                font-size: 26px;
            }
        }

        @media (max-width: 576px) {
            .page-title {
                font-size: 22px;
            }

            .progress-value {
                font-size: 48px;
            }

            .stat-value {
                font-size: 32px;
            }

            .card h3 {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>

<?php include 'trainee-sidebar.php'; ?>

<main class="main-content">
    <header class="header">
        <div>
            <h2 class="page-title">Welcome, <?= htmlspecialchars($name) ?></h2>
            <p class="page-subtitle">Continue your cybersecurity learning journey</p>
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
            <!-- Learning Progress Card (LinkedIn-style) -->
            <div class="card">
                <div class="card-header">
                    <div class="card-avatar"><i class="fas fa-user-graduate"></i></div>
                    <div class="card-meta">
                        <div class="title">Learning Progress</div>
                        <div class="muted">Updated: <?= htmlspecialchars($last_activity) ?></div>
                    </div>
                    <div style="margin-left:auto;">
                        <button class="btn-ghost">Export</button>
                    </div>
                </div>

                <div class="card-body">
                    <div class="progress-section">
                        <div class="progress-header">
                            <span>Overall Proficiency</span>
                            <span><?= $understanding ?>%</span>
                        </div>
                        <div class="progress-bar-outer">
                            <div class="progress-fill" style="width: <?= $understanding ?>%"></div>
                        </div>
                        <div class="progress-value"><?= $understanding ?>%</div>
                    </div>

                    <div class="meta-info">
                        <div class="meta-item">
                            <span>Courses Completed</span>
                            <strong><?= $completed ?>/<?= $total_modules ?></strong>
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
                        <h4><i class="fas fa-rocket"></i> Current Exercise</h4>
                        <p><strong>Name:</strong> <?= htmlspecialchars($campaign_desc) ?></p>
                        <p><strong>Duration:</strong> <?= htmlspecialchars($campaign_dates) ?></p>
                    </div>
                    <?php else: ?>
                    <div class="campaign-info">
                        <p>No active exercise is currently assigned to your department. Check back soon!</p>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="card-actions">
                    <button class="btn">View Progress</button>
                    <button class="btn-ghost">Share</button>
                </div>
            </div>

            <!-- Available Courses (LinkedIn-style rows) -->
            <div class="card">
                <div class="card-header">
                    <div class="card-avatar"><i class="fas fa-book-open"></i></div>
                    <div class="card-meta">
                        <div class="title">Course Catalog</div>
                        <div class="muted">Choose a course to begin or resume</div>
                    </div>
                    <div style="margin-left:auto;">
                        <button class="btn-ghost">Browse All</button>
                    </div>
                </div>

                <div class="card-body">
                    <ul class="module-list">
                        <li>
                            <a href="modules/phishing.php" class="module-link">
                                <div class="left"><i class="fas fa-envelope"></i><span class="title">Phishing Recognition</span></div>
                                <div><button class="btn">Start</button></div>
                            </a>
                        </li>
                        <li>
                            <a href="modules/credentials.php" class="module-link">
                                <div class="left"><i class="fas fa-key"></i><span class="title">Fake Login Pages</span></div>
                                <div><button class="btn">Start</button></div>
                            </a>
                        </li>
                        <li>
                            <a href="modules/social.php" class="module-link">
                                <div class="left"><i class="fas fa-phone-alt"></i><span class="title">Social Engineering</span></div>
                                <div><button class="btn">Start</button></div>
                            </a>
                        </li>
                        <li>
                            <a href="modules/attachments.php" class="module-link">
                                <div class="left"><i class="fas fa-paperclip"></i><span class="title">Dangerous Attachments</span></div>
                                <div><button class="btn">Start</button></div>
                            </a>
                        </li>
                        <li>
                            <a href="modules/links.php" class="module-link">
                                <div class="left"><i class="fas fa-link"></i><span class="title">Suspicious Links</span></div>
                                <div><button class="btn">Start</button></div>
                            </a>
                        </li>
                    </ul>

                    <div class="advice-box">
                        <strong><i class="fas fa-bulb"></i> Next Steps:</strong>
                        <?= $recommendation ?>
                    </div>
                </div>

                <div class="card-actions">
                    <button class="btn-ghost">Manage Learning Path</button>
                    <button class="btn">Enroll</button>
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