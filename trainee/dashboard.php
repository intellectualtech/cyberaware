<?php
// trainee/dashboard.php
// Complete version - January 2026 style

// =============================================
// CONFIG & SESSION & DB CONNECTION
// =============================================
require_once '../config/database.php'; // This starts session safely and defines getDBConnection()

// Get DB connection (critical - this creates $pdo)
$pdo = getDBConnection();

// Simple login check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SESSION['role'] !== 'trainee') {
    header("Location: ../pages/home.php?error=access_denied");
    exit;
}

// =============================================
// FETCH REAL USER & PROGRESS DATA
// =============================================
$user_id = $_SESSION['user_id'];

// Fetch user details safely
$stmt = $pdo->prepare("SELECT id, username, full_name, last_login FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: ../login.php?error=user_not_found");
    exit;
}

$name = $user['full_name'] ?? $user['username'] ?? 'Trainee';

// Real progress statistics
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
$total_modules = 5; // ← Change to dynamic later (SELECT COUNT(*) FROM training_modules WHERE is_active = 1)
$progress_percent = $total_modules > 0 ? round(($completed / $total_modules) * 100) : 0;

$last_activity = $stats['last_activity']
    ? date('M j, Y g:i A', strtotime($stats['last_activity']))
    : 'No activity yet';

$understanding = $stats['avg_score'] !== null ? round((float)$stats['avg_score']) : 0;

// Personalized recommendation
$recommendation = "You're making good progress — keep going!";
if ($understanding < 50) {
    $recommendation = "Your current understanding needs strengthening. Start with <strong>Phishing Recognition</strong> — it's the foundation.";
} elseif ($understanding < 75) {
    $recommendation = "Solid foundation! Try <strong>Social Engineering Scenarios</strong> or <strong>Fake Login Pages</strong> next to reach expert level.";
} else {
    $recommendation = "Excellent results! You're doing great — consider advanced scenarios or helping colleagues.";
}

// Risk level
$risk_level = $understanding >= 80 ? 'Low' : ($understanding >= 60 ? 'Medium' : 'High');
$risk_class = strtolower($risk_level);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>CyberAware • Dashboard</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #1d4ed8;
            --primary-dark: #1e40af;
            --primary-light: #3b82f6;
            --success: #15803d;
            --warning: #c2410c;
            --danger: #b91c1c;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 16px rgba(0,0,0,0.1);
            --radius: 14px;
            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--gray-50);
            color: var(--gray-800);
            line-height: 1.6;
            min-height: 100vh;
            padding: 2.5rem 1.25rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        header {
            text-align: center;
            margin-bottom: 3.5rem;
        }

        h2 {
            font-size: 2.5rem;
            font-weight: 700;
            letter-spacing: -0.025em;
            color: var(--gray-800);
        }

        .subtitle {
            color: var(--gray-600);
            font-size: 1.15rem;
            max-width: 680px;
            margin: 0.75rem auto 0;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
        }

        @media (min-width: 992px) {
            .dashboard-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        .card {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            padding: 2.2rem 2.4rem;
            border: 1px solid var(--gray-200);
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.6rem;
            color: var(--gray-800);
        }

        /* Progress Section */
        .progress-section {
            margin: 1.8rem 0 2.2rem;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.9rem;
            font-weight: 500;
            color: var(--gray-700);
        }

        .progress-bar-outer {
            height: 18px;
            background: var(--gray-200);
            border-radius: 9px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-light), var(--primary));
            border-radius: 9px;
            transition: width 1.2s ease;
        }

        .progress-value {
            text-align: center;
            font-size: 2.1rem;
            font-weight: 700;
            color: var(--primary);
            margin-top: 1rem;
        }

        .meta-info {
            display: flex;
            justify-content: space-between;
            font-size: 1rem;
            color: var(--gray-600);
            margin-top: 1.4rem;
        }

        .risk-badge {
            display: inline-block;
            padding: 0.5rem 1.1rem;
            border-radius: 2rem;
            font-size: 0.9rem;
            font-weight: 600;
            margin-top: 1.4rem;
        }

        .risk-low    { background: #d1fae5; color: #065f46; }
        .risk-medium { background: #fef3c7; color: #92400e; }
        .risk-high   { background: #fee2e2; color: #991b1b; }

        /* Modules List */
        .module-list {
            list-style: none;
        }

        .module-list li {
            margin: 1.2rem 0;
        }

        .module-link {
            display: flex;
            align-items: center;
            padding: 1.2rem 1.5rem;
            background: var(--gray-50);
            border-radius: var(--radius);
            text-decoration: none;
            color: var(--gray-800);
            font-weight: 500;
            border-left: 5px solid var(--primary);
            transition: var(--transition);
        }

        .module-link:hover {
            background: var(--primary);
            color: white;
            transform: translateX(12px);
            box-shadow: 0 8px 24px rgba(29,78,216,0.18);
        }

        /* Recommendation Box */
        .advice-box {
            margin-top: 2.8rem;
            padding: 1.8rem 2.2rem;
            background: white;
            border-radius: var(--radius);
            border-left: 5px solid var(--primary);
            box-shadow: var(--shadow-sm);
            line-height: 1.7;
            font-size: 1.02rem;
        }

        /* Footer */
        .footer-note {
            text-align: center;
            margin-top: 5rem;
            color: var(--gray-600);
            font-size: 0.95rem;
            opacity: 0.9;
        }
    </style>
</head>
<body>

<div class="container">

    <header>
        <h2>Welcome back, <?= htmlspecialchars($name) ?>!</h2>
        <p class="subtitle">Your personal security awareness training dashboard</p>
    </header>

    <div class="dashboard-grid">

        <!-- Progress Card -->
        <div class="card">
            <h3>Your Current Status</h3>

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
                <div>Completed sessions: <strong><?= $completed ?></strong></div>
                <div>Last activity: <strong><?= htmlspecialchars($last_activity) ?></strong></div>
            </div>

            <div class="risk-badge risk-<?= $risk_class ?>">
                Risk Level: <?= $risk_level ?>
            </div>
        </div>

        <!-- Modules & Recommendation Card -->
        <div class="card">
            <h3>Training Modules</h3>
            <ul class="module-list">
                <li><a href="modules/phishing.php" class="module-link">→ Phishing Recognition</a></li>
                <li><a href="modules/credentials.php" class="module-link">→ Fake Login Pages</a></li>
                <li><a href="modules/social.php" class="module-link">→ Social Engineering Scenarios</a></li>
                <li><a href="modules/attachments.php" class="module-link">→ Dangerous Attachments & Malware</a></li>
                <li><a href="modules/links.php" class="module-link">→ Suspicious Links & Website Safety</a></li>
            </ul>

            <div class="advice-box">
                <strong>Personal Recommendation:</strong><br>
                <?= $recommendation ?>
            </div>
        </div>

    </div>

    <div class="footer-note">
        All simulations are 100% safe and created for learning purposes only.<br>
        <strong>CyberAware</strong> — Building a stronger security culture together • 2026
    </div>

</div>

<script>
// Small JS enhancements
document.addEventListener('DOMContentLoaded', () => {
    const progressFill = document.querySelector('.progress-fill');
    if (progressFill) {
        const targetWidth = progressFill.style.width;
        progressFill.style.width = '0%';
        setTimeout(() => {
            progressFill.style.width = targetWidth;
        }, 100);
    }

    document.querySelectorAll('.card').forEach((card, i) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'opacity 0.7s ease, transform 0.7s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, i * 150);
    });
});
</script>

</body>
</html>