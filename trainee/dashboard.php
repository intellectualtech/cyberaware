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
    $total_sessions = (int)$stats['total_sessions'];
    $total_modules = 5; // Adjust if you add more modules
    $progress_percent = $total_modules > 0 ? round(($completed / $total_modules) * 100) : 0;

    $last_activity = $stats['last_activity']
        ? date('M j, Y g:i A', strtotime($stats['last_activity']))
        : 'No activity yet';

    $understanding = $stats['avg_score'] !== null ? round((float)$stats['avg_score']) : 0;

    $xp = max(0, ($completed * 140) + ($understanding * 6));
    if ($understanding >= 80) {
        $tier = 'Gold';
        $tier_class = 'tier-gold';
        $momentum = 'On a roll';
    } elseif ($understanding >= 60) {
        $tier = 'Silver';
        $tier_class = 'tier-silver';
        $momentum = 'Steady climb';
    } else {
        $tier = 'Bronze';
        $tier_class = 'tier-bronze';
        $momentum = 'New explorer';
    }

    $hero_message = $completed > 0
        ? "You're building sharp instincts one module at a time."
        : "Kick off your journey with a quick win today.";

    $next_goal = $completed < $total_modules
        ? "Next badge: complete " . ($completed + 1) . " of " . $total_modules . " modules."
        : "Top badge earned. Revisit any module to stay sharp.";

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

    // Weekly mission + streak (uses inbox actions when available)
    $week_start = date('Y-m-d', strtotime('monday this week'));
    $week_label = date('M j', strtotime($week_start)) . ' - ' . date('M j', strtotime($week_start . ' +6 days'));
    $mission_goal = 5;
    $mission_done = 0;
    $mission_correct = 0;
    $mission_accuracy = null;

    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total,
                   SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct
            FROM inbox_actions
            WHERE user_id = ? AND week_start = ?
        ");
        $stmt->execute([$user_id, $week_start]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $mission_done = (int)$row['total'];
            $mission_correct = (int)$row['correct'];
            $mission_accuracy = $mission_done > 0 ? round(($mission_correct / $mission_done) * 100) : null;
        }
    } catch (Exception $e) {
        // Inbox tables may not exist yet; keep defaults.
    }

    $week_starts = [];
    $stmt = $pdo->prepare("
        SELECT DISTINCT DATE_SUB(DATE(completed_at), INTERVAL WEEKDAY(completed_at) DAY) as week_start
        FROM training_sessions
        WHERE user_id = ? AND completed_at IS NOT NULL
    ");
    $stmt->execute([$user_id]);
    $week_starts = $stmt->fetchAll(PDO::FETCH_COLUMN);

    try {
        $stmt = $pdo->prepare("SELECT DISTINCT week_start FROM inbox_actions WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $inbox_weeks = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($inbox_weeks as $ws) {
            $week_starts[] = $ws;
        }
    } catch (Exception $e) {
        // Inbox tables may not exist yet; ignore.
    }

    $week_set = array_fill_keys(array_unique($week_starts), true);
    $weekly_streak = 0;
    $cursor = $week_start;
    while (isset($week_set[$cursor])) {
        $weekly_streak++;
        $cursor = date('Y-m-d', strtotime($cursor . ' -7 days'));
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
    
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --brand-a: #FF8C42;
            --brand-b: #FFA969;
            --brand-c: #0f172a;
            --surface: #ffffff;
            --ink: #0f172a;
            --muted: #5f6b7a;
            --line: rgba(15, 23, 42, 0.08);
            --shadow-sm: 0 6px 18px rgba(15, 23, 42, 0.06);
            --shadow-md: 0 16px 40px rgba(15, 23, 42, 0.12);
            --radius-lg: 24px;
            --radius-md: 16px;
            --sidebar-height: 72px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Manrope', 'Segoe UI', sans-serif;
            background: radial-gradient(1200px 600px at 10% -10%, #fff1e4 0%, transparent 60%),
                linear-gradient(135deg, #fff8f1 0%, #ffffff 100%);
            color: var(--ink);
            line-height: 1.6;
        }

        h1, h2, h3 {
            font-family: 'Space Grotesk', 'Segoe UI', sans-serif;
        }

        .main-content {
            margin-bottom: var(--sidebar-height);
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 28px 80px;
        }

        .hero {
            position: relative;
            padding: 48px 0 24px;
        }

        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(600px 300px at 85% 10%, rgba(255, 140, 66, 0.18), transparent 60%),
                radial-gradient(420px 260px at 20% 20%, rgba(255, 170, 105, 0.2), transparent 60%);
            z-index: 0;
            pointer-events: none;
        }

        .hero-inner {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 32px;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            align-items: stretch;
        }

        .hero-copy {
            padding: 12px 8px;
        }

        .eyebrow {
            text-transform: uppercase;
            letter-spacing: 0.18em;
            font-size: 12px;
            font-weight: 700;
            color: rgba(15, 23, 42, 0.55);
        }

        .hero-title {
            font-size: clamp(28px, 3vw, 44px);
            font-weight: 700;
            margin: 10px 0 8px;
        }

        .hero-subtitle {
            font-size: 16px;
            color: var(--muted);
            max-width: 520px;
        }

        .hero-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 18px;
        }

        .btn {
            border: none;
            padding: 12px 18px;
            border-radius: 999px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn.primary {
            background: linear-gradient(135deg, var(--brand-a), #ff7a20);
            color: #ffffff;
            box-shadow: var(--shadow-sm);
        }

        .btn.ghost {
            background: rgba(255, 255, 255, 0.7);
            color: var(--ink);
            border: 1px solid var(--line);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .pill-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }

        .pill {
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 13px;
            font-weight: 600;
            color: var(--ink);
        }

        .tier-gold {
            background: rgba(234, 179, 8, 0.16);
            border-color: rgba(234, 179, 8, 0.4);
        }

        .tier-silver {
            background: rgba(100, 116, 139, 0.16);
            border-color: rgba(100, 116, 139, 0.4);
        }

        .tier-bronze {
            background: rgba(255, 140, 66, 0.16);
            border-color: rgba(255, 140, 66, 0.4);
        }

        .hero-progress-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 28px;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(15, 23, 42, 0.06);
            display: grid;
            gap: 18px;
        }

        .progress-ring {
            --progress: 0;
            width: 220px;
            height: 220px;
            margin: 0 auto;
            border-radius: 50%;
            background: conic-gradient(var(--brand-a) calc(var(--progress) * 1%), #f5e3d4 0);
            display: grid;
            place-items: center;
            position: relative;
        }

        .progress-ring::after {
            content: '';
            position: absolute;
            inset: 14px;
            background: #ffffff;
            border-radius: 50%;
            box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.06);
        }

        .progress-center {
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .progress-label {
            font-size: 12px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--muted);
            font-weight: 700;
        }

        .progress-value {
            font-size: 44px;
            font-weight: 800;
            color: var(--ink);
            margin: 2px 0;
        }

        .progress-meta {
            font-size: 14px;
            color: var(--muted);
        }

        .progress-details {
            display: grid;
            gap: 12px;
        }

        .detail {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
            padding: 12px 14px;
            border-radius: 12px;
            font-size: 14px;
            color: var(--muted);
        }

        .detail strong {
            color: var(--ink);
        }

        .motivation {
            background: linear-gradient(135deg, rgba(255, 170, 105, 0.16), rgba(255, 140, 66, 0.12));
            border-radius: 14px;
            padding: 14px 16px;
            font-weight: 600;
            color: var(--ink);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .motivation i {
            color: #FF8C42;
        }

        .section {
            margin-top: 40px;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }

        .section-title {
            font-size: 24px;
            font-weight: 700;
        }

        .section-subtitle {
            font-size: 14px;
            color: var(--muted);
        }

        .grid-split {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        }

        .card {
            background: var(--surface);
            border-radius: var(--radius-md);
            padding: 22px;
            border: 1px solid rgba(15, 23, 42, 0.06);
            box-shadow: var(--shadow-sm);
        }

        .card h3 {
            font-size: 18px;
            margin-bottom: 12px;
        }

        .card p {
            color: var(--muted);
            font-size: 14px;
        }

        .challenge-meta {
            margin-top: 12px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            font-size: 13px;
            color: var(--muted);
        }

        .achievement-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            margin-top: 12px;
        }

        .achievement {
            background: #f8fafc;
            border-radius: 12px;
            padding: 12px;
            font-size: 13px;
            color: var(--muted);
        }

        .mission-bar {
            height: 10px;
            border-radius: 999px;
            background: #e2e8f0;
            overflow: hidden;
            margin-top: 10px;
        }

        .mission-bar span {
            display: block;
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, #FF8C42, #FFB070);
        }

        .mission-meta {
            margin-top: 10px;
            font-size: 13px;
            color: var(--muted);
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(255, 140, 66, 0.12);
            color: var(--brand-a);
            font-weight: 700;
        }

        .achievement strong {
            display: block;
            font-size: 18px;
            color: var(--ink);
        }

        .course-grid {
            display: grid;
            gap: 22px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .course-card {
            background: var(--surface);
            border-radius: var(--radius-md);
            padding: 20px;
            text-decoration: none;
            color: var(--ink);
            border: 1px solid rgba(15, 23, 42, 0.06);
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            display: flex;
            flex-direction: column;
            gap: 12px;
            position: relative;
            overflow: hidden;
        }

        .course-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(120px 80px at 80% 10%, rgba(255, 140, 66, 0.18), transparent 60%);
            opacity: 0.6;
        }

        .course-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-md);
        }

        .course-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            z-index: 1;
        }

        .course-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: rgba(255, 140, 66, 0.12);
            color: var(--brand-a);
            font-size: 20px;
        }

        .course-badge {
            background: rgba(255, 140, 66, 0.18);
            color: #b45309;
            font-size: 12px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 999px;
        }

        .course-card h3 {
            font-size: 18px;
            margin: 0;
            position: relative;
            z-index: 1;
        }

        .course-card p {
            font-size: 14px;
            color: var(--muted);
            position: relative;
            z-index: 1;
        }

        .course-meta {
            display: flex;
            gap: 12px;
            font-size: 12px;
            color: var(--muted);
            position: relative;
            z-index: 1;
        }

        .course-cta {
            margin-top: auto;
            font-weight: 700;
            color: var(--brand-a);
            position: relative;
            z-index: 1;
        }

        .coach-card {
            margin-top: 22px;
            display: flex;
            gap: 16px;
            align-items: flex-start;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));
            border-radius: var(--radius-md);
            padding: 18px;
            border: 1px solid rgba(15, 23, 42, 0.06);
        }

        .coach-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(255, 140, 66, 0.2);
            display: grid;
            place-items: center;
            color: #b45309;
            flex-shrink: 0;
        }

        .reveal {
            opacity: 0;
            transform: translateY(12px);
            animation: fadeUp 0.8s ease forwards;
        }

        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }

        @keyframes fadeUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .hero-inner {
                grid-template-columns: 1fr;
            }

            .progress-ring {
                width: 190px;
                height: 190px;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>

<?php include 'trainee-sidebar.php'; ?>

<main class="main-content">
    <div class="container">
        <section class="hero">
            <div class="hero-inner">
                <div class="hero-copy reveal">
                    <div class="eyebrow">Your learning studio</div>
                    <h1 class="hero-title">Welcome back, <?= htmlspecialchars($name) ?></h1>
                    <p class="hero-subtitle">A calm space to level up your security instincts. Short lessons, real-world scenarios, and quick wins.</p>
                    <div class="hero-actions">
                        <a class="btn primary" href="modules/phishing.php"><i class="fas fa-play"></i> Continue learning</a>
                        <a class="btn ghost" href="phish-inbox.php"><i class="fas fa-inbox"></i> Phish inbox</a>
                        <a class="btn ghost" href="progress.php"><i class="fas fa-chart-line"></i> View progress</a>
                    </div>
                    <div class="pill-row">
                        <span class="pill">XP <?= $xp ?></span>
                        <span class="pill <?= $tier_class ?>">Shield Tier: <?= $tier ?></span>
                        <span class="pill"><?= htmlspecialchars($department) ?></span>
                    </div>
                </div>
                <div class="hero-progress-card reveal delay-1">
                    <div class="progress-ring" data-progress="<?= $progress_percent ?>" style="--progress: <?= $progress_percent ?>;">
                        <div class="progress-center">
                            <div class="progress-label">Course progress</div>
                            <div class="progress-value"><?= $progress_percent ?>%</div>
                            <div class="progress-meta"><?= $completed ?>/<?= $total_modules ?> modules</div>
                        </div>
                    </div>
                    <div class="progress-details">
                        <div class="detail"><span>Understanding</span><strong><?= $understanding ?>%</strong></div>
                        <div class="detail"><span>Momentum</span><strong><?= htmlspecialchars($momentum) ?></strong></div>
                        <div class="detail"><span>Last activity</span><strong><?= htmlspecialchars($last_activity) ?></strong></div>
                    </div>
                    <div class="motivation">
                        <i class="fas fa-sparkles"></i>
                        <span><?= htmlspecialchars($hero_message) ?></span>
                    </div>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="section-header reveal delay-2">
                <div>
                    <div class="eyebrow">Your challenge</div>
                    <div class="section-title">Spotlight and milestones</div>
                    <div class="section-subtitle"><?= htmlspecialchars($next_goal) ?></div>
                </div>
            </div>
            <div class="grid-split">
                <div class="card reveal delay-2">
                    <h3><i class="fas fa-rocket"></i> Challenge spotlight</h3>
                    <?php if ($current_campaign): ?>
                    <p><?= htmlspecialchars($campaign_name) ?></p>
                    <div class="challenge-meta">
                        <div><strong>Focus:</strong> <?= htmlspecialchars($campaign_desc) ?></div>
                        <div><strong>Dates:</strong> <?= htmlspecialchars($campaign_dates) ?></div>
                    </div>
                    <?php else: ?>
                    <p>No active exercise is assigned yet. We will drop one soon.</p>
                    <?php endif; ?>
                </div>
                <div class="card reveal delay-3">
                    <h3><i class="fas fa-trophy"></i> Wins so far</h3>
                    <p>Small wins stack fast. Keep collecting them.</p>
                    <div class="achievement-grid">
                        <div class="achievement"><strong><?= $completed ?></strong>Modules finished</div>
                        <div class="achievement"><strong><?= $total_sessions ?></strong>Sessions logged</div>
                        <div class="achievement"><strong><?= $understanding ?>%</strong>Skill accuracy</div>
                    </div>
                </div>
                <div class="card reveal delay-3">
                    <h3><i class="fas fa-bolt"></i> Weekly streak</h3>
                    <p>Complete a mission each week to keep your streak alive.</p>
                    <div class="badge-pill"><i class="fas fa-fire"></i> <?= $weekly_streak ?> week streak</div>
                    <?php $mission_progress = $mission_goal > 0 ? min(100, round(($mission_done / $mission_goal) * 100)) : 0; ?>
                    <div class="mission-bar"><span style="width: <?= $mission_progress ?>%"></span></div>
                    <div class="mission-meta">
                        <div><strong><?= $mission_done ?></strong> of <?= $mission_goal ?> inbox tags (<?= htmlspecialchars($week_label) ?>)</div>
                        <div>Accuracy: <strong><?= $mission_accuracy !== null ? $mission_accuracy . '%' : 'n/a' ?></strong></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="section-header reveal delay-2">
                <div>
                    <div class="eyebrow">Course cards</div>
                    <div class="section-title">Choose your next mission</div>
                    <div class="section-subtitle">Bite-sized lessons with real-world scenarios.</div>
                </div>
                <a class="btn ghost" href="modules/phishing.php"><i class="fas fa-compass"></i> Start next</a>
            </div>

            <div class="course-grid">
                <a href="modules/phishing.php" class="course-card reveal delay-1">
                    <div class="course-top">
                        <div class="course-icon"><i class="fas fa-envelope"></i></div>
                        <span class="course-badge">Core</span>
                    </div>
                    <h3>Phishing Recognition</h3>
                    <p>Learn to spot the telltale signs in emails, texts, and social messages.</p>
                    <div class="course-meta">
                        <span><i class="fas fa-clock"></i> 8 min</span>
                        <span><i class="fas fa-signal"></i> Beginner</span>
                    </div>
                    <div class="course-cta">Start now</div>
                </a>
                <a href="modules/credentials.php" class="course-card reveal delay-1">
                    <div class="course-top">
                        <div class="course-icon"><i class="fas fa-key"></i></div>
                        <span class="course-badge">Hands-on</span>
                    </div>
                    <h3>Fake Login Pages</h3>
                    <p>Catch the subtle UI traps that steal credentials before you click.</p>
                    <div class="course-meta">
                        <span><i class="fas fa-clock"></i> 10 min</span>
                        <span><i class="fas fa-signal"></i> Intermediate</span>
                    </div>
                    <div class="course-cta">Start now</div>
                </a>
                <a href="modules/social.php" class="course-card reveal delay-1">
                    <div class="course-top">
                        <div class="course-icon"><i class="fas fa-phone-alt"></i></div>
                        <span class="course-badge">Scenario</span>
                    </div>
                    <h3>Social Engineering</h3>
                    <p>Build confidence in high-pressure conversations and requests.</p>
                    <div class="course-meta">
                        <span><i class="fas fa-clock"></i> 12 min</span>
                        <span><i class="fas fa-signal"></i> Intermediate</span>
                    </div>
                    <div class="course-cta">Start now</div>
                </a>
                <a href="modules/attachments.php" class="course-card reveal delay-2">
                    <div class="course-top">
                        <div class="course-icon"><i class="fas fa-paperclip"></i></div>
                        <span class="course-badge">Quick win</span>
                    </div>
                    <h3>Dangerous Attachments</h3>
                    <p>Know the file types and red flags before you open anything.</p>
                    <div class="course-meta">
                        <span><i class="fas fa-clock"></i> 7 min</span>
                        <span><i class="fas fa-signal"></i> Beginner</span>
                    </div>
                    <div class="course-cta">Start now</div>
                </a>
                <a href="modules/links.php" class="course-card reveal delay-2">
                    <div class="course-top">
                        <div class="course-icon"><i class="fas fa-link"></i></div>
                        <span class="course-badge">Deep dive</span>
                    </div>
                    <h3>Suspicious Links</h3>
                    <p>Decode URLs and previews so you can click with confidence.</p>
                    <div class="course-meta">
                        <span><i class="fas fa-clock"></i> 9 min</span>
                        <span><i class="fas fa-signal"></i> Beginner</span>
                    </div>
                    <div class="course-cta">Start now</div>
                </a>
            </div>

            <div class="coach-card reveal delay-3">
                <div class="coach-avatar"><i class="fas fa-bolt"></i></div>
                <div>
                    <h3>Coach's note</h3>
                    <p><?= $recommendation ?></p>
                </div>
            </div>
        </section>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const progressRing = document.querySelector('.progress-ring');
    if (progressRing) {
        const targetProgress = progressRing.dataset.progress || '0';
        progressRing.style.setProperty('--progress', '0');
        setTimeout(() => {
            progressRing.style.setProperty('--progress', targetProgress);
        }, 120);
    }
});
</script>

</body>
</html>